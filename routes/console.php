<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Contracts\DocumentStore;
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

Artisan::command('school:local-admin', function (DocumentStore $store) {
    if (! app()->environment('local') || config('school.store') !== 'sqlite') { $this->error('Perintah ini khusus lingkungan lokal SQLite.'); return 1; }
    $email = 'admin@sdceria.local';
    $password = Str::password(24);
    if ($store->create('admins',hash('sha256',$email),['name'=>'Admin Sekolah','email'=>$email,'password'=>Hash::make($password),'role'=>'owner','active'=>true,'created_at'=>now()->toIso8601String()])) {
        Storage::disk('local')->put('local-admin.txt',"Email: {$email}\nPassword: {$password}\nURL: http://127.0.0.1:8000/admin/login\n\nGanti kata sandi melalui Profil & Kata Sandi setelah masuk.\n");
        $this->info('Kredensial lokal disimpan di storage/app/private/local-admin.txt.');
    } else { $this->info('Akun lokal sudah tersedia; kata sandi tidak diubah.'); }
})->purpose('Siapkan akun admin lokal dengan kata sandi acak');

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

Artisan::command('school:import-firestore', function () {
    if (!config('school.firebase_project')) { $this->error('Isi konfigurasi Firebase terlebih dahulu.'); return 1; }
    $target = new \App\Services\FirestoreDocumentStore;
    $count = 0;
    \Illuminate\Support\Facades\DB::table('documents')->orderBy('collection')->orderBy('document_id')->chunk(100,function($rows) use($target,&$count) {
        foreach($rows as $row) $count += (int)$target->create($row->collection,$row->document_id,json_decode($row->payload,true,512,JSON_THROW_ON_ERROR));
    });
    \Illuminate\Support\Facades\Cache::forget('school-content-firestore');
    $this->info("{$count} dokumen baru disalin ke Firestore. Dokumen lama tidak ditimpa. Berkas unggahan tetap berada di storage/app.");
})->purpose('Salin data lokal ke Firestore setelah konfigurasi diisi');

Artisan::command('school:prune-uploads', function (DocumentStore $store) {
    $disk = Storage::disk('local');
    $removed = 0;
    foreach($disk->directories('applications') as $directory) {
        $id = basename($directory);
        if (!preg_match('/^CN-\d{4}-[0-9A-Z]{10}$/',$id)) continue;
        $files = $disk->files($directory);
        $latest = $files ? max(array_map(fn($file)=>$disk->lastModified($file),$files)) : time();
        if ($latest < now()->subDays(2)->timestamp && !$store->get('applications',$id)) { $disk->deleteDirectory($directory); $removed++; }
    }
    $this->info("{$removed} unggahan pendaftaran yang tidak selesai dibersihkan.");
})->purpose('Hapus unggahan tanpa pendaftaran setelah dua hari');

\Illuminate\Support\Facades\Schedule::command('school:prune-uploads')->daily()->withoutOverlapping();
