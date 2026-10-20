# CIKO — QC booking ingress, 20 Oktober 2026

Status: **PASS untuk cakupan perbaikan ingress dan feedback yang diuji**. Tidak ada masalah kritis/mayor terbuka dalam cakupan ini. Browser milik CIKO sudah ditutup melalui finally; tidak ada server CIKO baru yang dijalankan.

## Lingkungan dan batas keselamatan

Pengujian menggunakan runtime lokal terisolasi port52873, schema berawalan gulhouse_test_mobile_bookingretain26_, dengan guard contains_real_occupants=false. Semua POST bisnis dan satu perubahan status arsip fixture terbatas pada identitas sintetis unik milik CIKO. Tidak ada POST atau perubahan terhadap database aplikasi asli, booking asli19/20, maupun produksi.

Alasan penolakan POST asli sebelumnya **tidak dapat ditentukan dari bukti yang tersedia**. Hasil ini membuktikan alur setelah perbaikan; bukan diagnosis retrospektif terhadap pilihan kamar atau isi formulir asli el.

## Bukti aktual independen

`node tests/mobile-booking-ingress.cjs` selesai exit0: **8 PASS, 0 FAIL**, tanpa pageerror.

| Pemeriksaan | Hasil |
|---|---|
| Nomor mengandung huruf pada390px | POST303; error jelas; modal terbuka kembali; lima nilai lama tetap; fokus ke phone; tidak ada insert |
| Kasus sama pada1280px | Perilaku sama, tidak ada overflow halaman |
| Nomor valid dengan spasi dan tanda hubung | Form terlihat dikirim; sukses; modal tidak terbuka otomatis; tepat satu lead aktif tersimpan |
| Kirim ulang lead aktif dalam lima menit | Tetap satu baris |
| Arsipkan hanya fixture sendiri lalu kirim ulang | Dua baris berbeda: satu arsip dan satu aktif |
| Pilihan kamar tidak dikenal/tidak tersedia yang dipaksakan melalui POST | Error pilihan kamar, nilai lama dipertahankan, fokus room_interest, tanpa insert |
| Login Master sebenarnya dan daftar Booking aktif | Lead aktif baru terlihat tepat satu baris dalam hasil pencarian |
| Pageerror dan log runtime yang tersedia | Nol pageerror; nama/nomor sintetis pengujian tidak ditemukan dalam dua file log runtime yang diperiksa |

Screenshot phone-error-390.png dan master-active-1280.png sudah dibuka dan diperiksa secara visual: pesan kesalahan dapat dibaca, nomor salah mendapat fokus, dan lead valid tampil di Master. Bukti tambahan phone-error-1280.png serta unavailable-error-390.png tersimpan privat bersama hasil JSON.

Artefak:

- tests/mobile-booking-ingress.cjs
- tests/mobile-booking-ingress-fixture.php
- .e2e/mobile/bookingretain26/ciko-ingress-results.json
- .e2e/mobile/bookingretain26/ciko-ingress-db-readback.json
- .e2e/mobile/bookingretain26/ciko-ingress/

Identitas fixture dan kredensial tetap dalam file privat dan tidak disalin ke laporan ini.

## Koreksi harness dan cakupan yang tidak diklaim

Percobaan awal berhenti sebelum POST karena selector mengambil tautan navigasi tersembunyi pada mobile; diperbaiki ke tombol visible. Percobaan berikutnya mendapati fixture tidak mempunyai tipe kamar penuh; tidak dibuat perubahan inventaris tambahan. Pembukaan formulir juga diperbaiki agar tidak mengklik tautan di belakang modal yang sudah terbuka otomatis. Ini koreksi harness, bukan defect aplikasi.

Fixture browser ini tidak mempunyai kamar dengan available_count=0. Karena itu penolakan kamar penuh secara spesifik bukan bukti browser CIKO; browser menguji pilihan tidak dikenal/tidak tersedia. WASP melaporkan18/18 tes validasi model, termasuk kamar penuh, dan13 tes ingress sebelumnya; angka tersebut bukan pengulangan independen CIKO.

Tidak menguji outage DB nyata, semua kemungkinan bentuk PII/log di luar runtime yang diperiksa, semua kombinasi browser, atau perubahan bisnis lain di luar ingress. Pengarsipan fixture untuk menguji dedupe dilakukan oleh helper terjaga, bukan pengujian baru tombol arsip. Tidak ada tes tambahan diperlukan untuk menutup scope utama ini.
