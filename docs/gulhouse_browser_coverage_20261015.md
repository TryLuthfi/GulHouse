# Cakupan uji browser operasional GulHouse

## Koreksi cakupan setelah error dashboard riil

Hasil 186 di bawah adalah riwayat versi sebelum patch unsigned; fixture sebelumnya belum mencakup overpaid. Setelah laporan el, tujuh ekspresi SQL diperbaiki. Regresi `gulhouse-overpayment.e2e.ts` lulus pada desktop/wide/mobile melalui pembayaran berlebih di UI dan pemeriksaan dashboard/grafik, pembayaran serta piutang, dengan report `.e2e-overpayment-r2-*`. Backend 240 ulang dan actual Dashboard_model read-only seluruh 13 periode riil (91 pemeriksaan) lulus. Suite penuh kini 32 kasus; jangan menyebut riwayat 186 sebagai dua putaran versi 32 yang belum dijalankan. Detail ada di delivery, dan tester real read-only memerlukan `--real-readonly` secara eksplisit.

Pemilik POCO. Uji `tests/gulhouse-operations.e2e.ts` memakai browser dengan click/fill/select, modal konfirmasi, filter dan unduhan CSV. Pembacaan file CSV adalah verifikasi hasil unduhan browser; tidak menggantikan interaksi dengan request HTTP atau write database.

Status final terverifikasi: enam report versi gabungan selesai, masing-masing 31 selected/executed/PASS dan exit code 0. Total 186 PASS, nol fail, skip, flaky dan interrupted: 31 kasus diuji dua kali pada desktop 1366, wide 1920 dan mobile 390, dengan schema fresh setiap run. Dua siklus core terdahulu (174 PASS) disimpan sebagai history dan tidak ditambahkan ke 186. Semua report `.e2e-final31-cycle{1|2}-*` serta snapshot `.e2e/final-verification-20261015.json` telah dibaca. Tidak ada browser baru dijalankan POCO selama konsolidasi.

Percobaan frozen desktop terdahulu berhenti pada bug logout: report `.e2e-frozen-cycle1-desktop-1366/report.json`, run `01a13be7-f79a-7a97-a297-dba9ce2be695`, 29 selected, 21 executed, 20 PASS, 1 FAIL, 8 skipped. Trace membuktikan click anchor Logout menghasilkan GET `/master/logout` dan HTTP405 sebelum JavaScript pengubah POST siap. Ini bug produk pada timing normal, bukan assertion casing atau alasan untuk login ulang diam-diam. Agent keamanan menerapkan native POST form/button dengan hidden CSRF pada sembilan view. POCO mengganti selector test menjadi `[data-logout-form] button[data-logout]`; assertion no-dead-buttons mengecualikan tombol Logout yang sah. Seluruh enam run final setelah patch sudah diulang dan lulus. Hasil 20 PASS pada trial lama tidak membuat 8 skipped menjadi PASS.

## Bukti run yang sudah selesai

| Output report | Run ID | Pilihan yang benar-benar dijalankan | Hasil |
| --- | --- | --- | --- |
| `.e2e-poco-operations-trial4/report.json` | `01a13bb6-bccc-7ba3-b6db-aa218847167d` | 11 Operations desktop, versi sebelum extension | 11 PASS |
| `.e2e-full-desktop-r2/report.json` | `01a13bcc-a698-7c7e-95de-5cd4a75660ee` | Seluruh 26 test desktop pada versi run tersebut | 26 PASS |
| `.e2e-extensions-mobile-r1/report.json` | `01a13bd2-bfee-79b3-859b-42ee91b25b54` | 5 admin/security + 14 Operations pada mobile 390 | 19 PASS |
| `.e2e-financial-desktop-r1/report.json` | `01a13bd7-81f5-7e08-b0d7-8a4501b60f71` | 1 financial desktop | 1 PASS |
| `.e2e-complete-cycle1-desktop-1366/report.json` | `01a13c23-e046-7757-9d73-d341a54a7301` | Seluruh 29 core desktop pada freeze terbaru | 29 PASS, 0 fail/skip/flaky |
| `.e2e-complete-cycle1-wide-1920/report.json` | `01a13c2f-64fd-7c4e-9bb9-be4f1677afe6` | Seluruh 29 core wide pada freeze terbaru | 29 PASS, 0 fail/skip/flaky |
| `.e2e-complete-cycle1-mobile-390/report.json` | `01a13c39-23f1-7abf-aa7a-507c684af744` | Seluruh 29 core mobile pada freeze terbaru | 29 PASS, 0 fail/skip/flaky |
| `.e2e-complete-cycle2-desktop-1366/report.json` | `01a13c42-edc3-7316-95de-d4eed47bc9df` | Seluruh 29 core desktop, siklus kedua fresh | 29 PASS, 0 fail/skip/flaky |
| `.e2e-complete-cycle2-wide-1920/report.json` | `01a13c4d-182f-711d-84b8-d8756e4f2761` | Seluruh 29 core wide, siklus kedua fresh | 29 PASS, 0 fail/skip/flaky |
| `.e2e-complete-cycle2-mobile-390/report.json` | `01a13c57-d994-7e9d-b084-e254b056f1a2` | Seluruh 29 core mobile, siklus kedua fresh | 29 PASS, 0 fail/skip/flaky |
| `.e2e-final31-cycle1-desktop-1366/report.json` | `01a13c6e-e8e8-7427-a1e4-561813e72eef` | Seluruh 31 versi gabungan desktop, siklus pertama | 31 PASS, 0 fail/skip/flaky |
| `.e2e-final31-cycle1-wide-1920/report.json` | `01a13c79-41ee-7a3f-83ad-1e720ee73909` | Seluruh 31 versi gabungan wide, siklus pertama | 31 PASS, 0 fail/skip/flaky |
| `.e2e-final31-cycle1-mobile-390/report.json` | `01a13c83-bc6e-7e14-8d47-929741ac8213` | Seluruh 31 versi gabungan mobile, siklus pertama | 31 PASS, 0 fail/skip/flaky |
| `.e2e-final31-cycle2-desktop-1366/report.json` | `01a13c8d-cc80-715f-b69f-33115c0e88f7` | Seluruh 31 versi gabungan desktop, siklus kedua fresh | 31 PASS, 0 fail/skip/flaky |
| `.e2e-final31-cycle2-wide-1920/report.json` | `01a13c9c-6b14-7a4a-b630-fa061aa938a3` | Seluruh 31 versi gabungan wide, siklus kedua fresh | 31 PASS, 0 fail/skip/flaky |
| `.e2e-final31-cycle2-mobile-390/report.json` | `01a13ca6-dc25-7180-b8a6-0919381231cd` | Seluruh 31 versi gabungan mobile, siklus kedua fresh | 31 PASS, 0 fail/skip/flaky/interrupted; exit 0 |

Status final31: siklus pertama 93 PASS dan siklus kedua 93 PASS, total 186. Keenam run memiliki 31 selected/executed, zero fail/skip/flaky/interrupted dan exit 0. Ini enam eksekusi penuh, bukan penjumlahan hasil trial parsial.

Report extension dan financial juga mencantumkan test yang tidak dipilih sebagai skipped; test tersebut bukan bukti kelulusan pada run tersebut. Hasil lama desktop 26 tidak mencakup semua tambahan yang menjadikan jumlah deklarasi final 29.

Report core lama memiliki hingga 31 discovered tetapi hanya 29 selected/executed; proof/billing-job saat itu tidak dipilih. Keenam report final terbaru memiliki seluruh 31 selected/executed. CSV browser tercakup export pada Operations; tambahan keamanan CSV memiliki 30 assertion backend, bukan file browser baru yang menambah jumlah 31. Riwayat 174 tidak ditambahkan ke 186 final31.

## Status core versi freeze terbaru

Semua aksi pada matrix berikut, termasuk 14 Operations, financial, proof dan billing-job, didukung dua siklus final31 PASS pada ketiga viewport. Label trial4/mobile extension pada baris tertentu menyimpan provenance awal; dukungan terbaru adalah enam report final. Setiap run memakai schema fresh. Jumlah total PASS tidak memperluas cakupan ke integrasi eksternal atau data riil yang tidak diuji.

Runner membuat schema `gulhouse_test_*` terisolasi. Hasil berada di output khusus `.e2e-poco-operations`, supaya tidak berkonflik dengan suite WASP atau keamanan. Percobaan pertama dengan output bersama gagal EPERM sebelum test dimulai; itu kegagalan runner, bukan hasil produk.

## Matrix aktual

| Modul/aksi | Test browser | Status terakhir |
| --- | --- | --- |
| Maintenance tambah/edit/status/filter/reset/export/delete | operations maintenance | PASS trial4 |
| Inventaris tambah/edit/jumlah/kondisi/filter/export/delete | operations inventory | PASS trial4 |
| Komplain tambah/edit/cancel/filter/export/delete | operations complaints | PASS trial4 |
| Fasilitas tambah/edit/kondisi/tindak lanjut/filter/export/delete | operations facilities | PASS trial4 |
| Housekeeping tambah/edit/selesai/filter/export/delete | operations housekeeping | PASS trial4 |
| Audit bulanan tambah/edit/hasil/tindakan/filter/export/delete | operations audits | PASS trial4 |
| Keuangan tambah/edit/nominal/paid/filter/export/delete | operations finance | PASS trial4 |
| Notification draft/manual tambah/edit/cancel/filter/export/delete | operations notifications | PASS trial4; tidak ada pengiriman eksternal |
| Template tambah/edit/jenis/isi/filter/export/delete; klik preview dan kembali | operations templates | PASS trial4 |
| Komplain escalate dua kali tanpa duplikat -> maintenance done -> komplain resolved -> satu expense unpaid/paid | linked complaint/expense | PASS trial4 |
| Laporan November/Desember; sewa 0, biaya 75.000, selisih -75.000; CSV periode | linked complaint/expense | PASS trial4 |
| Dashboard November/Desember: biaya sesuai periode, tidak mencampur saldo sewa/deposit | linked complaint/expense | PASS trial4 |
| Dashboard pencarian kamar dan shortcut Komplain/Reminder berfungsi | dashboard destinations | PASS trial4 |
| Pencarian metrik laporan, no result dan CSV tepat satu hasil | linked complaint/expense extension | PASS mobile extension |
| Shortcut dashboard finance/maintenance sesuai role; logout/login dan navigasi Komplain maintenance | dashboard role restrictions | PASS mobile extension |
| Input quantity negatif, done tanpa tanggal selesai, expense nol ditolak dengan data awal dipertahankan | operational validation | PASS mobile extension |
| Batal konfirmasi simpan/hapus maintenance mempertahankan data | operations maintenance extension | PASS mobile extension |
| Template Aktif off/on, Print nonaktif disembunyikan, preview aktif kembali | operations templates extension | PASS mobile extension |
| Filter periode Keuangan bulan berisi -> kosong -> kembali | operations finance extension | PASS mobile extension |
| Reminder metadata queued -> failed -> sent manual, waktu tersimpan, CSV status | manual reminder states | PASS mobile extension; tidak mengirim pesan |

## Pemetaan suite lain ke alur bisnis

| File test | Alur yang dibuktikan | Run selesai yang mendukung |
| --- | --- | --- |
| `gulhouse-admin.e2e.ts` | Property/type/room CRUD dan konfirmasi batal; file foto asli/preview/rotate/delete; akun edit/disable/self-protection; viewer/CSRF; password reset | Desktop26 untuk empat test awal; mobile19 untuk kelima test termasuk reset |
| `gulhouse-lifecycle.e2e.ts` | Booking, follow-up, kontrak/dokumen/deposit/check-in, billing, pembayaran parsial/pelunasan, kuitansi, renewal, checkout | Desktop26 |
| `gulhouse-tenants.e2e.ts` | Input penghuni manual, cascade pilihan kamar, validasi data, draft dan pembatalan terjaga | Desktop26 |
| `gulhouse-portal.e2e.ts` | Modal/FAQ/map/gallery landing; akses penghuni/periode/komplain/logout/isolasi; periode tanpa invoice; file dokumen asli/unduh/invalid MIME/delete | Desktop26 |
| `gulhouse-master.e2e.ts` | Login/dashboard serta semua halaman dan batas overflow viewport | Desktop26 |
| `gulhouse-journey.e2e.ts` | Journey booking dan input operasi lintas menu | Desktop26 |
| `gulhouse-financial.e2e.ts` | Pembayaran edit/move/delete, invoice asal/tujuan berubah; Print kontrak substitusi nama/tanggal/tarif; deposit received/edit/credit adjustment/deduction/refund dan saldo periode | Financial1 desktop |
| `gulhouse-payment-proofs.e2e.ts` | Bukti pembayaran lewat file input, unduh privat, validasi file dan penghapusan sesuai hasil test | Enam run final31 PASS |
| `gulhouse-billing-job.e2e.ts` | Kontrak aktif dibuat lewat browser, job bulanan dipicu melalui CLI pada schema uji, invoice/due date/idempotence terlihat di UI | Enam run final31 PASS |

Financial fixture menguji received Rp1.000.000 diubah Rp900.000, credit adjustment Rp50.000, deduction Rp25.000 dan refund Rp25.000 sehingga saldo Rp900.000; refund berlebih dan refund saat sewa belum lunas ditolak. Perpindahan pembayaran memperlihatkan invoice asal unpaid dan tujuan partial, kemudian delete membuat tujuan unpaid. Status tersebut benar-benar dibaca di UI; cakupan angka kas lintas periode dan rincian payment allocation tidak boleh diasumsikan hanya dari badge.

Review aturan deposit terhadap DOCX memicu perbaikan tambahan: refund langsung sekarang meminta checkbox pemeriksaan akhir yang tidak otomatis tercentang, alasan/referensi tersimpan, seluruh invoice relevan lunas dan saldo mencukupi. Tidak digunakan pengecualian refund admin yang tidak ada di sumber. Checkout tetap alur utama penutupan; berkas sumber tidak memaksa status ended sebagai satu-satunya syarat teknis. Financial test diperluas dengan negative missing-inspection/reason/debt dan positive setelah kewajiban selesai. Standalone financial PASS yang lebih awal mendahului pengetatan tersebut; hasil versi final mengikuti restart runner frozen.

## Temuan dan perbaikan

Audit awal menemukan kolom pencarian dashboard tidak memiliki form dan tombol ID/Mail/Bell tanpa aksi. Perbaikan dashboard mengarahkan form pencarian ke Kamar dengan parameter q, mengganti Mail/Bell dengan link Komplain/Reminder sesuai hak akses, dan ID menjadi indikator bahasa. Finance/maintenance tidak diberi shortcut yang tidak berhak mereka akses. PHP lint lulus; status interaksi browser mengikuti hasil suite, bukan lint.

Trial1 output terpisah selesai 1 PASS dan 10 FAIL. Sembilan FAIL CRUD adalah asumsi test tentang casing: SDK membaca teks status yang sudah memakai transform CSS uppercase. Assertion diperbaiki untuk membandingkan lowercase tanpa mengubah status bisnis. Satu FAIL alur komplain berasal dari sesi yang hilang ketika direktori session diperbarui agent keamanan di tengah run; rerun fresh tanpa login otomatis diperlukan untuk membuktikan kestabilan.

Targeted trial2 maintenance melewati create, edit, filter dan unduhan CSV, tetapi gagal membaca path file karena SDK mengembalikan path artifact relatif terhadap attempt. Resolusi path diperbaiki di test, dengan pemeriksaan file hasil browser ditemukan tepat satu kali. Targeted trial3 maintenance lulus seluruh create/edit/filter/reset/CSV/delete: run `01a13bb4-fc37-72b7-bc4f-d0ab996d36ba`, schema `gulhouse_test_20261015_013744_007`, output `.e2e-poco-maintenance-trial3`, durasi test 56,81 detik.

Full regression trial4 memakai schema fresh `gulhouse_test_20261015_013926_160`, run `01a13bb6-bccc-7ba3-b6db-aa218847167d`, output `.e2e-poco-operations-trial4`, dan berhenti pada failure pertama. Hasil aktual seluruh 11 test PASS; waktu test total 412,43 detik, runner 457,71 detik. Host yang sibuk memperlambat trial, tetapi fresh run ini tidak membutuhkan login otomatis untuk menyembunyikan session loss. Trace setiap test tersimpan. Preview template dilakukan dengan click dan Kembali pada UI produk same-tab.

Sesudah trial4, audit menemukan Cari pada Laporan diabaikan backend. WASP memperbaiki filter metric/value/notes; test diperluas untuk search/nofound/filtered CSV. Dua test baru role dashboard dan negative input menjadikan file berisi 13 deklarasi. Tambahan tersebut kemudian lulus dalam run extension mobile. Hasil 11 PASS tetap hanya berlaku untuk versi/cakupan trial4. Untuk runner pusat gunakan `GULHOUSE_E2E_OUTPUT` sesuai `--output`, karena path download SDK relatif terhadap direktori attempt.

Audit cakupan lanjutan menambah cancel save/delete khusus Operations, toggle template aktif, filter periode finance dan satu test status reminder manual. Seluruh 14 deklarasi lulus pada extension awal dan keenam run final. Pada guard template, audit menemukan `get_document_template()` belum memfilter is_active dan link Print masih selalu muncul. WASP menambahkan filter aktif serta menyembunyikan Print untuk template nonaktif. Test tidak mengubah expectation untuk meloloskan keadaan lama.

Fixture gabungan portal mempunyai sewa dan expense November 2026. Karena itu test numeric linked complaint kini memakai November/Desember 2086 pada desktop, 2087 pada wide dan 2088 pada mobile; tanggal expense memakai tahun yang sama. Sewa tetap diassert nol, expense tepat Rp75.000, net tepat minus Rp75.000 pada periode khususnya, tanpa mengurangi assertion. Setiap runner harus membuat schema fresh; mengulang target yang sama dalam schema lama dapat meninggalkan expense sebelumnya. Tahun test bukan periode riil dan tidak menjadi default laporan bisnis.

Test reminder mengubah metadata administratif sintetis, memeriksa tidak ada tombol Send serta request browser ke domain provider yang dikenal. Ini bukan bukti pengiriman server atau integrasi WhatsApp; isi fixture menyatakan tidak ada delivery riil dan row dihapus melalui UI sesudah test.

## Belum dicakup suite POCO

- Preview template kini memakai same-tab, sehingga suite trial4 melakukan click Print, memeriksa isi preview dan klik Kembali. Status mengikuti hasil trial4. Dialog cetak native atau file PDF dari printer belum diverifikasi dan tidak disamakan dengan preview HTML.
- Pengiriman WhatsApp eksternal, provider pembayaran online dan tanda tangan elektronik tidak diuji dan tidak diklaim tersedia.
- Foto/file dokumen/integritas unggahan, booking/kontrak/billing/payment lifecycle, admin/role/password serta master-data adalah kepemilikan suite agent lain; hasilnya harus dicantumkan dari bukti suite tersebut.
- Angka fixture bukan data riil; konflik sumber tarif, apartment dan issue migrasi tetap tercatat dalam dokumen data gap.

## Addon yang disiapkan sesudah freeze core

README meminta bukti transfer dalam histori pembayaran dan tagihan bulanan otomatis. Core yang diuji sebelumnya mencatat pembayaran manual/kuitansi dan menyediakan tombol Generate Billing; upload dokumen kontrak bukan bukti transfer yang terikat langsung pada payment, dan tombol generate bukan scheduler yang sudah aktif. Dua gap ini memicu addon berikut, yang tidak termasuk 87 PASS core cycle1:

| Addon | Berkas implementasi | Status verifikasi |
| --- | --- | --- |
| Bukti pembayaran privat per transaksi | `PaymentProofs.php`, `Payment_proof_model.php`, view `payment_proofs/index.php`, patch tabel, test browser/backend dan fixture file | Backend 13 PASS dan enam run final31 PASS |
| Billing CLI | Controller `Jobs.php`, runner PHP/PowerShell dan installer scheduler preview/apply dengan target schema eksplisit, lock per schema/periode, timezone Asia/Jakarta | CLI 17 PASS, enam run final31 PASS dan smoke OS selesai; scheduler demo Ready, scheduler riil tidak dipasang |
| Export CSV aman untuk spreadsheet | Sanitasi CSV terintegrasi, test backend dan browser export | Backend 30 PASS dan export pada enam run final31 PASS |

Model proof menyimpan evidence tanpa mengubah amount atau status payment. Verifikasi harus memakai file input asli, memastikan file valid dapat diunduh privat dengan bytes yang benar, invalid MIME/ekstensi/size ditolak, penghuni/peran lain tidak mendapat akses tidak sah, payment move tetap membawa evidence ke transaksi yang sama, serta delete proof/payment membersihkan metadata dan file secara konsisten.

Review read-only sebelumnya mendapati ketidakjelasan hasil delete setelah unlink gagal. WASP memperbaiki dengan journal privat sesuai schema sebelum commit, hasil `deleted` terpisah dari `cleanup_pending`, warning UI dan audit, serta retry cleanup yang mempertahankan file dengan metadata masih aktif. Filesystem failure/retry 10 PASS dilaporkan WASP; integrasi HTTP/backend dan browser final telah lulus. Tidak diklaim bahwa transaksi database dapat di-rollback sesudah commit.

Preview Jobs melaporkan periode/schema, jumlah bill yang ada dan pending cleanup tanpa mutasi; bukan proyeksi nominal invoice baru. Apply memakai generator bisnis yang sama dengan UI, membedakan jumlah generated/updated dari row bill baru dan menjalankan cleanup setelah billing berhasil. Guard real schema memerlukan opt-in eksplisit. Installer jadwal default hanya memvalidasi XML preview; `-Install` diperlukan untuk pendaftaran. Koneksi WhatsApp eksternal dan pembayaran online tetap tidak termasuk addon ini.

## Smoke Task Scheduler Windows yang benar-benar dieksekusi

Agent keamanan melaporkan task sementara pada schema fresh `gulhouse_test_scheduler_20261014_220901_036` didaftarkan hidden/LeastPrivilege, dijalankan melalui Start Scheduled Task, lalu selesai kembali Ready dengan LastTaskResult 0. Log `.deploy/billing-logs/gulhouse_test_scheduler_20261014_220901_036.jsonl` dibaca langsung dan memuat ok true, apply periode 2026-10, timezone Asia/Jakarta, 69 generated/updated rows serta 69 row baru, cleanup pending false.

Fixture ini kosong dari stay/kontrak chargeable: 69 row katalog/placeholder tidak berarti 69 tagihan sewa; jumlah chargeable nol menurut pemeriksaan agent keamanan. Task sementara yang tepat sudah unregister dan ketiadaannya diverifikasi. Task demo akhir tetap Ready untuk next run 1 November 2026 pukul 09.00 dan tidak diganti oleh smoke. Pemeriksaan OS ini dilaporkan terpisah; bukan test browser PASS dan tidak ditambahkan ke rencana 186 final31.

## Pemeriksaan akhir sebelum penyerahan

Snapshot `.e2e/final-verification-20261015.json` mencatat verifikasi pada 15 Oktober 2026 pukul 06.13.14: 186 browser PASS; backend 240; CLI 17; filesystem 10; lint memeriksa 137 berkas dengan nol syntax error. Angka lint mencakup log runtime dan tidak disebut 137 source files. Hash 129 berkas source yang dibekukan tetap identik; 34 log runtime dari manifest awal dikeluarkan dengan benar dari perbandingan source.

Jumlah data pada seluruh 29 tabel riil dipertahankan, termasuk 51 tenant, 64 stay, 69 kamar, 515 payment, 897 bill dan 0 proof. Demo port 8034 mengembalikan HTTP200 untuk login dan landing. Scheduler demo tetap Ready, next run 1 November 2026 pukul 09.00. Tidak ada scheduler/CLI riil yang dijalankan. Cutoff pembayaran riil tetap September 2026 dengan 254 issue import; provider pembayaran online/WhatsApp dan tanda tangan elektronik tetap belum terhubung.

Hasil kelulusan berlaku untuk 31 kasus dan versi source yang telah dibekukan. Jumlah total test tidak memberikan PASS untuk aksi yang tidak benar-benar dilakukan.
