# Arsip booking dan penghapusan demo — 20 Oktober 2026

ZEYN mengoordinasikan BUBU untuk UI, WASP untuk backend/migrasi/CLI, dan CIKO untuk audit independen. Implementasi dan audit menggunakan subagent sesi yang benar-benar terpisah.

## Penggunaan

Pada **Master → Booking**, tombol **Arsipkan** meminta alasan dan konfirmasi. Booking dipindahkan dari daftar aktif tanpa mengubah status bisnis atau data penghuni/kontrak. Pilih filter **Arsip** untuk memulihkan, atau **Semua booking** untuk melihat keduanya. Daftar, jumlah, dashboard, dan export CSV mengikuti pengarsipan.

**Super admin** dapat membuka **Tinjau data terkait**. Halaman ini menampilkan jenis data, jumlah, ID, lampiran, dan alasan pemblokiran. Hapus permanen hanya tersedia jika booking sudah diarsipkan, telah terdaftar sebagai demo melalui prosedur operator, dan seluruh pemeriksaan relasi lulus. Pengguna harus mengetik `HAPUS DEMO #ID` sesuai nomor booking, mengisi alasan, dan memakai pratinjau yang masih berlaku.

Super admin dapat memakai **Verifikasi sebagai data demo** langsung pada halaman **Tinjau data terkait**: tinjau cakupan, centang pernyataan bahwa seluruh data merupakan demo, lalu isi keterangan. Pengguna tidak perlu PowerShell, ID turunan, atau fingerprint. Sistem menelusuri tautan eksplisit atau satu kandidat konversi lama yang konsisten; hubungan ambigu, tidak cocok, atau bersama tetap diblokir. Nama/telepon/kata “demo” tidak otomatis menetapkan status demo. Verifikasi membutuhkan konfirmasi manusia dan audit, serta tidak mengarsipkan atau menghapus otomatis. Fingerprint dan token keamanan tetap ditangani server. CLI tetap tersedia bagi operator sebagai pilihan, bukan persyaratan penggunaan web. Panduan teknis: `docs/booking-retention-contract.md`.

## Perlindungan data

- Peran akun dibaca dari database dan dikunci ulang dalam transaksi; hapus permanen hanya `super_admin` aktif. POST dan CSRF wajib, termasuk melalui URL controller langsung.
- Data asli, relasi bersama, rujukan silang di luar kepemilikan demo, relasi baru yang belum didukung, dan tabel nontransaksional menyebabkan seluruh penghapusan ditolak.
- Rencana mengikat schema, akun, ID booking, seluruh baris dan hash lampiran; berlaku lima menit. Perubahan setelah peninjauan membuat rencana tidak berlaku.
- Sebelum hapus, sistem menyimpan dan memverifikasi backup AES-GCM atas baris serta isi lampiran dalam direktori aplikasi privat. Kunci fitur ini terpisah dari kunci login yang sudah ada.
- Penghapusan dilakukan berdasarkan ID yang telah terbukti dimiliki demo dalam satu transaksi. Jika ada kegagalan SQL, seluruh perubahan dibatalkan. Master kamar/properti/tipe/katalog tidak dihapus. Status kamar hanya dilepas jika tidak ada masa tinggal aktif/reservasi/internal lain.
- Lampiran baru dibersihkan setelah commit, dengan pemeriksaan jalur, hash, dan referensi tersisa. Antrean retry dipertahankan bila file berubah/dilindungi/gagal dihapus; pengguna mendapat pemberitahuan. Backup, audit, dan riwayat global tetap dipertahankan.
- Kunci, backup terenkripsi, dan bukti pembayaran privat dikecualikan dari Git. Akses HTTP ke direktori backup diuji ditolak403.

## Berkas implementasi

Model baru `master/application/models/Booking_retention_model.php`; perubahan terbatas pada Operations controller/model, Dashboard_model, Mobile_service (penjagaan aksi booking arsip), Security_hook, routes, view `operations/index.php`, view baru `operations/booking_retention.php`, serta master CSS/JS. CLI: `scripts/booking-retention.php` dan bootstrap; patch: `database/patch_gulhouse_booking_retention_20261020.sql`. `.gitignore` melindungi penyimpanan privat.

Backup kode sebelum perubahan: `.e2e/booking-retention-20261020/`. Schema lokal dan fingerprint sembilan tabel bisnis sebelum migrasi: subdirektori `local-migration/`.

## Verifikasi

- WASP: 31 pengujian transaksi nyata pada database sintetis, termasuk konversi, penghapusan data turunan, rollback SQL di tengah proses, dekripsi backup dengan byte lampiran identik, dan GC/retry; lulus.
- CIKO independen: 36 pemeriksaan adversarial ditambah lima pemeriksaan schema/FK/engine; lulus. Uji browser dicatat pada laporan `docs/ciko-booking-retention-20261020.md`.
- CIKO browser/HTTP: 17/17 lulus, termasuk arsip/pulihkan, pratinjau yang diblokir, penghapusan demo konversi melalui UI, GC lampiran, CSRF, larangan GET mutasi, pembatasan peran, dan viewport390/768/1280. Satu label fixture awal salah memakai akun superadmin sebagai admin; diperbaiki pada harness, lalu admin sebenarnya diuji403. Tidak ada perubahan aplikasi untuk koreksi harness tersebut.
- ZEYN: lint 11 PHP dan sintaks JS lulus; regresi galeri mobile77 lulus; akses direktori backup melalui Apache dan server uji keduanya403.

Keputusan ZEYN: **QC PASS untuk cakupan arsip/pulihkan dan hapus demo yang aman**. Tidak ada temuan kritis/mayor terbuka dalam cakupan tersebut. Uji stale/rollback benar-benar dijalankan pada SQL/model dan UI; ini bukan klaim uji beban konkurensi produksi.

## Aktivasi lokal

Setelah QC, patch aditif diterapkan ke database lokal dan kunci khusus fitur diinisialisasi tanpa mengubah kunci login. Backup schema dan receipt berada pada `.e2e/booking-retention-20261020/local-migration/`. Jumlah dan hash seluruh isi sembilan tabel bisnis asli identik sebelum/sesudah migrasi (kolom baru dikecualikan dari pembandingan); tidak ada nilai bisnis lama yang ditimpa. Manifest15berkas implementasi: `.e2e/booking-retention-20261020/implementation-manifest.json`.

Seluruh penghapusan pengujian hanya mengenai fixture sintetis yang dibuat untuk tugas ini. Agent hanya memeriksa kontrak **GH-CTR-20261015-9672** secara baca-saja dan tidak menjalankan registrasi/arsip/penghapusan atas kontrak tersebut. Tindakan manual el sesudahnya merupakan langkah terpisah. Tidak ada deployment atau perubahan database server online.
