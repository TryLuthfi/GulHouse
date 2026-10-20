# Mengatur foto utama kamar

Di **Master → Foto → Galeri Gedung & Tipe Kamar**, pilih gedung dan tipe kamar, lalu tekan **Jadikan foto utama** pada foto yang diinginkan. Foto terpilih ditandai **Foto utama**. Pengaturan berlaku untuk kartu kamar pada landing dan foto pertama galeri detail, termasuk katalog API yang menggunakan model publik yang sama.

Pilihan disimpan per gedung dan tipe dalam `uploads/{gedung}/{tipe}/.gallery-cover.json`. Foto tidak diganti nama, disalin antar gedung, atau diedit. Bila foto yang dipilih sudah tidak tersedia atau metadata rusak, sistem memakai pemilihan bawaan di galeri yang sama; galeri kosong tetap menampilkan foto belum tersedia.

Aksi memakai POST, CSRF, pemeriksaan role existing, pasangan gedung/tipe dalam katalog, serta pemeriksaan nama file dan batas direktori. Tidak ada migrasi database. Viewer dapat melihat foto tetapi tidak mengubah pilihan.

Pada pekerjaan ini, sampul lokal **GH 2 / VIP** dipilih menjadi `kamar_06.jpeg` (foto frontal tempat tidur). Semua 18 foto VIP tetap ada. Pada checkpoint sebelum impor ulang foto terbaru, pemeriksaan SHA256 membuktikan 90 file gambar pada upload lokal tetap utuh. Impor ulang berikutnya didokumentasikan terpisah di `.e2e/photo-review-20261020-r2/`. Backup pilihan sebelumnya dan bukti berada di `.e2e/photo-cover-20261020/`.

Perubahan belum dipush atau diterapkan ke server online. Foto dan metadata pada `uploads/` merupakan data runtime yang diabaikan Git; deployment kode saja tidak memindahkan pilihan sampul atau file foto.

