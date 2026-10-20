# GulHouse — penyerahan lifecycle penghuni, 19 Oktober 2026

## Hasil implementasi

Implementasi lokal melanjutkan CodeIgniter existing, tanpa push atau deployment VPS. ZEYN mengintegrasikan pekerjaan subagent sesi WASP (backend), BUBU (UI), dan CIKO (audit independen). Perubahan lama yang belum di-commit dipertahankan.

- Calon penghuni survey melalui WhatsApp, datang langsung atau website, kemudian mengisi identitas awal. KTP terbaru PDF/JPG/PNG wajib; buku nikah opsional. Dokumen privat, maksimal 10 MB.
- Admin membuat reservasi beserta kontrak draft dan invoice awal, atau menautkan reservasi yang cocok. Penghuni existing dapat diikutkan melalui enrollment eksplisit dengan KTP; masa tinggal lama tidak ditulis ulang.
- Portal menampilkan kesepakatan sewa, deposit, periode, pembayaran invoice awal, dan sisa kewajiban. Penghuni dapat upload bukti pembayaran. Upload tidak membuat pembayaran sah, mengubah ledger, atau membuka biodata secara otomatis.
- Setelah pembayaran aktual dicatat dan diverifikasi admin, penghuni melengkapi biodata, identitas dan kontak darurat. Check-in memerlukan persetujuan kontrak, kelengkapan biodata/KTP, pelunasan awal, deposit dan pemeriksaan fasilitas awal final.
- Tanda terima dan checklist tetap dicetak, diisi serta ditandatangani secara fisik. Cetakan mencakup 19 fasilitas sesuai sumber, termasuk dua bantal. Pemeriksaan terikat kamar, penghuni, masa tinggal, tanggal aktual dan periode.
- Scan/foto tersimpan privat. OCR lokal menghasilkan teks dan saran; petugas membandingkan scan dan hasil transkripsi, mengonfirmasi kelengkapan tanda tangan, lalu memfinalisasi. Final tidak dapat ditimpa; koreksi menjadi revisi baru dengan alasan dan verifikasi ulang.
- Riwayat awal, bulanan dan checkout dapat dilihat penghuni dan admin. Temuan dapat ditautkan ke maintenance. Checklist bulanan belum diperiksa tidak dianggap baik. Dashboard menunjukkan cakupan bulanan serta penghuni aktif yang belum onboarding.
- Pemberitahuan keluar paling lambat H-15 memakai hari kalender. Keterlambatan ditandai tanpa potongan otomatis. Koreksi admin sebelum checkout memerlukan alasan dan mempertahankan tanggal pemberitahuan asli.
- Checkout memerlukan penyelesaian kewajiban, inspeksi akhir final dan pengembalian kunci. Tenggat refund adalah tanggal keluar aktual +2 hari kalender. Potongan disepakati, nominal disengketakan, hak refund, dan transfer nyata dipisahkan. Bagian tidak disengketakan tetap dapat dibayar.
- Transfer refund memerlukan nominal, tanggal aktual, referensi unik dan bukti. Kewajiban refund tidak diperlakukan sebagai pembayaran. Portal tetap tersedia setelah keluar untuk melihat penyelesaian deposit.

## Cara memakai

1. Form identitas awal: `http://localhost/GulHouse/landing/index.php/resident/start`.
2. Portal dengan kode akses pribadi: `http://localhost/GulHouse/landing/index.php/resident/access`. Kode hanya ditampilkan sekali; sampaikan secara pribadi, jangan ke grup.
3. Admin login: `http://localhost/GulHouse/master/index.php/login`, kemudian menu **Siklus Penghuni**.
4. Admin membuat reservasi dan mencatat pembayaran di modul Pembayaran. Tombol **Verifikasi pembayaran awal** memeriksa transaksi aktual; bukti yang baru diunggah belum cukup.
5. Cetak formulir fasilitas dari detail penghuni; unggah scan ke jenis pemeriksaan yang sesuai. Isi jumlah/kondisi, jalankan OCR bila diperlukan, periksa hasil dan tanda tangan, kemudian finalisasi.
6. Untuk penghuni aktif existing, gunakan **Aktifkan portal untuk penghuni existing**. Tidak ada onboarding massal otomatis atas data asli.

## Verifikasi dan bukti

- Playwright langsung melalui Chromium/Chrome: **54/54 PASS**, tidak ada page error. Runner baru diulang pada schema sintetis fresh; seluruh tindakan penting memakai formulir dan tombol aplikasi, termasuk upload asli, OCR lokal, pembayaran, inspeksi, maintenance, checkout, refund dan penyelesaian sengketa. Cakupan responsif 320/390/768/1440.
- Audit independen CIKO: **134 pemeriksaan browser/HTTP +10 regresi normalisasi OCR PASS**, tanpa Critical/Major/Minor terbuka. Lihat `docs/ciko-lifecycle-audit-report.md`.
- Backend terbaru: **44/44 HTTP/database assertions PASS**. Lihat `docs/lifecycle-backend-verification.md` dan `tests/verify-lifecycle-backend.php`.
- Regresi HTTP/database existing: **253 PASS** dari tujuh skrip `scripts/run-local-checks.ps1` (47 flow,34 operations,118 access,6 upload,5 deposit,13 payment proof,30 CSV).
- OCR lokal nyata: **8/8 PASS**. Mendeteksi saran centang baik/tidak baik pada fixture sintetis, membiarkan sel kosong belum diperiksa, mengenali19 label pada formulir asli kosong, dan selalu mewajibkan verifikasi manusia.
- Tiga cetakan check-in/routine/check-out dihasilkan melalui browser: masing-masing satu A4. Preview bulanan diperiksa ZEYN, BUBU dan CIKO;19 baris, catatan dan tanda tangan utuh.
- Lint116 PHP application files PASS; JavaScript/Python/PowerShell checks PASS; git diff check tanpa whitespace error.
- Regresi browser existing: **96/96 kombinasi skenario dan viewport memiliki hasil PASS terverifikasi** (32 masing-masing desktop1366,wide1920,mobile390), direkonsiliasi oleh `tests/summarize-lifecycle-coverage.cjs` ke `.e2e/lifecycle/legacy-coverage.json`. Ini gabungan cakupan, bukan satu run96 yang seluruhnya hijau. Run awal56PASS/2FAIL/38SKIP terkena isolasi fixture overpayment dan perubahan konfigurasi runner saat berjalan. Trace awal dipertahankan; cleanup fixture diperbaiki, konfigurasi dibekukan, dan bagian gagal/terlewat diuji ulang pada schema fresh: recheck15/15, wide2/2, mobile27/27 PASS. Beberapa kasus overlap; hasil tidak dijumlahkan sebagai100 skenario unik. Tidak ada skenario yang belum memiliki hasil PASS atau defect terbuka. Seluruh laporan runner mencatat0 model tokens dan0 model calls.

Bukti raw dan screenshot sintetis: `.e2e/lifecycle/zeyn/`; laporan verifikasi independen: `.e2e/lifecycle/ciko/`. Folder bukti dapat memuat kode akses sintetis, sehingga tidak dibagikan atau dimasukkan Git.

## Migrasi lokal

`scripts/migrate-gulhouse-lifecycle.php --apply` telah dijalankan ZEYN pada database lokal setelah pengujian. Backup SQL privat dibuat terlebih dahulu; seluruh jumlah baris dan hash isi29 tabel yang sudah ada tetap identik. Delapan tabel additive ditambahkan, termasuk katalog19 fasilitas. Tidak ada transaksi penghuni riil atau dokumen KTP riil dibuat oleh pengujian.

Checksum backup: `a4c70901e9612b5eee9dbef29fca01a94a147d0f751faab954bf0d5ea33d9614`.

## Menjalankan ulang tes

```powershell
# Python bundled atau GULHOUSE_OCR_PYTHON yang sudah dikonfigurasi:
python -m pip install --target .tools/ocr-python -r scripts/lifecycle-ocr-requirements.txt
./scripts/run-lifecycle-playwright.ps1
./scripts/run-local-checks.ps1
```

Gunakan executable Python bundled yang sama dengan instalasi OCR, bukan Windows Store alias. Runner Playwright membuat schema `gulhouse_test_*` fresh, memakai akun/penghuni sintetis, dan menghentikan server miliknya setelah selesai (kecuali `-KeepServer` untuk audit lanjutan). Konteks tes privat disimpan di `.e2e/lifecycle/runtime.json`; jangan mencetak atau membagikan isinya.

## Batas verifikasi

OCR tidak memastikan keaslian tanda tangan dan tidak menjamin pembacaan semua tulisan tangan. Saran kondisi bukan keputusan tanggung jawab atau potongan deposit. Dokumen bertanda tangan fisik tetap disimpan sesuai kebijakan arsip. Pengujian memakai dokumen/identitas/transaksi sintetis; tidak menguji printer fisik, transfer bank nyata, notifikasi WhatsApp otomatis atau provider tanda tangan elektronik. Tidak ada pemanggilan inference API berbayar; OCR berjalan lokal.

## File utama

- Backend: `master/application/controllers/Lifecycle.php`, `master/application/models/Lifecycle_model.php`, `landing/application/controllers/Resident.php`, `landing/application/models/Lifecycle_model.php` serta routes/hooks dan guard legacy yang terkait.
- UI: `master/application/views/lifecycle/`, `landing/application/views/resident/`, kedua aset `lifecycle.css`/`lifecycle.js`, dan navigasi existing.
- Schema/runtime: `database/patch_gulhouse_lifecycle_20261019.sql`, `scripts/migrate-gulhouse-lifecycle.php`, `scripts/lifecycle-ocr.py`, `scripts/lifecycle-ocr-requirements.txt`.
- QA: `scripts/run-lifecycle-playwright.ps1`, `tests/lifecycle-playwright.cjs`, `tests/verify-lifecycle-backend.php`, `tests/ciko-lifecycle-*`, `tests/verify-lifecycle-ocr.py`, fixture setup dan cleanup overpayment regression.
