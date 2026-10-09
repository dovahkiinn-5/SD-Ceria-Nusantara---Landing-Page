# Sumber desain dan implementasi

Empat PDF dipakai sebagai desain utama:

| Berkas | Penggunaan |
| --- | --- |
| `sd-ceria-nusantara-landing-desktop-v3.pdf` | Beranda desktop 1440 px, urutan bagian, warna, foto, dan dekorasi hero |
| `contoh Full Pages.pdf` | Halaman lengkap desktop, informasi sekolah, formulir, dan konfirmasi |
| `Full Pages.pdf` | Susunan tablet 768 px, navigasi, kartu, konten tablet, dan formulir |
| `Interactive Viewports.pdf` | Tampilan awal halaman dan keadaan interaksi/formulir |

Foto sekolah, logo, mosaik fasilitas, dan foto guru diekstrak dari PDF ke `public/assets/design`. Dekorasi hero beranda diekstrak sebagai SVG. Font Poppins disimpan lokal beserta lisensinya.

Implementasi memakai Blade, `public/css/site.css`, dan `public/js/site.js`. Layout desktop berlaku di atas 1000 px; tablet sampai 1000 px; penyesuaian ponsel sampai 600 px. Konten tablet mengikuti variasi yang terdapat pada PDF, termasuk susunan kartu dan bagian beranda yang berbeda. Ponsel menyesuaikan susunan tablet agar teks dan formulir tetap terbaca.

Halaman publik memakai reveal ringan saat elemen masuk viewport, efek hover untuk kartu dan tombol pada perangkat yang mendukung hover, serta gerak morph dekorasi hero beranda yang lambat. Hero beranda memiliki slideshow otomatis gambar kawasan sekolah yang diberikan pengguna serta foto kelas dan kegiatan; slideshow berputar setiap 5,5 detik dan berhenti saat tab browser tidak aktif. Autoplay dan gerak dekoratif nonaktif pada pengaturan reduced motion. Fasilitas memakai enam tile dari kolase yang tersedia; masing-masing dapat diperbesar dan diberi efek zoom. Tombol WhatsApp mengambang berada di pojok bawah: pada desktop ia muncul saat pointer mendekati pojok tersebut dan melebar saat di-hover; pada perangkat sentuh ia tetap terlihat. Animasi scroll hanya aktif jika JavaScript dan `IntersectionObserver` tersedia; tanpa dukungan tersebut konten tetap terlihat. Semua gerak dekoratif dan transisi dihormati/nonaktif pada pengaturan `prefers-reduced-motion`.

Panel admin memakai warna dan tipografi sekolah. Desain admin tidak tersedia dalam PDF. Data awal seperti nama, alamat, statistik, biaya, dan periode mengikuti desain; pengelola dapat menyesuaikannya melalui admin sebelum dipakai sekolah.

## Pemeriksaan

- Beranda diperiksa pada lebar 1440, 768, dan 390 px.
- Halaman publik, formulir, dan login diperiksa melalui Edge headless.
- Pendaftaran empat langkah diuji dengan upload PDF/PNG, persetujuan, dan nomor pendaftaran.
- Login admin, dokumen privat, status, ekspor CSV, konten, kunjungan, dan logout diuji melalui browser.
- Test Laravel memeriksa validasi, hak akses, penyimpanan, pagination, dan permintaan Firestore menggunakan respons HTTP simulasi.

Screenshot dan laporan lokal tersedia di `design-audit/browser`, yang dikecualikan dari Git. Integrasi terhadap Firestore `sd-ceria-nusantara` sudah diuji langsung melalui browser untuk login admin, perubahan konten, pendaftaran, kunjungan, dan perubahan status. Hasil dan konfigurasi tersedia pada [status koneksi Firebase](FIREBASE-CONNECTION.md).
