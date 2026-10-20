# QC foto utama GulHouse — CIKO

**20 Oktober 2026 · FINAL QC PASS untuk mekanisme pemilihan foto utama yang diuji.** Tidak ada masalah kritis/mayor terbuka. Seluruh browser CIKO ditutup dan fixture bersama sudah dibersihkan.

## Cakupan

CIKO membaca implementasi secara independen dan menguji helper, endpoint Master Foto, serta hubungan cover kartu landing dengan foto pertama galeri detail. Implementasi dimiliki ZEYN/BUBU; CIKO hanya menulis tes, fixture, bukti, dan laporan.

Pengujian memakai schema khusus `gulhouse_test_auto_billing_*`, dua gedung/tipe sintetis berawalan `CIKOCOVER`, dan gambar berwarna buatan untuk membedakan pilihan. Tidak ada pemilihan cover, penghapusan, rotasi, atau penggantian foto asli oleh CIKO. Seluruh POST pengujian diarahkan hanya pada galeri sintetis tersebut.

## Hasil aktual

- **Helper: 19 PASS, 1 SKIP.** Pemilihan valid tersimpan; fallback mengutamakan `kamar_01`; galeri lain tidak terpengaruh; file hilang, nama traversal, tipe input salah, gambar palsu, JSON rusak/terlalu besar, serta metadata lintas direktori ditolak atau kembali ke fallback yang aman. Hash gambar fixture tidak berubah saat metadata cover diperbarui. Uji symlink aktual dilewati karena Windows tidak mengizinkan pembuatan symlink pada lingkungan ini; tidak diklaim lulus.
- **Browser/HTTP utama: 15 PASS.** Tombol asli “Jadikan foto utama” menyimpan pilihan fixture, menampilkan satu badge, mengubah cover kartu landing dan foto pertama detail secara bersamaan, serta mempertahankan tiga foto di galeri. GET setter ditolak, CSRF kosong ditolak, pengguna tanpa sesi tidak dapat menulis. File hilang, traversal, file dari galeri lain, pasangan gedung/tipe yang tidak sah, scope slider, dan input array tidak mengubah metadata yang sah.
- **Viewer diperiksa ulang secara sempit: PASS.** Tidak ada tombol setter yang terlihat; forged POST dengan CSRF valid mendapat HTTP 403 dan tidak mengubah metadata. Assert awal salah mengharapkan form hilang dari DOM; implementasi existing menyembunyikan tombol melalui CSS. Riwayat kegagalan harness dipertahankan, bukan dianggap sebagai bug aplikasi.
- **Decode gambar: 2 PASS.** PNG fixture mendapat HTTP 200 dan benar-benar didecode menjadi 640×420; foto VIP pada checkpoint mendapat HTTP 200 dan didecode menjadi 1600×1066. Capture awal terlalu cepat setelah lazy-scroll sehingga thumbnail tampak kosong. Setelah menunggu decode, gambar terlihat; ini koreksi cara capture, bukan perbaikan aplikasi.
- **Responsif: 3 PASS pada 390/768/1280 px.** Screenshot setelah decode ditinjau langsung. Gambar fixture, badge foto utama, serta tombol tetap terlihat; body tidak meluber.
- **Stale cover: PASS.** File terpilih milik fixture dibuat sementara tidak tersedia, lalu kartu dan detail kembali ke `kamar_01` pada galeri yang sama. File dipulihkan dalam `finally` dan hash semua gambar fixture tetap sama sebelum cleanup.

Hasil di atas memiliki pengulangan cakupan antar-run dan tidak dijumlahkan sebagai jumlah assertion unik baru.

## Keamanan dan isolasi

Source review memeriksa pemetaan role dan CSRF melalui `Security_hook`, validasi pasangan gedung/tipe dari katalog, basename file, validitas gambar, serta batas resolved uploads dan penolakan parent symlink pada controller. Helper menyimpan metadata atomik `.gallery-cover.json`; tidak mengganti nama atau mengurutkan ulang file gambar asli.

Penolakan symlink ditinjau melalui sumber, tetapi uji runtime symlink tidak tersedia. Tidak ada klaim pengujian seluruh role, race filesystem, seluruh sistem operasi, atau deployment online.

## Bukti

Direktori `.e2e/photo-cover-20261020/ciko/`:

- `pure-results.json`: 19 PASS / 1 SKIP.
- `web-results.json`: 15 PASS / 1 assert harness awal yang kemudian dikoreksi.
- `narrow-results.json`: viewer 403 dan dua pemeriksaan media/decode PASS.
- `responsive-results.json` dan `master-decoded-{390,768,1280}.png`: tampilan fixture setelah decode.
- `master-gh2-vip-decoded-1280.png`: checkpoint baca-saja Master Foto VIP yang memperlihatkan foto kamar dan badge.
- `cleanup-results.json`: cleanup terverifikasi.

## Cleanup dan perubahan permintaan terbaru

Cleanup memvalidasi schema, prefix, resolved absolute path, daftar file, identitas row, dan tidak adanya relasi penghuni sebelum bekerja. Dua folder galeri sintetis beserta metadata/gambarnya, dua kamar, dua properti, dan dua tipe sintetis sudah dihapus. Folder/foto GH maupun APT tidak disentuh oleh cleanup. Fixture helper di bawah `.e2e` dipertahankan sebagai bukti.

Setelah checkpoint VIP di atas, el meminta impor ulang dari sumber foto lokal yang sudah diperbarui. Impor tersebut dimiliki ZEYN dan berada di luar validasi mekanisme ini. Screenshot VIP checkpoint bukan bukti kebenaran kumpulan foto hasil impor baru. Tidak ada klaim hash foto GH tetap sama setelah impor baru yang terotorisasi itu.

PASS ini berlaku untuk pemilihan cover dan isolasi yang diuji, bukan seluruh fitur Master Foto, unggahan ulang baru, seluruh CRUD, native Android/iOS, atau deployment produksi.
