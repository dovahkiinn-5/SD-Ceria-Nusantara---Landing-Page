# SD Ceria Nusantara

Website Laravel 12 berdasarkan empat PDF desain sekolah, dengan pendaftaran online, permintaan kunjungan, dan panel admin. Memakai PHP 8.2+, Blade, CSS, dan JavaScript lokal. Tidak diperlukan proses build frontend.

## Buka instalasi yang sudah disiapkan

- Website: **http://127.0.0.1:8000**
- Admin: **http://127.0.0.1:8000/admin/login**
- Akun admin awal: lihat [`storage/app/private/local-admin.txt`](storage/app/private/local-admin.txt). Akun ini sudah disalin ke Firestore. Ganti kata sandi melalui menu profil setelah masuk.

Database aplikasi sekarang memakai **Cloud Firestore**, proyek `sd-ceria-nusantara`, database `(default)`. Akun admin dan 8 dokumen konten sudah dipindahkan. Login admin, penyimpanan konten, pendaftaran, dan kunjungan menggunakan Firestore. Salinan SQLite tetap tersedia sebagai data sebelum perpindahan. Lihat [status koneksi](docs/FIREBASE-CONNECTION.md) atau [panduan Firebase](docs/FIREBASE.md).

Jika server berhenti, buka PowerShell dari folder proyek:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\Start-Website.ps1
```

Atau gunakan PHP XAMPP yang terpasang di komputer ini:

```powershell
& 'C:\xampp\php\php.exe' -d upload_max_filesize=5M -d post_max_size=20M -S 127.0.0.1:8000 -t public tools/serve-router.php
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

Siapkan PHP 8.2+, Composer, serta ekstensi Laravel termasuk PDO SQLite, mbstring, cURL, OpenSSL, DOM/XML, dan fileinfo.

```text
composer run setup
php artisan school:admin admin@sekolah.sch.id --name="Admin Sekolah"
composer run dev
```

`setup` memasang dependensi, menyiapkan `.env`, membuat application key jika belum ada, menjalankan migrasi SQLite, dan mengisi konten awal. Masukkan kata sandi saat `school:admin` meminta. Gunakan email sendiri. Jalankan dari direktori proyek.

Pada komputer ini Composer juga tersedia di `.tools/composer.phar`:

```powershell
& 'C:\xampp\php\php.exe' -d extension=zip .tools/composer.phar validate --no-check-publish
```

Untuk database cloud, ikuti [FIREBASE.md](docs/FIREBASE.md). Untuk sumber desain dan struktur tampilan, lihat [DESIGN.md](docs/DESIGN.md).

## Data dan berkas

| Lokasi | Isi |
| --- | --- |
| `database/database.sqlite` | Data lokal ketika `SCHOOL_STORE=sqlite` |
| `resources/content/site.php` | Konten awal dari desain |
| `storage/app/private/applications` | Dokumen pendaftaran privat |
| `storage/app/public/media` | Gambar konten yang diunggah admin |
| `storage/app/private/firebase-service-account.json` | Kredensial server Firebase, jika dipasang |
| `public/assets/design` | Aset yang diekstrak dari PDF |

Jaga pasangan data pendaftar dan berkasnya saat backup atau pindah server. Data pribadi, kunci, database lokal, dan unggahan dikecualikan dari Git. Document root hosting harus mengarah ke `public`.

## Pengujian

```text
php artisan test --compact
```

Test memakai SQLite sementara dan respons HTTP simulasi untuk Firestore. Pemeriksaan terhadap Firebase sebenarnya dilakukan dengan `php artisan school:firebase-check` setelah kredensial dipasang.

Skrip `tools/verify_browser.py` dan `tools/verify_workflow.py` membutuhkan Python, Playwright, Edge, dan website lokal aktif. Workflow memakai akun di file privat, membuat data percobaan, lalu menghapus record percobaan tersebut. `tools/verify_firebase_workflow.py` memeriksa penyimpanan langsung ke Firestore, memulihkan perubahan konten, membersihkan record percobaan, dan memastikan data SQLite tidak berubah.

Untuk hosting produksi dan konfigurasi PHP unggahan, lihat bagian hosting pada [panduan Firebase](docs/FIREBASE.md).
