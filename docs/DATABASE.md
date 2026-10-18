# Dokumentasi database per node

Dokumen ini menjelaskan struktur berdasarkan kode aplikasi. Nilai konten awal lengkap, termasuk setiap item daftar, ada di [DATABASE-CONTENT.md](DATABASE-CONTENT.md). Ini bukan audit isi database aktif; data pendaftar, akun, dan runtime di bawah dijelaskan sebagai skema tanpa menyalin data pribadi.

## Peta node

Semua koleksi berikut berada langsung di root Cloud Firestore; objek bersarang tidak menjadi subkoleksi.

| Koleksi / ID dokumen | Isi dan sumber |
| --- | --- |
| `content/settings` | Identitas sekolah, kontak, statistik, informasi penerimaan, gambar, privasi, syarat |
| `content/home` | Hero, profil, nilai, keunggulan, program, kepala sekolah, testimoni, FAQ, variasi tablet |
| `content/about` | Hero, cerita sekolah, visi, misi, nilai, kutipan |
| `content/program` | Hero, pendekatan, kurikulum, aktivitas |
| `content/facilities` | Hero, daftar ruangan dan fitur fasilitas |
| `content/teachers` | Hero, nilai pendampingan, dan profil guru/staf di field `directory` |
| `content/gallery` | Hero dan daftar berita |
| `content/contact` | Hero dan jam kunjungan |
| `admins/{sha256(email)}` | Akun pengelola |
| `applications/{reference}` | Pendaftaran anak beserta orang tua dan referensi berkas |
| `visits/{ULID}` | Permintaan kunjungan |
| `uploaded_files/{sha256(path)}` | Metadata unggahan |
| `uploaded_file_chunks/{sha256(path + ':' + index)}` | Potongan isi unggahan |
| `runtime_sessions/{sha256(namespace + ':' + sessionId)}` | Session Laravel |
| `runtime_cache/{sha256(prefix + NUL + key)}` | Cache dan pembatasan permintaan |
| `runtime_locks/{sha256(prefix + NUL + name)}` | Lock cache |

## Konten website

Field dan nilai lengkap setiap dokumen `content` tersedia di [snapshot konten](DATABASE-CONTENT.md). Sumbernya [site.php](../resources/content/site.php). String, array, dan objek dipertahankan sesuai sumber; baris baru ditulis sebagai `\n` dalam JSON.

`SchoolContent::all()` membaca default lalu menggabungkan dokumen tersimpan dengan `array_replace_recursive`. Field `id` hasil pembacaan dibuang sebelum penggabungan. Cache hasil bertahan 300 detik dan dibersihkan ketika admin menyimpan konten. `school:seed-content` membuat dokumen yang belum ada tanpa menimpa ID yang sudah ada.

Profil guru/staf aktif dibaca dari array `directory` pada dokumen `content/teachers`. Profil kepala sekolah berada di `directory[0]`, dengan field `name`, `role`, `description`, dan `photo`. Admin, beranda, direktori, struktur sekolah, dan halaman tentang memakai profil yang sama. Dokumen ini dibaca ulang pada setiap permintaan halaman, sehingga perubahan langsung melalui konsol Firebase terlihat setelah halaman dimuat ulang. Field `team` dipertahankan untuk kompatibilitas; ubah profil melalui `directory` atau editor admin.

[staff-directory.php](../resources/content/staff-directory.php) menyediakan nilai awal serta susunan struktur sekolah. Gambar bawaan `/assets/...` adalah aset publik proyek; path unggahan merujuk penyimpanan berkas di bawah.

## admins

| Field | Tipe / isi |
| --- | --- |
| `name`, `email` | String nama dan email pengelola; email dinormalisasi oleh alur pembuatan akun |
| `password` | String hash kata sandi Laravel |
| `role` | String `owner` atau `editor` |
| `active` | Boolean status akun |
| `created_at` | String tanggal/waktu ISO 8601 |

Sumber: [AdminController](../app/Http/Controllers/AdminController.php), [perintah admin](../routes/console.php). ID merupakan hash email yang dipakai saat akun dibuat, bukan password.

## applications

| Field / node bersarang | Tipe / isi |
| --- | --- |
| `reference` | String `CN-{tahun}-{10 karakter akhir ULID}`, juga ID dokumen |
| `submission_hash` | String SHA-256 token pengiriman untuk memeriksa pengiriman ulang |
| `child.child_name`, `child.nickname` | String wajib, maksimal 120 / 60 karakter |
| `child.birth_date` | String tanggal lahir |
| `child.gender` | String `Laki-laki` / `Perempuan` |
| `child.previous_school`, `child.support_needs` | String opsional/null, maksimal 180 / 1000 karakter |
| `parent.parent_name` | String wajib, maksimal 120 karakter |
| `parent.relationship` | String `Ayah`, `Ibu`, atau `Wali` |
| `parent.phone`, `parent.email` | String kontak wajib |
| `parent.address`, `parent.emergency_contact` | String opsional/null |
| `parent.domicile_latitude`, `parent.domicile_longitude` | Angka koordinat opsional; disimpan berpasangan |
| `parent.domicile_distance_km` | Angka jarak garis lurus hasil perhitungan, dibulatkan 2 desimal; hanya jika koordinat tersedia |
| `documents.birth_certificate` | Objek referensi akta |
| `documents.family_card` | Objek referensi KK |
| `documents.child_identity_card` | Objek referensi KIA |
| `documents.photo` | Objek referensi pas foto |
| `documents.{jenis}.path` | String `applications/{reference}/{UUID}.{ekstensi}` |
| `documents.{jenis}.name`, `.size` | String nama asli berkas dan integer ukuran byte |
| `status` | `baru`, `ditinjau`, `diterima`, `ditolak` |
| `notes` | String kosong saat pengiriman awal |
| `consent_at`, `created_at` | String tanggal/waktu ISO 8601 |
| `admin_notes` | String/null catatan admin, ditambahkan saat pembaruan; berbeda dari `notes` |
| `updated_at`, `updated_by` | String waktu ISO 8601 dan ID admin, ditambahkan saat pembaruan |

Draf sebelum pengiriman berada dalam session `registration` dengan `reference`, `token`, `completed`, `child`, `parent`, dan `documents` sesuai langkah yang selesai. Dokumen `applications` dibuat setelah persetujuan dan pemeriksaan keempat unggahan. Sumber: [RegistrationController](../app/Http/Controllers/RegistrationController.php).

## visits

| Field | Tipe / isi |
| --- | --- |
| `name`, `phone`, `email` | String kontak wajib |
| `date` | String tanggal kunjungan pada hari kerja |
| `notes` | String opsional/null dari pengunjung, maksimal 1000 karakter |
| `consent` | Nilai persetujuan yang lolos validasi `accepted`; bukan otomatis timestamp |
| `created_at` | String waktu ISO 8601 |
| `status` | `baru`, `dikonfirmasi`, `selesai`, `dibatalkan` |
| `admin_notes`, `updated_at`, `updated_by` | Catatan string/null, waktu ISO 8601, ID admin; ditambahkan saat pembaruan |

Sumber: [VisitController](../app/Http/Controllers/VisitController.php). ID ULID dibuat server.

## Berkas unggahan

`uploaded_files` memiliki `path` (string), `content_type` (string MIME), `size` (integer byte), `chunk_count` (integer 1–2), dan `created_at` (string ISO 8601). `uploaded_file_chunks` memiliki `file_id` (string ID metadata), `index` (integer mulai 0), dan `data` (string Base64).

Isi biner dipecah per 512 KiB, maksimal 1 MiB per berkas. Relasi: `applications.documents.*.path` → hash path pada metadata → ID chunk dari hash path dan indeks. Base64 adalah encoding isi, bukan enkripsi. Sumber: [FirestoreFileStorage](../app/Services/FirestoreFileStorage.php).

## Session, cache, dan lock

| Koleksi | Field dan format |
| --- | --- |
| `runtime_sessions` | `namespace`: string SHA-256 namespace; `payload`: string Base64 data session; `expires_at`: integer Unix detik |
| `runtime_cache` | `namespace`: string SHA-256 prefix; `payload`: Base64 hasil PHP `serialize(value)`; `expires_at`: integer Unix detik, 0 berarti tanpa kedaluwarsa |
| `runtime_locks` | Format sama dengan cache; nilai payload adalah string pemilik lock |

Sumber: [FirestoreSessionHandler](../app/Services/FirestoreSessionHandler.php), [FirestoreCacheStore](../app/Services/FirestoreCacheStore.php). Ini data internal yang dibuat aplikasi, bukan konten halaman.

## Representasi dan pemeliharaan

Tanggal aplikasi disimpan sebagai string ISO 8601, bukan tipe timestamp Firestore; waktu kedaluwarsa runtime berupa integer. `FirestoreDocumentStore` mengubah objek menjadi map, daftar menjadi array, serta mempertahankan integer, float, boolean, dan null. `id` hasil pembacaan berasal dari ID dokumen dan tidak perlu menjadi field tersimpan.

SQLite hanya digunakan untuk pengujian dan impor lama. Adapter dokumen lokal menyimpan padanan koleksi/ID/payload; tabel SQL bawaan Laravel bukan node produksi Firestore.

Untuk memperbarui snapshot setelah perubahan konten awal, jalankan `php tools/document_content.php`. Perintah membaca sumber dan konfigurasi lokal tanpa mengekspor database aktif. Tinjau hasil diff sebelum membagikannya karena konfigurasi alamat/peta dapat berbeda antarinstalasi.
