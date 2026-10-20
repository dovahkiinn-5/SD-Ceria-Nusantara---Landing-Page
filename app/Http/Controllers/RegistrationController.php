<?php

namespace App\Http\Controllers;

use App\Contracts\DocumentStore;
use App\Services\FirestoreFileStorage;
use App\Services\SchoolContent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    private const DOCUMENT_FIELDS = [
        'birth_certificate',
        'family_card',
        'child_identity_card',
        'photo',
    ];

    private function initialize(Request $request): array
    {
        if (! $request->session()->has('registration')) {
            $request->session()->put('registration', [
                'reference' => 'CN-'.now()->year.'-'.substr((string) Str::ulid(), -10),
                'token' => Str::random(40), 'completed' => 0,
            ]);
        }

        return $request->session()->get('registration');
    }

    public function show(Request $request, SchoolContent $content, int $step = 1)
    {
        abort_unless($step >= 1 && $step <= 4, 404);
        $draft = $this->initialize($request);

        if ($step > $draft['completed'] + 1) {
            return redirect()->route('registration', $draft['completed'] + 1);
        }

        return view('registration.form', [
            'step' => $step, 'draft' => $draft, 'site' => $content->all(), 'page' => 'registration',
            'regions' => $step === 2 ? $this->regions() : [],
        ]);
    }

    public function save(Request $request, FirestoreFileStorage $storage, int $step)
    {
        abort_unless(in_array($step, [1, 2, 3]), 404);
        $draft = $this->initialize($request);

        if ($step > $draft['completed'] + 1) {
            return redirect()->route('registration', $draft['completed'] + 1);
        }

        if ($step === 1) {
            $draft['child'] = $request->validate([
                'child_name' => 'required|string|max:120', 'nickname' => 'required|string|max:60',
                'birth_date' => 'required|date|before:today|after:2005-01-01', 'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
                'previous_school' => 'nullable|string|max:180', 'support_needs' => 'nullable|string|max:1000',
            ]);
        } elseif ($step === 2) {
            $regions = $this->regions();
            $parent = $request->validate([
                'parent_name' => 'required|string|max:120', 'relationship' => ['required', Rule::in(['Ayah', 'Ibu', 'Wali'])],
                'phone' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{9,25}$/'], 'email' => 'required|email|max:190',
                'province_code' => ['required', Rule::in(array_keys($regions))],
                'regency_code' => ['required', Rule::in(array_keys($regions[$request->input('province_code')]['regencies'] ?? []))],
                'district' => 'required|string|max:120', 'village' => 'required|string|max:120',
                'address' => 'required|string|max:1000', 'postal_code' => ['nullable', 'regex:/^[0-9]{5}$/'],
                'emergency_contact' => ['nullable', 'string', 'regex:/^\+?[0-9 ()-]{9,25}$/'],
            ]);

            $parent['province'] = $regions[$parent['province_code']]['name'];
            $parent['regency'] = $regions[$parent['province_code']]['regencies'][$parent['regency_code']];
            $draft['parent'] = $parent;
        } else {
            $rules = [];
            $messages = [];
            foreach (self::DOCUMENT_FIELDS as $field) {
                $rules[$field] = [
                    isset($draft['documents'][$field]) ? 'nullable' : 'required',
                    'file', 'max:1024',
                    $field === 'photo' ? 'mimes:jpg,jpeg,png' : 'mimes:jpg,jpeg,png,pdf',
                ];
                $messages[$field.'.max'] = 'Ukuran :attribute melebihi 1 MB. Pilih berkas yang lebih kecil.';
            }
            $request->validate($rules, $messages);

            foreach (array_keys($rules) as $field) {
                if (! $file = $request->file($field)) {
                    continue;
                }

                $path = 'applications/'.$draft['reference'].'/'.Str::uuid().'.'.$file->extension();
                $storage->putUploadedFile($path, $file);

                if (isset($draft['documents'][$field])) {
                    $storage->delete($draft['documents'][$field]['path']);
                }

                $draft['documents'][$field] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        $draft['completed'] = max($draft['completed'], $step);
        $request->session()->put('registration', $draft);

        return redirect()->route('registration', $step + 1);
    }

    public function submit(Request $request, FirestoreFileStorage $storage, DocumentStore $store)
    {
        if (! $request->session()->has('registration') && $request->session()->has('registration_success')) {
            return redirect()->route('registration.success');
        }

        $draft = $this->initialize($request);
        if ($draft['completed'] < 3) {
            return redirect()->route('registration', $draft['completed'] + 1);
        }

        // Drafts created before the address became mandatory must be completed first.
        foreach (['province_code', 'regency_code', 'district', 'village', 'address'] as $field) {
            if (blank($draft['parent'][$field] ?? null)) {
                return redirect()->route('registration', 2);
            }
        }

        foreach (self::DOCUMENT_FIELDS as $field) {
            $file = $draft['documents'][$field] ?? null;
            if (! $file || empty($file['path']) || ! $storage->exists($file['path'])) {
                throw ValidationException::withMessages([
                    'documents' => 'Berkas tidak ditemukan. Silakan unggah kembali pada langkah 3.',
                ]);
            }
        }

        // A retried request must never replace or delete files of an accepted submission.
        $existing = $store->get('applications', $draft['reference']);
        if ($existing) {
            abort_unless(hash_equals($existing['submission_hash'] ?? '', hash('sha256', $draft['token'])), 409);
            $request->session()->put('registration_success', $draft['reference']);
            $request->session()->forget('registration');
            return redirect()->route('registration.success');
        }
        $settings = array_replace(app(SchoolContent::class)->defaults()['settings'], $store->get('content', 'settings') ?? []);
        $amount = (int) preg_replace('/[^0-9]/', '', (string) ($settings['fee'] ?? '0'));
        abort_if($amount < 1, 422, 'Biaya pendaftaran belum tersedia. Hubungi panitia.');
        $paymentData = $request->validate([
            'payment_method' => ['required', Rule::in(['offline', 'transfer'])],
            'payment_proof' => [
                Rule::requiredIf(fn () => $request->input('payment_method') === 'transfer'
                    && (empty($draft['payment']['proof']['path'])
                        || ! $storage->exists($draft['payment']['proof']['path']))),
                'nullable', 'prohibited_if:payment_method,offline', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120',
            ],
            'consent' => 'accepted',
        ], [
            'payment_proof.max' => 'Ukuran bukti pembayaran melebihi 5 MB. Pilih berkas yang lebih kecil.',
            'payment_proof.mimes' => 'Bukti pembayaran harus berupa PDF, JPG, PNG, atau WEBP.',
        ]);

        $paymentMethod = $paymentData['payment_method'];
        $previousProof = $draft['payment']['proof'] ?? null;
        if ($paymentMethod === 'transfer') {
            if ($proof = $request->file('payment_proof')) {
                $path = 'applications/'.$draft['reference'].'/payment/'.Str::uuid().'.'.$proof->extension();
                $storage->putUploadedFile($path, $proof);
                if ($previousProof && ! empty($previousProof['path'])) {
                    $storage->delete($previousProof['path']);
                }
                $previousProof = [
                    'path' => $path,
                    'name' => $proof->getClientOriginalName(),
                    'size' => $proof->getSize(),
                    'mime' => $proof->getMimeType(),
                ];
            }
            $draft['payment'] = [
                'method' => 'transfer', 'amount' => $amount, 'status' => 'menunggu_verifikasi',
                'bank' => 'BCA', 'account_number' => '8535459831',
                'account_name' => 'Muhamad Azmer Fahrezi', 'proof' => $previousProof,
            ];
        } else {
            if ($previousProof && ! empty($previousProof['path'])) {
                $storage->delete($previousProof['path']);
            }
            $draft['payment'] = ['method' => 'offline', 'amount' => $amount, 'status' => 'belum_dibayar'];
        }

        if ($paymentMethod === 'transfer') {
            $draft['documents']['payment_proof'] = $previousProof;
        } else {
            unset($draft['documents']['payment_proof']);
        }
        $request->session()->put('registration', $draft);

        $record = [
            'reference' => $draft['reference'], 'submission_hash' => hash('sha256', $draft['token']),
            'child' => $draft['child'], 'parent' => $draft['parent'], 'documents' => $draft['documents'],
            'payment_method' => $paymentMethod, 'payment' => $draft['payment'],
            'status' => 'baru', 'notes' => '', 'consent_at' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ];
        $created = $store->create('applications', $draft['reference'], $record);

        if (! $created) {
            $existing = $store->get('applications', $draft['reference']);
            abort_unless(hash_equals($existing['submission_hash'] ?? '', $record['submission_hash']), 409, 'Nomor pendaftaran bertabrakan. Hubungi panitia.');
        }

        $request->session()->put('registration_success', $draft['reference']);
        $request->session()->forget('registration');

        return redirect()->route('registration.success');
    }

    public function success(Request $request, SchoolContent $content, DocumentStore $store)
    {
        if (! $request->session()->has('registration_success')) {
            return redirect()->route('registration', 1);
        }

        $record = $store->get('applications', $request->session()->get('registration_success'));

        return view('registration.success', [
            'site' => $content->all(), 'page' => 'registration',
            'reference' => $request->session()->get('registration_success'),
            'payment' => $record['payment'] ?? null,
        ]);
    }

    private function regions(): array
    {
        static $regions;

        return $regions ??= json_decode(file_get_contents(resource_path('data/indonesia-regions.json')), true, 512, JSON_THROW_ON_ERROR);
    }
}
