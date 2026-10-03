<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Contracts\DocumentStore;
use App\Services\FirestoreFileStorage;
use App\Services\SchoolContent;

Artisan::command('school:seed-content', function (DocumentStore $store, SchoolContent $content) {
    foreach ($content->defaults() as $key => $data) $store->create('content',$key,$data);
    \Illuminate\Support\Facades\Cache::forget('school-content-'.config('school.store'));
    $this->info('Konten awal siap. Konten yang sudah ada tetap dipertahankan.');
})->purpose('Isi konten awal dari desain PDF tanpa menimpa perubahan admin');

Artisan::command('school:admin {email} {--name=Administrator}', function (DocumentStore $store) {
    $email = mb_strtolower(trim($this->argument('email')));
    $password = $this->secret('Kata sandi baru (minimal 12 karakter, huruf dan angka)');
    $validator = Validator::make(['email'=>$email,'password'=>$password],['email'=>'required|email|max:190','password'=>['required',\Illuminate\Validation\Rules\Password::min(12)->letters()->numbers()]]);
    if ($validator->fails()) { foreach($validator->errors()->all() as $error) $this->error($error); return 1; }
    $created = $store->create('admins',hash('sha256',$email),[
        'name'=>$this->option('name'),'email'=>$email,'password'=>Hash::make($password),
        'role'=>'owner','active'=>true,'created_at'=>now()->toIso8601String(),
    ]);
    if (! $created) { $this->error('Email sudah terdaftar.'); return 1; }
    $this->info('Akun pemilik berhasil dibuat. Masuk melalui /admin/login.');
})->purpose('Buat akun pemilik tanpa kata sandi bawaan');

Artisan::command('school:firebase-check', function () {
    if (config('school.store') !== 'firestore') { $this->error('Atur SCHOOL_STORE=firestore terlebih dahulu.'); return 1; }
    try {
        // Listing an empty collection succeeds; a missing database must fail.
        app(DocumentStore::class)->page('content', null, 1);
        $this->info('Koneksi dan autentikasi Firestore berhasil.');
    } catch (\Throwable $e) {
        report($e);
        $status = $e instanceof \Illuminate\Http\Client\RequestException ? $e->response->status() : null;
        $this->error(match ($status) {
            401 => 'Autentikasi Firestore ditolak. Periksa kredensial service account, lalu bersihkan cache Laravel.',
            403 => 'Akses Firestore ditolak. Periksa izin IAM service account dan pastikan API Firestore aktif.',
            404 => 'Database Firestore tidak ditemukan. Periksa Project ID, FIREBASE_DATABASE_ID, dan apakah database sudah dibuat.',
            default => 'Koneksi gagal. Periksa project ID, service account, izin IAM, dan koneksi internet.',
        });
        return 1;
    }
})->purpose('Periksa koneksi Firestore tanpa menulis data');

Artisan::command('school:migrate-local-uploads', function (FirestoreFileStorage $firebase) {
    if (! app()->environment('local', 'testing') || config('school.store') !== 'firestore') {
        $this->error('Migrasi unggahan hanya dapat dijalankan dari lingkungan lokal dengan Firestore aktif.');
        return 1;
    }

    $migrated = 0;
    foreach ([
        ['local', 'applications'],
        ['public', 'media'],
    ] as [$diskName, $directory]) {
        $disk = Storage::disk($diskName);

        foreach ($disk->allFiles($directory) as $path) {
            $contents = $disk->get($path);
            if (! is_string($contents)) {
                throw new \RuntimeException("Tidak dapat membaca berkas lokal: {$path}");
            }

            $firebase->put($path, $contents, $disk->mimeType($path) ?: 'application/octet-stream');
            $migrated++;
            $this->line("Tersalin: {$path}");
        }
    }

    $this->info("{$migrated} berkas lokal tersalin ke Firestore. Berkas sumber lokal tidak dihapus.");
})->purpose('Salin unggahan lokal ke Firestore tanpa menghapus sumber');

Artisan::command('school:import-firestore', function () {
    if (! app()->environment('local', 'testing') || config('database.default') !== 'sqlite') {
        $this->error('Impor SQLite hanya berjalan secara lokal dengan DB_CONNECTION=sqlite.');
        return 1;
    }
    if (!config('school.firebase_project')) { $this->error('Isi konfigurasi Firebase terlebih dahulu.'); return 1; }
    $target = app(\App\Services\FirestoreDocumentStore::class);
    $count = 0;
    \Illuminate\Support\Facades\DB::table('documents')->orderBy('collection')->orderBy('document_id')->chunk(100,function($rows) use($target,&$count) {
        foreach($rows as $row) $count += (int)$target->create($row->collection,$row->document_id,json_decode($row->payload,true,512,JSON_THROW_ON_ERROR));
    });
    \Illuminate\Support\Facades\Cache::forget('school-content-firestore');
    $this->info("{$count} dokumen baru disalin ke Firestore. Dokumen lama tidak ditimpa.");
})->purpose('Salin data lokal ke Firestore setelah konfigurasi diisi');
