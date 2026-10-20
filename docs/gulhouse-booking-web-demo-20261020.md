# Verifikasi demo langsung dari web

Permintaan el: seluruh alur verifikasi demo dapat dilakukan melalui Master Booking, tanpa PowerShell atau mencari ID/fingerprint.

## Alur

1. Buka Master Booking → Tinjau data terkait.
2. Untuk booking yang belum terverifikasi dan lolos pemeriksaan, bagian **Verifikasi sebagai data demo** menampilkan konfirmasi dan kolom keterangan.
3. Super admin meninjau seluruh cakupan, mencentang pernyataan bahwa data itu demo, mengisi keterangan20–500 karakter, lalu menekan tombol verifikasi.
4. Verifikasi hanya mencatat status demo beserta bukti dan audit. Arsip dan hapus tetap tindakan terpisah.
5. Setelah diarsipkan dan seluruh pemeriksaan aman, gunakan tombol Hapus permanen pada halaman yang sama.

Server mencari tautan yang sudah ada atau satu kandidat konversi lama dengan catatan/label sumber yang persis cocok, identitas/masa tinggal/kontrak konsisten, dan kepemilikan eksklusif seluruh graf. Petunjuk tersebut hanya menyusun kandidat; penetapan demo tetap memerlukan konfirmasi manusia. Data bersama atau ambigu tetap ditolak. GET menggunakan pengikatan virtual dalam PHP dan tidak menulis ke database. POST memakai role superadmin dari database, CSRF, kunci baris, token lima menit yang terikat tujuan/akun/schema/booking/cakupan, dan pemeriksaan ulang.

## Perubahan

WASP: model Booking_retention_model, Operations controller, routes dan Security_hook. BUBU: view booking_retention.php dan CSS terkait. Tidak ada migrasi database atau perubahan kunci login untuk penambahan ini. Backup kode sebelum perubahan tersimpan pada `.e2e/booking-web-demo-20261020/` dan subdirektori `web-verification-before` pada evidence tugas arsip sebelumnya.

## Verifikasi

WASP:20 pengujian delta pada database sintetis lulus, termasuk GET tanpa tulis, konfirmasi/token/role/stale/replay, kandidat lama unik, pemblokiran kandidat ambigu/bersama, pemeliharaan timestamp historis, dan tidak ada arsip/purge otomatis. ZEYN: lint PHP terkait lulus; pemeriksaan baca-saja booking19 membuktikan data asli tidak berubah oleh preview. Saat pemeriksaan terbaru, booking19 sudah terverifikasi dan memenuhi syarat hapus—agent tidak melakukan pendaftaran atau penghapusannya.

CIKO independen: **16/16 pengujian delta web lulus** tanpa page error. Mencakup form baru390/1280 tanpa input ID/hash manual, GET tanpa tulis, kandidat ambigu/phone-only/data bersama ditolak, CSRF403, konfirmasi/keterangan wajib, pratinjau berubah ditolak, verifikasi baru dan konversi lama melalui POST, serta hapus demo konversi setelah langkah terpisah. Readback database membuktikan status verifikasi/audit dan penghapusan sesuai cakupan; admin biasa mendapat403. Penghapusan hanya pada fixture CIKO, bukan booking19/20.

Keputusan ZEYN: QC PASS untuk penambahan verifikasi demo langsung dari web. Penambahan ini aktif pada kode lokal; tidak ada deployment server online.
