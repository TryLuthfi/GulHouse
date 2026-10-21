# CIKO — fokus foto kartu landing

Audit independen lokal, 21 Oktober 2026. CIKO tidak mengubah implementasi, data bisnis, galeri asli, ataupun server produksi.

## Hasil

- `tests/ciko-photo-focus-pure.php`: 40 pemeriksaan PASS, isolated temporary gallery. Batas koordinat 0/100, pembacaan ulang, penolakan 11 jenis koordinat tidak valid tanpa perubahan metadata, nama berisi traversal, file bukan gambar, file hilang, pembaruan satu foto mempertahankan foto lain, fallback metadata korup dan di luar rentang, tidak ada temporary file tertinggal.
- `tests/ciko-photo-focus-http.cjs`: 29 pemeriksaan PASS. Landing HTTP200; lima gambar kartu aktual HTTP200 dengan MIME image; setiap kartu tanpa background gambar kedua dan tanpa atribut parallax; posisi fokus valid; satu aturan CSS room-photo 4:3 tanpa override tinggi pada mobile; POST tanpa CSRF HTTP403; GET anonim redirect login HTTP303.
- `tests/ciko-photo-focus-policy.php`: delapan pemeriksaan PASS terhadap Security_hook aktual dengan mock sesi/DB. Owner, super admin, admin, manager diizinkan; viewer, finance, maintenance ditolak HTTP403; GET admin ditolak HTTP405.

Total awal 77 pemeriksaan PASS. Tidak ditemukan defect terkonfirmasi dalam cakupan ini.

## Tambahan Preview foto

Setelah el meminta foto Preview tidak terpotong, `tests/ciko-preview-natural-http.cjs` menjalankan tambahan 24 pemeriksaan PASS: empat figure masing-masing satu gambar, semua media HTTP200/MIME image, tanpa inline crop/lapisan background/parallax, tidak ada ukuran baris 190/220px atau tinggi gambar paksa, gambar height:auto/object-fit:contain/transform:none, tanpa span foto pertama, mobile satu kolom, dan caption di luar gambar. Total kumulatif **101 pemeriksaan PASS**. Ini pemeriksaan HTTP dan CSS, bukan pengukuran piksel browser. Verifikasi visual aktual tetap milik ZEYN/BUBU.

### Revisi ukuran seragam

El kemudian meminta semua foto Preview berukuran seragam. BUBU menambahkan kanvas **4:3** yang sama untuk keempat gambar, dengan `object-fit:contain` agar isi foto tetap utuh. Retest independen `ciko-preview-natural-http.cjs` versi terbaru **28 PASS**, menggantikan 24 pemeriksaan sebelumnya (bukan tambahan kumulatif baru). Aturan gambar tunggal tanpa override per foto/first-child; 4:3 bersama; hover tanpa transform/filter; dua kolom desktop dan satu kolom mobile; caption tetap di luar foto. Total final backend/policy/publicHTTP/source **105 PASS = 40 + 29 + 8 + 28**. Dimensi piksel aktual merupakan verifikasi browser ZEYN terpisah.

## Peninjauan sumber

Gallery_focus menyimpan pilihan per berkas dalam galeri asal; helper Gallery_cover memvalidasi basename, ekstensi, gambar, realpath, dan penolakan symlink. Manage memvalidasi pasangan properti/tipe aktif, batas uploads, scalar input. Koordinat integer 0–100, metadata ditulis melalui temporary file dan rename. Landing_model mengambil fokus dari foto utama terpilih. Preview master menggunakan elemen img 4:3, bukan background yang dapat berulang; slider memperbarui object-position dan output persentase. Label range membungkus kontrol. CSRF aktif dan endpoint fokus tidak dikecualikan.

## Status dan batas

**PASS untuk backend helper, kebijakan akses terisolasi, source review dan HTTP publik dalam cakupan perubahan.** Tidak ada Critical/Major terbuka. Pemeriksaan browser visual, input slider nyata, simpan master melalui sesi login lalu reload, dan pengukuran dimensi desktop/tablet/ponsel merupakan cakupan ZEYN/BUBU terpisah; laporan ini tidak mengklaim pemeriksaan tersebut. Kebijakan role diuji dengan mock DB/sesi, bukan login akun produksi.
