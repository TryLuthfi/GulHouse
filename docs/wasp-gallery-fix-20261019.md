# WASP: audit independen perbaikan galeri native

Tanggal: 19 Oktober 2026. Implementasi, backup dan deployment dimiliki ZEYN. WASP hanya membaca source, menjalankan pemeriksaan fungsi terisolasi dan public HTTPS GET, serta menulis dokumen ini. Tidak ada perubahan source/backend/deploy, database, ADB, atau memory oleh WASP.

## Temuan utama GAL-01

Pengguna melaporkan foto Master GH2/VIP berjumlah18 tetapi mobile hanya menampilkan satu. Pembacaan independen sebelum perbaikan menunjukkan API detail GH2/VIP mengembalikan0 foto, begitu pula semua tipe GH1/GH2; APT/Mejestic mengembalikan8.

Penyebab terverifikasi dari source: Mobile_service::catalog_photos mengganti pemisah nama dengan underscore, sehingga GH 2 menjadi GH_2 dan PIK 2 menjadi PIK_2. Landing_model::folder_key menghapus karakter nonalphanumerik dan menghasilkan GH2/PIK2. Filter scope yang tidak cocok menghapus foto sah sebelum payload dikirim ke aplikasi.

Perbaikan ZEYN menyamakan normalisasi ke folder_key, tanpa mengganti routing, autentikasi, data foto atau APK. Pemeriksaan fungsi aktual setelah patch mempertahankan seluruh18 berkas gambar lokal GH2/VIP, termasuk ekstensi JPEG/PNG/WebP yang didukung. Pemeriksaan awal JPEG-only menemukan11 JPEG; angka18 diverifikasi kembali dengan seluruh ekstensi yang memang didukung, bukan mengasumsikan semua berkas JPEG.

## Temuan tambahan GAL-02 — CLOSED pada source setelah retest

Audit adversarial sebelum patch tambahan menemukan filter strpos memeriksa seluruh URL, termasuk query/fragment. URL foto properti lain dengan query `scope=/media/gallery/APT/MEJESTIC/` diterima untuk scope APT/Mejestic. Nama file berisi encoded traversal yang menunjuk berkas gambar nyata juga lolos predicate filter/media lokal.

Bukti diperoleh dengan memanggil catalog_photos aktual melalui Reflection dan fake model penghasil metadata, memakai file gambar existing serta sanitizer Mobile_public_data aktual. Ini adalah kelemahan validasi scope pada metadata yang diinjeksi ke fungsi; **bukan bukti eksploitasi HTTP atau kebocoran file privat**. Landing_model normal saat ini menghasilkan URL bersih dan controller gallery mempunyai pemeriksaan tersendiri.

ZEYN memperbaiki fungsi yang sama agar memeriksa komponen path URL: media/gallery/property/type/satu filename. Query/fragment tidak dijadikan bukti scope; decoded traversal, filename kosong/dot/backslash dan properti/tipe yang berbeda ditolak. Sanitizer tetap memeriksa origin yang dikonfigurasi, file nyata dan URL publik canonical.

Retest independen pada fungsi aktual sesudah patch: **8/8 PASS**:

| Kasus | Hasil |
|---|---:|
| Scope APT/Mejestic yang benar | 1 diterima |
| Foto properti berbeda | 0 |
| Foto tipe berbeda | 0 |
| Host asing | 0 |
| Scope hanya pada query | 0 |
| Scope hanya pada fragment | 0 |
| Encoded traversal filename | 0 |
| Seluruh gambar GH2/VIP sah | 18 diterima |

Tidak ada koneksi DB atau mutasi pada pemeriksaan fungsi tersebut. GAL-02 ditutup untuk source yang diuji; verifikasi online dilakukan terpisah setelah deployment ZEYN.

## Verifikasi source dan suite existing

- Hash source Mobile_service.php yang diperiksa: `8dd42f352e2feaf0c56969609b76784e9604162b01d7565c2f0f1202570f56f4`.
- `php tests/verify-mobile-gallery-scope.php`:77 pemeriksaan PASS, mencakup lima variasi nama properti/tipe, foto scope sendiri, alias gallery, cover, serta penolakan scope/host tidak sah. Suite ditulis ZEYN; WASP menjalankannya sebagai pemeriksaan tambahan setelah delapan uji independen di atas.
- `php tests/verify-mobile-public-base.php`:15 PASS.
- `php tests/verify-mobile-sanitizer.php`:4 PASS.

Command dijalankan menggunakan PHP lokal dari root repo. Uji independen memakai inline PHP stdin, stub CI_Model tanpa constructor, Reflection pada private catalog_photos, dan fake get_room_gallery. File actual di uploads hanya diperiksa/read-only; tidak dibuat atau dihapus. Tidak ada credential/token/nama penghuni yang dicetak.

## Pembacaan API publik sebelum deployment

GET katalog dan seluruh sembilan slug detail pada origin produksi menghasilkan:

| Slug | Foto sebelum |
|---|---:|
| apt-mejestic | 8 |
| apt-pik-2 | 0 |
| apt-the-nest | 0 |
| gh-1-standart | 0 |
| gh-1-deluxe | 0 |
| gh-1-vip | 0 |
| gh-2-standart | 0 |
| gh-2-deluxe | 0 |
| gh-2-vip | 0 |

Hasil ini adalah pembacaan payload publik, bukan pengamatan layar mobile. Verifikasi pascadeploy dan byte gambar ditambahkan setelah source produksi diperbarui oleh ZEYN.

## Verifikasi independen pascadeploy produksi

ZEYN melaporkan bahwa hanya Mobile_service.php dipasang pada produksi dan demo retained, dengan backup, hash8dd42f35 sesuai, lint dan mode/owner dipertahankan; tidak ada penulisan DB/uploads. WASP tidak menjalankan deployment tersebut.

Pembacaan public HTTPS GET independen setelah pemberitahuan deployment menghasilkan:

| Slug | Foto sesudah | URL unik | Cover berada dalam galeri sendiri |
|---|---:|---:|---|
| apt-mejestic | 8 | 8 | Ya |
| apt-pik-2 | 8 | 8 | Ya |
| apt-the-nest | 8 | 8 | Ya |
| gh-1-standart | 8 | 8 | Ya |
| gh-1-deluxe | 8 | 8 | Ya |
| gh-1-vip | 8 | 8 | Ya |
| gh-2-standart | 8 | 8 | Ya |
| gh-2-deluxe | 8 | 8 | Ya |
| gh-2-vip | 18 | 18 | Ya |

Alias photos dan gallery cocok pada semua sembilan detail. Seluruh18 URL GH2/VIP berada pada origin HTTPS resmi dan path canonical `/index.php/media/gallery/GH2/VIP/`. Setiap URL diminta melalui GET dengan Range maksimal64KiB untuk pemeriksaan header; seluruh respons berhasil, MIME image/jpeg, header didekode Pillow sebagai JPEG dengan dimensi positif. Ini bukan checksum seluruh body atau rendering di perangkat. Tidak ada cookie, login, credential, mutasi API/DB, perubahan file, atau ADB pada pemeriksaan ini.

Command verifikasi berupa inline Python urllib + ThreadPoolExecutor max3 dan Pillow Image.open terhadap header. Katalog dibaca satu kali, seluruh sembilan detail dibaca, lalu18 header gambar diperiksa. URL/filename foto tidak dicetak dalam laporan; hasil agregat di atas berasal dari eksekusi tersebut.

## Status final audit

**PASS, tidak ada temuan terbuka pada cakupan yang diuji. GAL-01 ditutup setelah API produksi kembali mengirim18 foto; GAL-02 ditutup setelah retest scope fungsi aktual.** Pembacaan demo retained dan interaksi carousel native/browser tambahan ditangani ZEYN/CIKO. Pengamatan perangkat/native tetap milik ZEYN/CIKO; audit ini tidak mengklaim seluruh flow mobile atau semua layar telah diuji ulang. Demo tidak diubah atau dinonaktifkan oleh WASP.
