# GulHouse Living — hasil redesign aplikasi Android

19 Oktober 2026. **QC PASS pada cakupan redesign yang diuji; tidak ada temuan kritis/mayor terbuka.** APK terbaru sudah terpasang pada tablet el. Aplikasi ditinggalkan di katalog server produksi dalam keadaan logout. Rute dan layanan QA sementara sudah ditutup.

## Hasil implementasi

BUBU menggunakan referensi el dan riset internet untuk mengubah komposisi aplikasi: ivory/putih, olive, aksen clay, font Manrope asli yang dibundel lokal, foto kamar aktual, koleksi horizontal, grid adaptif, galeri thumbnail, CTA survey, dan navigasi bawah yang lebih ringan. Foto yang belum tersedia tetap ditandai secara jujur.

Menu pengelola dikelompokkan, angka dashboard diprioritaskan, dan portal penghuni memperoleh ringkasan masa tinggal/deposit serta indikator perjalanan. Form, input, pilihan data, upload, sheet, kartu, status, error/empty/loading memakai desain bersama. Riset dan sumber yang ditinjau: [mobile/DESIGN.md](D:/XAMPP/htdocs/GulHouse/mobile/DESIGN.md).

BUBU memiliki implementasi UI; CIKO memeriksa secara independen dengan Playwright serta review bukti tablet; WASP mengaudit regresi sumber/perilaku; ZEYN menjalankan build, ADB, uji perangkat, isolasi dan penutupan QA. Semuanya merupakan subagent sesi nyata, bukan bot Telegram.

## Perbaikan temuan

| Temuan | Dampak | Perbaikan dan hasil |
|---|---|---|
| RD01: nominal dashboard terpotong di ponsel | Nilai keuangan bisa salah terbaca | Kartu uang diberi ruang penuh pada layar kompak, ellipsis dihapus; angka besar diuji320/390/768. CLOSED. |
| RD02: label admin/pembayaran memakai descriptor Inggris | Label kurang konsisten | Terjemahan presentasi terpusat; key, endpoint, nilai enum/FK tidak berubah. CLOSED. |
| RD03: kartu admin terlalu tinggi pada Android, grid menyisakan kolom kosong | Menu sulit ditemukan dan layout tablet buruk | Hilangkan persentase tinggi pada kontainer tak terbatas; ukur lebar kontainer nyata dengan onLayout. Build pengganti diuji langsung: tile normal,3 kolom tablet terisi. CLOSED. |
| Pemulihan foto dan nama kontrol | Foto gagal tetap fallback setelah URI berubah; ikon mengganggu nama aksesibel | Reset error saat URI berubah, sembunyikan ikon dekoratif dan beri nama eksplisit. CLOSED. |

RD03 ditemukan di tablet setelah browser lulus. APK dibangun ulang, dipasang ulang, dan hasil Android diperiksa ulang sebelum QC ditutup.

## Verifikasi

- CIKO Playwright:41 pemeriksaan utama,8 fokus perbaikan nominal/copy,6 aksesibilitas dasar,6 pendaftaran/unggahan KTP sintetis,16 semantik; retest grid sesudah RD03:9 pemeriksaan tambahan PASS. KTP wajib, buku nikah opsional, pilih/hapus file dan penyimpanan aktual diuji. Cakupan25 modul berupa membuka data, filter dan editor; bukan semua kombinasi CRUD tersimpan.
- WASP:57 pemeriksaan perilaku/presentasi pada batch desain;12 pemeriksaan delta grid terakhir. Sembilan berkas sensitif identik dengan baseline,62 pemanggilan bisnis tetap sama. Byte adapter upload, sesi, file privat, request key dan native Back tetap terjaga.
- Android nyata: Samsung SM-X406B/Android16. Receipt berisi33 checkpoint lintas dua build:9 pada build sebelum perbaikan grid dan24 pada APK final23b. Pemisahan ini tersimpan dalam `native-final-summary.json`; tidak diklaim semua33 berasal dari APK final.
- Bukti final Android: login admin, hub/dashboard, filter pembayaran dan Back, empat status penghuni, validasi biodata/pilihan data, gating sebelum pembayaran, riwayat kerusakan AC/tiket, detail refund/H+2, pencarian kosong/reset, galeri foto2/8, form survey, portrait/landscape, serta root Back/background/resume. Pengiriman keluhan DEMO lewat UI terbukti tersimpan tepat satu kali melalui readback API.
- Form scan-first, pembukaan kamera/pemilih dokumen dan pembatalan tanpa membuat pemeriksaan diuji pada build sebelum delta grid; komponen/alur tersebut tidak berubah dalam delta grid. Finalisasi OCR/manual19 fasilitas tidak diulang penuh pada putaran desain ini.
- Tanda tangan APK valid dengan sertifikat rilis existing. APK dibaca kembali dari tablet dan hash tepat sama dengan rilis. Fingerprint source akhir juga tepat sama dengan receipt build. Tidak ditemukan penanda fatal pada log proses aplikasi yang diamati; ini bukan klaim seluruh histori crash.

Audit rinci: [CIKO](D:/XAMPP/htdocs/GulHouse/docs/ciko-mobile-redesign-20261019.md) dan [WASP](D:/XAMPP/htdocs/GulHouse/docs/wasp-mobile-redesign-20261019.md).

## APK dan bukti

- APK: [GulHouse-1.0.0.apk](D:/XAMPP/htdocs/GulHouse/releases/android/GulHouse-1.0.0.apk),35.492.415 bytes; Android arm64-v8a/armeabi-v7a.
- SHA256: `23b0920081cc6e2b2c769250a534c0945a49c1508ce8c081df7df7b46d39e1e3`.
- Source SHA256: `44f1906c1c25d6e3c986a257521f515016ca56f8812e2253a516047a33b08a3a`.
- Receipt: `releases/android/build-receipt.json`; bukti perangkat/penutupan ada di `.e2e/mobile/redesign26/{native-final-summary,tablet-install-final,final-source-verification,tablet-runtime-health,cleanup-health}.json`.
- Gambar tablet: `tablet-admin-hub-native-fix.png`, `tablet-dashboard-final.png`, `tablet-room-gallery-final.png`, `tablet-catalog-grid-landscape-final.png`, `tablet-production-resumed-final.png` dalam folder bukti yang sama.

## Berkas implementasi berubah

`mobile/App.tsx`; `mobile/src/components/{UI,NativeForm}.tsx`; `mobile/src/components/useGridWidth.ts`; `mobile/src/screens/{Catalog,Admin,Dashboard,Resident,Lifecycle,Payments}.tsx`; `mobile/src/theme/{index,copy}.ts`; `mobile/assets/fonts/` beserta OFL; `mobile/package.json`; `mobile/package-lock.json`; `mobile/DESIGN.md`.

## Backup, isolasi dan penutupan

Snapshot source pra-redesign: `.e2e/mobile/redesign-20261019/mobile-before-redesign.tar.gz`, SHA256 `5f1ee534a83cae144551bfc55cbe068c29650ef35cd10c231c3f593fc3b20e5e`; APK sebelumnya disimpan sebagai `GulHouse-before-redesign.apk`. Tidak ada reset/commit/push Git dalam pekerjaan ini.

Semua penulisan uji menuju schema `gulhouse_test_online_20261019_122114` dengan storage terpisah dan penghuni DEMO. Tidak ada transaksi penghuni asli dibuat. Backend business API produksi tidak diubah oleh redesign.

Aplikasi tablet sudah kembali ke `https://gulhouse.co.id/master/index.php/api/mobile` dan logout. Rute HTTPS QA404, layanan QA dihentikan, endpoint katalog produksi200/ok; loopback preview dan PHP lokal milik sesi dihentikan. Pengaturan sementara keep-awake dikembalikan ke nilai awal dan orientasi bebas dipulihkan. Kredensial/kode akses tidak dilampirkan.

## Batas pemeriksaan

Satu tablet Android fisik, dibantu preview ukuran ponsel/tablet. iOS belum dibuild/diuji; TalkBack dan seluruh kombinasi peran/CRUD bukan cakupan yang terbukti. Bukti browser dipisahkan dari Android. Status PASS berlaku untuk keluarga layar dan alur redesign yang disebut di atas; tidak menyatakan setiap kemungkinan alur bisnis telah diuji ulang penuh.

## Catatan komunikasi nyata — ringkasan

[CIKO -> ZEYN | Kritik]
Nominal ponsel terpotong; kemudian kartu admin Android terlalu tinggi meski browser lulus. Dampak dan reproduksi dilampirkan dalam laporan CIKO.

[BUBU -> ZEYN | Revisi]
Kartu uang diperlebar, label dirapikan, tinggi persentase dihapus dan grid memakai lebar kontainer nyata. Source dibekukan ulang untuk build pengganti.

[CIKO -> ZEYN | Jawaban]
Retest browser dan screenshot/interaksi tablet menutup RD01/RD02/RD03; tidak ada kritis/mayor terbuka pada cakupan yang diperiksa.

[WASP -> ZEYN | Jawaban]
Audit perilaku dan delta grid lulus; API, nilai payload, sesi, file privat dan pengaman transaksi tetap terjaga.

[ZEYN -> CIKO | Keputusan]
APK final sudah dipasang, hash/source cocok, uji perangkat selesai dan QA ditutup. Aplikasi dikembalikan ke produksi/logout; batas pengujian tetap dilaporkan.
