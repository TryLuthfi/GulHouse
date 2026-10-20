# GulHouse acceptance bisnis untuk demo lokal

Audit POCO 15 Oktober 2026. Dokumen ini adalah kriteria penerimaan, bukan klaim bahwa seluruh skenario sudah lulus. Implementasi menggunakan kode yang ada, localhost, pembayaran manual, perubahan minimal landing. Cutoff pembayaran riil yang diverifikasi adalah September 2026; asumsi awal November dikoreksi setelah membaca workbook dan database. November 2026 pada demo adalah data sintetis. Tahun dan cakupan periode harus terlihat di layar, dengan data DEMO dipisahkan dari laporan riil.

## Sumber dan prioritas

Folder sumber: `C:\Users\TKM\Pictures\GUL HOUSE`.

- `README.txt`: alur kamar, penghuni, kontrak, tagihan, pembayaran, deposit, komplain, maintenance, keuangan dan hak akses.
- `DATA BASE KAMAR GH 1 DAN 2 - 7 Sept 2026.xlsx`: data detail kamar dan tarif, lebih kuat daripada jumlah contoh prototype.
- `Data Penghuni Gul House 1.xlsx` dan `Data Penghuni Gul House 2.xlsx`: data penghuni/kontak darurat; jangan tampilkan identitas mentah dalam dokumentasi atau demo publik.
- `DATA PEMBAYARAN.xlsx`: histori pembayaran per kamar/periode dan monitoring; deposit, pembayaran sewa dan nominal tunggakan harus dipisahkan.
- Prototype 1 dan 2: struktur billing/deposit/master tarif; angka 80 adalah kapasitas template.
- Template 3 dan 4: checklist fasilitas, housekeeping, maintenance, komplain, inventaris, area umum, audit bulanan dan keuangan.
- Dokumen 5: perjanjian, pernyataan, tata tertib, serah terima dan data/kontak darurat. Kebijakan yang berupa titik-titik adalah input konfigurasi atau kontrak, bukan fakta bisnis.

Validasi independen workbook kamar: GH1 28 fisik, 26 bertarif; GH2 36 fisik, 34 bertarif. Total 64 fisik, 60 bertarif dan 4 mess. Potensi sewa GH1 Rp41.750.000, GH2 Rp80.500.000, total Rp122.250.000 per bulan. Ini potensi penuh dari tarif katalog, bukan pemasukan atau piutang. Deposit GH1 Rp500.000 dan GH2 Rp1.000.000 pada kamar bertarif. Tidak ada kode kamar duplikat dalam masing-masing properti; identitas wajib property + room_code karena kode dapat sama antar properti.

## Aturan eksplisit dan asumsi

Eksplisit sumber: deposit dapat digunakan untuk tunggakan/kerusakan/kehilangan yang menjadi tanggung jawab penghuni; sisa dikembalikan sesudah pemeriksaan akhir dan seluruh kewajiban selesai. Perpanjangan perlu persetujuan dan ketersediaan. Penghuni bertanggung jawab atas inventaris dan kunci. Owner melihat keseluruhan; manager mengelola operasi; finance pembayaran/piutang; maintenance pekerjaan teknis; penghuni hanya data sendiri.

Belum ditetapkan sumber: prorata bulan pertama/terakhir, denda, pembatalan booking/refund, batas hold, hari jatuh tempo baku, biaya utility, notice keluar, tanda tangan elektronik dan provider WhatsApp. Jangan mengaktifkan denda/prorata/pengiriman otomatis tanpa kebijakan. Masukkan tanggal jatuh tempo dan harga kesepakatan pada kontrak; jika memakai default tanggal 10, tampilkan sebagai default yang dapat diganti. Pembayaran online dilewati sesuai arahan el. Dokumen upload/print manual tetap bisa diuji.

## Matrix acceptance

| ID | Proses dan input | Hasil wajib | Uji negatif/batas |
| --- | --- | --- | --- |
| A01 | Landing pilih kamar dan submit booking valid | Booking tersimpan dengan referensi kamar/properti; admin dapat follow-up | Mess/nonpublik/maintenance/occupied tidak bisa dipesan; field wajib dan submit ulang ditangani |
| A02 | Admin konfirmasi booking | Satu tenant, stay reserved, kontrak draft dan checklist dokumen; kamar reserved | Konversi ulang tidak mencipta duplikat; konflik kamar ditolak atomik |
| A03 | Data penghuni dan dokumen | Identitas, pekerjaan, kontak darurat, tanggal masuk, vehicle bila berlaku; lima dokumen administrasi terlacak | Tidak menerima tenant/kamar/kontrak saling berbeda; file invalid ditolak |
| A04 | Kontrak harga, deposit, mulai/akhir, due day, persetujuan | Nomor unik dan snapshot kesepakatan; print berisi identitas yang sah dan aturan berlaku | End sebelum start, nilai negatif dan kamar bentrok ditolak; edit tarif katalog tidak mengubah histori kontrak |
| A05 | Deposit diterima + check-in + inventaris awal | Deposit ledger diterima, stay active dan kamar occupied; checklist awal tersimpan | Check-in ulang idempotent; kamar tidak mendapat dua stay overlap |
| A06 | Generate billing periode overlap stay | Invoice hanya kewajiban sewa sah pada periode; tarif kontrak, due date, tenant dan stay benar | Rerun tidak duplikat; reserved belum aktif, vacant, mess dan kontrak berakhir tidak menjadi piutang; batas tanggal 28/29/30/31 |
| A07 | Pembayaran manual sebagian lalu pelunasan | Histori pembayaran dan bukti; paid_total, partial/paid, saldo piutang sinkron | Nilai negatif/invalid ditolak; pembayaran deposit tidak menambah paid sewa; submit ulang tidak menggandakan penerimaan |
| A08 | Koreksi pembayaran / pembatalan | Saldo tagihan, kas dan histori terhitung ulang dengan jejak audit | Jangan menghapus invoice yang punya pembayaran; overpayment tidak disembunyikan sebagai nol tanpa catatan |
| A09 | Perpanjangan kontrak | Persetujuan, tanggal dan tarif baru tercatat; invoice masa depan mengikuti kontrak baru | Tidak mengubah harga invoice historis; tidak mencipta stay overlap |
| A10 | Checkout dengan inspeksi + potongan + refund | Ledger penutupan, dokumen inventaris akhir, kontrak/stay closed; kamar available atau maintenance | Potongan/refund melebihi saldo ditolak; checkout ulang tidak refund dua kali; billing setelah keluar berhenti |
| A11 | Keluhan -> PIC -> maintenance -> selesai | Tiket terkait penghuni/kamar, deadline, solusi, completion date; biaya tercatat sekali | Done tanpa penyelesaian tidak membingungkan; maintenance dan expense tidak digandakan dalam laba |
| A12 | Fasilitas, area umum, housekeeping, audit bulanan | Kondisi baik/perlu perbaikan/rusak, petugas/tanggal/PIC/deadline, bukti dan tindak lanjut | Satu task selesai tidak menyembunyikan task terbuka lain; foto dan filter sesuai task |
| A13 | Owner/manager/finance/maintenance/penghuni/viewer | Setiap peran hanya akses data/aksi sesuai mandat README | Viewer tidak mutate; finance tidak mengedit akses; penghuni tidak melihat identitas/tagihan penghuni lain |
| A14 | Dashboard/laporan/export tanggal & properti | Periode yang dipilih jelas, KPI dapat direkonsiliasi ke detail; export seluruh hasil filter | Data demo/future tidak otomatis mengganti bulan riil; tidak truncate 300 rows tanpa keterangan |
| A15 | Reminder manual dan dokumen cetak | Pesan draft memuat nominal/due date benar; upload/print dapat dipakai | Provider belum terhubung tidak diberi status terkirim; placeholder kontrak yang belum lengkap terlihat |
| A16 | Bukti transfer pada satu pembayaran manual | Upload PDF/JPG/PNG asli maksimal 10 MB, unduh privat; proof tidak mengubah saldo pembayaran | Invalid MIME/size, akses tanpa hak dan mutasi viewer ditolak; hapus proof/payment membersihkan file atau mencatat cleanup tertunda |
| A17 | Billing bulanan melalui CLI dan scheduler | Preview nonmutating; apply memakai bulan/schema tepat, tarif kontrak, clamp due day dan idempotence | HTTP, target database salah dan akses riil tanpa opt-in ditolak; lock antar-run; jadwal baru aktif sesudah instalasi eksplisit |
| A18 | CSV dibuka sebagai spreadsheet | Filter/period sesuai tampilan; nilai dan row count benar; teks pengguna berbahaya menjadi literal | Formula injection dan karakter kontrol tidak dieksekusi sebagai formula; teks numerik/ID serta nominal yang sah tetap terpelihara |

## Rumus kontrol dan rekonsiliasi

- Okupansi operasional = kamar occupied / kamar rentable pada snapshot yang sama. Mess/nonrentable dikeluarkan; definisi kamar maintenance harus terlihat dan konsisten.
- Potensi penuh katalog = jumlah tarif kamar rentable. Pendapatan aktual sewa berbeda dari potensi.
- Invoice sah = sewa kesepakatan + biaya tambahan disetujui - diskon/kredit. Perubahan tarif tidak boleh mengubah histori.
- Dibayar sewa = jumlah alokasi pembayaran sewa terverifikasi minus reversal. Deposit dicatat terpisah.
- Piutang total = jumlah saldo invoice sah positif; kamar vacant/internal/reserved tanpa kewajiban sewa bukan piutang.
- Tunggakan = saldo invoice sah positif dengan due_date sebelum tanggal laporan. Saldo belum jatuh tempo ditampilkan terpisah. Aging dihitung dari due_date, bukan tanggal import.
- Collection rate = pembayaran yang dialokasikan ke invoice periode / jumlah invoice sah periode. Jika tidak ada invoice, tampilkan n.a.; pembayaran untuk periode lain tidak masuk pembilang.
- Kas sewa periode penerimaan = pembayaran sewa terverifikasi dengan payment_date dalam bulan; jangan mencampur dengan laporan berdasarkan periode invoice tanpa label.
- Saldo deposit per stay = received + adjustment kredit - deduction - refund - adjustment debit. Skema unsigned adjustment hanya mendukung kredit; jangan mengklaim adjustment debit tersedia tanpa perubahan skema. Refund tidak boleh melebihi saldo stay tersebut.
- Kewajiban deposit = jumlah saldo deposit nonnegatif per stay. Saldo negatif harus menjadi error rekonsiliasi, jangan ditutupi MAX pada total agregat.
- Laba kas sederhana = kas sewa + pemasukan operasional relevan - pengeluaran operasional dibayar pada periode yang sama. Deposit masuk/refund bukan pendapatan/biaya operasi; biaya maintenance yang sudah menjadi expense dihitung sekali.

## Temuan awal dan bukti perbaikannya

Audit awal menemukan generator billing memakai semua kamar non-inactive, due_date tanggal 10 dan stay terbaru tanpa batas periode. Piutang tidak memfilter status nonchargeable; dashboard menjumlah base_price kamar kosong dan memilih max periode sehingga fixture 2099 dapat mengubah KPI. Indikator kontrak <=30 hari belum mengecualikan kontrak yang telah lewat. Perbaikan kemudian diterapkan dan alur core terkait diuji dalam dua siklus browser pada tiga viewport; temuan awal tersebut tidak disebut sebagai bug yang masih terbuka tanpa bukti baru.

## Status verifikasi

Lulus oleh POCO: inventaris file sumber dan rekonsiliasi hitungan/tarif kamar dengan workbook; pembacaan struktur checklist dan kontrak; lint perubahan dashboard. Audit riil lanjutan mengidentifikasi perbedaan workbook/database yang dicatat di `docs/gulhouse_data_gap_20261015.md` dan tidak dikoreksi tanpa sumber operasional.

Hasil core terbaru yang dibaca langsung: enam report `.e2e-complete-cycle{1|2}-*`, masing-masing 29 executed/29 PASS, total 174 PASS, nol fail/skip/flaky. Cakupan meliputi CRUD, akses, upload, browser lifecycle, koreksi transaksi, deposit/inspeksi, kontrak print, report/CSV dan portal pada desktop 1366, wide 1920 dan mobile 390. Bukti per test dan run ID ada dalam `docs/gulhouse_browser_coverage_20261015.md`.

Addon A16-A18 diintegrasikan sesudah core. Backend gabungan 240 assertion PASS, CLI 17 PASS, filesystem cleanup failure/retry 10 PASS dan lint 137 berkas tanpa error (termasuk log runtime). Keenam report browser final31 dibaca: setiap run 31 selected/executed/PASS dengan exit 0; total 186 PASS, nol fail/skip/flaky/interrupted. Riwayat 174 core tidak ditambahkan ke total final.

Snapshot `.e2e/final-verification-20261015.json` mencatat hash 129 source files tetap identik, 34 log runtime dikeluarkan dari perbandingan source, dan jumlah pada 29 tabel riil dipertahankan, termasuk 51 tenant, 64 stay, 69 kamar, 515 payment, 897 bill dan 0 proof. Login dan landing demo port 8034 HTTP200. Hasil berlaku untuk kasus/versi yang diuji; kebutuhan migrasi data riil dan provider eksternal tetap dicatat terpisah.

Scheduler demo pada schema `gulhouse_test_demo_20261015_050242_619` telah terdaftar Ready untuk tanggal 1 setiap bulan pukul 09.00 Asia/Jakarta, mulai 1 November 2026. Scheduled task menggunakan InteractiveToken/LeastPrivilege dan memerlukan user Windows login. Penjadwalan riil tidak dipasang; CLI riil tidak dijalankan. Billing otomatis yang diklaim aktif saat ini terbatas pada target demo tersebut.

Uji OS scheduler A17 telah dilakukan terpisah pada schema fresh `gulhouse_test_scheduler_20261014_220901_036`: task sementara benar-benar Start lalu Ready/LastTaskResult 0, log apply 2026-10 ok true. Pemeriksaan menunjukkan 69 placeholder katalog dan nol chargeable. Task sementara unregister dan absence verified; task demo akhir tetap Ready. Uji OS tidak dihitung sebagai PASS browser.

Jangan memberi semua A01-A18 status PASS secara otomatis hanya dari jumlah assertion. Gunakan hasil runner terakhir dengan fixture dan expected/actual untuk coverage setiap ID, termasuk akses penghuni dan kebijakan yang belum diputuskan. Koneksi pembayaran online dilewati sesuai arahan el; provider WhatsApp, tanda tangan elektronik dan data riil sampai November belum terbukti tersedia. Lulus lint/HTTP 200 atau keberadaan menu saja tidak membuktikan lifecycle lengkap.
