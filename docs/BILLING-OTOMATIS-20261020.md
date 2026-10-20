# Billing otomatis GulHouse

Implementasi lokal, 20 Oktober 2026. Tidak ada deployment online atau perubahan transaksi penghuni asli.

## Perilaku

- Reservasi membuat tagihan sewa awal dan deposit sesuai kesepakatan kamar.
- Masa tinggal Lifecycle yang telah aktif/notice dan terdaftar dalam kebijakan sewa otomatis menerima tagihan berulang dari tanggal dasar sewa. Tagihan baru memakai snapshot harga kesepakatan masa tinggal, bukan harga master kamar yang mungkin berubah.
- Penerbitan default H-7, mendahului deadline pembayaran H-2. Kalender menyesuaikan akhir bulan/tahun kabisat dan kembali mengikuti tanggal dasar pada bulan berikutnya.
- Tagihan berulang mempunyai deposit_amount=0. Pembayaran/saldo deposit masa tinggal tetap terpisah dari sewa.
- Job harian mengisi periode yang terlewat, tidak menerbitkan duplikasi, tidak menulis ulang tagihan existing, dan berhenti untuk masa tinggal yang telah keluar. Reservasi yang belum aktif dan data lama yang belum direkonsiliasi tidak otomatis ditagih berulang.
- Rincian Bill readonly. Endpoint edit tagihan dan generator manual web/API juga diblokir; bukan sekadar menyembunyikan tombol.

## Operasional

Runner: scripts/run-gulhouse-billing.ps1 -Database <schema> -Automatic -LeadDays 7. Default preview; -Apply harus eksplisit. Database asli memerlukan -AllowRealDatabase.

Installer lokal: scripts/install-gulhouse-auto-billing.ps1. Task **GulHouse Daily Lifecycle Billing - gulhouse_db** sudah dipasang dan diverifikasi Ready/enabled, harian pukul 09:00 Asia/Jakarta, tersembunyi, LeastPrivilege, IgnoreNew, StartWhenAvailable, tanpa menyimpan password. NextRunTime terverifikasi **21 Oktober 2026 09:00 WIB**. Perlu Windows menyala dan akun pengguna login. Tidak dilakukan Run Now pada database asli. Receipt: .e2e/auto-billing-20261020/local-task-receipt.json; XML dibaca kembali dari Task Scheduler. Ini bukan scheduler server online.

Jobs dibatasi CLI dan target schema yang eksplisit. Runner PowerShell memeriksa hasil JSON agar redirect/keluaran kosong tidak dianggap sukses.

## Bukti dan keamanan data

- Backup tiga tabel yang menjadi target penerbitan: .e2e/auto-billing-20261020/local-safety-v2/.
- Hash/count seluruh 43 tabel gh_* sebelum/sesudah pemeriksaan lokal: tidak berubah. Runner database asli hanya dijalankan dalam mode preview.
- Tes WASP: 19 model, 4 konkurensi proses DB independen; regresi pembayaran 28 transaksi dan 4 konkurensi. Semuanya PASS. Lihat wasp-automatic-billing-20261020.md.
- Audit independen CIKO PASS: model 18, konkurensi/CLI 2, tindak lanjut model 10, GUI awal 6, GUI final 3. Cakupan bertumpang tindih tidak dijumlahkan menjadi jumlah skenario unik. Meliputi Bill readonly, POST palsu tidak mengubah nilai, HTTP Jobs 404, harga kesepakatan, tanpa deposit berulang, keluar menghentikan penerbitan, serta kalender dan duplikasi. Lihat ciko-billing-auto-20261020.md untuk bukti dan batas pemeriksaan.
- Screenshot browser pengguna: .e2e/auto-billing-20261020/live-bill-readonly-final.png.

## File implementasi

- master/application/models/Operations_model.php; Master_model.php
- master/application/controllers/Jobs.php; Operations.php; Manage.php
- master/application/hooks/Security_hook.php
- master/application/views/manage/payments.php; operations/index.php
- master/assets/master/css/payments-layout.css
- scripts/run-gulhouse-billing.php; run-gulhouse-billing.ps1; install-gulhouse-auto-billing.ps1

Tidak ada migrasi schema untuk perubahan ini. Data legacy, nilai tagihan historis, pembayaran, dan deposit lama tetap dipertahankan.
