# Pengaturan koneksi sd-ceria-nusantara

Status pemeriksaan pada 2 Oktober 2026:

| Pengaturan | Nilai / status |
| --- | --- |
| Project ID | `sd-ceria-nusantara`, terverifikasi melalui Firebase CLI |
| Database | `(default)`, aktif |
| Edisi / mode | Standard / Firestore Native |
| Lokasi | `asia-southeast2` (Jakarta) |
| Kuota gratis | `freeTier: true` pada metadata database |
| Akses Firebase CLI | Berhasil dengan login lokal yang sudah ada |
| Rules database | Akses langsung klien ditolak (`allow read, write: if false`) |
| Rules Firestore | Sudah diterapkan; menolak akses browser langsung |
| Indexes Firestore | Perubahan pengecualian field payload file lolos validasi dry-run; perlu diterapkan sebelum rilis |
| Project ID Laravel | Sudah diisi pada `.env` |
| Kredensial Laravel | JSON yang disediakan pengguna sudah dipasang dan autentikasinya berhasil |
| Penyimpanan aktif Laravel | **Firestore** (`SCHOOL_STORE=firestore`) |
| Penyimpanan berkas | Koleksi Firestore `uploaded_files` dan `uploaded_file_chunks`; tidak memerlukan Firebase Storage atau paket Blaze |
| Sertifikat HTTPS PHP | Bundle CA diperbarui; verifikasi HTTPS tetap aktif |

**1 akun admin dan 8 dokumen konten sudah disalin ke Firestore.** Data aplikasi, session, cache, dan berkas memakai Firestore tanpa fallback lokal. File SQLite tetap menjadi salinan sebelum perpindahan dan backend tes/impor lama, bukan penyimpanan aktif.

## Konfigurasi yang dipakai

1. **Service account**: akun pada JSON yang Anda unduh dari pengaturan Firebase. Laravel tidak memakai sesi login ekstensi VS Code.
2. **Akses data**: kredensial berhasil membaca dan membuat dokumen Firestore. Untuk instalasi lain, service account dapat diberi `roles/datastore.user`; hak pemilik proyek tidak diperlukan untuk menjalankan aplikasi.
3. **Kunci JSON**: tersedia di `storage/app/private/firebase-service-account.json`; file dikecualikan dari Git dan tidak disajikan ke browser.
4. **Konfigurasi**:

   ```dotenv
   SCHOOL_STORE=firestore
   FIREBASE_PROJECT_ID=sd-ceria-nusantara
   FIREBASE_DATABASE_ID=(default)
   ```

   `FIREBASE_CREDENTIALS` opsional jika memakai lokasi standar di atas. Koneksi SQL hanya dipakai oleh tes dan perintah impor data SQLite lama; aplikasi berjalan dengan `SCHOOL_STORE=firestore`.

5. **Uji koneksi dan import** sudah berhasil. Skrip berikut dapat dipakai pada instalasi lain setelah kredensial tersedia:

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File .\Connect-Firebase.ps1
   ```

   Opsi `-ExecutionPolicy Bypass` berlaku hanya pada proses PowerShell tersebut, agar skrip lokal ini dapat dijalankan tanpa mengubah kebijakan PowerShell secara permanen. Skrip memeriksa Project ID pada JSON, menguji baca database, menyalin data lokal tanpa menimpa ID yang sudah ada, mengisi konten yang belum ada, lalu mengaktifkan Firestore pada `.env`. Driver aktif baru diubah setelah pemeriksaan dan import berhasil.

6. **Rules dan indeks**: rules sudah menolak akses browser langsung. Perubahan pengecualian index payload file lolos validasi dry-run; terapkan sebelum rilis:

   ```text
   firebase deploy --only firestore:rules,firestore:indexes --project sd-ceria-nusantara
   ```

7. **Verifikasi website**: login admin, simpan perubahan konten, buat pendaftaran percobaan, dan periksa data pada Firestore. Hapus data percobaan setelah selesai.

| Bagian website | Penyimpanan aktif |
| --- | --- |
| Akun admin, hash kata sandi, peran dan status akun | Koleksi `admins` di Firestore |
| Teks, informasi sekolah, pengaturan dan lokasi gambar | Koleksi `content` di Firestore |
| Data pendaftar, status, catatan dan lokasi berkas | Koleksi `applications` di Firestore |
| Permintaan kunjungan dan statusnya | Koleksi `visits` di Firestore |
| Berkas akta, KK, foto pendaftar, dan gambar yang diunggah admin | Koleksi privat `uploaded_files` dan `uploaded_file_chunks` |
| Session login dan cache | Koleksi runtime Firestore |

Koleksi pendaftar/kunjungan muncul saat record pertamanya dibuat. Login menggunakan autentikasi Laravel dengan akun yang dibaca dari Firestore; Firebase Authentication tidak perlu diaktifkan.

## Hasil pengujian koneksi nyata

Pengujian browser terhadap data cloud sebelum migrasi penyimpanan berkas berhasil pada 2 Oktober 2026:

- Login memakai akun dan hash kata sandi yang dibaca langsung dari Firestore.
- Perubahan teks melalui admin tersimpan di Firestore dan terlihat pada halaman publik.
- Pendaftaran empat langkah menyimpan data, persetujuan, dan tiga referensi dokumen di Firestore.
- Admin dapat mengunduh dokumen privat, mengubah status pendaftar, dan mengekspor CSV.
- Permintaan kunjungan dan konfirmasi admin tersimpan di Firestore.
- Logout berhasil. Data uji dibersihkan dan konten asli dipulihkan persis.
- Salinan SQLite tidak berubah selama seluruh pengujian browser.

Pada 3 Oktober 2026, uji integrasi berkas sintetis 1 MiB ke Firestore berhasil: upload, unduh dengan isi identik, lalu hapus. Suite Laravel setelah perubahan ini: **45 tes lulus**. Unggahan lokal lama dapat disalin tanpa menghapus sumber dengan `php artisan school:migrate-local-uploads`; saat pemeriksaan ini folder unggahan lokal tidak berisi file.

Hasil akhir cloud yang diverifikasi: 1 admin, 8 dokumen konten, 0 pendaftar, dan 0 kunjungan. Laporan historis: `design-audit/browser/firebase-workflow-report.json`.

Record admin, pendaftar, dan kunjungan yang dibuat aplikasi memiliki `created_at`, yang digunakan untuk menampilkan data terbaru. Pertahankan field tersebut jika mengimpor record dari sumber lain.

## Sertifikat HTTPS pada komputer ini

Bundle CA bawaan XAMPP berasal dari 2022 dan menyebabkan kesalahan cURL 60. Bundle Mozilla terbaru dari [situs resmi curl](https://curl.se/docs/caextract.html) disimpan sebagai `storage/app/private/firebase-ca-bundle.pem`, lalu dikonfigurasi melalui `FIREBASE_CA_BUNDLE` di `.env`. Verifikasi sertifikat berlaku untuk autentikasi Google dan permintaan database; tidak dinonaktifkan.

Saat pindah hosting, sesuaikan path tersebut atau hapus variabel `FIREBASE_CA_BUNDLE` jika PHP hosting sudah memiliki trust store yang benar. Jangan menyalin path Windows ke hosting Linux. Setelah mengganti kunci Firebase atau pengaturan koneksi, jalankan `php artisan optimize:clear`.

## Jika membuat kunci sendiri

Buka [Service accounts proyek](https://console.firebase.google.com/project/sd-ceria-nusantara/settings/serviceaccounts/adminsdk), lalu buat/unduh kunci service account dan simpan di lokasi privat di atas. Jangan kirim isi kunci lewat chat. Panduan lengkap dan izin server dijelaskan pada [dokumentasi Firebase](https://firebase.google.com/docs/admin/setup) dan [panduan proyek ini](FIREBASE.md).

Login ekstensi/CLI dipakai untuk mengelola proyek dari komputer. Proses PHP Laravel menggunakan kredensial server pada file JSON yang dikonfigurasi.
