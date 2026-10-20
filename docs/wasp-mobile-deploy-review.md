# WASP: review deployment API dan lifecycle GulHouse

Tanggal pemeriksaan: 19 Oktober 2026. ZEYN memiliki seluruh tindakan produksi dan tablet. WASP melakukan inspeksi, menyiapkan manifest/helper, serta memperbaiki guard migrator yang secara khusus ditugaskan ZEYN. Tidak ada koneksi atau perubahan database produksi oleh WASP.

## Temuan dan keputusan yang perlu diterapkan

1. **OCR Linux membutuhkan konfigurasi nyata.** `Lifecycle_model::extract_inspection` awalnya memiliki fallback Python Windows. ZEYN sudah memperbaiki default lintas platform menjadi `.tools/ocr-venv/bin/python` pada Linux, dengan environment `GULHOUSE_OCR_PYTHON` tetap didahulukan. Sediakan symlink/venv Linux dan helper, pastikan `proc_open` diizinkan PHP-FPM, lalu jalankan inferensi pada dokumen sintetis. Jangan menyalin binary Windows `.tools/ocr-python`. Tanpa ini, jalur manual tetap bekerja tetapi OCR belum terverifikasi.
2. **Migrator produksi sekarang mempunyai guard eksplisit.** Schema aktual dari inventaris adalah `gulhouse_co_id`. Kedua migrator menerima target ini hanya dengan `--production-schema=gulhouse_co_id` yang cocok dengan schema koneksi dan host localhost/127.0.0.1. Nama lain, flag tidak cocok/duplikat, serta host remote ditolak. Default lokal/test dipertahankan. `--check-target` memeriksa guard tanpa menghubungkan database. Migrator mengatur umask0077 sebelum membuat backup.
3. **Proteksi Nginx wajib diperiksa terpisah.** `.htaccess` tidak mengamankan Nginx. Tutup akses publik ke application, system, .env, .deploy, .e2e, .tools, database, scripts, tests, source mobile dan dokumen privat. Teruskan Authorization ke PHP. Regex pemblokiran `/mobile` harus berlaku pada direktori root saja, bukan substring `/api/mobile`.
4. **Isolasi filesystem dan database untuk full-flow online.** Storage lifecycle dipisah menurut hash nama database, tetapi galeri `uploads/SLIDER` dan `uploads/{property}/{type}` tidak dipisah menurut schema. Uji admin foto pada schema sintetis yang menggunakan filesystem produksi tetap berisiko mengubah foto asli. Gunakan salinan kode dan direktori uploads staging yang terpisah.

## Bukti online yang dibaca

- `.deploy/online-files-current.json`: 512 entri path/hash online; dibandingkan dengan SHA256 berkas lokal.
- `.deploy/schema-before-current.json`: PHP8.1.32, MariaDB10.11.10, environment production, fileinfo aktif, 29 tabel existing. Seluruh CREATE tabel existing mempunyai kolom id sehingga strategi snapshot ORDER BY id kompatibel dengan inventaris ini.
- Backup produksi dilaporkan ZEYN sudah dibuat sebelum penerapan. WASP tidak membuka isi dump penghuni ataupun credential.

Inventaris ini adalah snapshot sebelum deployment; ZEYN perlu mencocokkan ulang hash target tepat sebelum menimpa berkas bila ada perubahan bersamaan.

## Manifest minimum

`.deploy/wasp-deploy-manifest.json` berisi **53 delta wajib**, setiap entri mempunyai path, SHA256 lokal, SHA256 online bila ada, dan status new/changed. Pembuatnya `.deploy/wasp-create-manifest.py` hanya membaca berkas lokal/inventaris dan menulis manifest lokal.

Kelompok wajib: controller/model/libraries API; shared controller redirect adapter; guard model lama; config routes/CSRF; security hook; seluruh views serta CSS/JS lifecycle baru; controller/model proxy dan views penghuni; sidebar menuju lifecycle; dua patch additive dan migrator; helper/requirements OCR. File sama persis dengan online tidak dimasukkan.

**Delapan delta tampilan existing dipisahkan sebagai opsional:** contact_helper, landing_home, payment_lookup, payment_status, room_detail, landing.css, landing.js, master.css. Runtime API/lifecycle baru tidak memerlukan penggantian desain halaman existing ini. Tinjau sebagai perubahan website yang utuh bila ingin diterapkan; jangan menyisipkan separuh pasangan HTML/CSS/JS tanpa pemeriksaan. Manifest minimum menjaga UI existing secara default.

Preservasi wajib: `.env` online dan encryption key, database.php online, semua uploads/dokumen privat, PDF house_rules existing, sesi/cache/log, konfigurasi PHP-FPM/Nginx yang belum ditinjau. Jangan unggah akun/dump lokal, source mobile/node_modules, test artifacts, atau signing key Android. Endpoint cetak native bergantung pada template PHP online existing yang hash-nya tidak berubah; template itu dipertahankan.

## Urutan migrasi dan batas rollback

1. Cocokkan inventory terbaru, backup kode/config serta full SQL secara privat dan verifikasi hash/ukuran. Bila memungkinkan, uji restore dump ke schema kosong terisolasi. Gunakan umask0077.
2. Lakukan dry-run guarded dengan schema produksi eksplisit; gunakan executable PHP8.1 server. Tidak ada autorun dari constructor atau request GET.
3. Terapkan patch lifecycle terlebih dahulu, lalu mobile tokens. Mobile migrator menolak penerapan bila delapan tabel lifecycle/catalog belum ada.
4. Rekonsiliasi count dan content hash seluruh tabel lama. Lifecycle menambah delapan tabel, mobile satu tabel; tidak menulis occupant/financial existing. Tabel catalog baru diisi19 item sesuai formulir asli.
5. Jalankan ulang secara idempoten bila diperlukan setelah meninjau hasil. Catalog yang sudah ada tetapi masih parsial adalah kasus khusus: INSERT IGNORE dapat menambah baris catalog existing, sehingga pemeriksa hash akan gagal setelah DDL/seed diterapkan. Jangan mengartikan kegagalan rekonsiliasi sebagai rollback otomatis.

DDL MariaDB tidak dibatalkan oleh transaksi aplikasi. Hindari concurrent writes saat mengambil backup dan hash; apabila terjadi perbedaan, periksa penyebab. Jangan otomatis menghapus tabel baru atau memulihkan dump lama setelah transaksi sah baru masuk. Backup migrator meliputi tabel gh_*; backup produksi eksternal harus mencakup kebutuhan pemulihan situs/config/uploads secara lengkap.

## Kompatibilitas runtime

Tidak ditemukan sintaks PHP8.2+ pada kode API/lifecycle yang ditinjau. Pengujian lokal berjalan di PHP8.2.12, sehingga **lint dan smoke dengan PHP8.1.32 aktual tetap diperlukan**. Perlukan mysqli, fileinfo, mbstring, openssl, json, session dan hash; fungsi random_bytes/hash_file/session_abort/getimagesize. proc_open khusus untuk OCR. GD bukan syarat rotasi native karena client mengunggah hasil ImageManipulator.

Upload membutuhkan 10MiB per berkas; intake dapat membawa dua lampiran, inspeksi scan+foto. Pastikan post_max_size/client_max_body_size cukup untuk multipart total; saran minimum24MiB, instalasi lokal40MiB. OCR dapat berlangsung65detik, sehingga timeout PHP-FPM/proxy perlu mencukupi request native120detik. App config menetapkan Asia/Jakarta; pastikan waktu database/server tidak menyebabkan timestamp audit membingungkan.

`.deploy/wasp-runtime-preflight.php` adalah helper CLI baca-saja: PHP/extensions/fungsi, limit dan akses storage tanpa membaca DB/secret. Lint dan eksekusi lokal PASS; tidak membuktikan setting PHP-FPM. Jalankan sebagai user layanan FPM dengan PHP server dan periksa setting pool secara terpisah. Output tidak memuat credential atau token.

## Rancangan pengujian online terisolasi

Gunakan schema `gulhouse_test_mobile_online_*` dengan fixture sintetis, tanpa clone data penghuni asli. Root staging berada di release privat dengan filesystem/uploads terpisah. Akun role, kamar, KTP palsu bertanda QA, scan fasilitas, invoice, pembayaran, referensi refund, serta bukti transfer semuanya milik fixture tersebut. Simpan credential/token hanya dalam artifact privat dan revoke setelah tes.

Jika subdomain/certificate terpisah tidak tersedia, Nginx dapat meneruskan randomprefix HTTPS ke PHP loopback staging. Jangan membiarkan API menghasilkan URL foto absolute produksi: wrapper router staging harus mengatur SCRIPT_NAME/REQUEST_URI agar common prefix tetap konsisten, serta HTTPS/Host publik dari konfigurasi terpercaya. URL tablet menjadi `https://gulhouse.co.id/{prefix}/master/index.php/api/mobile`. Validasi me/catalog/foto bearer sebelum masuk ke flow. Router loopback saja, private source tetap ditolak, serta prefix dilepas setelah QC. Cookie path web legacy dapat berbeda pada prefix; tes native bearer tidak bergantung cookie.

Matriks flow sintetis: cari kamar/survey booking; intake KTP wajib dan optional marriage; reserve/tagihan; payment proof tidak otomatis verified; transaksi nyata fixture dan verifikasi; biodata lengkap; cetak19 fasilitas; scan/foto/OCR manual-review lalu final; check-in; routine history + maintenance; notice H-15 dan koreksi admin; checkout tanpa tunggakan dengan inspeksi final; refund H+2 serta dispute/undisputed dan proof; token logout/revocation; larangan lintas penghuni/role/private static. Tidak perlu menunggu15hari: gunakan fixture tanggal terkontrol oleh setup staging, jangan mengganti jam produksi.

Di endpoint produksi asli, lakukan smoke baca-saja terotorisasi: katalog, login, me,25 modul, own-document guards, logout, dan regresi website login/dashboard. Jangan memakai tenant/kamar/invoice asli untuk percobaan pembayaran, checkout, deposit, atau refund. Tablet fisik RR2YA006P7W dioperasikan ZEYN; WASP tidak mengklaim verifikasi hardware.

## Verifikasi WASP pada tugas deployment ini

- Dua migrator lint PHP lokal PASS.
- Guard target: **16/16 PASS**, tanpa koneksi DB atau mutasi, termasuk produksi tanpa flag ditolak dan schema arbitrer/remote ditolak.
- Runtime preflight lokal PASS dengan batas verifikasi CLI di atas.
- Manifest dihitung dari SHA256 lokal vs512 entri inventory online:53 wajib,8 opsional.
- Sebelum tugas deployment: suite API lokal100/100 dan serializer4/4 PASS. Ini bukan bukti deployment, full-flow online, atau tablet.

Tidak ada penulisan memory, perubahan app source di luar dua migrator yang ditugaskan, perubahan produksi, atau tindakan device oleh WASP.

## Pemeriksaan susulan OCR online

CIKO mencapai flow sintetis online sampai check-in, lalu ekstraksi OCR rutin menghasilkan500. Diagnosis ZEYN membuktikan `proc_open` tidak tersedia pada SAPI web meskipun CLI tersedia. Ini menegaskan batas pemeriksaan CLI di atas.

Usulan menambah pool dalam FPM master existing dengan `php_admin_value[disable_functions]` berisi daftar tanpa proc_open tidak cukup bila fungsi tersebut sudah diblokir php.ini global. [Manual resmi PHP-FPM](https://www.php.net/manual/en/install.fpm.configuration.php) menjelaskan bahwa disable_functions pada konfigurasi pool menambahkan larangan yang ada, bukan menggantikannya. Dampaknya, pool baru dapat tetap menghasilkan fungsi undefined.

Alternatif dengan isolasi lebih jelas: jalankan FPM master/service khusus GulHouse memakai INI terpisah yang mempertahankan daftar larangan kecuali proc_open, konfigurasi dan socket privat sendiri, serta user worker nonroot. Situs lain tetap memakai service/INI sebelumnya. Batasi akses socket ke Nginx/user yang diperlukan, pertahankan pemblokiran exec/system/popen/shell_exec dan source upload, uji konfigurasi sebelum start, lalu ubah vhost GulHouse saja. Pembukaan proc_open tetap menambah kemampuan mengeksekusi program pada kode GulHouse; pemanggilan OCR saat ini memakai argv array dengan executable/helper terpercaya, bukan shell gabungan dari input penghuni.

Uji lewat SAPI/pool web yang benar: function_exists untuk proc_open, proc_get_status, proc_terminate dan proc_close; inferensi OCR sintetis; timeout; akses storage; serta fallback manual dengan pesan422 bila kemampuan tidak tersedia. Guard seluruh fungsi proses lebih lengkap daripada hanya proc_open. Mengaktifkan fungsi lewat PHP CLI -d pada QA tidak membuktikan kemampuan pool FPM produksi. ZEYN tetap pemilik seluruh perubahan layanan dan kode guard.

## Pembacaan ulang produksi setelah deployment

WASP melakukan pemeriksaan independen baca-saja menggunakan remote bridge yang ditugaskan ZEYN. Hasil:

- Seluruh53 berkas terpasang cocok SHA256 manifest final server; tidak ada mismatch. Flag maintenance sudah tidak ada. Backup kode dan database sebelum deployment tersedia.
- Service GulHouse FPM khusus, service QC, Nginx dan PHP-FPM81 existing semuanya active/running. Pool GulHouse menggunakan worker www:www, satu pool, socket privat mode0600. INI khusus mengizinkan proc_open/proc_get_status/proc_terminate/proc_close; exec/system/popen/shell_exec tetap diblokir. Setting ini dibaca dari service/INI aktual, tanpa mencetak environment atau secret.
- Vhost memakai TLS, document root landing, socket GulHouse dan aturan larangan direktori privat. Penerusan bearer efektif dibuktikan dengan token format-valid yang tidak pernah diterbitkan: HTTP401 TOKEN_EXPIRED, sehingga header sampai ke aplikasi. Tidak memakai credential produksi.
- Enam probe HTTP untuk path source/privat mengembalikan404. Katalog publik menghasilkan URL canonical `/index.php/media/...`; JPEG yang tersedia mengembalikan200 dan434.707 byte. URL sebelum perbaikan mengembalikan404.

Tidak ditemukan masalah kritis/mayor baru pada cakupan pembacaan ulang ini. Rekonsiliasi akhir29 tabel original masih menunggu cleanup QA oleh ZEYN; WASP belum menyatakan tabel original identik. OCR online28 pemeriksaan dilaporkan ZEYN/CIKO, bukan diulang melalui transaksi produksi oleh WASP. Build APK, tiga kasus UI tablet dan pemeriksaan native hardware masih menjadi pekerjaan ZEYN/CIKO. Hasil sumber/runtime di sini tidak berarti seluruh native flow telah diuji.
