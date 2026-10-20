# Konsep visual formulir pendaftaran

Gambar di folder ini adalah rancangan, belum mengganti halaman pendaftaran yang berjalan. Teks, ilustrasi, dan komponen dirender dari `mockup.html` menggunakan Chrome headless. Data calon siswa pada gambar adalah contoh.

Hero menggunakan [ilustrasi vektor lokal](hero-ceria.svg): dua anak, buku, matahari, dan aksen ceria. [Ornamen latarnya](hero-pattern.svg) mengikuti lengkungan dan aksen lembut hero beranda, ditambah pesawat kertas, bintang, titik, dan coretan. Pada desktop ilustrasi berada di sisi kanan teks; pada mobile ukurannya diperkecil agar judul dan keterangan tetap mudah dibaca. Kedua SVG transparan ini dapat dipakai ulang saat rancangan disetujui untuk diterapkan ke halaman aplikasi.

| Bagian | Desktop | Mobile |
| --- | --- | --- |
| 1. Data anak | [01-data-anak.png](01-data-anak.png) | [07-mobile-data-anak.png](07-mobile-data-anak.png) |
| 2. Orang tua / wali | [02-orang-tua.png](02-orang-tua.png) | [09-mobile-orang-tua.png](09-mobile-orang-tua.png) |
| 3. Unggah berkas | [03-unggah-berkas.png](03-unggah-berkas.png) | [10-mobile-unggah-berkas.png](10-mobile-unggah-berkas.png) |
| 3. Berkas terlalu besar | [04-berkas-terlalu-besar.png](04-berkas-terlalu-besar.png) | [08-mobile-unggah-error.png](08-mobile-unggah-error.png) |
| 4. Periksa dan kirim | [05-periksa-kirim.png](05-periksa-kirim.png) | [11-mobile-periksa-kirim.png](11-mobile-periksa-kirim.png) |
| 4. Bukti transfer terlalu besar | [06-bukti-transfer-terlalu-besar.png](06-bukti-transfer-terlalu-besar.png) | [12-mobile-bukti-transfer-error.png](12-mobile-bukti-transfer-error.png) |

Batas sesuai validasi saat ini: keempat berkas pendaftaran maksimal **1 MB per file**; bukti transfer maksimal **5 MB**. Foto anak menerima JPG/PNG; tiga dokumen lain JPG/PNG/PDF; bukti transfer JPG/PNG/WEBP/PDF. Pesan kesalahan dirancang muncul di dekat berkas yang perlu diganti, dengan ringkasan di atas formulir.
