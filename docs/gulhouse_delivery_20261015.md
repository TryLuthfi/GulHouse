# GulHouse — hasil pengembangan lokal 15 Oktober 2026

## Pembaruan penghuni dari workbook 15 Oktober

Setelah analisis POCO dan dua ronde review, ZEYN menerapkan import lokal terverifikasi dari dua workbook penghuni GH1/GH2: 64 observasi kamar, 53 operasi. Ditambahkan 7 penghuni, 7 stay bertanggal sesuai sumber dan 7 kontak darurat; diperbarui 1 nomor HP penghuni, 3 kontak darurat dan 15 metadata kamar. Sebanyak 13 stay lama ditutup administratif berdasarkan observasi (10 kosong, 2 penggantian penghuni, 1 reservasi lama tanpa identitas/tanggal yang telah tersupersesi); tanggal keluar tidak dibuat-buat, riwayat dan catatan lama dipertahankan.

Total setelah import: 59 penghuni, 72 stay, 56 kontak darurat. Enam tabel pembayaran/tagihan/kontrak/dokumen/deposit/booking tetap identik secara hash sebelum-sesudah, termasuk 515 pembayaran dan 897 tagihan. Backup penuh dan checksum tersedia privat; import ulang menghasilkan nol operasi. Pengujian meliputi 33 planner, 16 integrasi, 9 pemeriksaan salinan offline seluruh sumber serta 91 pemeriksaan model dashboard riil read-only setelah apply. Salinan offline tidak dibuka melalui server web.

Pengecualian: tiga perpindahan GH2 (B-301, A-308, B-317) telah dikonfirmasi el tetapi tanggal belum diketahui, sehingga perubahan masa tinggal ditahan; dua baris dengan identitas/kontak sama dan nama berbeda ditahan; kontrak baru el di GH1 A-205 tetap dilindungi meskipun file menyebut kosong. Baris internal tanpa identitas tidak dibuat sebagai penghuni rekaan. Ringkasan tanpa identitas pribadi: `.deploy/tenant-update-20261015/import-result-summary.json`; rincian/backup privat pada direktori yang sama. Cutoff pembayaran tetap September 2026; snapshot penghuni kini 15 Oktober 2026, dengan pengecualian yang tercatat.

Riwayat pengujian pukul 06.13: 31 kasus sintetis dijalankan dua kali pada tiga viewport, total 186 PASS. Kasus data riil overpaid belum tercakup saat itu dan menyebabkan error dashboard yang dilaporkan el setelahnya. Fondasi CodeIgniter yang ada dilanjutkan, tanpa push atau deployment. Tampilan utama landing dipertahankan; perubahan landing berfokus pada validasi booking dan portal penghuni.

## Koreksi dashboard data riil setelah laporan el

Pada 15 Oktober 2026, el menemukan `BIGINT UNSIGNED value is out of range` pada dashboard Apache/data riil. Database memiliki 16 invoice dengan `paid_total > base_price`. Pengurangan UNSIGNED dievaluasi sebelum `GREATEST(...,0)`, sehingga clamp nol tidak mencegah error. Tujuh ekspresi pada Dashboard_model, Master_model dan Operations_model diperbaiki dengan konversi kedua operand ke DECIMAL(20,0) sebelum dikurangi; tidak mengubah data atau sql_mode server.

Verifikasi patch: actual Dashboard_model beserta query builder dijalankan pada seluruh 13 periode riil dalam transaksi READ ONLY, 91 pemeriksaan lulus. Backend gabungan 240 pemeriksaan ulang lulus. Regresi browser baru `gulhouse-overpayment.e2e.ts` membuat pembayaran rent berlebih melalui UI dan memeriksa dashboard/grafik, pembayaran dan piutang; lulus pada tiga viewport dengan schema fresh (`.e2e-overpayment-r2-*`). Suite rutin kini mencakup 32 kasus; seluruh suite 32 belum dinyatakan lulus dua siklus hanya dari riwayat 186 yang mendahului patch. Lint tiga model lulus. Audit WASP tidak menemukan ekspresi pengurangan unsigned lain yang terlewat. Jumlah pembayaran/tagihan riil tetap 515/897; pengujian ini tidak menulis data riil.

## Hasil implementasi

Booking/follow-up → reservasi → draft kontrak dan lima checklist dokumen → pencatatan deposit → aktivasi dan check-in → billing periode → pembayaran manual sebagian/lunas dan kuitansi → perpanjangan → inspeksi, penyelesaian deposit dan checkout tersedia sebagai alur terhubung. Booking dan transaksi berulang dilindungi; invoice terpisah per stay memungkinkan pergantian penghuni pada bulan yang sama.

Portal penghuni menampilkan tagihan, kontrak, administrasi dan deposit serta mengirim komplain. Komplain dapat diteruskan menjadi maintenance; penyelesaian mencatat biaya satu kali. Fasilitas, inventaris, housekeeping, audit bulanan, pengeluaran, laporan periode, CSV, dokumen privat dan administrasi tujuh peran tersedia.

Deposit tidak mengurangi sewa atau menjadi pendapatan. Edit pembayaran memutakhirkan invoice lama dan baru. Refund dibatasi saldo, seluruh kewajiban relevan dan konfirmasi pemeriksaan akhir dengan alasan tercatat; koreksi deposit yang membuat saldo negatif ditolak. Kontrak/tagihan historis dipertahankan saat perpanjangan. Imported active stays tetap dapat ditagih dengan label bahwa kontrak legal belum direkonsiliasi.

Bukti transfer PDF/JPG/PNG asli maksimal 10 MB kini terhubung ke satu transaksi pembayaran, dengan akses privat dan audit. Penghapusan metadata dan file membedakan hasil selesai dari cleanup tertunda yang dapat dicoba kembali. CSV mendapat perlindungan agar teks pengguna dibuka sebagai literal dalam spreadsheet. Billing tersedia melalui CLI preview/apply dan definisi jadwal bulanan; hanya scheduler demo sintetis yang telah dipasang.

## Hasil verifikasi yang benar-benar dijalankan

| Pemeriksaan | Hasil |
|---|---|
| Browser core sebelum addon, dua siklus × tiga viewport × 29 kasus | 174 PASS; 0 fail/skip/flaky, enam report fresh |
| HTTP/database gabungan termasuk bukti transfer dan CSV | 240 assertion PASS menurut runner ZEYN terbaru |
| Billing CLI: guard target/HTTP, preview, apply, idempotence, periode dan cleanup | 17 assertion PASS |
| Task Scheduler Windows nyata pada fixture fresh sementara | Start selesai Ready, LastTaskResult 0; log ok true; task sementara dihapus dan ketiadaan diverifikasi |
| Filesystem cleanup bukti transfer dengan failure/retry | 10 assertion PASS |
| PHP lint versi gabungan | 137 berkas diperiksa, termasuk log runtime; 0 syntax error |
| Browser final versi 31 kasus, dua siklus × tiga viewport | 186 PASS; enam report masing-masing 31 selected/executed, exit 0, nol fail/skip/flaky/interrupted |
| Integritas source yang dibekukan | Hash 129 source files tetap identik; 34 log runtime dari manifest awal dikeluarkan dari perbandingan source |
| Migrasi lokal dan data | Backup tersedia; jumlah pada 29 tabel riil dipertahankan: tenant 51, stay 64, kamar 69, payment 515, bill 897, proof 0 |

Runner backend: `scripts/run-local-checks.ps1`; CLI: `scripts/test-gulhouse-billing.ps1`; browser: `scripts/run-gulhouse-e2e.ps1`. Pengujian menggunakan schema `gulhouse_test_*` fresh dengan foreign keys dan data sintetis. Log backend ada di `.e2e/acceptance`; report core di `.e2e-complete-cycle{1|2}-*`; enam report final gabungan di `.e2e-final31-cycle{1|2}-*`. Bukti penyerahan ada di `.e2e/final-verification-20261015.json`, diverifikasi pada 15 Oktober pukul 06.13.14. Riwayat 174 core tidak ditambahkan ke 186 final. Mapping 31 kasus: 14 Operations, 5 admin, 4 portal, 2 master dan 6 alur tunggal, termasuk proof dan billing-job.

Trial awal yang gagal tercatat: casing status test, path artifact download, template inactive yang masih bisa dicetak, logout GET sebelum JavaScript siap, serta tata letak/navigasi mobile. Koreksi diikuti fixture fresh dan rerun. Bukti final menggunakan report versi terakhir; screenshot failure tidak dihapus untuk menyamarkan hasil.

## Demo siap buka

- Master demo: http://127.0.0.1:8034/master/login
- Landing demo: http://127.0.0.1:8034/landing/
- Jalankan ulang melalui `scripts/start-demo.cmd`; jendela server perlu tetap berjalan.
- Demo memiliki tiga penghuni sintetis, kontrak aktif/draft, pembayaran lunas/sebagian, deposit dan pekerjaan maintenance. Nama dan catatan diberi tanda DEMO. Baca kredensial akun demo pada keluaran launcher lokal; kredensial akun riil tidak diubah.
- Demo aktif terakhir memakai schema `gulhouse_test_demo_20261015_050242_619` pada port 8034. Scheduler `GulHouse Demo Monthly Billing - gulhouse_test_demo_20261015_050242_619` terdaftar Ready; next run 1 November 2026 pukul 09.00 Asia/Jakarta. Eksekusi hidden/LeastPrivilege menggunakan InteractiveToken, sehingga user Windows harus tetap login dan MySQL aktif. Scheduler riil tidak dipasang dan CLI riil tidak dijalankan.
- Login master dan landing demo port 8034 diperiksa kembali pada penyerahan; keduanya HTTP200.
- Launcher membuat schema demo baru setiap kali dibuka. Jadwal yang terpasang menunjuk schema demo di atas; jangan menganggap jadwal berpindah otomatis ke schema baru setelah restart launcher.
- Smoke OS scheduler memakai schema berbeda `gulhouse_test_scheduler_20261014_220901_036`, berhasil apply Oktober 2026 dan dibersihkan setelah selesai. Fixture kosong menghasilkan 69 placeholder katalog dan nol tagihan chargeable. Keberhasilan ini tidak mengklaim task demo sudah menjalankan periode November atau billing riil pernah dijalankan.
- Aplikasi/data riil: http://localhost/GulHouse/master/login dan http://localhost/GulHouse/landing/.
- Urutan presentasi: `docs/gulhouse_demo_runbook_20261016.md`.

## Batas kesiapan yang harus disampaikan

Data source pembayaran tersedia September 2025 sampai September 2026, belum Oktober/November 2026. Audit awal sebelum pembaruan penghuni mencatat 69 unit (64 GH1/GH2 dan 5 apartment), 51 penghuni, 64 stay, 515 pembayaran, 897 bill dan 254 issue import lama. Jumlah terkini setelah import penghuni tercantum pada bagian pembaruan di awal dokumen: 59 penghuni dan 72 stay. Kontrak historis, checklist dokumen dan saldo awal deposit riil belum seluruhnya direkonsiliasi dari bukti aslinya; kontrak baru pengguna tetap dilindungi. Detail dan perbedaan tarif/status terhadap workbook ada dalam `docs/gulhouse_data_gap_20261015.md`.

Pembayaran online dilewati sesuai arahan el. WhatsApp masih draft/manual; belum ada provider pengiriman otomatis. Kontrak dapat dicetak/PDF melalui browser; belum ada integrasi tanda tangan elektronik. Denda, prorata, utility dan kebijakan refund/notice yang belum ditentukan sumber tidak diaktifkan otomatis. Billing default satu bulan penuh untuk stay yang overlap, dengan jatuh tempo mengikuti awal kontrak/stay atau override `GULHOUSE_BILLING_DUE_DAY`; asumsi ini terlihat pada invoice. Perubahan tarif/base invoice tersedia bagi pengelola sesuai kesepakatan operasional.

Kelulusan skenario di atas membuktikan cakupan yang diuji, bukan jaminan tidak ada bug pada semua kemungkinan input. Kesiapan demonstrasi aplikasi dipisahkan dari kelengkapan migrasi data riil dan integrasi eksternal.

## Kolaborasi nyata

ZEYN mengoordinasikan subagen sesi WASP (implementasi flow), POCO (template, KPI dan data), serta subagen keamanan (RBAC dan regression akses). Dua ronde review POCO/WASP menghasilkan perbaikan saldo deposit, placeholder invoice, pergantian penghuni dan checkout terhadap invoice mendatang.

[POCO -> WASP | Kritik]
Ringkasan: satu invoice per kamar/bulan menghilangkan penghuni sebelumnya; refund/koreksi pembayaran bisa membuat saldo deposit negatif. Alternatif yang diajukan: invoice per stay dan validasi saldo dengan lock.

[WASP -> ZEYN | Jawaban]
Ringkasan: invoice per stay, lock/idempotence dan validasi reversal deposit diterapkan; biaya maintenance juga dihubungkan satu kali ke expense.

[ZEYN -> WASP | Keputusan]
Ringkasan: fondasi existing dilanjutkan, data riil dipertahankan, dan kelulusan dibuktikan pada database sintetis terpisah sebelum demo lokal.
