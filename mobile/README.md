# GulHouse Mobile

Aplikasi React Native + Expo untuk Android dan iOS, memakai komponen native dan JSON API GulHouse. Tidak memakai WebView sebagai antarmuka. Website landing dan master tetap tersedia secara terpisah.

## Menjalankan

Node.js dan npm diperlukan. Dari folder `mobile`:

```sh
npm ci
npm run typecheck
npm start
```

Backend default: `https://gulhouse.co.id/master/index.php/api/mobile`. API dan migrasi online diterapkan pada 19 Oktober 2026; bukti dan cakupan pengujian ada di `../docs/gulhouse-online-mobile-deploy-20261019.md`. Deployment source aplikasi pada masa mendatang tetap memerlukan penerapan backend yang sesuai. Alamat dapat diubah di **Lainnya → Koneksi server**, atau melalui `EXPO_PUBLIC_API_URL` sebelum bundling. Perubahan server menghapus sesi agar akun tidak tercampur.

Untuk perangkat fisik, `localhost` mengarah ke perangkat itu sendiri. Gunakan HTTPS server online; untuk pengembangan lokal gunakan IP laptop yang dapat dijangkau perangkat. Preview web hanya dipakai sebagai alat QC lintas ukuran, bukan bukti pengujian Android/iPhone.

## Build Android APK

Pada workspace Windows ini, JDK/Android SDK dan npm terpasang secara lokal di `.tools/` yang tidak masuk Git. Dari root repo:

```powershell
./scripts/build-gulhouse-android.ps1
```

Script membuat APK release dengan JavaScript tertanam, dua ABI ARM, alamat API online, dan penandatanganan privat tersendiri. Output: `releases/android/GulHouse-1.0.0.apk` beserta SHA-256 dalam `build-receipt.json`. APK dapat dibagikan sebagai dokumen melalui WhatsApp; pengguna perlu mengizinkan pemasangan dari sumber tersebut di Android.

Kunci dan kredensial signing tersimpan privat di `.deploy/android-signing/`, tidak dimasukkan Git atau APK. Simpan backup privat keduanya untuk memperbarui aplikasi dengan identitas yang sama. Jangan mengganti keystore setelah distribusi. Konfigurasi build tidak mengizinkan release lokal memakai debug key default.

`eas.json` menyediakan profil APK internal dan profil produksi, tetapi build cloud/upload tidak dijalankan otomatis. iOS memerlukan proses signing/distribusi tersendiri; TestFlight menunggu akun Apple Developer.

## Akses dan cakupan

- **Publik:** katalog, foto kamar, pencarian/filter, fasilitas/biaya/aturan/lokasi/kontak, permintaan survey, pendaftaran identitas awal dengan KTP wajib dan buku nikah opsional.
- **Penghuni:** akses kode portal, kelengkapan biodata setelah pembayaran terverifikasi, bukti pembayaran, riwayat tagihan/transaksi, fasilitas final, dokumen privat, komplain, pemberitahuan keluar H-15, dan status pengembalian deposit H+2.
- **Penghuni existing:** akses baca tagihan memakai nomor HP dan empat digit akhir NIK, tanpa memperoleh akses biodata/dokumen/aksi portal.
- **Admin:** dashboard dan seluruh modul existing, sesuai peran; CRUD/form/action native, filter, pembayaran dan bukti, dokumen/PDF/CSV, foto, lifecycle, scan 19 fasilitas/OCR/verifikasi, revisi dan maintenance, checkout, sengketa dan transfer refund.

Token native disimpan melalui SecureStore; preview web memakai sessionStorage. Dokumen privat diunduh dengan header bearer, bukan token dalam URL. Upload bukti pembayaran tidak memverifikasi transaksi. OCR tetap berupa bantuan transkripsi dengan verifikasi manusia; tanda tangan fisik tetap bagian alur pemeriksaan.

Kontrak API dan batas deployment: `../docs/mobile-api-contract.md`. Bukti audit, status build dan keterbatasan pengujian perangkat dilaporkan terpisah pada laporan penyerahan.
