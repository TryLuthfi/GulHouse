# Form survei dan katalog landing — 20 Oktober 2026

Permintaan el: nomor hanya angka, pilihan kamar hanya yang memiliki stok, judul tiap input, estimasi mulai tinggal melalui date picker; CTA header menjadi Survey Kamar; kota di bawah tipe kamar; menghapus room-browser; filter jenis kamar pada carousel; tombol booking tidak tersedia untuk tipe penuh, termasuk halaman detail.

Implementasi lokal dikerjakan WASP pada model dan BUBU pada dua view landing serta CSS/JS. CIKO memeriksa secara independen. Berkas utama: `landing/application/models/Landing_model.php`, `landing/application/views/landing_home.php`, `landing/application/views/room_detail.php`, `landing/assets/landing/css/landing.css`, dan `landing/assets/landing/js/landing.js`. Cadangan berada di `.e2e/landing-request-20261020/`.

Nomor WhatsApp tetap berupa teks numerik agar nol di awal terpelihara, dengan validasi server9–15 digit. Estimasi tanggal tetap opsional sebagaimana kolom sebelumnya; jika diisi harus tanggal kalender YYYY-MM-DD yang valid, hari ini atau sesudahnya menurut Asia/Jakarta. Pemeriksaan stok di server tetap berlaku untuk menghindari pengiriman paksa pilihan yang penuh.

Sumber kota menggunakan kolom kota bila tersedia, lalu kota/kabupaten eksplisit dalam alamat. GH1/GH2 memakai Jakarta Barat dari alamat peta existing ketika alamat master kosong; GH3 memakai Balikpapan dari alamat master. APT belum memiliki alamat/kota, sehingga menampilkan Lokasi belum diatur. Tidak ada kota yang ditebak dari nama apartemen.

Tidak ada migrasi atau perubahan data operasional, push, maupun deployment online. Pengujian dilakukan dengan fixture milik run pada database sintetis terpisah.

WASP:34/34 pemeriksaan model/controller dan13/13 ingress HTTP lulus; lint PHP lulus. Dua pemeriksaan kegagalan database memakai fault injection, bukan gangguan database nyata. ZEYN memeriksa GET Apache lokal200, aset versi baru, kedua kota yang terverifikasi, hilangnya room-browser dan date picker; detail GH2VIP penuh memiliki nol tautan ke booking. CIKO menguji perilaku UI secara independen pada390/768/1280; hasil akhir tercatat dalam laporan QC terpisah.

CIKO:22/22 pemeriksaan scoped lulus, termasuk17 inti dan5 lanjutan (dua pemeriksaan pemulihan fixture). Filter status/properti/jenis kamar, kombinasi kosong/reset, carousel serta dots diuji melalui klik nyata; nomor ketik/paste dan nol awal, label, tanggal opsional, penolakan tanggal lampau, serta pemblokiran booking stok nol diverifikasi. Fixture stok kembali ke hash awal, browser ditutup, dan tidak ada page error atau masalah kritis/mayor. Laporan: `docs/ciko-landing-survey-20261020.md`. ZEYN membuka tangkapan layar formulir dan kartu ponsel untuk pemeriksaan visual tambahan. Status QC PASS pada cakupan ini. Tanggal kosong tidak dikirim menjadi lead melalui browser; penerimaannya diverifikasi pada pengujian model. Setelah QC, lima hash sumber tetap cocok dan hanya server PHP uji milik run dihentikan; Apache lokal tetap aktif.
