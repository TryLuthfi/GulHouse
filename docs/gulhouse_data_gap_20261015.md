# Rekonsiliasi data riil GulHouse

Audit POCO tanggal 15 Oktober 2026 menggunakan snapshot/koneksi lokal `gulhouse_db` secara READ ONLY untuk analisis. Setelah analisis dan dua ronde peninjauan pemetaan, ZEYN menerapkan pembaruan penghuni yang diminta el dengan backup terverifikasi. Dokumen hanya memuat agregat dan kode kamar, tanpa identitas penghuni atau kredensial. Snapshot penghuni kini bertanggal **15 Oktober 2026**, sedangkan cutoff riwayat pembayaran tetap **September 2026**. Keberhasilan pengujian tidak menghapus gap sumber data.

## Kamar sumber dan operasional

| Area | Workbook sumber 7 September | Database operasional |
| --- | --- | --- |
| GH1 | 28 kamar fisik; 26 bertarif; potensi Rp41.750.000 | 28 kamar; 26 bertarif; 2 mess; tarif katalog Rp41.750.000 |
| GH2 | 36 kamar fisik; 34 bertarif; potensi Rp80.500.000 | 36 kamar; 34 mempunyai tarif; 3 mess; tarif katalog Rp79.500.000 |
| Apartment | Tidak ada di workbook kamar GH1/GH2 | Properti APT, 5 kamar; 4 mempunyai tarif; potensi Rp8.700.000 |

`gh_room_database` berisi 64 sumber GH1/GH2; `gh_rooms` berisi 69 karena 5 apartment tambahan. Seluruh 64 source ID terpetakan tepat satu kali, tidak ada source ID duplikat atau source yang hilang. Tidak ada pasangan property_id + room_code duplikat. Lima apartment tidak memiliki source_room_database_id, sehingga provenance-nya perlu dikonfirmasi dari sumber operasional lain sebelum dianggap berasal dari workbook ini.

GH2 A-315 mempunyai tarif Rp2.000.000 dalam sumber dan database, tetapi status operasional mess. Ini menambah satu mess operasional di luar empat mess sumber. Tarif katalog GH2 yang mengecualikan kamar berstatus mess menjadi Rp77.500.000. Perubahan status ini bukan alasan otomatis mengubah harga atau mengembalikan availability.

Perbandingan dilakukan pada nomor fisik kamar dan properti karena awalan kelas juga berbeda:

| GH2 nomor | Workbook | Database sumber dan operasional |
| --- | --- | --- |
| 303 | B-303 Deluxe Rp2.500.000 | C-303 VIP Rp3.000.000 |
| 309 | C-309 VIP Rp3.000.000 | B-309 Deluxe Rp2.500.000 |
| 310 | C-310 VIP Rp3.000.000 | B-310 Deluxe Rp2.500.000 |
| 317 | C-317 VIP Rp3.000.000 | B-317 Deluxe Rp2.500.000 |

Selisih bersih tarif GH2 Rp1.000.000 per bulan menjelaskan Rp80.500.000 workbook versus Rp79.500.000 database. Ini konflik sumber yang perlu keputusan pengelola; audit tidak mengubah kelas, tarif, kode ataupun histori pembayaran. Jangan menampilkan Rp122.250.000 workbook sebagai potensi database operasional tanpa label sumber/tanggal dan rekonsiliasi ini.

## Isi operasional dan gap migrasi

Tabel berikut adalah **snapshot audit awal sebelum transaksi pengguna dan impor penghuni 15 Oktober**, dipertahankan sebagai jejak pembanding.

| Dataset | Jumlah audit awal |
| --- | ---: |
| Tenant | 51 |
| Stay | 64 |
| Invoice/rekap bill | 897 |
| Payment | 515 |
| Contract | 0 |
| Contract document | 0 |
| Deposit ledger | 0 |
| Import issue | 254 |

Pada audit awal, stay terdiri dari 49 active, 2 reserved, 5 internal dan 8 empty. Status kamar terdiri dari 54 occupied, 2 reserved, 6 available, 2 maintenance dan 5 mess. Selisih occupied versus active ini merupakan temuan historis sebelum rekonsiliasi; bukan hitungan penghuni saat ini. Jangan menyimpulkan otomatis ada penghuni tanpa dokumen hanya dari selisih agregat.

### Pembaruan penghuni riil 15 Oktober 2026

Dua workbook penghuni GH1/GH2 berlabel update 15 Oktober dan periode Oktober 2026 mencakup 64 baris kamar, seluruhnya terpetakan unik: GH1 28 dan GH2 36. Observasi sumber terdiri dari 45 kamar dengan penghuni sewa, 5 internal/mess dan 14 bertuliskan kosong. Ini observasi workbook, bukan distribusi status akhir database: kontrak pengguna yang lebih baru dan perpindahan tanpa tanggal tetap dilindungi. Lima apartment berada di luar cakupan impor.

| Perubahan yang diterapkan | Jumlah |
| --- | ---: |
| Profil penghuni baru | 7 |
| Nomor HP profil lama diperbarui | 1 |
| Stay baru berdasarkan tanggal sumber | 7 |
| Stay lama ditutup sebagai observasi | 13 |
| Kontak darurat baru | 7 |
| Kontak darurat lama diperbarui | 3 |
| Kamar diperbarui status/publikasinya | 15 |
| Total operasi database | 53 |

Penutupan 13 stay terdiri dari 10 observasi kamar kosong, 2 pergantian penghuni terverifikasi dan 1 reservasi lama tanpa penghuni/tanggal yang terbukti tidak memiliki relasi kontrak, deposit, bill stay atau booking aktif. Status menjadi ended dengan checkout tetap NULL. Catatan mencatat tanggal observasi 15 Oktober dan `actual_exit_unknown`; tanggal keluar sebenarnya tidak dikarang. Bill/pembayaran lama tidak dipindahkan ke stay baru, dan penutupan ini tidak menghasilkan refund.

| Dataset setelah impor riil | Jumlah |
| --- | ---: |
| Kamar | 69 |
| Penghuni | 59 |
| Stay | 72 |
| Kontak darurat | 56 |
| Invoice/rekap bill | 897 |
| Payment | 515 |
| Contract | 1 |

Sebelum impor terdapat 52 penghuni dan 65 stay, termasuk transaksi pengguna yang dibuat setelah audit awal. Kontrak pengguna tersebut dipertahankan. Hash isi lengkap enam tabel payment, bill, contract, contract document, deposit ledger dan booking identik sebelum/sesudah impor. Backup lengkap telah diperiksa checksum-nya. Dry-run ulang sumber yang sama menghasilkan **0 operasi**. Pemeriksaan dashboard READ ONLY untuk 13 periode juga lulus 91 pemeriksaan. Bukti agregat disimpan privat pada `.deploy/tenant-update-20261015/import-result-summary.json`; artefak ekstraksi, rencana dan backup tidak untuk publikasi karena memuat data pribadi.

Pengecualian yang belum diselesaikan:

- **Tiga perpindahan nyata telah dikonfirmasi el, tetapi tanggal pindah belum diketahui**: GH2 B-301, A-308 dan B-317. Tanggal check-in workbook sama dengan tanggal historis di kamar sebelumnya, sehingga tidak digunakan sebagai tanggal perpindahan. Perubahan stay ditahan; profil/kontak yang valid dapat diproses terpisah, tanpa berarti selalu ada perubahan nilai.
- **Dua baris nama berbeda mempunyai NIK dan nomor HP sama**: GH1 A-203 dan GH2 A-410. Keduanya ditahan agar tidak salah menggabungkan penghuni. Perlu koreksi identitas dari sumber pengelola.
- **GH1 A-205** tercatat kosong pada workbook, tetapi mempunyai kontrak aktif hasil transaksi pengguna yang lebih baru. Kontrak dan stay aktif dilindungi; observasi kosong tidak menimpa transaksi ini.
- Baris staf/internal tanpa identitas cukup dan marker mess tidak dibuat menjadi penghuni sewa baru. Dua maintenance tetap maintenance. Data kosong tidak menghapus nilai lama.
- Satu NIK sumber berisi 15 digit dipertahankan tanpa menebak digit tambahan. Pembaruan identitasnya dilewati; field lain hanya diproses bila penghuni terverifikasi. Satu tanggal check-in tertulis “27 Agustus” tanpa tahun disimpan sebagai bukti mentah dan tidak mengganti tanggal database.

Ringkasan planner **58 baris diterima** mencakup observasi tanpa perubahan dan pemrosesan parsial profil. Angka itu bukan 58 penghuni diperbarui. Sembilan baris dengan issue mencakup pengecualian di atas dan marker internal; ini terpisah dari 254 issue impor pembayaran historis.

Riwayat bill mencakup September 2025 sampai September 2026: 13 periode, masing-masing 69 kamar, total 897. Impor penghuni Oktober tidak menambah bill atau payment Oktober/November. Cutoff November yang diminta el harus dibedakan dari periode pembayaran terbaru yang benar-benar tersedia. Dashboard memilih default periode terbaru yang ada sampai bulan berjalan (September 2026), tetap dapat memilih November secara eksplisit, dan menampilkan periode data terbaru. November kosong tidak membuktikan tidak ada bisnis/pendapatan.

Pengecekan workbook pembayaran memastikan blok terakhir `DATABASE!EQ3` adalah September; blok Oktober dan November di awal merupakan 2025, didukung label `LAPORAN!M4` Oktober 2025 dan `LAPORAN!U4` November 2025. Script `scripts/import_gulhouse_payments.ps1` memetakan 13 periode September 2025 sampai September 2026 secara eksplisit. Tidak ditemukan blok Oktober/November 2026 untuk diimport. Tanggal masa depan pada sel transaksi atau kolom lain bukan bukti adanya periode laporan tambahan.

Script import lama menghapus issue dari source_file yang sama sebelum membangun ulang issue dan menghubungkan payment melalui room_id + period_id. Setelah invoice mendukung multiple stay dalam satu room/periode, mapping ini perlu diperjelas agar tidak memilih invoice ambigu. Jangan menjalankan ulang script langsung pada data riil; trial harus memakai schema terisolasi/snapshot, peninjauan mapping dan rekonsiliasi, kemudian perubahan yang menjaga seluruh histori serta issue yang relevan.

Pada audit awal belum ada kontrak, dokumen atau deposit ledger yang dimigrasikan dari sumber historis. Saat impor penghuni diterapkan sudah terdapat satu kontrak hasil transaksi pengguna, yang dilindungi bersama tabel dokumen. Impor penghuni tidak membuat kontrak/dokumen atau transaksi deposit baru. Nilai deposit pada kamar/stay adalah kesepakatan atau tarif master, bukan bukti uang jaminan diterima. Jangan menciptakan ledger received hanya dari deposit master, atau kontrak yang dianggap sudah ditandatangani dari stay import.

## Issue import yang tetap terlihat

254 issue terdiri dari 244 `tenant_not_matched` dan 10 `payment_without_date`. Issue tidak boleh dihapus atau diberi status selesai semata untuk demo. Matching tenant membutuhkan sumber/periode yang tepat; pembayaran tanpa tanggal belum dapat dimasukkan ke laporan kas berbasis payment_date secara pasti.

| Periode sumber | Issue |
| --- | ---: |
| September 2025 | 24 |
| Oktober 2025 | 27 |
| November 2025 | 26 |
| Desember 2025 | 25 |
| Januari 2026 | 25 |
| Februari 2026 | 21 |
| Maret 2026 | 17 |
| April 2026 | 20 |
| Mei 2026 | 19 |
| Juni 2026 | 17 |
| Juli 2026 | 18 |
| Agustus 2026 | 7 |
| September 2026 | 8 |

## Langkah penutupan gap

1. Pengelola menetapkan sumber resmi untuk perubahan kelas/tarif empat kamar GH2, status A-315 dan provenance apartment. Koreksi kemudian harus menjaga histori dan alasan perubahan.
2. Minta sumber pembayaran riil Oktober/November bila tersedia; snapshot penghuni Oktober bukan pengganti bukti pembayaran. Impor tambahan harus idempotent dengan issue log, tanpa mengganti September dengan demo.
3. Lengkapi tanggal tiga perpindahan, koreksi dua baris identitas bentrok dan rekonsiliasi observasi kosong yang berkonflik dengan kontrak pengguna. Setelah itu rekonsiliasi mapping tenant/stay per periode dan tanggal pembayaran sebelum laporan keuangan dianggap final.
4. Migrasikan kontrak dan dokumen berdasarkan berkas sebenarnya; gunakan draft/needed jika bukti belum ada. Rekonsiliasi saldo awal deposit terhadap transaksi/bukti, bukan tarif master.
5. Uji aplikasi dengan fixture terpisah di port 8032. Demo localhost menggunakan data riil dengan gap yang dinyatakan, atau akun/stay DEMO yang ditandai jelas. Hasil uji dan kesiapan data dilaporkan terpisah.
