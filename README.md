# SD Ceria Nusantara

Website Laravel 12 berdasarkan empat PDF desain sekolah, dengan pendaftaran online, permintaan kunjungan, dan panel admin. Memakai PHP 8.2+, Blade, CSS, dan JavaScript lokal. Tidak diperlukan proses build frontend.

## Buka instalasi yang sudah disiapkan

- Website: **http://127.0.0.1:8000**
- Admin: **http://127.0.0.1:8000/admin/login**
- Akun admin tersimpan di Firestore. Gunakan kredensial pengelola yang sudah disiapkan, lalu ganti kata sandi melalui menu profil setelah masuk.

Penyimpanan aplikasi sepenuhnya menggunakan **Cloud Firestore** untuk konten, admin, pendaftaran, kunjungan, session, cache, serta berkas yang dipecah menjadi dokumen privat. Tidak memerlukan Firebase Storage, paket Blaze, atau filesystem server persisten. SQLite hanya tersisa sebagai sumber impor lama dan backend pengujian. Lihat [status koneksi](docs/FIREBASE-CONNECTION.md), [panduan Firebase](docs/FIREBASE.md), atau [panduan deployment Vercel](docs/VERCEL_FIREBASE.md).

Jika server berhenti, buka PowerShell dari folder proyek:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\Start-Website.ps1
```

Atau gunakan PHP XAMPP yang terpasang di komputer ini:

```powershell
& 'C:\xampp\php\php.exe' -d upload_max_filesize=1M -d post_max_size=4M -S 127.0.0.1:8000 -t public tools/serve-router.php
```

Biarkan terminal server berjalan. Hentikan dengan Ctrl+C. Untuk port lain gunakan `.\Start-Website.ps1 -Port 8001`.

## Fitur

- Beranda, tentang sekolah, program, fasilitas, guru/staf, galeri, dan kontak.
- Desktop dan tablet berdasarkan PDF, serta penyesuaian ponsel.
- Pendaftaran empat langkah: anak, orang tua, berkas, dan pemeriksaan/persetujuan.
- Upload akta, KK, dan foto dengan validasi tipe/ukuran; dokumen hanya dapat diunduh admin yang masuk.
- Formulir permintaan kunjungan sekolah.
- Admin untuk mengedit teks/gambar, meninjau pendaftar dan kunjungan, mengubah status/catatan, serta ekspor CSV.
- Akun pemilik dan editor, perubahan kata sandi, dan penonaktifan akun. Penghapusan data dan pengelolaan akun dibatasi untuk pemilik.
- Pagination, cache konten, validasi server, CSRF, pembatasan percobaan login, dan password hash.

Konfirmasi pendaftaran ditampilkan di website. Tautan WhatsApp/email membuka aplikasi terkait; pengiriman notifikasi otomatis belum dikonfigurasi.

## Instalasi pada komputer lain

Siapkan PHP 8.2+, Composer, serta ekstensi Laravel termasuk mbstring, cURL, OpenSSL, DOM/XML, dan fileinfo. Konfigurasikan Firebase pada `.env` sebelum menjalankan setup.

```text
composer run setup
php artisan school:admin admin@sekolah.sch.id --name="Admin Sekolah"
composer run dev
```

`setup` memasang dependensi, menyiapkan `.env`, membuat application key jika belum ada, dan mengisi konten awal ke Firestore. Untuk membuat akun pengelola baru, jalankan `php artisan school:admin admin@sekolah.sch.id --name="Admin Sekolah"` setelah Firebase terhubung. Perintah setup tidak membuat atau memigrasikan database SQL.

Pada komputer ini Composer juga tersedia di `.tools/composer.phar`:

```powershell
& 'C:\xampp\php\php.exe' -d extension=zip .tools/composer.phar validate --no-check-publish
```

Untuk koneksi Firebase, ikuti [FIREBASE.md](docs/FIREBASE.md). Untuk sumber desain dan struktur tampilan, lihat [DESIGN.md](docs/DESIGN.md).

## Data dan berkas

| Lokasi | Isi |
| --- | --- |
| Firebase Firestore | Konten, akun, pendaftaran, kunjungan, session, cache, dan blob berkas privat |
| Firestore (`uploaded_files`, `uploaded_file_chunks`) | Dokumen pendaftaran dan gambar konten, maksimal 1 MiB per berkas |
| `database/database.sqlite` | Sumber SQLite lama untuk impor satu kali dan backend tes saja |
| `resources/content/site.php` | Konten awal dari desain |
| `storage/app/private/applications` | Sumber lokal lama untuk unggahan yang belum dimigrasikan |
| `storage/app/public/media` | Sumber lokal lama untuk media yang belum dimigrasikan |
| `storage/app/private/firebase-service-account.json` | Kredensial server Firebase, jika dipasang |
| `public/assets/design` | Aset yang diekstrak dari PDF |

Untuk menyalin unggahan lokal lama tanpa menghapus sumber, jalankan `php artisan school:migrate-local-uploads` pada instalasi lokal dengan Firestore terkonfigurasi. Data pribadi, kunci, database lokal, dan unggahan dikecualikan dari Git. Document root hosting harus mengarah ke `public`.

## Pengujian

```text
php artisan test --compact
```

Test memakai SQLite sementara dan respons HTTP simulasi untuk Firestore. Pemeriksaan terhadap Firebase sebenarnya dilakukan dengan `php artisan school:firebase-check` setelah kredensial dipasang.

Skrip `tools/verify_browser.py` dan `tools/verify_workflow.py` membutuhkan Python, Playwright, Edge, dan website lokal aktif. Workflow memakai akun di file privat, membuat data percobaan, lalu menghapus record percobaan tersebut. `tools/verify_firebase_workflow.py` memeriksa penyimpanan langsung ke Firestore, memulihkan perubahan konten, membersihkan record percobaan, dan memastikan data SQLite tidak berubah.

Untuk hosting produksi dan konfigurasi PHP unggahan, lihat bagian hosting pada [panduan Firebase](docs/FIREBASE.md).
