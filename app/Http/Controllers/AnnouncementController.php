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
        ], [
            'reference' => ['required', 'string', 'max:40', 'regex:/^CN-[A-Z0-9-]+$/'],
        ], [
            'reference.regex' => 'Format nomor pendaftaran tidak sesuai.',
        ]);

        if ($validator->fails()) {
            return $this->noStore(
                redirect()->route('announcement')
                    ->withErrors($validator)
                    ->withInput(['reference' => $reference]),
            );
        }

        $record = $store->get('applications', $reference);
        if (! $record) {
            return $this->noStore(
                redirect()->route('announcement')
                    ->withErrors(['reference' => 'Nomor pendaftaran tidak ditemukan. Periksa kembali nomor Anda.'])
                    ->withInput(['reference' => $reference]),
            );
        }

        $status = (string) ($record['status'] ?? 'baru');
        $result = match ($status) {
            'diterima' => [
                'status' => 'diterima',
                'tone' => 'accepted',
                'label' => 'LULUS ADMINISTRASI',
                'title' => 'Selamat, kamu lolos administrasi!',
                'message' => 'Calon peserta didik lolos pemeriksaan administrasi. Silakan lanjutkan daftar ulang dan pemeriksaan dokumen di sekolah sebelum aktivasi siswa.',
            ],
            'ditolak' => [
                'status' => 'ditolak',
                'tone' => 'rejected',
                'label' => 'TIDAK LULUS',
                'title' => 'Terima kasih sudah mengikuti seleksi',
                'message' => 'Calon peserta didik belum dinyatakan lulus pada periode penerimaan ini.',
            ],
            'perlu_perbaikan' => [
                'status' => 'perlu_perbaikan', 'tone' => 'pending',
                'label' => 'PERLU PERBAIKAN', 'title' => 'Lengkapi berkas pendaftaran',
                'message' => (string) ($record['admin_notes'] ?? 'Hubungi panitia untuk melengkapi berkas.'),
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
