# GulHouse online — deployment API dan verifikasi Android

Tanggal: 19 Oktober 2026. Koordinator: ZEYN; review backend: WASP; implementasi navigasi/UI: BUBU; QC independen: CIKO. Pengujian Android dioperasikan ZEYN pada tablet Samsung SM-X406B milik el.

## Hasil akhir

API aktif di `https://gulhouse.co.id/master/index.php/api/mobile`. Migrasi additive selesai: delapan tabel lifecycle dan satu tabel token, total 38 tabel. Audit akhir cocok untuk 58 berkas: 53 sumber API/lifecycle, satu CSS landing, dan empat view yang hanya diganti versi URL CSS-nya. Maintenance mati; layanan produksi aktif. **QC PASS untuk cakupan utama yang diuji**, tanpa masalah critical/major terbuka.

APK akhir terpasang pada tablet Samsung Android 16, terhubung ke server produksi dan ditinggalkan pada katalog kamar dalam keadaan logout. File APK ditarik kembali dari perangkat dan checksum-nya identik dengan release:

- File: [GulHouse-1.0.0.apk](../releases/android/GulHouse-1.0.0.apk), paket `id.gulhouse.app`, ARM64 dan ARMv7.
- SHA256: `4d2ad0f92c8bad34796cb25fbc825bdfacacc75c298bfaef8a40b9cea5b70174`.
- Tanda tangan release diverifikasi aktual; sertifikat tetap sama sehingga APK dapat memperbarui pemasangan sebelumnya.
- Login produksi, Dashboard portrait/landscape, format rupiah, dan Back diuji pada APK akhir. Rotasi tablet dikembalikan ke mode bebas seperti semula.

## Alur utama pada tablet

1. Katalog dan pengisian identitas awal, KTP wajib melalui pemilih berkas Android, sesi/kode akses penghuni, reservasi dan tagihan.
2. Unggahan bukti PDF tidak otomatis melunasi. Admin mencatat satu pembayaran sewa Rp1.200.000 dan satu deposit Rp500.000, lalu memverifikasi pembayaran. Penghuni mengisi biodata lengkap dan kontak darurat.
3. Scan awal/bulanan/keluar disimpan sebagai draft, OCR membaca 19 label, lalu seluruh 19 jumlah/kondisi diperiksa manual dan konfirmasi tanda tangan dicatat. Draft tidak lengkap ditolak saat finalisasi. Check-in diaktifkan setelah prasyarat lengkap.
4. Kerusakan AC bulanan menghasilkan tiket maintenance dan dapat dilihat penghuni menurut periode pemeriksaan; kondisi awal AC tetap baik dalam riwayat sebelumnya.
5. Penghuni mengirim rencana keluar H−15 beserta rekening. Checkout menghitung tenggat H+2 kalender dari tanggal keluar aktual; status pembayaran deposit tetap belum lunas sebelum transfer dicatat.
6. Admin mencatat refund uji Rp500.000 dengan tanggal, referensi dan PDF bukti. Penghuni melihat “Refund selesai” dan riwayatnya.

Pengujian native berlangsung melewati beberapa build perbaikan; bukan satu perjalanan ulang bersih seluruhnya pada satu APK. Source transport tetap sama setelah perbaikan upload, sedangkan routine/refund dan smoke produksi dilakukan pada APK akhir. Kamera dibuka lalu dibatalkan tanpa foto; download privat, pembuatan PDF cetak, dan chooser berbagi diuji lalu dibatalkan tanpa memilih penerima.

## Backup dan perlindungan data

Backup privat server berada di `/root/gulhouse-releases/20261019-122114/`. Arsip kode dapat dibaca kembali; dump database diambil ulang pada saat cutover. Backup mencakup kode, konfigurasi, dan berkas sebelum deployment. Konfigurasi Nginx sebelum perubahan juga disimpan.

| Artefak | SHA256 |
|---|---|
| `database-before.sql` | `cb900bb9984859ea0771c423f4d56360c1bb0e46a0a4cb7fac4891e5e11f9465` |
| `code-before.tar.gz` | `5bf5cc5d7f0514f86a2baa0391470264b5961a5c5545a04b5fe9dd4b81d0218a` |

Migrator menjalankan dry-run, backup, dan perbandingan hash tabel sebelum menerapkan perubahan. Audit akhir setelah cleanup membuktikan seluruh baris lama pada 29 tabel tetap utuh: 28 tabel mempunyai hash penuh identik; seluruh 24 baris lama `gh_activity_logs` juga identik, dengan 15 log login pengujian baru yang dipertahankan. Foreign key existing mengubah referensi aktor log baru menjadi NULL saat akun QA dihapus. Log audit tidak dihapus untuk memaksakan hash sama. Tidak ada penghuni asli yang dipakai untuk transaksi uji.

## Perbaikan dari pengujian online/perangkat

- Multipart Expo58: unggahan native menggunakan byte `expo-file-system File`, sesuai converter fetch Expo. KTP PNG dan PDF kini terkirim; KTP native disimpan dengan hash identik, 4/4 pemeriksaan independen.
- OCR Linux: runtime Python terpisah dan FPM khusus GulHouse mengizinkan empat fungsi proses yang diperlukan. Konfigurasi FPM bersama dipertahankan. Fungsi `exec`, `system`, `popen`, dan `shell_exec` tetap diblokir. Model memberikan fallback bila runtime tidak tersedia.
- URL media publik mengikuti document root landing produksi. Foto katalog aktual mengembalikan HTTP200 dan JPEG valid.
- Placeholder native mempunyai warna eksplisit; scan fasilitas dapat disimpan sebagai draft dengan jumlah kosong. Finalisasi tetap memerlukan verifikasi semua fasilitas dan tanda tangan pada dokumen fisik.
- Form scan baru mengutamakan metadata dan unggahan; detail 19 fasilitas tersedia melalui Koreksi draf. Dashboard membedakan nominal rupiah, persentase, dan jumlah agar angka terbaca serta tidak salah satuan.
- Header landing pada lebar 681–980 px membungkus navigasi. Carousel ponsel memperbaiki kartu kamar pertama yang semula menyusut menjadi 2 px dan tertutup kartu berikutnya. Dua blok CSS minimal ditambahkan sambil mempertahankan byte sebelumnya; empat view hanya diganti query versi CSS agar cache klien diperbarui. SHA256 CSS final `3baba9b9c906add522142615ddd99b5377293b54956d339efcf28f51ecbcc01c`.
- Hardware Back Android 16: callback AndroidX melalui plugin Expo memperbaiki masalah pada tablet. Back Dashboard kembali ke ruang pengelola, Back pada dialog hanya menutup dialog, dan Back pada root memindahkan task ke background lalu melanjutkan sesi yang sama saat dibuka. CIKO menutup temuan RN-ONLINE04 berdasarkan checkpoint perangkat dan log native tersimpan.

## Bukti pengujian

Berkas implementasi utama: `mobile/src/api/client.ts` untuk upload; `mobile/App.tsx`, `mobile/plugins/withNativeBack.js`, dan `mobile/app.json` untuk Back; `mobile/src/screens/Lifecycle.tsx` serta `Dashboard.tsx` untuk form dan tampilan; komponen input/UI bersama untuk placeholder dan aksesibilitas. Backend mencakup `master/application/models/Lifecycle_model.php`, `master/application/libraries/Mobile_public_data.php`, sumber API dalam manifest release, serta dua migrator eksplisit `scripts/migrate-gulhouse-lifecycle.php` dan `scripts/migrate-gulhouse-mobile.php`. Patch web ada pada `landing/assets/landing/css/landing.css`; empat view publik hanya menerima perubahan versi URL aset.

| Cakupan | Hasil |
|---|---|
| Produksi, API baca/login dan halaman master setelah login | CIKO 40/40 PASS |
| Navigasi native 25 modul pengelola | 25 terbuka, tanpa kegagalan pada pemeriksaan awal |
| Siklus lengkap API HTTPS di server uji terisolasi | CIKO 28/28 PASS |
| OCR formulir PDF awal/bulanan/keluar | 19 label fasilitas, hasil tetap perlu verifikasi |
| Multipart converter Expo aktual | 10/10 PASS |
| KTP native, sesi penghuni dan hash download privat | 4/4 PASS |
| Native scan awal sebagai draft dan OCR | 19 jumlah kosong/kondisi belum diperiksa dipertahankan; OCR 19 label PASS |
| Native finalisasi sebelum review lengkap | Ditolak meskipun tanda tangan dikonfirmasi; readback CIKO masih 19 NULL/uninspected |
| Back Android 16 | Halaman, dialog, root/background/resume PASS |
| Sumber/runtime produksi | WASP 53/53 hash, service aktif, path privat404 |
| Siklus lengkap pada tablet | CIKO 17/17 readback independen; 41 checkpoint perangkat PASS termasuk smoke produksi akhir |
| Playwright website produksi | 26/26 PASS; login Dashboard, menu mobile dan klik detail kamar aktual |
| Carousel dan cache CSS produksi | 5/5 ukuran layar;2/2 readback versi CSS dan hash |
| Artefak APK akhir dan semantik UI | 14/14 artefak + apksigner aktual; 14/14 semantik source |
| Audit data/sumber akhir | Semua baris lama 29 tabel utuh; 58 hash sumber cocok |

Siklus API mencakup KTP, pembayaran aktual, bukti yang tidak otomatis melunasi, biodata, 3 pemeriksaan final × 19 fasilitas, kerusakan AC dan history penghuni, pemberitahuan H−15, checkout, tenggat H+2 kalender, refund tanpa sengketa Rp400.000, pelepasan sengketa Rp100.000 serta transfer kedua dengan bukti. Semua transaksi tersebut memakai database, akun database, direktori dokumen, dan prefix HTTPS uji tersendiri. Data produksi hanya digunakan untuk pemeriksaan baca/login dengan akun QA sementara.

Laporan QC terperinci: [ciko-mobile-online-report.md](ciko-mobile-online-report.md). Keberhasilan API tidak menggantikan bukti tombol dan navigasi Android. iPhone/iOS belum dibangun atau diuji; TestFlight menunggu akun Apple Developer el. CRUD seluruh 25 modul produksi sengaja tidak dijalankan agar data usaha asli tidak menjadi fixture pengujian.

OCR diuji dengan PDF formulir cetak sintetis berisi 19 label. Akurasi tulisan tangan dan foto formulir lapangan tetap memerlukan pemeriksaan admin; hasil OCR adalah draft, bukan persetujuan kondisi atau tanda tangan otomatis. Pembayaran/refund dicatat berdasarkan nominal, tanggal transfer, referensi, dan bukti yang dimasukkan pengelola. Pengujian memakai bukti sintetis dan tidak mengirim uang melalui bank.

## Cleanup dan bukti

Akun QA produksi dan token miliknya telah dihapus. Route HTTPS serta service uji dihentikan; URL uji mengembalikan 404, sedangkan homepage dan katalog produksi 200. Bukti/database/dokumen QA yang terisolasi dipertahankan privat untuk audit. Empat fixture pengujian pada folder Download tablet dibersihkan; dokumen pribadi el tidak disentuh.

Bukti lokal privat ada di `.e2e/mobile/online/`: `mobile-final-audit.json`, `online-qc-disabled.json`, `final-http-health.json`, `tablet-install-final.json`, `tablet-journey-results.json`, serta laporan CIKO pada subfolder `ciko/`. Receipt dan backup server berada di direktori release privat di atas. Tidak ada push Git dalam pengerjaan ini.

## Catatan komunikasi nyata — ringkasan

[CIKO -> ZEYN | Kritik]
Grid carousel mobile membuat kartu pertama hanya 2 px sehingga tombol detail tertutup. Alternatif yang diusulkan adalah menghapus explicit column track pada breakpoint ponsel.

[BUBU -> ZEYN | Revisi]
Aturan CSS minimal mengembalikan lebar kartu pertama menjadi 301 px pada viewport 390 px. Override diuji pada 13 ukuran layar; source mobile tidak diubah.

[CIKO -> ZEYN | Jawaban]
Setelah deployment, klik kartu pertama aktual lulus 5 ukuran layar, seluruh 26 pemeriksaan Playwright produksi lulus, dan tidak ada critical/major terbuka.

Keputusan ZEYN: terapkan hanya dua aturan CSS yang diperlukan beserta pembaruan cache, pertahankan log login untuk audit, dan serahkan APK setelah checksum perangkat cocok serta akun/endpoint QA dibersihkan. WASP memverifikasi sumber/runtime, BUBU menangani revisi UI, dan CIKO memberi verifikasi independen; semuanya merupakan subagent sesi ini.
