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
            $parent = $request->validate([
                'parent_name' => 'required|string|max:120', 'relationship' => ['required', Rule::in(['Ayah', 'Ibu', 'Wali'])],
                'phone' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{9,25}$/'], 'email' => 'required|email|max:190',
                'address' => 'nullable|string|max:1000', 'emergency_contact' => ['nullable', 'string', 'regex:/^\+?[0-9 ()-]{9,25}$/'],
                'domicile_latitude' => 'nullable|required_with:domicile_longitude|numeric|between:-90,90',
                'domicile_longitude' => 'nullable|required_with:domicile_latitude|numeric|between:-180,180',
            ]);

            if (isset($parent['domicile_latitude'], $parent['domicile_longitude'])) {
                $parent['domicile_latitude'] = (float) $parent['domicile_latitude'];
                $parent['domicile_longitude'] = (float) $parent['domicile_longitude'];
                $parent['domicile_distance_km'] = $this->distanceInKm(
                    $parent['domicile_latitude'],
                    $parent['domicile_longitude'],
                    (float) config('school.latitude'),
                    (float) config('school.longitude'),
                );
            } else {
                unset($parent['domicile_latitude'], $parent['domicile_longitude']);
            }

            $draft['parent'] = $parent;
        } else {
            $rules = [];
            foreach (self::DOCUMENT_FIELDS as $field) {
                $rules[$field] = [
                    isset($draft['documents'][$field]) ? 'nullable' : 'required',
                    'file', 'max:1024',
                    $field === 'photo' ? 'mimes:jpg,jpeg,png' : 'mimes:jpg,jpeg,png,pdf',
                ];
            }
            $request->validate($rules);

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

        $request->validate(['consent' => 'accepted']);

        foreach (self::DOCUMENT_FIELDS as $field) {
            $file = $draft['documents'][$field] ?? null;
            if (! $file || empty($file['path']) || ! $storage->exists($file['path'])) {
                throw ValidationException::withMessages([
                    'documents' => 'Berkas tidak ditemukan. Silakan unggah kembali pada langkah 3.',
                ]);
            }
        }

        $record = [
            'reference' => $draft['reference'], 'submission_hash' => hash('sha256', $draft['token']),
            'child' => $draft['child'], 'parent' => $draft['parent'], 'documents' => $draft['documents'],
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

    public function success(Request $request, SchoolContent $content)
    {
        if (! $request->session()->has('registration_success')) {
            return redirect()->route('registration', 1);
        }

        return view('registration.success', [
            'site' => $content->all(), 'page' => 'registration',
            'reference' => $request->session()->get('registration_success'),
        ]);
    }

    private function distanceInKm(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);
        $fromLatitude = deg2rad($fromLatitude);
        $toLatitude = deg2rad($toLatitude);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($fromLatitude) * cos($toLatitude) * sin($longitudeDelta / 2) ** 2;

        return round(6371.0088 * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine)), 2);
    }
}
