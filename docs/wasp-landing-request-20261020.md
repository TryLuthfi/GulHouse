# Validasi dan lokasi kamar landing — 20 Oktober 2026

Sesuai instruksi terbaru el, nomor WhatsApp booking kini hanya menerima 9–15 digit ASCII. Awalan nol tetap disimpan sebagai teks; tanda +, spasi di dalam nomor, huruf dan tanda baca ditolak dengan alasan kolom yang jelas. Ini menggantikan penerimaan format nomor bertanda baca pada implementasi sebelumnya.

`move_in_plan` tetap opsional seperti sebelumnya. Jika diisi, nilainya wajib tepat `YYYY-MM-DD`, merupakan tanggal kalender yang valid, dan tidak lebih awal dari hari ini menurut Asia/Jakarta, terlepas dari timezone bawaan PHP. Tidak ada perubahan isi booking lama atau migrasi kolom. Alasan penolakan dan bounded flash isian dari implementasi sebelumnya dipertahankan.

Kartu kamar canonical menyediakan `city` dan `property_address`; data ini ikut tersedia pada featured/all room cards serta detail/similar yang mempertahankan field kartu. Urutan sumber kota: kolom city properti bila tersedia dan terisi; token Kota/Kabupaten eksplisit pada alamat properti; peta terverifikasi GH1/GH2 Jakarta Barat dari alamat map landing yang sudah ada. Kota APT tidak direka saat datanya belum tersedia. Alamat properti dikembalikan dari database tanpa mengubah sumber.

Verifikasi WASP:

- `tests/verify-booking-validation.php`: **34/34 PASS** pada schema sintetis terpisah, mencakup nomor ketat dan nol awal, tanggal kalender tidak valid/past/today/future/opsional, prioritas sumber kota, alamat asli, penolakan stok nol, bounded flash dan pembersihan error setelah berhasil. Dua pemeriksaan kesalahan database memakai fault injection; bukan uji database mati.
- `tests/verify-booking-ingress.php`: **13/13 PASS**, termasuk POST ke action formulir hasil render dengan CSRF sampai booking aktif terlihat melalui model Master. Deduplikasi aktif dan kiriman ulang setelah arsip tetap berjalan dengan aturan nomor/tanggal terbaru.
- PHP lint Landing_model.php dan Home.php PASS. Home tidak perlu diubah lagi untuk aturan terbaru; bounded flash yang ada mempertahankan tanggal dan nomor sebagai teks.

Perubahan backend terbaru hanya Landing_model.php beserta penyesuaian fixture tes; backup tersedia di `.e2e/landing-request-20261020/`. Tidak ada migrasi, perubahan database operasional, atau deployment. Pemeriksaan UI independen milik CIKO terpisah dari bukti backend ini.
