# Ringkasan penghuni admin — implementasi WASP

19 Oktober 2026. WASP memiliki perubahan backend lokal Mobile_service.php, tes murni dan dokumen ini. BUBU memiliki UI tabel/ringkasan; ZEYN memiliki backup/deploy/preview/build. Tidak ada server, database, API writes, ADB, perubahan UI atau memory oleh WASP.

## Kontrak

GET `admin/tenants` tetap mengembalikan rows/count/filters/schema/options yang sama. `metrics` sekarang berisi lima integer:

| Key | Definisi | Label UI yang disetujui ZEYN |
|---|---|---|
| tenant_count | Jumlah tenant_id positif yang unik dalam seluruh hasil filter | Penghuni unik |
| stay_count | Jumlah seluruh baris masa tinggal hasil filter, sama dengan count API | Masa tinggal |
| active_stays | Jumlah baris stay_status active | Tinggal aktif |
| reserved_stays | Jumlah baris stay_status reserved | Reservasi |
| ended_stays | Jumlah baris stay_status ended | Sudah keluar |

Status counters menghitung masa tinggal, bukan orang unik pada setiap status. Satu orang dengan tiga masa tinggal dapat menghasilkan tenant_count1 dan stay_count3. Internal/empty/status lain tetap tercakup stay_count tetapi tidak ditambahkan ke tiga status tersebut. Tenant ID kosong/nol/negatif tidak dihitung sebagai orang. Tidak ada agregasi nominal, sewa, saldo atau deposit.

Ringkasan dihitung dari get_tenant_stays yang sudah mengembalikan seluruh hasil filter, sebelum array_slice limit/offset existing. Karena itu summary tidak terbatas50 baris dan tetap tersedia pada halaman kosong setelah akhir hasil. Filter q/property_id/room_type_id/room_id/stay_status tetap mengikuti Master_model existing; implementasi tidak mengubah atau menambahkan filter. Detail `admin/tenants/{id}` tetap mempunyai bentuk respons sebelumnya.

## Perubahan minimum dan backup

- File implementasi: `master/application/models/Mobile_service.php`.
- Satu branch tenants existing menambahkan pemanggilan helper pure `tenant_metrics($rows)` sesudah fetch existing.
- Helper hanya membaca array, mengumpulkan ID unik dan menaikkan counter; tidak mengakses DB, network, session atau file.
- Tidak ada query tambahan, perubahan pagination, row data, izin, skema database, atau metrik modul lain.
- Backup lokal sebelum edit: `.e2e/mobile/admin-table-20261019/Mobile_service-before-metrics.php`, SHA256 `8dd42f352e2feaf0c56969609b76784e9604162b01d7565c2f0f1202570f56f4`.
- SHA256 source setelah perubahan: `ea328b2f5f9ca712454f9d024b366d63e5b35cb9df083bc3ee69923d4f101b8e`.

Perbandingan source yang dinormalisasi newline membuktikan: menghapus helper dan mengembalikan satu branch tenants menghasilkan source backup yang identik. Fungsi galeri yang sebelumnya diperbaiki dan seluruh kode lainnya tetap identik; tidak ada perubahan tersembunyi pada auth, finansial, file privat atau aksi lifecycle.

## Verifikasi yang dijalankan

```powershell
D:/XAMPP/php/php.exe -l master/application/models/Mobile_service.php
D:/XAMPP/php/php.exe -l tests/verify-mobile-tenant-metrics.php
D:/XAMPP/php/php.exe tests/verify-mobile-tenant-metrics.php
D:/XAMPP/php/php.exe tests/verify-mobile-gallery-scope.php
D:/XAMPP/php/php.exe tests/verify-mobile-sanitizer.php
```

- Lint implementasi/tes PASS.
- Dedicated test **41/41 PASS**:75 baris sintetis/25 orang, halaman50+25, offset melewati akhir, penghuni dengan beberapa masa tinggal, filter pencarian/properti/status, hasil kosong, ID tidak positif/missing, status internal/empty/unknown, integer tanpa nominal, source rows tidak berubah, hanya satu fetch data existing, filter tidak dimodifikasi, viewer tetap readonly, resident tetap403, respons detail dan metrik pembayaran tetap sama.
- Galeri **77 PASS**, sanitizer **4 PASS**.
- Tidak ada koneksi DB, network request atau mutasi pada tes tersebut. Endpoint admin aktual dipanggil terhadap fake model/query boundaries, bukan hanya menguji counter/helper terpisah.

Tes fixture memodelkan hasil filter yang disediakan Master_model; ia tidak mengklaim menguji ulang SQL produksi. Tidak ada pengujian tablet/browser atau API online oleh WASP dalam tugas ini. Source backend sudah dibekukan dan hash/kontrak diserahkan kepada ZEYN/BUBU; verifikasi deployment dan tampilan dilakukan pemiliknya.
