<?php

namespace App\Http\Controllers;

use App\Contracts\DocumentStore;
use App\Services\SchoolContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
    public function show(SchoolContent $content)
    {
        return $this->page($content);
    }

    public function lookup(Request $request, DocumentStore $store, SchoolContent $content)
    {
        $reference = mb_strtoupper(trim((string) $request->input('reference')));
        $validator = Validator::make([
            'reference' => $reference,
            'birth_date' => $request->input('birth_date'),
        ], [
            'reference' => ['required', 'string', 'max:40', 'regex:/^CN-[A-Z0-9-]+$/'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before:today'],
        ], [
            'reference.regex' => 'Format nomor pendaftaran tidak sesuai.',
            'birth_date.date_format' => 'Format tanggal lahir tidak sesuai.',
        ]);

        if ($validator->fails()) {
            return $this->noStore(
                redirect()->route('announcement')
                    ->withErrors($validator)
                    ->withInput(['reference' => $reference]),
            );
        }

        $record = $store->get('applications', $reference);
        $storedBirthDate = (string) ($record['child']['birth_date'] ?? '');

        if (! $record || $storedBirthDate === '' || ! hash_equals($storedBirthDate, (string) $request->input('birth_date'))) {
            return $this->noStore(
                redirect()->route('announcement')
                    ->withErrors(['reference' => 'Nomor pendaftaran atau tanggal lahir tidak cocok.'])
                    ->withInput(['reference' => $reference]),
            );
        }

        $status = (string) ($record['status'] ?? 'baru');
        $result = match ($status) {
            'diterima' => [
                'status' => 'diterima',
                'tone' => 'accepted',
                'label' => 'LULUS',
                'title' => 'Selamat, kamu diterima!',
                'message' => 'Calon peserta didik dinyatakan lulus seleksi penerimaan SD Ceria Nusantara.',
            ],
            'ditolak' => [
                'status' => 'ditolak',
                'tone' => 'rejected',
                'label' => 'TIDAK LULUS',
                'title' => 'Terima kasih sudah mengikuti seleksi',
                'message' => 'Calon peserta didik belum dinyatakan lulus pada periode penerimaan ini.',
            ],
            default => [
                'status' => 'diproses',
                'tone' => 'pending',
                'label' => 'MASIH DIPROSES',
                'title' => 'Pengumuman belum tersedia',
                'message' => 'Data pendaftaran sudah ditemukan. Panitia masih memeriksa hasil seleksi.',
            ],
        };

        $result['reference'] = $reference;
        $result['child_name'] = (string) ($record['child']['child_name'] ?? 'Calon peserta didik');

        return $this->page(
            $content,
            $result,
            $status === 'diterima' ? (string) config('school.whatsapp_group_url') : null,
        );
    }

    private function page(SchoolContent $content, ?array $result = null, ?string $whatsappGroupUrl = null)
    {
        return $this->noStore(response()->view('pages.announcement', [
            'site' => $content->all(),
            'page' => 'announcement',
            'result' => $result,
            'whatsappGroupUrl' => $whatsappGroupUrl,
        ]));
    }

    private function noStore($response)
    {
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
