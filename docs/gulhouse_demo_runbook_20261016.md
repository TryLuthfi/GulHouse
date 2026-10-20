# Runbook presentasi GulHouse

Tujuan demo adalah memperlihatkan satu alur pengelolaan utuh dan hasil pengujiannya. Gunakan aplikasi lokal dan nama berawalan DEMO; pembayaran manual. Presentasikan kemajuan yang sudah diverifikasi, dan tandai koneksi eksternal atau kebijakan operasional yang belum ditetapkan secara jelas.

## Persiapan

1. Untuk demo aman, aktifkan MySQL lalu jalankan `scripts/start-demo.cmd`; gunakan `http://127.0.0.1:8034/landing/` dan `http://127.0.0.1:8034/master/login`. Launcher menampilkan akun sintetis dan menyiapkan tiga penghuni DEMO. Jendela server harus tetap berjalan. Aplikasi data riil tetap pada Apache. Website data riil: `http://localhost/GulHouse/landing/`; pengelola: `http://localhost/GulHouse/master/login`. Fixture pengujian menggunakan server terpisah `http://127.0.0.1:8032` dan schema uji terisolasi; hasil/akun fixture jangan disamakan dengan data demo riil.
2. Login dengan akun demo dari penyiapan lokal. Jangan menampilkan password, identitas penghuni asli, NIK atau kontak darurat pada layar presentasi.
3. Dashboard default memilih periode terbaru yang tersedia sampai bulan berjalan (snapshot riil September 2026), atau override valid `GULHOUSE_REPORT_PERIOD`. Untuk demo transaksi November pilih November 2026 secara eksplisit. Jelaskan bahwa tagihan memakai bulan pilihan, sementara status kamar/okupansi adalah kondisi saat ini dan saldo deposit mencakup ledger seluruh histori.
4. Siapkan satu kamar available yang dipakai khusus demo. Jangan mengubah stay penghuni riil. Catat ID kamar, booking, stay, kontrak dan invoice demo untuk mengikuti setiap transaksi.
5. Sebelum mengulang demo, gunakan fixture ulang yang disediakan tim pada database uji. Jangan membersihkan database riil dengan SQL delete atau membuat pembayaran dua kali pada data demo yang sama.
6. Baca `docs/gulhouse_data_gap_20261015.md`: snapshot riil memiliki 69 kamar termasuk 5 apartment, histori bill terakhir September 2026, 254 issue import, serta belum ada kontrak/dokumen/ledger deposit riil. Pemilih November tidak berarti data November sudah tersedia. Tampilkan status migrasi ini saat membahas kesiapan data.
7. Demo aktif terakhir dan scheduler memakai `gulhouse_test_demo_20261015_050242_619`. Scheduler sudah Ready untuk 1 November 2026 pukul 09.00 Asia/Jakarta dan tanggal 1 setiap bulan. User Windows harus tetap login dan MySQL aktif karena task memakai InteractiveToken/LeastPrivilege. Jangan menjalankan ulang launcher hanya untuk melihat dashboard; launcher mencipta schema baru dan task tidak berpindah otomatis ke schema itu. Scheduler riil belum dipasang.

## Urutan cerita demo

| Langkah | Tindakan di layar | Bukti yang diperlihatkan |
| --- | --- | --- |
| 1 | Landing: buka daftar, filter properti/tipe, buka detail kamar dan foto | Tarif katalog, deposit, fasilitas dan availability |
| 2 | Isi booking dengan nama DEMO, kontak uji dan kamar yang tersedia | Permintaan tersimpan; admin melihat request yang sama |
| 3 | Admin Booking: convert dengan periode sewa dan kamar yang disepakati | Tenant/stay reserved, draft kontrak, checklist dokumen; kamar tidak bisa dipesan ganda |
| 4 | Lengkapi kontrak dan dokumen administrasi, cetak preview | Harga/deposit/tanggal sewa, identitas demo, pernyataan/tata tertib/serah-terima |
| 5 | Penghuni: Draft Kontrak bila belum ada; Deposit: catat uang jaminan; Kontrak: buka Proses Kontrak, isi tanggal tanda tangan, klik Aktifkan & Check-in; verifikasi inventaris awal | Ledger deposit, stay active, kamar occupied dan inventaris masuk |
| 6 | Billing: generate November, lalu jalankan lagi | Invoice sewa sah satu kali; tidak ada tagihan untuk mess/kosong/reservasi tanpa kewajiban |
| 7 | Pembayaran: masukkan sebagian sewa, lalu sisa pelunasan; buka Bukti Transfer dan upload file sintetis PDF/JPG/PNG | Partial berubah paid; histori/piutang sesuai; proof terikat ke transaksi, bukan pembayaran baru |
| 8 | Dashboard: tampilkan November dan rincian invoice | Sewa masuk terpisah dari deposit; potensi katalog bukan pemasukan aktual |
| 9 | Komplain: buka keluhan; Maintenance: tetapkan PIC/deadline dan tutup dengan solusi | Task terbuka hingga selesai, kondisi fasilitas dan biaya operasi |
| 10 | Kontrak: buka Proses, Perpanjangan; isi akhir kontrak baru dan tarif, klik Perpanjang | Histori kontrak lama tetap; invoice masa depan memakai kesepakatan baru |
| 11 | Inventaris akhir; Kontrak aktif: buka Proses Kontrak, Checkout & Penyelesaian Deposit, isi tanggal/potongan/refund/metode, konfirmasi inspeksi, klik Checkout | Kewajiban diselesaikan; deposit direkonsiliasi; stay/kontrak berakhir; kamar siap atau maintenance |
| 12 | Fasilitas, housekeeping, area umum, audit bulanan dan laporan/export CSV | Aktivitas kontrol operasional serta rekap sesuai filter; teks pengguna dibuka sebagai literal spreadsheet |

Nominal demo mengikuti harga dan deposit kamar yang dipilih, bukan angka rekaan dalam laporan bisnis. Untuk menunjukkan pembayaran parsial, gunakan setengah harga kontrak dan sisanya; pastikan tidak ada deposit yang dicatat sekaligus sebagai sewa.

## Uji batas yang layak diperlihatkan

- Konversi booking ulang atau booking kamar yang sudah dipakai harus ditolak dengan pesan yang bisa dipahami.
- Generate tagihan ulang tidak menambah invoice/penerimaan.
- Potongan dan refund yang melebihi saldo deposit harus ditolak.
- Pembayaran manual mengubah saldo invoice yang benar; koreksi tidak meninggalkan paid_total pada invoice sebelumnya.
- Akun viewer tidak bisa menyimpan perubahan.
- Booking reserved yang telah mempunyai placeholder tagihan dapat diaktifkan, lalu generate ulang mengubah placeholder menjadi invoice yang sah.
- Dua penghuni bergantian dalam satu kamar pada bulan yang sama mempunyai invoice stay masing-masing, sesuai default bulanan penuh yang dijelaskan saat demo.
- Checkout dini sesudah generate tagihan bulan berikutnya membatalkan tagihan mendatang yang belum dibayar; tagihan mendatang yang sudah dibayar perlu penyelesaian koreksi sebelum checkout.
- Bukti transfer invalid format/MIME atau lebih dari 10 MB ditolak; viewer hanya dapat membaca/unduh. Proof tambahan tidak mengubah amount, status invoice atau kas.
- Refund langsung melalui ledger meminta pemeriksaan akhir serta alasan/referensi, seluruh kewajiban relevan selesai dan saldo cukup. Checkbox inspeksi tidak tercentang otomatis.

## Menunjukkan billing bulanan

Tunjukkan task `GulHouse Demo Monthly Billing - gulhouse_test_demo_20261015_050242_619` yang Ready dan jadwal tanggal 1 pukul 09.00. Jadwal menunjuk schema demo tertentu; tidak menagih data riil.

Eksekusi Windows scheduler sudah diuji pada schema sementara terpisah: task benar-benar Start, selesai Ready/LastTaskResult 0 dan log apply ok true. Fixture kosong menghasilkan 69 placeholder katalog, nol sewa chargeable. Task smoke telah dihapus; task demo pada layar tetap jadwal masa depan, bukan task smoke yang tadi dijalankan.

Untuk melihat preview tanpa perubahan, jalankan dari PowerShell:

```powershell
& 'D:\XAMPP\htdocs\GulHouse\scripts\run-gulhouse-billing.ps1' -Database 'gulhouse_test_demo_20261015_050242_619' -Period '2026-11'
```

Preview menampilkan jumlah bill yang sudah ada dan pending cleanup, bukan ramalan nominal tagihan baru. Untuk demonstrasi apply manual pada schema sintetis yang sama, tambahkan `-Apply`; jalankan ulang untuk memperlihatkan idempotence. Jangan menjalankan CLI riil atau menambah `-AllowRealDatabase` selama presentasi. UI Generate Billing dan CLI menggunakan generator bisnis yang sama.

Jika penghapusan proof menampilkan warning cleanup tertunda, metadata sudah dilepas dari transaksi dan file privat tercatat untuk retry. Preview tidak menghapus file; billing apply yang berhasil menjalankan rekonsiliasi cleanup sesuai schema. Jelaskan hasil aktual tanpa mengatakan operasi di-rollback sesudah commit.

## Bahasa status presentasi

Sampaikan "sudah diuji" hanya untuk skenario yang berstatus lulus pada hasil pengujian terakhir. Ada menu dan HTTP 200 belum membuktikan proses operasional selesai. Integrasi pembayaran online tidak masuk demo atas arahan el. Provider WhatsApp dan tanda tangan elektronik bukan koneksi yang aktif sebelum dibuktikan; tunjukkan draft pesan dan cetak/upload manual bila itu fitur yang tersedia.

Kebijakan prorata, denda, utility, notice keluar dan pembatalan belum menjadi aturan resmi dari template. Demo dapat memakai tarif bulanan penuh untuk periode yang overlap, dengan tanggal jatuh tempo sesuai konfigurasi/kesepakatan. Jangan menyebut default implementasi sebagai SOP final tanpa keputusan pengelola.

## Referensi verifikasi

Gunakan `docs/gulhouse_acceptance_20261015.md` untuk matrix A01–A18 dan rumus. Verifikasi akhir selesai: 31 kasus × dua siklus × tiga viewport = 186 PASS, tanpa fail/skip/flaky/interrupted. Backend 240, CLI 17, filesystem 10 serta lint 137 berkas lulus; angka lint mencakup log runtime, bukan jumlah source. Hash 129 berkas source yang dibekukan tetap identik, jumlah pada 29 tabel riil dipertahankan, login dan landing demo HTTP200 serta task demo Ready. Snapshot `.e2e/final-verification-20261015.json` mencatat hasil pukul 06.13.14. Riwayat 174 core dipisahkan; jangan menjumlahkannya ke hasil final. Mapping report ada di `docs/gulhouse_browser_coverage_20261015.md`.
