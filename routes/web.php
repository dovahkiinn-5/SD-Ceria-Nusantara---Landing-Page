<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\VisitController;
use App\Services\FirestoreFileStorage;
use Illuminate\Support\Facades\Route;

foreach (['home'=>'','about'=>'tentang-kami','program'=>'program','facilities'=>'fasilitas','teachers'=>'guru-staf','gallery'=>'galeri','contact'=>'kontak','privacy'=>'privasi','terms'=>'syarat-ketentuan'] as $page=>$path) {
    Route::get('/'.$path,[PageController::class,'show'])->defaults('page',$page)->name($page);
}
Route::get('/media/{filename}', function (string $filename, FirestoreFileStorage $storage) {
    abort_unless(preg_match('/^[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$/',$filename),404);
    $image = $storage->get('media/'.$filename);
    abort_unless($image,404);

    return response($image['contents'], 200, [
        'Content-Type' => $image['content_type'],
        'Content-Length' => $image['size'],
        'Cache-Control' => 'public, max-age=86400',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->name('media');
Route::get('/pendaftaran/berhasil',[RegistrationController::class,'success'])->name('registration.success');
Route::get('/pendaftaran/{step?}',[RegistrationController::class,'show'])->where('step','[1-4]')->name('registration');
Route::post('/pendaftaran/langkah/{step}',[RegistrationController::class,'save'])->where('step','[1-3]')->middleware('throttle:submissions')->block(20,20)->name('registration.save');
Route::post('/pendaftaran/kirim',[RegistrationController::class,'submit'])->middleware('throttle:submissions')->block(30,30)->name('registration.submit');
Route::post('/kunjungan',[VisitController::class,'store'])->middleware('throttle:visits')->name('visit.store');
Route::get('/kunjungan/berhasil',[VisitController::class,'success'])->name('visit.success');
Route::middleware('guest')->group(function () {
    Route::get('/admin/login',[AdminController::class,'loginForm'])->name('login');
    Route::post('/admin/login',[AdminController::class,'login'])->middleware('throttle:login')->name('login.submit');
});
Route::prefix('admin')->name('admin.')->middleware(['auth','auth.session'])->group(function () {
    Route::get('/',[AdminController::class,'dashboard'])->name('dashboard');
    Route::post('/logout',[AdminController::class,'logout'])->name('logout');
    Route::get('/pendaftar/export',[AdminController::class,'export'])->name('export');
    Route::get('/data/{collection}',[AdminController::class,'records'])->name('records');
    Route::get('/data/{collection}/{id}',[AdminController::class,'record'])->name('record');
    Route::patch('/data/{collection}/{id}',[AdminController::class,'updateRecord'])->name('record.update');
    Route::delete('/data/{collection}/{id}',[AdminController::class,'deleteRecord'])->name('record.delete');
    Route::get('/berkas/{id}/{field}',[AdminController::class,'document'])->name('document');
    Route::get('/konten/{section?}',[AdminController::class,'content'])->name('content');
    Route::put('/konten/{section}',[AdminController::class,'updateContent'])->name('content.update');
    Route::get('/akun',[AdminController::class,'accounts'])->name('accounts');
    Route::post('/akun',[AdminController::class,'createAccount'])->name('accounts.store');
    Route::patch('/akun/{id}',[AdminController::class,'toggleAccount'])->name('accounts.toggle');
    Route::get('/profil',fn () => view('admin.profile'))->name('profile');
    Route::put('/profil',[AdminController::class,'changePassword'])->middleware('throttle:login')->name('password');
});
