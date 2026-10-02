# Membuat Firebase gratis dan menghubungkannya ke Laravel

Panduan ini mengaktifkan **Cloud Firestore** untuk akun admin, konten sekolah, pendaftar, dan permintaan kunjungan. Proyek `sd-ceria-nusantara` sudah dibuat dan dapat diakses Firebase CLI. Lihat [status dan pengaturan koneksi Laravel](FIREBASE-CONNECTION.md) untuk langkah pada instalasi ini.

## 1. Pilih paket gratis

Gunakan **Spark**, yang dapat dimulai tanpa metode pembayaran. Pertahankan paket Spark jika ingin membatasi penggunaan ke layanan gratis. [Harga Firebase](https://firebase.google.com/pricing).

Kuota gratis Firestore, diperiksa pada 2 Oktober 2026:

| Penggunaan | Kuota |
| --- | --- |
| Penyimpanan database | 1 GiB |
| Pembacaan dokumen | 50.000 per hari |
| Penulisan dokumen | 20.000 per hari |
| Penghapusan dokumen | 20.000 per hari |
| Transfer data keluar | 10 GiB per bulan |

Satu database per proyek mendapat kuota gratis. Pantau tab **Usage**. Backup terkelola, pemulihan, dan beberapa fitur lanjutan memerlukan penagihan. [Kuota resmi Firestore](https://firebase.google.com/docs/firestore/quotas).

Firestore menyimpan dokumen. Aplikasi sudah mempunyai adapter Firestore sehingga formulir dan admin dapat menggunakan layanan ini. Berkas KK, akta, dan foto tetap disimpan pada disk privat server Laravel; Firestore menyimpan data dan lokasi berkas. Kapasitas berkas mengikuti ruang disk server.

## 2. Buat proyek dan database

1. Masuk menggunakan akun Google ke [Firebase Console](https://console.firebase.google.com/).
2. Pilih **Create a project / Add project**, misalnya `SD Ceria Nusantara`.
3. Ikuti langkah pembuatan proyek. Google Analytics opsional untuk website ini.
4. Buka **Project settings → General**. Catat **Project ID**, misalnya `sd-ceria-nusantara-xxxxx`. Project ID berbeda dari nama tampilan proyek.
5. Buka **Firestore Database** melalui menu database, lalu **Create database**.
6. Pilih **Standard edition** bila ada pilihan edisi, dengan database ID **`(default)`**. Pilih lokasi dekat pengguna/server; Jakarta atau Singapura dapat dipilih jika tersedia pada konsol.
7. Pilih **Production mode**, lalu selesaikan pembuatan database. Mode ini menolak akses langsung dari browser; server yang memiliki izin tetap dapat mengakses database. [Panduan pembuatan Firestore](https://firebase.google.com/docs/firestore/quickstart).

## 3. Siapkan kredensial server

1. Buka **Project settings → Service accounts**.
2. Pilih **Generate new private key**, kemudian konfirmasikan unduhan JSON. Pilihan bahasa contoh SDK tidak mengubah format JSON. [Panduan service account Firebase](https://firebase.google.com/docs/admin/setup).
3. Simpan berkas tersebut sebagai:

   ```text
   E:\Landing Page Website\storage\app\private\firebase-service-account.json
   ```

4. Simpan kunci hanya di server/komputer yang dipercaya. Jangan letakkan di `public`, kirim lewat chat, atau commit ke Git. Lokasi di atas sudah dikecualikan dari Git.

Untuk hosting, gunakan service account khusus aplikasi dengan izin data Firestore yang diperlukan, misalnya **Cloud Datastore User** (`roles/datastore.user`). Koneksi server menggunakan OAuth dan izin IAM. [Autentikasi Firestore REST API](https://firebase.google.com/docs/firestore/use-rest-api).

## 4. Atur koneksi Laravel

Buka `.env`, lalu sesuaikan:

```dotenv
SCHOOL_STORE=firestore
FIREBASE_PROJECT_ID=sd-ceria-nusantara-xxxxx
FIREBASE_DATABASE_ID=(default)
```

Ganti contoh Project ID dengan ID sendiri. Bila kredensial disimpan di lokasi standar langkah 3, tidak perlu variabel lain. Untuk lokasi berbeda, tambahkan path absolut dengan garis miring biasa:

```dotenv
FIREBASE_CREDENTIALS="E:/Landing Page Website/storage/app/private/firebase-service-account.json"
```

Di PowerShell:

```powershell
Set-Location -LiteralPath 'E:\Landing Page Website'
& 'C:\xampp\php\php.exe' artisan optimize:clear
& 'C:\xampp\php\php.exe' artisan school:firebase-check
```

Hasil yang diharapkan: **Koneksi dan autentikasi Firestore berhasil.** Pemeriksaan hanya membaca. Apabila PHP sudah tersedia di PATH, `php` dapat menggantikan path XAMPP pada seluruh perintah panduan ini.

## 5. Isi database

Pilih salah satu cara berikut.

### A. Pindahkan data lokal yang sudah ada

Cara ini cocok untuk instalasi sekarang: konten dan akun admin lokal sudah tersedia.

```powershell
& 'C:\xampp\php\php.exe' artisan school:import-firestore
& 'C:\xampp\php\php.exe' artisan optimize:clear
```

Perintah menyalin dokumen SQLite ke Firestore dan melewati ID yang sudah ada. Data lokal tetap ada. Akun lokal yang disalin dapat dipakai untuk masuk; kata sandi tersimpan sebagai hash. Buat akun pengelola dengan email sendiri melalui menu **Akun admin**, lalu ganti kata sandi melalui **Profil & Kata Sandi**.

Import tidak menyalin berkas ke cloud. Saat pindah komputer atau hosting, ikut salin folder `storage/app/private/applications` dan `storage/app/public/media` melalui saluran privat. Data Firestore dan berkasnya harus tetap berpasangan.

### B. Mulai database baru

```powershell
& 'C:\xampp\php\php.exe' artisan school:seed-content
& 'C:\xampp\php\php.exe' artisan school:admin admin@sekolah.sch.id --name="Admin Sekolah"
```

Gunakan email pengelola sendiri. Perintah kedua meminta kata sandi secara tersembunyi, minimal 12 karakter dengan huruf dan angka. Konten yang sudah ada tidak ditimpa oleh seed.

## 6. Pasang aturan database

Buka **Firestore Database → Rules**, salin isi [`firebase/firestore.rules`](../firebase/firestore.rules), lalu pilih **Publish**:

```text
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /{document=**} {
      allow read, write: if false;
    }
  }
}
```

Semua akses pengguna melewati Laravel, termasuk login dan pengunduhan dokumen. Service account server mendapat akses melalui IAM. Firebase Authentication dan konfigurasi Firebase JavaScript di browser tidak diperlukan untuk implementasi ini.

Jika Firebase CLI sudah terpasang, rules dan pengecualian indeks dapat diterapkan dengan:

```text
firebase deploy --only firestore:rules,firestore:indexes --project PROJECT_ID_ANDA
```

Pengecualian indeks tersedia di [`firebase/firestore.indexes.json`](../firebase/firestore.indexes.json) untuk data yang tidak dicari melalui indeks.

## 7. Periksa dari website

1. Buka `http://127.0.0.1:8000/admin/login` dan masuk.
2. Ubah satu teks melalui **Konten website**, simpan, lalu periksa halaman publik.
3. Buat satu pendaftaran percobaan dengan data dan dokumen contoh.
4. Pastikan record muncul di admin dan koleksi `applications` pada Firestore Console.
5. Hapus data percobaan melalui admin agar berkas terkait juga terhapus.

Koleksi utama: `admins`, `content`, `applications`, dan `visits`. Daftar admin memakai pagination; konten publik memakai cache lima menit yang dibersihkan saat admin menyimpan perubahan.

## Jika koneksi belum berhasil

| Gejala | Yang diperiksa |
| --- | --- |
| Kredensial tidak ditemukan | Nama file, lokasi privat, dan `FIREBASE_CREDENTIALS` bila diisi |
| `PERMISSION_DENIED` / HTTP 403 | Izin IAM service account dan API Firestore pada proyek |
| `NOT_FOUND` / HTTP 404 | Project ID, database sudah dibuat, ID database `(default)` |
| Kesalahan koneksi / sertifikat | Internet, ekstensi cURL/OpenSSL, dan konfigurasi CA PHP. Jika perlu, isi `FIREBASE_CA_BUNDLE` dengan path bundle CA tepercaya; verifikasi TLS tetap aktif |
| Akun lokal tidak bisa masuk setelah beralih | Jalankan import, atau buat akun pemilik di Firestore |
| Kuota habis | Periksa Usage; tunggu pembaruan kuota atau evaluasi kebutuhan kapasitas |

Detail kesalahan tersedia di `storage/logs/laravel.log`; log dapat mengandung informasi aplikasi, jadi jangan dipublikasikan. Aplikasi tidak berpindah diam-diam ke SQLite ketika Firestore gagal. Untuk kembali ke data lokal, ubah `SCHOOL_STORE=sqlite` dan jalankan `artisan optimize:clear`; perubahan cloud setelah import tidak otomatis tersalin kembali.

## Saat dipasang di hosting

Firestore gratis adalah layanan database. Laravel tetap membutuhkan server PHP dan penyimpanan berkas yang menetap. Firebase Hosting menyajikan konten statis, sedangkan pemrosesan dinamis memerlukan backend tambahan. [Kemampuan Firebase Hosting](https://firebase.google.com/docs/hosting).

Gunakan hosting PHP 8.2+ dengan document root `public` dan konfigurasi:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-sekolah-anda
SESSION_SECURE_COOKIE=true
```

Pertahankan `APP_KEY`, simpan kunci Firebase secara privat, dan pastikan `storage` serta `bootstrap/cache` dapat ditulis PHP. Gunakan `upload_max_filesize=5M` dan `post_max_size=20M`. Jalankan `php artisan schedule:run` setiap menit agar unggahan pendaftaran yang tidak selesai dibersihkan setelah dua hari. Simpan backup database dan berkas privat sesuai kebutuhan sekolah.

Jika memakai lebih dari satu server Laravel, siapkan session, cache, lock, dan penyimpanan berkas bersama. Konfigurasi bawaan memakai satu server dengan disk lokal.
