# CIKO — audit aplikasi React Native GulHouse

Tanggal: 19 Oktober 2026. Cakupan implementasi: aplikasi React Native/Expo Android, admin dan penghuni; API PHP tetap memakai validasi bisnis existing. CIKO membaca implementasi tanpa mengedit source aplikasi/API. Semua mutasi pengujian memakai database `gulhouse_test_mobile_ciko_*` terisolasi, data sintetis. File identitas, scan, rekening dan bukti transfer pengujian tidak mewakili penghuni atau tanda tangan sungguhan.

## Bukti yang sudah dijalankan

| Pemeriksaan independen | Hasil | Batas |
|---|---|---|
| `tests/mobile-api-surface.cjs` | 29/29 PASS | JSON semua 25 modul, metadata, autentikasi dan kebocoran nama field privat; bukan semua aksi tulis |
| `tests/mobile-api-security.php` | 37/37 PASS | Token hash, expiry/revoke, hak role dibaca ulang, API tidak mewarisi sesi browser, dispatch tidak mengangkat sesi browser, CSRF web tetap berlaku |
| `tests/mobile-api-resident.php` | 18/18 PASS | KTP wajib, intake multipart, biodata belum bayar ditolak, scope antar penghuni, byte file privat, revoke satu sesi |
| `tests/mobile-module-ui.cjs` | 25 modul melalui klik UI PASS | Membuka editor tambah bila tersedia, tanpa menyimpan data modul umum. Tidak mengklaim seluruh kombinasi aksi CRUD lulus |
| `tests/mobile-journey.cjs` | **26/26 PASS clean one-shot**, snapshot final50116 | Pendaftaran baru sampai refund aktual melalui UI; tanpa seed API/mode resume |
| `tests/mobile-logout-ui.cjs` | 2/2 PASS | Pengiriman request logout asli ditunda, login baru tetap bertahan sesudah logout lama selesai |
| `tests/mobile-media-audit.cjs` | 20 image bytes PASS (19 privat) | Header bearer serta401 tanpa autentikasi |
| `tests/mobile-photo-ui.cjs` | PNG/JPEG/WebP3/3 PASS | Upload awal seed API hanya file sintetis baru; rotasi90° lewat UI, ukuran40×20 menjadi20×40, cleanup sendiri |
| `tests/mobile-history-lookup-ui.cjs` | 7/7 PASS | Riwayat bulanan19kondisi, unduh scan privat nyata, lookup salah/benar, token terbatas403 ke portal/admin/file |

Journey menggunakan komponen React Native Web yang benar-benar diklik, API langsung tanpa mock atau perubahan state UI lewat script. Urutan: identitas awal + KTP → reservasi/invoice → transaksi sewa dan deposit terpisah → verifikasi pembayaran → biodata lengkap → scan/19 kondisi dan jumlah fasilitas awal → verifikasi tanda tangan/finalisasi → check-in → pemeriksaan bulanan dengan AC rusak → OCR lokal (tetap draft) → finalisasi → pemberitahuan H−15 → pemeriksaan keluar → checkout → tenggat refund H+2 → transfer refund dengan bukti → penghuni melihat refund selesai. Dokumen scan sintetis kecil hanya memverifikasi jalur upload/OCR, bukan ketepatan pembacaan tulisan tangan.

Artefak privat lokal: `.e2e/mobile/ciko/{api-surface-results.json,module-ui-results.json,journey-results.json,modules-*.png,journey-*.png}`. Berkas `*-private.json`/runtime mengandung sesi/kode pengujian dan tidak untuk dibagikan.

## Temuan dan penyelesaiannya

1. **Major RN-API01 — URL media salah konteks master/landing.** Public cover memberikan HTML404, foto admin memerlukan sesi web. WASP mengganti URL publik sesuai landing dan endpoint foto bearer. Verifikasi ulang:20 file gambar aktual termasuk19 endpoint privat lulus; katalog landing200.
2. **Major RN-API02 — output JSON terkontaminasi PHP warning setelah perintah tersimpan.** `json_decode(null)` memicu HTML warning, membuat klien tidak mengetahui mutasi sukses. Perbaikan cast/JSON dan bridge diuji ulang: success/error JSON valid, sesi browser tidak naik hak.
3. **Major blokir infrastruktur — router pengujian memblokir `/api/mobile`.** Regex privat `/mobile` tidak dibatasi ke root source. Root memperbaiki router; catalog kembali200 + CORS dan source root mobile tetap403. Ini bukan bug CORS controller produksi.
4. **Medium RN-UI01 — logout terlambat mereset navigasi/form login baru.** Repro nyata setelah biodata: klik logout, cepat berpindah Pengelola dan isi kredensial, lalu respons logout mengembalikan katalog. BUBU memperbaiki pelepasan sesi/navigasi lokal sinkron dan root mengamankan respons401 stale serta antrean penyimpanan. Retest actual UI snapshot final lulus; navigasi, token lokal, dan bearer server baru bertahan meskipun logout lama ditunda.

Review source juga menemukan risiko boolean `'0'` dianggap true, opsi FK pembayaran tidak tersedia, invoice tertukar transaksi, flag late `'0'`, aksi sah modul readonly disembunyikan, endpoint `{id}` tidak diganti dan timeout OCR terlalu pendek. Pemilik mengoreksi; kesimpulan akhir mengikuti bukti runtime, bukan pernyataan pemilik.

## Status sementara

**QC API dan alur React Native Web yang diuji: PASS. Tidak ada kritis/mayor terbuka pada cakupan tersebut. Native Android pada perangkat/emulator: BELUM TERVERIFIKASI.** Source memakai komponen RN serta DocumentPicker, ImagePicker, SecureStore, FileSystem, Sharing dan Print; bukan WebView aplikasi. Build APK, instalasi, kamera, picker native, keyboard, Android back, sharing/print, akses jaringan perangkat serta iOS memerlukan bukti tersendiri. Ekspor web/HTTP tidak membuktikan perilaku native fisik.

Tidak ada klaim semua kombinasi tombol/error/CRUD seluruh25modul telah diuji. Semua25modul diuji navigasi,11kategori diuji tulis, dan alur lifecycle diuji dua journey penuh melalui UI.

## Perluasan CRUD umum final

**11/11 kategori** lolos validasi wajib, create, edit, delete/disable/arsip lewat UI nyata: properties,room-types,rooms,users,maintenance,inventory,housekeeping,finance,notifications,templates,documents. Seluruh mutasi hanya record dengan prefix unik milik run. Akun dinonaktifkan dan kamar diarsipkan sesuai perilaku existing. Dokumen PNG sintetis diunduh melalui tombol privat; uang1000→1200 tersimpan; kedua DATETIME notifikasi12:00:00 tetap utuh setelah edit; password akun baru tersedia dan numeric0 tampil unchecked.

Artefak `general-ui-final-results.json` menggabungkan10kategori dari run terakhir dan rerun users terisolasi setelah memperbaiki assertion harness dari atribut aria-checked menjadi properti checked+DB0. Hasil mentah gagal assertion harness tetap disimpan sebagai `general-ui-results.json`; ini bukan kegagalan aplikasi yang disembunyikan. Cleanup seluruh11kategori berhasil.

Journey standar **26/26** dan varian klarifikasi **28/28** masing-masing clean fresh one-shot. Varian: deposit500ribu,100ribu dalam klarifikasi, transfer400ribu tetap dapat dicatat, klarifikasi dilepas100ribu, transfer kedua100ribu dengan bukti baru, penghuni melihat refund selesai.
- **RN-API04 Major: foto bearer tanpa index.php.** Endpoint admin/photos menghasilkan base berbeda dari API native. WASP memperbaiki canonical entrypoint; independent20imagebytes serta3rotasi format aktual lulus. CLOSED.
- **RN-API05 Major: field password descriptor disaring sanitizer.** Native create user tidak punya input wajib backend. Sanitizer kini mempertahankan descriptor tipe password saja dan membuang nilai/hash; actual create/edit/nonaktifkan akun sintetis viaUI berhasil, password/hash credential tidak dikembalikan. CLOSED.
- **RN-API06 Medium: pencarian users mengabaikan q.** Query unik mengembalikan semua akun. WASP menambah filter q/role/is_active dan pagination; retest UI pencarian unik menampilkan satu akun, edit dan nonaktifkan berhasil. CLOSED.
- **RN-UI07 Major: edit DATETIME notifikasi terblokir.** Row awal berisi `YYYY-MM-DD HH:mm:ss`, UI mengubah tipe menjadi date dan menolak nilai yang tidak diubah. Screenshot awal `crud-FAIL-notifications.png`; BUBU mempertahankan tipe datetime, retest actual UI create/edit dua kolom full timestamp12:00:00 lulus tanpa kehilangan jam. CLOSED.

- **RN-API08 Medium: kolom kartu core tidak sesuai DTO.** Users menampilkan Nama—/Status— karena defaultname/status, padahal DTO fullname/is_active. WASP mengganti metadata kolom ke field nyata. Independent5module metadata contract lulus (properties,room-types,rooms,users,payments), memastikan username/peran/aktif tersedia pada users. CLOSED: actual UI kartu akun menampilkan username, Viewer, Aktif0 serta tombol Edit/Nonaktifkan; screenshot users-card390.png diperiksa, tidak overflow.

## Handoff final

Implementasi source aplikasi tetap milik BUBU/WASP/root; CIKO hanya menambah tests/mobile-* dan docs/ciko-mobile-* serta artefak pengujian privat. Runtime CIKO http://127.0.0.1:65171 dibiarkan menyala sesuai permintaan ZEYN. Tidak ada perubahan data penghuni nyata, pengiriman Telegram, deployment atau update memory oleh CIKO.

Screenshot utama: `modules-390.png`, `users-card390.png`, `journey-dispute-06-resident-refund.png`, `history390.png`, `lookup390.png`, `photo-webp.png`. Ekspor Web yang diuji final index-11209e71b9da3bce65f169e58e3a9f1c.js; versi sebelumnya86d263 juga menuntaskan journey standar. Tidak ada klaim instalasi/kemampuan kamera/print/sharing native pada perangkat Android atau iOS.
