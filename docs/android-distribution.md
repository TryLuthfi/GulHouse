# Distribusi Android GulHouse

1. Pastikan API dan migrasi server online sudah diterapkan sesuai `mobile-api-contract.md`. APK default menghubungi `https://gulhouse.co.id/master/index.php/api/mobile`.
2. Bagikan `releases/android/GulHouse-1.0.0.apk` sebagai **Dokumen** melalui WhatsApp. Tidak perlu Expo Go atau komputer menyala untuk menjalankan APK release.
3. Pengguna membuka APK lalu mengizinkan pemasangan aplikasi dari WhatsApp/pengelola berkas bila diminta Android. Aplikasi bernama **GulHouse**, package `id.gulhouse.app`.
4. Admin memakai akun GulHouse existing. Penghuni baru mendaftar dengan KTP, lalu memakai kode akses pribadi yang diterbitkan alur pendaftaran/admin. Jangan membagikan kode penghuni kepada penghuni lain.
5. Lakukan uji perangkat sebelum membagikan luas: login kedua peran, pilih/foto KTP atau scan sintetis, unduh dokumen, print/sharing, keyboard/back, rotasi layar, keluar/login lagi dan koneksi internet perangkat.

Pembaruan APK harus ditandatangani dengan kunci release yang sama di direktori privat `.deploy/android-signing`. Backup privat kunci dan receipt rahasianya; jangan ikut mengirimkannya lewat WhatsApp atau memasukkannya ke repository.

iPhone belum didistribusikan. Proyek React Native mendukung target iOS; signing/build dan TestFlight menunggu akun serta proses Apple tersendiri.
