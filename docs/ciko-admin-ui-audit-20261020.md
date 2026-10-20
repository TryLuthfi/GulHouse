# CIKO — audit UI web admin GulHouse, 20 Oktober 2026

**FINAL: QC PASS untuk cakupan tampilan dan kontrol baca-saja yang diuji. Tidak ada masalah kritis/mayor terbuka.** UI01–UI04 sudah ditutup melalui verifikasi ulang aktual. Audit ini bukan pengesahan seluruh operasi CRUD, transaksi keuangan, native Android/iOS, atau kepatuhan WCAG penuh.

## Lingkungan dan batas keselamatan

CIKO memeriksa implementasi aplikasi secara baca-saja. Browser menggunakan Chrome headless/Playwright pada server uji52874 dan schema khusus `gulhouse_test_auto_billing_*` yang ditandai tidak mengandung penghuni asli. Login akun sintetis diizinkan. Navigasi, filter GET, membuka/menutup dialog, memilih kontrol, dan menguji keyboard dilakukan tanpa submit formulir bisnis.

Fixture netral tambahan yang disetujui ZEYN terdiri dari kamar nonpublik, calon penghuni belum reservasi, booking baru, serta satu metadata bukti sintetis dengan nama panjang. Tidak membuat/mengubah stay, bill, pembayaran, atau ledger. Metadata bukti sengaja tidak memiliki berkas fisik: tautan unduh hanya diperiksa tampilannya, tidak diklik. Percobaan awal fixture booking memakai kolom `notes` yang tidak ada; ini kesalahan harness, diperbaiki menjadi kolom `message`, bukan bug aplikasi. Satu fixture calon penghuni netral dari percobaan itu tetap berada hanya pada schema uji.

Tidak ada perubahan data operasional/produksi, unggahan dokumen bisnis, pengiriman WhatsApp, deployment, perubahan konfigurasi produksi, atau interaksi perangkat Android oleh CIKO. Semua browser/proses pengujian milik CIKO telah ditutup.

## Inventaris seluruh sidebar

Seluruh25rute berikut benar-benar dibuka pada390,768,1280px, sebelum dan sesudah perbaikan:

| Kelompok | Halaman/rute yang diuji |
|---|---|
| Utama | Dashboard `/dashboard` |
| Master | Properti `/properties`; Tipe Kamar `/room-types`; Kamar `/rooms`; Penghuni `/tenants`; Foto `/photos` |
| Sales/dokumen | Siklus Penghuni `/lifecycle`; Booking `/booking`; Kontrak `/contracts`; Dokumen `/documents`; Template Dokumen `/templates` |
| Keuangan | Billing `/billing`; Pembayaran `/payments`; Piutang `/receivables`; Deposit `/deposits`; Keuangan `/finance`; Laporan `/reports` |
| Operasional | Maintenance `/maintenance`; Inventaris `/inventory`; Komplain `/complaints`; Fasilitas `/facilities`; Housekeeping `/housekeeping`; Audit `/audits`; WhatsApp `/notifications` |
| Akses | Admin & Role `/users` |

Inventaris diambil langsung dari25tautan sidebar setelah login; tidak menghitung halaman tambahan sebagai menu baru. Bukti `.e2e/admin-ui-guideline-20261020/ciko/inventory.json`.

## Hasil pemeriksaan aktual

- **Baseline75GET (25×3)**: semuanyaHTTP200, tanpa pageerrorJavaScript/peringatanPHP yang terdeteksi; mereproduksi UI01/UI02.
- **Sesudah perbaikan75GET seluruh sidebar**: semuanyaHTTP200, tanpa body overflow, pageerrorJavaScript, atau peringatanPHP terdeteksi.
- **12GET detail siklus (4state×3lebar)**: identitas awal belum reservasi, reservasi, notice, serta kasus aktif legacy. SemuanyaHTTP200 dan body tetap muat. Sembilan tercatat di `final-results.json`, tiga sisa1280 di `narrow-results.json` setelah penyesuaian waktu tunggu harness.
- **105pembukaan/penutupan dialog aktual** pada19keluarga halaman, dari trigger yang tersedia. Properti, tipe kamar, kamar, penghuni, foto, booking, kontrak, dokumen, template, pembayaran, deposit, keuangan, maintenance, inventaris, komplain, fasilitas, housekeeping, audit, dan WhatsApp. Tidak ada error open/close yang tercatat. Ini sampel dialog, bukan setiap baris/aksi di setiap modul.
- **14kontrol unik terverifikasi**: menu Dashboard/Pembayaran/Lifecycle pada3lebar; pencarian Dashboard benar-benar mengarahkan GETke `/rooms?q=CIKO` pada3lebar; dialog Booking dapat Tab/Escape/kembali fokus dengan satu role dialog; fallback tanpaJavaScript tetap menampilkan25tautan sidebar. Bukti hasil kontrol dan uji ulang role dijelaskan di bawah.
- **8pemeriksaan sempit**: tiga detail1280, presentasi reservasi tiga ukuran, kontrol inline Users+input unggah Foto tetap tersedia, asetCSS/JS yang teramati tidak mengalami HTTPerror.
- **10pemeriksaan final**: action/tab/summary Lifecycle memenuhi tinggi minimal44px pada3lebar; halaman bukti pembayaran terisi metadata nama panjang serta tombol Unduh pada3lebar; halaman retensi dan login pada390/1280HTTP200 dan muat.

Warna computed readonly aktual `rgb(237,241,238)` dan editable putih `rgb(255,255,255)` terlihat pada dialog pembayaran/Bill. Reservasi memiliki3inputreadonly abu-abu; tanggal mulai tetap editable dan tanggal akhir readonly. Bill tidak diberi tombol penyimpanan baru. Screenshot pembayaran390 ditinjau: nilai tetap terbaca, tombol/form tidak terpotong. Checkbox kecil tidak otomatis dianggap target kecil karena label juga menjadi area klik.

Tabel yang lebih lebar dari viewport berada di wrapper horizontal. Hal itu dibedakan dari body overflow; bukan ditutup dengan `overflow-x:hidden` pada body. Tidak ada klaim bahwa setiap tabel pada setiap kombinasi data sudah diuji gestur perangkat fisik.

## Temuan dan penyelesaiannya

| ID/status | Severity/lokasi | Reproduksi dan aktual sebelum perbaikan | Harapan, dampak, alternatif, bukti ulang |
|---|---|---|---|
| **UI01 CLOSED** | MAJOR; Dashboard topbar/search/actions | Pada768body853px. `.dashboard-actions` x468,31+w384,31, kanan852,63; Logout mulai x775,25. Input Cari kamar42,86×19px pada768/1280; tombol40,45×21px; label pecah53px tinggi. | Pencarian dan aksi harus terbaca/terjangkau. Layout adaptif dan kontrol44px memperbaiki penyebab tanpa masking. Pencarian aktual GETberhasil pada3lebar; screenshot `final-dashboard-390.png`, `final-dashboard-768.png`, `final-dashboard-1280.png`. |
| **UI02 CLOSED** | MAJOR; shared sidebar/mobile shell | Pada390/768,24dari25rute memulai konten setelah menu1299px; Users1311px. Lifecycle sebelumnya memiliki collapse tersendiri. | Menu harus tersedia tanpa memaksa gulir panjang sebelum bekerja. Collapse progresif bersama kini buka/tutup, Escape/fokus,25tautan, desktop tetap terlihat, fallback tanpaJS tetap lengkap. `controls-results.json` dan `roles-diagnosis.json`. |
| **UI03 CLOSED** | MINOR; semantik modal Booking | Setelah guideline awal, outer `.modal` dan innersection sama-sama `role=dialog`, sehingga satu modal diumumkan sebagai dua dialog. | Satu dialog bernama; gunakan elemen dialog yang sudah ada. Aktual ulang satu role, Tab tetap di dalam, Escape menutup dan fokus kembali ke trigger. `roles-diagnosis.json`2PASS. |
| **UI04 CLOSED** | MINOR; tautan aksi/tab Lifecycle | Tab37,19px; Reset/Catat pembayaran22,39px tinggi, di bawah target44px yang dipakai guideline. | Beri target yang nyaman dengan padding/min-height pada tautan aksi/tab/summary, tanpa mengubah tindakan. Aktual semua tab/aksi diperiksa44px dan summary≥44px pada3lebar. `focused-final-results.json`. |

Tambahan source-review BUBU: tautan Unduh Bukti pada subhalaman belum memakai kelas aksi standar. Perbaikan kelas diterapkan dengan persetujuan ZEYN; CIKO memverifikasi kontrol populated aktual61,19px tinggi pada3lebar, href tetap menunjuk bukti sintetis yang sama, layout tetap muat. Tidak mengklaim mengunduh/menguji isi berkas.

## Bukti dan riwayat harness

Direktori bukti: `.e2e/admin-ui-guideline-20261020/ciko/`.

- `baseline.json`/`baseline-ROUTE-WIDTH.png`: keadaan sebelum edit BUBU.
- `final-results.json`:84halaman final awal (75sidebar+9detail),105dialog, semuanyaHTTP200/nooverflow/noJS/PHPwarning; entri terakhir berupa timeout harness saat menunggu `networkidle`6detik untuk detail berikutnya.
- `narrow-results.json`:8/8PASS; tiga detail tersisa diulang hanya GETdengan wait/load20detik, tidak ada masalah aplikasi.
- `controls-results.json`:12kontrol navigasi/pencarian dan fallbackPASS; assert fokus dialog yang dilakukan segera setelahEscape sempat mendahului `requestAnimationFrame`. Ini koreksi harness, bukan regresi produk. Bukti akhir `roles-diagnosis.json` menunggu frame dan membuktikan Tab/Escape/fokus/satu role dialogPASS; tidak menghapus riwayat awal.
- `focused-final-results.json`:10/10PASS setelah koreksi aksesibilitas terakhir; `proof-populated-*`, `retention-*`, `final-reserve-*`.

Screenshot yang ditinjau langsung: Dashboard390/768; siklus intake390; modal tambah pembayaran390; halaman bukti terisi390. Label, jarak, dan pemisahan readonly/editable tampak jelas. Screenshot semua rute tersedia, tetapi inspeksi visual manual setiap piksel seluruh halaman panjang tidak diklaim.

## Batas yang belum diverifikasi ulang

Audit menggunakan akun superadmin sintetis; seluruh matriks izin tiaprole tidak diulang karena tugas ini tampilan. Tidak menyimpan formulir CRUD, membuat pembayaran, melakukan transfer bank, memproses refund, menghapus/mereset kode penghuni, mengunggah/mengekstrak dokumen, mencetak PDF, mengeksekusi scheduler, atau mengirim WhatsApp. Baris kosong, foto kosong, dan bukti dummy tidak dijadikan klaim keberhasilan tindakan bisnis terisi. Pengujian keamanan/keuangan terdahulu tidak disajikan sebagai rerun. NativeAndroid/iOS, semua browser, seluruh ukuran/isi ekstrem, audit pembaca layar, dan kepatuhan WCAG penuh berada di luar cakupan.

Tidak ada kritis/mayor yang tersisa dari pemeriksaan utama ini. Rekomendasi penutupan: **PASS cakupan UI web admin yang diuji**, dengan batas tersebut.

## Tindak lanjut koreksi visual dari el — UI05

Sesudah audit utama, el menunjukkan ketidaksamaan bentuk aksi header (IDlingkaran, tautanpill, kapsulakun besar). Ini tidak tertangkap oleh hasil geometryPASS awal; keterbatasan tersebut diakui. BUBU memperbaiki gayaheader dan CIKO melakukan pemeriksaan sempit aktual3lebar sekaligus meninjau ketiga screenshot. UI05 kini CLOSED: Komplain/Reminder/Logout44px, radius8px, font14px; nama akun tanpa kapsul; IDinert dihapus; kondisi role/href dan logoutPOSTCSRF tetap. Rincian serta batasnya di `docs/ciko-admin-header-followup-20261020.md`. Tidak mengklaim pengulangan seluruh matriks25halaman.
