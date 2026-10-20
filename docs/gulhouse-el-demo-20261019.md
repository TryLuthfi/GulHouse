# Akun full demo GulHouse untuk el — 19 Oktober 2026

Akun penghuni baru **El Penghuni DEMO** dibuat pada schema/storage demo terpisah dari produksi. Penghuni aktif, biodata lengkap, sewa simulasi terverifikasi, deposit simulasi Rp500.000,19 fasilitas, serah-terima awal, pemeriksaan bulanan dengan contoh AC rusak, dan tiket perbaikan tersedia. KTP/scan/data identitas semuanya sintetis.

Akses contoh tahap pendaftaran, biodata setelah pembayaran, dan refund juga disertakan. Admin khusus demo `el_demo` dibuat agar el dapat menjalankan langkah pengelola seperti reservasi, verifikasi pembayaran, pemeriksaan, checkout dan refund tanpa memakai akun produksi. Password dan kode hanya dalam file privat yang diberikan ke el; tidak dimuat dalam laporan ini.

Petunjuk siap pakai: [AKSES DEMO GULHOUSE.txt](<C:/Users/TKM/Desktop/AKSES DEMO GULHOUSE.txt>). Buka Lainnya, salin Alamat layanan GulHouse DEMO dari file dan simpan; lalu pilih Penghuni dan salin kode akses. Untuk mencoba dari awal, gunakan pendaftaran baru dengan data dan dokumen sintetis. Alamat API produksi untuk kembali juga tercantum.

Verifikasi: login penghuni pada4 tahap dan login admin demo berhasil setelah layanan demo diaktifkan. API katalog produksi tetap200/ok. Schema demo `gulhouse_test_online_20261019_122114`; tidak ada penulisan transaksi/penghuni produksi dalam provisioning ini. Receipt non-secret: `.e2e/mobile/redesign26/el-demo-delivery-receipt.json`.

Demo sengaja dibiarkan aktif untuk pengujian el, menggantikan status QA ditutup dari sesi audit sebelumnya. Layanan khusus `gulhouse-resident-demo.service` hanya melayani salinan backend/storage sintetis melalui rute HTTPS terpisah, dijalankan sebagai www dan diaktifkan saat boot. FPM/API produksi tetap terpisah. Guard retensi mencegah helper cleanup QA lama menghapus/merotasi akses demo selama masih dipakai el. Setelah el selesai, nonaktifkan demo secara eksplisit: periksa marker `/root/gulhouse-releases/20261019-122114/el-resident-demo-retained.json`, service khusus dan rute persisnya; jangan memakai helper cleanup lama tanpa penyesuaian unit/rute yang benar.

Tablet tidak terdeteksi melalui USB saat provisioning, sehingga aplikasi tablet tidak diubah otomatis. Kredensial telah diuji melalui API, bukan klaim login tablet pada sesi ini. Tidak ada perubahan source aplikasi, build APK atau deploy business API produksi pada tugas pembuatan akun ini.

