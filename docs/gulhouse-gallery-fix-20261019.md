# Perbaikan galeri mobile GulHouse — 19 Oktober 2026

**Selesai dan terverifikasi pada API produksi serta demo.** Master GH2/VIP memiliki18 berkas gambar. Sebelum perbaikan, API mobile mengembalikan0 foto galeri untuk GH2 VIP dan beberapa tipe lain; UI detail kemudian memakai satu gambar cadangan/sampul. Kartu katalog memang menampilkan satu sampul, sedangkan detail memiliki galeri.

## Penyebab dan perubahan

`Mobile_service::catalog_photos()` menormalkan kode/spasi menjadi underscore (GH2 yang berlabel `GH 2` menjadi `GH_2`, `PIK 2` menjadi `PIK_2`). `Landing_model::folder_key()` dan folder Master memakai format tanpa pemisah: GH2 dan PIK2. Filter scope foto menolak URL yang sebenarnya benar.

Normalisasi API kini mengikuti folder Master. Validasi scope diperketat melalui komponen path URL yang tepat, bukan pencarian substring pada seluruh URL, sehingga foto dari properti/tipe lain dan penyamaran scope melalui query/path traversal tetap ditolak. `Mobile_public_data::media()` tetap memeriksa origin dan keberadaan file.

Implementasi hanya mengubah [Mobile_service.php](D:/XAMPP/htdocs/GulHouse/master/application/models/Mobile_service.php). Tidak ada perubahan database, unggahan, source aplikasi Android, atau APK. Tes regresi ditambahkan di `tests/verify-mobile-gallery-scope.php`.

## Verifikasi

- Root: lint PHP PASS,77 regresi galeri PASS,15 URL publik PASS,4 serializer PASS.
- WASP independen:8 pemeriksaan actual method PASS, termasuk normalisasi18 foto serta penolakan foto beda scope, origin asing, query/fragment spoof dan traversal.
- API produksi dan demo masing-masing mengembalikan18 foto GH2 VIP; delapan tipe publik lainnya masing-masing8 foto. Seluruh kartu memiliki sampul dari galeri tipe masing-masing.
- WASP memverifikasi18 URL produksi unik: seluruh GET berhasil, MIME JPEG dan header/dimensi gambar valid. Ini bukan perbandingan checksum seluruh berkas.
- Playwright React Native Web390×844:18 tombol thumbnail, navigasi ke18/18, gambar terakhir terdecode1264px, tanpa pageerror. Bukti `.e2e/mobile/gallery-fix/playwright-results.json` dan `gh2-vip-18-phone.png`. Percobaan locator penantian gambar awal diperbaiki karena selector ARIA/penantian terlalu singkat; bukan perubahan kode aplikasi.
- Tablet tidak terhubung pada putaran ini. Bukti render berasal dari RNWeb; tidak diklaim pemeriksaan Android fisik baru.

## Deployment dan backup

Backup kedua file server sebelum penerapan di `/root/gulhouse-releases/20261019-gallery-fix/`, disertai receipt. Hanya method galeri berubah; source di luar method cocok tepat setelah normalisasi line-ending. Mode/owner target dipertahankan; PHP server lint PASS. Hash lama kedua target `3be73fa0ebf9d5ca88e1b6ca7895fd5ce953a3dd2491c9808eff0b0ccadb5c25`; hash final `8dd42f352e2feaf0c56969609b76784e9604162b01d7565c2f0f1202570f56f4`.

Target produksi `/www/wwwroot/gulhouse.co.id/master/application/models/Mobile_service.php`; target demo `/var/lib/gulhouse-qc/20261019-122114/master/application/models/Mobile_service.php`. Demo penghuni yang diminta el tetap aktif; yang dihentikan hanya preview loopback sesi pemeriksaan.

## Catatan komunikasi nyata — ringkasan

[WASP -> ZEYN | Kritik]
Validasi substring pada seluruh URL juga dapat menerima scope yang disisipkan dalam query atau path traversal pada fixture internal. Dampak: filter kepemilikan galeri tidak cukup ketat. Alternatif: validasi komponen path sebelum sanitizer media.

[ZEYN -> WASP | Revisi]
Normalisasi disamakan dengan folder Master dan scope dibandingkan melalui komponen path yang tepat; tes query/traversal ditambahkan.

[WASP -> ZEYN | Jawaban]
Reproduksi independen lulus setelah perbaikan,18 gambar produksi tersedia, dan tidak ada temuan terbuka dalam cakupan tersebut. Laporan independen: `docs/wasp-gallery-fix-20261019.md`.

Pengguna cukup memuat ulang detail GH2 VIP. Instal ulang APK tidak diperlukan.
