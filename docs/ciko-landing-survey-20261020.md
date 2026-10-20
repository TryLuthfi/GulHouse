# CIKO — Landing Survey Kamar, 20 Oktober 2026

Status: **PASS untuk cakupan perubahan landing yang diuji**. Tidak ada kritis/mayor terbuka dalam cakupan ini. Browser CIKO ditutup melalui finally. Semua perubahan fixture stok dikembalikan dan hash status cocok dengan snapshot awal.

## Lingkungan dan keselamatan

Actual Chrome headless/Playwright pada runtime lokal terisolasi52873 dengan schema guard gulhouse_test_mobile_bookingretain26_ dan contains_real_occupants=false. Implementasi dibaca saja oleh CIKO. Tidak ada perubahan database aplikasi asli/produksi atau deployment oleh CIKO.

Dengan izin root, helper memilih satu kelompok properti+tipe pada schema sintetis, menyimpan snapshot ID/status, mengubah status kelompok menjadi maintenance untuk menghasilkan stok0, menjaga tipe lain tetap tersedia, lalu restorefinally. Kedua run mencatat restore hash-identical. Tidak ada perubahan harga, identitas penghuni atau ledger pembayaran.

## Hasil aktual independen

- tests/mobile-landing-request.cjs: **17/17 PASS**, exit0.
- tests/mobile-landing-request-focused.cjs: **5/5 PASS**, exit0.
- Total22 pemeriksaan termasuk dua pemeriksaan pemulihan fixture; bukan22 alur bisnis berbeda.

Pada390/768/1280: header Survey Kamar, room-browser dihapus, label formulir terlihat dengan warna teks gelap, tanggal opsional bertipe date dan min tanggal server, dropdown hanya tipe tersedia, kota di bawah nama tipe, tidak ada overflow halaman. Filter properti dan tipe beririsan serta reset mengembalikan kartu.

Pengisian nomor melalui ketikan dan event paste membuang karakter selain digit serta mempertahankan0 awal. Nomor hasil paste08123456789 tetap sama. Ini pengujian event paste browser, bukan clipboard perangkat fisik.

Fixture stok0 tidak mempunyai pembuka booking pada kartunya dan tidak masuk dropdown. Halaman detail stok0 tidak mempunyai data-booking-open maupun tautan #booking, termasuk area header/nav/sticky. Halaman masih dapat dibuka untuk melihat informasi.

Focused actual button clicks: status Tersedia menyembunyikan stok0; kombinasi properti+tipe tanpa hasil menampilkan empty state; reset memulihkan kartu; panah carousel mengubah scrollLeft dan dots tersedia. Fresh page setelah final JS memegang min tanggal server yang sama ketika modal dibuka.

POST tanggal lampau yang sengaja melewati native browser validation ditolak server, mengembalikan tanggal lama, membuka modal dan memfokuskan move_in_plan. Tidak ada pageerror pada run utama.

Screenshot booking-390.png, catalog-768.png dan catalog-cards-390.png dibuka dan diperiksa visual: label serta helper terbaca, Survey Kamar terlihat, filter tersusun, kartu/kota ditampilkan. Screenshot tambahan semua viewport dan detail stok0 tersimpan privat.

## Bukti

- .e2e/mobile/bookingretain26/ciko-landing-request-results.json
- .e2e/mobile/bookingretain26/ciko-landing-request-focused-results.json
- .e2e/mobile/bookingretain26/ciko-landing-ui-stock-restored.json
- .e2e/mobile/bookingretain26/ciko-landing-request/
- tests/mobile-landing-request-fixture.php

Implementasi yang diaudit: landing_home.php, room_detail.php, landing.js, landing.css, Landing_model.php. CIKO tidak mengubah file implementasi tersebut.

## Batas

Tidak mengulang seluruh lifecycle/retention, tidak menguji perangkat native atau semua browser, dan tidak mengklaim audit aksesibilitas lengkap. Validasi semua varian calendar/leap-year/phone server dilaporkan WASP34/34 secara terpisah; bukan rerun CIKO. Alur tanggal opsional kosong diperiksa sebagai kontrak input optional browser; run ini melakukan POST negatif tanggal lampau, tidak membuat lead baru dengan tanggal kosong. Backend ingress HTTP13 dan kota GH1/GH2 Jakarta Barat serta GH3 Balikpapan diverifikasi root/WASP secara terpisah, bukan diasumsikan dari label fixture CIKO.
