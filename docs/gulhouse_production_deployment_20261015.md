# Deployment production GulHouse — 15 Oktober 2026

Production: https://gulhouse.co.id/ dan https://gulhouse.co.id/master/login.

Release `20261015-093018` sudah diterapkan. Sebanyak 595 berkas kode/aset publik dipasang dan checksum hasil pemasangan dicocokkan dengan manifest. File `.env` lokal, database/akun demo, sesi, log, dan dokumen pribadi penghuni tidak dimasukkan ke paket. PDF tata tertib yang dipilih el dipasang secara terpisah ke template `house_rules` production.

## Database

Patch: `database/patch_gulhouse_production_20261015.sql`, disusun dari patch operations, flow, roles, dan payment proofs yang sudah ditinjau. Tabel bertambah dari 14 menjadi 29. Seed inventaris dan template memakai `INSERT IGNORE` untuk mempertahankan pengaturan yang sudah ada. Perubahan existing mencakup enum peran dan invoice per stay. Patch snapshot/sinkronisasi data lama tidak diterapkan.

Sebelum migrasi, backup SQL dibuat kembali saat maintenance. Sesudah migrasi, seluruh 14 tabel existing dibandingkan berdasarkan jumlah baris dan SHA-256 nilai pada kolom lamanya, urut berdasarkan ID. Semuanya tetap sama. Data lokal booking/kontrak/deposit/transaksi tidak disalin.

| Data production | Sesudah deployment |
|---|---:|
| Penghuni | 51 |
| Stay | 64 |
| Kamar | 69 |
| Pembayaran | 515 |
| Tagihan | 897 |

## Environment dan runtime

- Environment production dan kredensial database server dipertahankan; tidak memakai `.env` lokal.
- Konfigurasi membaca `GULHOUSE_ENCRYPTION_KEY` dari environment server. Nilai rahasia tidak dicatat dalam laporan.
- Cookie menggunakan atribut Secure pada HTTPS.
- Ekstensi `fileinfo` PHP 8.1 disiapkan dari source resmi PHP 8.1.32, checksum source diverifikasi, modul diuji sebelum dipasang, lalu PHP-FPM dimuat ulang.
- Nginx diarahkan untuk mengakses master melalui front controller existing, dan memblokir akses statis ke application, system, file tersembunyi, serta dokumen privat.
- Autoload Composer hanya diaktifkan jika file autoloader tersedia.
- Sintaks properti dinamis pada dua salinan library Profiler dikoreksi agar kompatibel dengan PHP 8.1. Parser jalur master di landing dikoreksi agar tidak salah membaca segmen `master` kedua pada URL CSS/JS.
- Scheduler billing production tidak dibuat atau dijalankan dalam deployment ini.

## Verifikasi

- Regresi backend lokal: 253 assertion HTTP/database lulus pada schema sintetis terpisah sebelum cutover. Setelah penyesuaian konfigurasi akhir, regresi PDF/template 13 pemeriksaan kembali lulus.
- Lint PHP 8.1 server atas source paket: 422 berkas PHP; dua berkas Profiler yang awalnya gagal telah diperbaiki dan lint ulang berhasil. Perubahan index/config terakhir juga lint ulang berhasil.
- Patch diuji dua kali pada salinan skema production kosong di database lokal terpisah: 29 tabel, index invoice per stay, seed tidak berganda. Tidak memakai baris transaksi production.
- Production: 40 pemeriksaan HTTP lulus, termasuk 24 halaman admin, PDF master/landing identik dengan file sumber, cookie HTTPS, dan blokir jalur privat. Halaman admin diperiksa dengan sesi uji server sementara yang dihapus setelah pemeriksaan; tidak mengganti password, membuat akun baru, atau mengirim form transaksi production.
- Pemeriksaan dari luar server: landing utama dan www, login master, CSS, JavaScript, dan PDF tata tertib berhasil; hash PDF publik cocok dengan sumber.
- Maintenance sudah dinonaktifkan dan situs kembali aktif.

Pemeriksaan production ini adalah pemeriksaan HTTP/render halaman, bukan full browser E2E atau uji penulisan transaksi production.

## Backup dan bukti

Backup privat server berada di `/root/gulhouse-releases/20261015-093018/`:

- `code-before.tar.gz`: kode dan environment sebelum deployment.
- `database-initial.sql` dan `database-before.sql`: backup database awal dan saat cutover.
- `nginx-before.conf`, `php.ini.before`, `php-cli.ini.before`: konfigurasi sebelum perubahan.
- `code-verified.tar.gz`: source release terakhir yang telah dicocokkan checksum.
- `data-before-patch.json`, `migration-result.json`, `http-verification.json`, `release-result.json`: bukti rekonsiliasi dan pemeriksaan.

Manifest dan salinan laporan hasil tersedia di `.deploy/` lokal. Backup berisi data privat; jangan dipindahkan ke document root atau Git.

Untuk rollback, gunakan backup kode/environment dan konfigurasi di atas, uji konfigurasi Nginx/PHP-FPM sebelum reload, serta verifikasi kembali endpoint. Migrasi database bersifat additive; jangan otomatis menghapus tabel/kolom atau mengimpor dump lama setelah ada transaksi baru. Pemulihan database memerlukan peninjauan transaksi yang masuk setelah deployment.
