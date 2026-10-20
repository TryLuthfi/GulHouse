# GulHouse

Repository untuk ekosistem GUL HOUSE.

- `master/` web internal/admin
- `landing/` landing page publik dan booking
- `database/` migration, seed, dan dokumen skema
- `docs/` catatan analisa dan perencanaan
- `mobile/` aplikasi React Native Android/iOS untuk penghuni dan pengelola

## Aplikasi native

Panduan aplikasi dan build: [mobile/README.md](mobile/README.md). Cakupan fitur, pengujian, APK dan prasyarat server: [penyerahan native 19 Oktober 2026](docs/gulhouse-native-delivery-20261019.md). API/migrasi produksi memerlukan penerapan terpisah; build aplikasi tidak otomatis men-deploy server.

## Siklus penghuni dan fasilitas

- Form identitas awal: `/landing/index.php/resident/start` (KTP wajib, buku nikah opsional).
- Portal penghuni: `/landing/index.php/resident/access` memakai kode pribadi yang diberikan sekali.
- Admin: `/master/index.php/lifecycle` setelah login, termasuk enrollment penghuni existing.
- Form fasilitas tetap ditandatangani fisik, lalu scan dan hasil OCR lokal diverifikasi admin. Riwayat awal/bulanan/checkout terpisah per masa tinggal.
- Pemberitahuan keluar H-15 dan tenggat pengembalian deposit H+2 memakai hari kalender.

Panduan, file utama, bukti QC dan batas verifikasi: [penyerahan lifecycle 19 Oktober 2026](docs/gulhouse-lifecycle-delivery-20261019.md).

Migrasi lokal bersifat additive dan eksplisit melalui `php scripts/migrate-gulhouse-lifecycle.php --apply`; skrip membuat backup privat dan membandingkan hash/baris existing. Constructor aplikasi tidak menjalankan migrasi.

Pengujian lifecycle memakai `scripts/run-lifecycle-playwright.ps1` dengan database sintetis fresh. OCR memerlukan Python bundled dan dependency lokal dari `scripts/lifecycle-ocr-requirements.txt`; tidak menggunakan inference API berbayar.
