# Deployment Laravel + Firebase ke Vercel

Deployment produksi memakai Firestore untuk data halaman, akun admin, pendaftar, session, cache, serta isi berkas. Tidak perlu Firebase Storage, paket Blaze, bucket, atau storage server persisten.

Vercel menjalankan Laravel melalui community runtime `vercel-php@0.7.4` (PHP 8.3) di region Singapore (`sin1`). Runtime PHP ini dikelola komunitas, bukan runtime PHP resmi dari Vercel.

## 1. Penyimpanan berkas di Firestore

Setiap berkas disimpan sebagai potongan 512 KiB pada koleksi privat `uploaded_file_chunks` dan satu metadata document pada `uploaded_files`. Ukuran potongan menjaga payload Base64 di bawah batas 1 MiB per dokumen Firestore. Batas aplikasi **1 MiB per berkas**. Semua akses melewati Laravel; Firestore Rules tetap menolak akses browser langsung.

Penyimpanan berkas menggunakan kuota Firestore yang sama dengan data website, tanpa Firebase Storage atau paket Blaze. Satu berkas maksimum memakai dua dokumen potongan dan satu dokumen metadata; pantau kuota baca/tulis serta kapasitas Firestore di Firebase Console.

## 2. Salin service account ke clipboard sebagai Base64

Jalankan perintah berikut dari root proyek. Perintah ini membaca file lokal dan menaruh hasil Base64 ke clipboard tanpa mencetak private key ke terminal:

```powershell
$CredentialFile = Resolve-Path '.\storage\app\private\firebase-service-account.json'
$CredentialBase64 = [Convert]::ToBase64String([IO.File]::ReadAllBytes($CredentialFile.Path))
Set-Clipboard -Value $CredentialBase64
Remove-Variable CredentialBase64
```

Di Vercel, buat environment variable bernama `FIREBASE_CREDENTIALS_JSON_BASE64`, lalu tempel isi clipboard. Pilih **Production** dan **Preview**. Jangan memakai awalan `NEXT_PUBLIC_` atau `VITE_`, karena kredensial ini hanya boleh tersedia pada server.

Jangan unggah file JSON ke Git, Vercel project files, atau folder `public`. `.vercelignore` sudah mengecualikan folder kredensial lokal.

## 3. Buat APP_KEY produksi

Perintah berikut menaruh kunci baru ke clipboard tanpa mencetaknya:

```powershell
$AppKey = & 'C:\xampp\php\php.exe' artisan key:generate --show
Set-Clipboard -Value $AppKey
Remove-Variable AppKey
```

Simpan hasilnya sebagai `APP_KEY` di Vercel. Pertahankan kunci ini pada deployment berikutnya agar cookie dan data terenkripsi tetap dapat dibaca.

## 4. Environment Variables Vercel

Buka **Project → Settings → Environment Variables** dan tambahkan nilai berikut.

| Nama | Nilai |
| --- | --- |
| `APP_NAME` | `SD Ceria Nusantara` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | hasil langkah 3 |
| `APP_URL` | URL HTTPS produksi, misalnya `https://nama-proyek.vercel.app` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `warning` |
| `SCHOOL_STORE` | `firestore` |
| `FIREBASE_PROJECT_ID` | `sd-ceria-nusantara` |
| `FIREBASE_DATABASE_ID` | `(default)` |
| `FIREBASE_CREDENTIALS_JSON_BASE64` | hasil langkah 2 |
| `SESSION_DRIVER` | `firestore` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CACHE_STORE` | `firestore` |
| `QUEUE_CONNECTION` | `sync` |

Jangan mengatur `FIREBASE_CREDENTIALS` ke path file Windows pada Vercel. Jangan mengatur `FIREBASE_CA_BUNDLE`; runtime Vercel memakai certificate authority dari sistemnya.

`api/index.php` juga memasang nilai produksi tersebut sebagai nilai bawaan. Nilai dari dashboard tetap diprioritaskan. File cache konfigurasi dan compiled views dibuat di `/tmp`, karena filesystem Vercel Function selain `/tmp` bersifat read-only dan tidak persisten.

## 5. Hubungkan repository ke Vercel

1. Terapkan rules Firestore dan pengecualian index file (sekali sebelum rilis):

   ```powershell
   firebase deploy --only firestore:rules,firestore:indexes --project sd-ceria-nusantara
   ```

2. Push proyek ke repository Git pribadi. Pastikan `git status` tidak menampilkan `.env` atau file service account.
3. Di Vercel pilih **Add New → Project**, lalu impor repository.
4. Pilih **Framework Preset: Other**. `vercel.json` juga menetapkan preset `other` agar Vercel tidak menganggap aplikasi Laravel ini sebagai Vite dan mencari folder `dist`.
5. Pada **Settings → Build and Deployment**, hapus nilai `dist` dari **Output Directory** (matikan override bila aktif) dan kosongkan **Build Command**. Jangan set output ke `dist` atau `public`; `vercel.json` mengatur rute serverless dan aset aplikasi.
6. Masukkan semua environment variables pada langkah 4.
7. Jalankan deployment.

Konfigurasi `vercel.json` mengarahkan aset statis dari `public/assets`, `public/css`, dan `public/js` langsung sebagai file statis. Request lain masuk ke Laravel melalui `api/index.php`.

Jika masih ada unggahan dari instalasi lokal lama, jalankan `php artisan school:migrate-local-uploads` sebelum deploy. Perintah ini menyalin file ke Firestore dan membiarkan sumber lokal tetap ada.

## 6. Batas upload

Vercel Functions membatasi request dan response sekitar 4,5 MB. `api/php.ini` membatasi seluruh POST menjadi 4 MB dan satu file menjadi 1 MB. Validasi aplikasi menerapkan batas 1 MiB per dokumen pendaftaran maupun gambar admin.

File tetap persisten setelah redeploy karena disimpan di Firestore, bukan di filesystem function.

## 7. Pemeriksaan setelah deployment

1. Buka `/up` dan pastikan respons sukses.
2. Buka landing page dan periksa aset, tampilan mobile, tablet, serta desktop.
3. Login ke `/admin/login` dengan akun admin yang sudah ada di Firestore.
4. Ubah satu konten, muat ulang halaman publik, lalu pastikan perubahan tetap ada.
5. Kirim satu pendaftaran lengkap dengan tiga dokumen kecil.
6. Dari admin, periksa data pendaftar dan unduh setiap dokumen.
7. Redeploy lalu ulangi login dan buka data tadi. Konten, session, pendaftar, dan file harus tetap tersedia karena semuanya berada di Firebase.

Jika function mengembalikan `500`, lihat **Vercel → Deployment → Functions → Logs**. Dengan `LOG_CHANNEL=stderr`, pesan Laravel muncul di sana tanpa mencoba menulis `storage/logs/laravel.log`.
