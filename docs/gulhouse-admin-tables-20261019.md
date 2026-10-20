# GulHouse Android — ringkasan dan tabel pengelola

Permintaan el: data pengelola dibuat ringkas, dengan kotak ringkasan di atas dan satu kartu tabel di bawah, agar tidak harus membaca kartu rincian panjang untuk setiap baris.

## Hasil implementasi

- Modul daftar pengelola menggunakan satu kartu tabel dengan kolom sejajar, baris bergantian warna, geser horizontal, dan tombol Aksi berukuran 48 dp. Dashboard tetap memakai dashboard; Foto tetap memakai galeri.
- Rincian lengkap dan tindakan yang sudah tersedia dibuka dari Aksi. Rincian, editor, dan preview memakai satu sheet, menghindari modal bertumpuk. Nilai panjang yang dipotong pada tabel tersedia lengkap dalam rincian.
- Kotak ringkasan Penghuni: Penghuni unik, Tinggal aktif, Reservasi, Sudah keluar. Jumlah masa tinggal ditampilkan pada keterangan tabel. Satu penghuni dapat memiliki lebih dari satu riwayat tinggal; kedua hitungan sengaja dibedakan.
- Ringkasan dihitung dari seluruh hasil filter sebelum pagination, bukan hanya baris halaman yang sedang terlihat. Modul tanpa metrik khusus menampilkan Total hasil dan Ditampilkan secara eksplisit.
- Pagination 20 baris per halaman; pencarian/filter mengembalikan ke halaman pertama; hasil kosong menampilkan 0–0.
- Temuan AT01: filter status penghuni sebelumnya mengirim `status`, sedangkan API menerima `stay_status`. Diperbaiki khusus Penghuni agar kontrak filter modul lain tidak berubah.

## Berkas utama

- `mobile/src/screens/Admin.tsx`
- `mobile/src/components/AdminDataTable.tsx` (baru)
- `mobile/src/components/AdminSummary.tsx` (baru)
- `master/application/models/Mobile_service.php`: agregat penghuni sebelum pagination; tidak mengubah alur pembayaran, dokumen, atau galeri.

## Backup dan distribusi

Backup UI/APK sebelum perubahan: `.e2e/mobile/admin-table-20261019/`. Backup server sebelum penggantian model: `/root/gulhouse-releases/20261019-admin-tables/`. Deployment memverifikasi bahwa selisih hanya agregat penghuni, mempertahankan pemilik/izin berkas, lint PHP, dan hash setelah tulis. Tidak ada perubahan database atau unggahan produksi.

APK: `releases/android/GulHouse-1.0.0.apk`, SHA256 `4c3668cfcc96effe83dc4b37e291c29c71c4849a522155b6ba8631c77f57318e`. Ditandatangani dengan sertifikat existing, dipasang melalui update (`install -r`) ke Samsung SM-X406B. APK yang dibaca kembali dari perangkat memiliki hash identik.

## Verifikasi

Keputusan ZEYN: **QC PASS untuk lingkup ringkasan, kartu tabel, filter, pagination, dan akses rincian/tindakan yang diuji**, berdasarkan bukti CIKO dan pengujian perangkat fisik. Tidak ada masalah kritis/mayor terbuka pada lingkup ini; AT01 telah diperbaiki dan lulus uji klik filter.

- BUBU: TypeScript dan Prettier lulus; pemeriksaan format nilai dan pelestarian aksi lulus.
- WASP: tes agregat 41/41 lulus; hitungan orang unik dan masa tinggal dibedakan.
- CIKO independen: tes API 62/62, perbandingan source AST 10/10, dan Playwright GUI 33/33 lulus tanpa page error. GUI mencakup 24 keluarga modul selain Dashboard, responsif 390/768/1180, pagination, filter status, hasil kosong, detail/editor satu sheet. Laporan QC: `docs/ciko-mobile-admin-table-20261019.md`.
- Root: API demo online terisolasi membuktikan metrik sesuai seluruh data serta invariant terhadap ukuran halaman; katalog produksi tetap tersedia.
- Tablet fisik: empat summary sejajar, tabel padat, tombol Aksi membuka rincian dan tindakan, Hardware Back menutup sheet, gestur horizontal berjalan. Screenshot: `.e2e/mobile/redesign26/tablet-admin-table-summary.png`, `tablet-admin-table-actions.png`, `tablet-admin-table-horizontal.png`. Bukti terstruktur: `.e2e/mobile/admin-table-20261019/native-results.json`.

Pengujian tablet menggunakan demo terisolasi dan tidak mengubah catatan demo el. Setelah tes, tablet kembali ke API produksi dalam keadaan logout. Pemeriksaan ini bukan pembuktian transaksi simpan seluruh modul atau pengujian iOS. Demo penghuni yang diminta sebelumnya tetap dipertahankan.
