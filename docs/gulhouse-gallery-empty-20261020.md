# Galeri kamar tanpa foto — 20 Oktober 2026

Permintaan el: foto detail harus berasal dari properti dan tipe kamar yang sesuai. Jika belum ada foto, tampilkan “Foto belum tersedia”.

Penyebab: `Landing_model::get_room_gallery()` memanggil preview umum ketika galeri sendiri kosong. Preview itu mencampur unggahan berbagai properti dan dapat memakai foto stok. Selain itu, kartu beranda dan rekomendasi memakai kelas CSS `photo-N` yang memuat foto stok ketika cover kosong.

Perubahan lokal:

- `landing/application/models/Landing_model.php`: detail mengembalikan hanya galeri unggahan dengan folder properti/tipe yang sesuai, atau array kosong.
- `landing/application/views/room_detail.php`: galeri kosong menjadi pesan noninteraktif; tidak ada gambar, tombol buka foto, atau markup modal. Galeri yang memiliki foto tetap memakai navigasi lama. Kartu rekomendasi tanpa cover juga menampilkan placeholder.
- `landing/application/views/landing_home.php`: kartu tanpa cover menampilkan placeholder dan tidak memakai kelas foto stok.
- `landing/assets/landing/css/landing.css`: gaya placeholder netral mengikuti ukuran layar.
- Kedua view memakai versi cache CSS `20261020-gallery-empty` agar browser mengambil gaya terbaru.

Backup sebelum perubahan: `.e2e/gallery-empty-20261020/before`, dengan manifest hash `before-hashes.json`. Perubahan lama pada berkas yang sudah dirty dipertahankan. Tidak ada perubahan database, unggahan foto, API mobile, atau deployment server.

Verifikasi ZEYN:

- Baseline tes gagal karena GH 3 Standart menerima foto pinjaman; sesudah perbaikan `tests/verify-landing-gallery-isolation.php` lulus 45 pemeriksaan tanpa database/tulis data.
- Regresi `tests/verify-mobile-gallery-scope.php`: 77 pemeriksaan lulus.
- Lint PHP model dan dua view lulus.
- HTTP lokal GH 3 Standart: status 200, pesan kosong ada, data galeri `[]`, tidak ada kontrol pembuka galeri, tidak ada PHP warning.
- HTTP lokal GH 2 VIP: status 200, 18 foto seluruhnya dalam scope `/media/gallery/GH2/VIP/`, kontrol galeri tetap ada, tidak ada PHP warning.

QC browser independen CIKO: **PASS 19/19** pada Apache lokal, viewport 390/768/1280. GH 3 kosong tanpa img/opener/modal, GH 2 VIP tetap 18 foto scope benar dan gambar berhasil dimuat, lightbox next/prev/close berfungsi; cover beranda dan kartu rekomendasi tidak mengambil foto stok. Tidak ada JavaScript error atau kegagalan jaringan lokal. Bukti screenshot dan `results.json` berada pada subdirektori QC di `.e2e/gallery-empty-20261020/`.

Keputusan ZEYN: QC PASS untuk isolasi galeri dan state foto kosong yang diminta. Tidak ada masalah kritis/mayor terbuka pada cakupan ini. Verifikasi lokal tidak menyatakan server online telah diperbarui.
