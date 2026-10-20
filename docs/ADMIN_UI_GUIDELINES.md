# Pedoman UI admin GulHouse

Status: **diimplementasikan; QC PASS untuk cakupan web yang diuji**, 20 Oktober 2026. CIKO memeriksa secara independen. Lihat [laporan QC](ciko-admin-ui-audit-20261020.md). Ini bukan klaim seluruh interaksi bisnis telah diuji.

## Cakupan sumber

Sidebar `master/application/views/partials/sidebar.php` memuat 25 rute sebelum pembatasan role:

| Keluarga | Rute | View utama |
| --- | --- | --- |
| Ringkasan | dashboard | dashboard/index.php |
| Master | properties, room-types, rooms, tenants, photos, payments | manage/*.php |
| Siklus hunian | lifecycle | lifecycle/index.php dan detail.php |
| Operasional | booking, contracts, documents, templates, billing, receivables, deposits, finance, reports, maintenance, inventory, complaints, facilities, housekeeping, audits, notifications | operations/index.php |
| Akun | users | users/index.php |

State tambahan: `booking/retention/{id}`, `lifecycle/detail/{id}`, `paymentproofs/index/{id}`, dan login. Aksesnya tetap mengikuti role/backend. Halaman cetak kontrak, kuitansi, dan formulir fasilitas tetap memakai layout cetak; halaman error framework tidak memakai shell admin.

## Satu lapisan bersama

Urutan stylesheet: `master.css`, stylesheet khusus halaman bila ada, lalu `admin-guidelines.css`. `admin-guidelines.js` hanya mengelola navigasi dan perilaku presentasi; tidak mengirim mutasi bisnis.

Aturan bersama dipasang pada body `.gh-admin-ui`. Perubahan halaman baru mengikuti lapisan ini daripada menyalin aturan halaman lain. Cache aset bersama memakai versi yang sama pada seluruh head admin. Jangan mengubah nama field, action, CSRF, atau kontrak API untuk memperbaiki layout.

## Pedoman komponen

- Teks dasar admin 14 px; input teks/select dan tombol minimum 44 px. Label tetap terlihat; placeholder tidak menggantikan label. Checkbox/radio memakai kontrol native dan label; audit ini tidak menyatakan setiap elemen interaktif memenuhi ukuran target atau seluruh WCAG.
- Input editable berlatar putih. Input readonly berwarna abu-abu lembut dengan teks kontras; readonly bukan disabled dan nilainya tetap dapat disalin.
- Filter mengikuti lebar konten, bukan jumlah kolom dengan minimum yang memaksa halaman melebar. Kontrol dan label mempunyai `min-width: 0`.
- Pencarian dashboard memiliki label sendiri, bidang fleksibel, dan tombol Cari yang tidak menyusut. Header boleh membungkus ke baris baru.
- Tombol aksi tabel membungkus dengan jarak konsisten. Teks tombol tetap utuh. Tabel lebar bergulir di `.table-wrap` atau `.lc-table-wrap`.
- Jangan memakai `overflow-x: hidden` pada body untuk menutupi cacat layout. Nama file, referensi, dan teks panjang dibungkus dalam area yang sesuai.
- Modal memiliki ruang tepi dan scroll vertikal internal, judul terbaca, tombol tutup yang jelas, serta grid formulir adaptif. Form readonly tidak memperoleh tombol simpan.
- Pada lebar sampai 980 px, sidebar dilipat melalui tombol yang menyatakan `aria-expanded` dan `aria-controls`. Tanpa JavaScript, menu tetap dapat diakses. Desktop mempertahankan sidebar.
- Tab/filter foto dan navigasi bagian detail membungkus atau mempunyai scroll internal yang jelas; tidak memperlebar body.
- Loading, kosong, error, sukses, konfirmasi finansial, dan pembatasan role tetap mengikuti implementasi masing-masing halaman.

## Invarian revisi terakhir

- Booking selesai hanya menutup status tindak lanjut; tidak membuat reservasi.
- Harga/deposit reservasi dan rincian Bill readonly; tanggal mulai reservasi baru tetap editable dan batas periode otomatis satu bulan kalender.
- Pembayaran baru mengikuti quote sisa sewa/deposit server dan Transfer; transaksi historis hanya mengizinkan perubahan catatan.
- Perubahan kamar terbatas sebelum check-in; tarif, konflik periode, transaksi, dan persetujuan tetap diperiksa backend.
- Reset akses, dokumen privat, pemeriksaan final, denda, tindak lanjut H+7, checkout, dan refund tidak dilonggarkan oleh UI.
- Header, filter, tab, dan modal tidak mengirim transaksi saat sekadar dibuka.

## Pemeriksaan

Target viewport: ponsel 390 px, tablet 768 px termasuk lebar peralihan 980/981, dan desktop 1280/1440 px. Pemeriksaan dimensi membedakan scroll tabel yang disengaja dari overflow halaman.

CIKO memeriksa secara independen menggunakan fixture lokal. Dokumentasikan rute yang benar-benar dibuka, role, modal/state, screenshot, serta hal yang belum diuji. Ketersediaan tombol tidak membuktikan otorisasi backend. Pemeriksaan GET/click bukan pengujian save/delete, build Android, atau deployment.

Bukti dan backup batch ini berada di `.e2e/admin-ui-guideline-20261020/`. Aset terkini setelah tindak lanjut header: `admin-guidelines.css?v=20261020-6`, `admin-guidelines.js?v=20261020-2`, serta versi bersama `master.css/master.js` pada head yang memakainya `20261020-admin-ui-1`.

### Hasil aktual batch ini

- CIKO memeriksa **87 GET yang dirender**: 25 rute sidebar × 390/768/1280 px, ditambah empat state detail lifecycle pada tiga lebar. Halaman berada dalam viewport, tabel lebar tetap memiliki scroll internal. Pengujian ini memakai fixture lokal.
- CIKO juga membuka/menutup **105 sampel dialog dari 19 keluarga halaman**. Pemeriksaan terarah terakhir **10/10 PASS**, termasuk target aksi lifecycle, bukti transfer berisi metadata sintetis, login, dan retensi. Pemeriksaan dialog memastikan satu role dialog, siklus fokus, Escape, dan pengembalian fokus; fallback tanpa JavaScript tetap menyediakan navigasi. Angka pemeriksaan ini saling beririsan dan tidak dijumlahkan sebagai jumlah skenario unik.
- UI01 (pencarian/aksi dashboard terpotong), UI02 (menu mendahului konten), UI03 (role dialog ganda), dan UI04 (target aksi lifecycle kecil) sudah ditutup setelah verifikasi CIKO.
- Pemeriksaan sumber BUBU: 14 view PHP lint lulus, JavaScript bersama lulus pemeriksaan sintaks, nama field serta action formulir sama dengan backup. Root melaporkan 11 hash file bisnis dan snapshot 43 tabel tetap sama.

### Batas verifikasi

Tidak menjalankan penyimpanan CRUD, penghapusan, reset kode, upload, transfer, pengiriman WhatsApp, atau transaksi produksi pada audit ini. Fixture netral hanya disiapkan dalam schema uji. Isi hasil unduhan bukti dummy tidak diuji. Seluruh kombinasi role/data, kepatuhan WCAG formal, browser lain, native Android/iOS, build, scheduler, dan deployment bukan hasil audit ini. Keberhasilan GET dan perubahan visual tidak menggantikan validasi bisnis/backend.

### Tindak lanjut visual header — UI05

Audit awal memverifikasi containment dan perilaku kontrol, tetapi belum cukup menangkap ketidakkonsistenan visual baris aksi akun. Tindak lanjut berdasarkan tangkapan layar el menghapus badge ID tanpa fungsi, menyeragamkan Komplain/Reminder/Logout menjadi kontrol 44 px, radius 8 px, teks 14 px, serta memisahkan nama akun dari latar kapsul.

CIKO menyatakan **UI05 CLOSED, 3/3 PASS** pada dashboard 390/768/1280 px, disertai pemeriksaan visual manual ketiga screenshot. Nomor tujuan tautan, kondisi role, POST logout, dan CSRF dipertahankan; Logout tidak disubmit. Ini pemeriksaan terarah, bukan pengulangan seluruh 87 GET. [Laporan tindak lanjut header](ciko-admin-header-followup-20261020.md); bukti dan backup `.e2e/admin-header-actions-20261020/`.


## Audit visual lanjutan — 20 Oktober 2026

Status batch BV01–BV05: **QC PASS dalam cakupan audit visual web; seluruh lima temuan ditutup setelah verifikasi CIKO**. Lihat [laporan audit visual terbaru](ciko-admin-visual-all-20261020.md). Bukti 87 GET/105 sampel dialog di atas tetap hasil batch historis dan tidak dihitung sebagai pengujian ulang batch ini.

BUBU meninjau screenshot aktual 25 rute × 390/768/1280 px (75 pasangan halaman/lebar), ditambah screenshot bagian tabel. Ledger awal independen berada di `.e2e/admin-visual-all-20261020/bubu-initial-visual-review.json`. Bagian yang tidak tampak dalam screenshot tidak dianggap telah diperiksa. Capture state Bill, pembayaran, reservasi, booking dan dokumen pendukung diperiksa terpisah oleh CIKO setelah source dibekukan.

### Token kontrol bersama

| Peran | Nilai |
| --- | --- |
| Aksi utama | latar/border `#2563eb`, teks putih; hover `#1d4ed8` |
| Aksi sekunder | latar putih, teks `#334155`, border `#cbd5e1`; hover `#f8fafc` |
| Radius kontrol | 8 px |
| Tipografi kontrol | 14 px, bobot 500, line-height 1.4 |
| Tinggi kontrol | minimum 44 px |
| Readonly / editable | abu-abu lembut / putih, mengikuti aturan input di atas |

Aksi destruktif, peringatan dan badge status tetap memakai warna semantiknya. Badge status boleh berbentuk kapsul; tombol aksi memakai radius 8 px. Tautan inline dalam kalimat tidak otomatis diubah menjadi tombol. Identitas visual hero lifecycle tetap dipertahankan; kontrolnya mengikuti token bersama.

### Keputusan layout dan label

- Header `.topbar` tidak lagi sticky, termasuk desktop. Judul/akun/pencarian ikut bergulir agar tidak menutup tabel atau ringkasan. Sidebar desktop tetap mengikuti perilaku existing.
- Grup aksi sederhana yang menjadi anak langsung sel tabel memakai lebar intrinsik dan tidak membungkus. Tabel tetap bergulir horizontal di wrapper internal. Aturan ini tidak memaksa semua kolom melebar dan tidak diterapkan pada `.booking-retention-actions`, yang berisi formulir alur booking.
- Ringkasan yang berasal dari daftar hasil memakai label **Data ditampilkan** dan **Nominal data ditampilkan**; agregat tabel global yang sudah disediakan backend diberi label **Seluruh data**. Tidak ada perhitungan baru di view.
- Label status mengikuti subset yang memang dihitung model existing, misalnya Draf/Terkirim untuk antrean WhatsApp, Belum lunas/Lunas untuk billing, dan Diperlukan/diterima/Terverifikasi untuk dokumen. Kartu status/nominal yang tidak relevan tidak ditampilkan; laporan tidak menampilkan empat angka generik nol yang bukan ringkasan laporannya.
- State kosong memakai pesan hasil kosong/filter, tanpa mengasumsikan database belum diperbarui.

Source pemeriksaan batch baru: 13 view PHP lint lulus; nama field serta action formulir sama dengan backup; versi CSS v6 seragam pada 13 head. Ini pemeriksaan sumber, bukan bukti visual atau transaksi bisnis. Backup dan hasil pemeriksaan berada di `.e2e/admin-visual-all-20261020/`.

Tindak lanjut state kosong: pesan hasil kosong dibatasi sebagai blok teks yang membungkus di dalam sel colspan, sehingga petunjuk tetap terbaca tanpa menggeser tabel horizontal. Kolom tabel dan perilaku scroll tidak berubah.

Konfirmasi booking selesai memakai kelas eksplisit `admin-primary` untuk membedakannya dari Batal; ini hanya presentasi, tanpa perubahan POST. Pesan kosong tetap huruf normal 14 px agar tidak mewarisi gaya label status pada tabel.


### Hasil verifikasi visual terbaru

- BUBU dan CIKO meninjau baseline screenshot aktual 25 rute pada 390/768/1280 px secara independen (75 pasangan halaman/lebar). BUBU mencatat file yang benar-benar dilihat dalam ledger awalnya; pemeriksaan hanya mencakup bagian yang tampak.
- Setelah batch utama, CIKO menjalankan capture/pemeriksaan geometris baru 75 pasangan halaman/lebar, lalu meninjau manual kelompok yang terdampak dan 15 state pendukung (Bill, pembayaran, reservasi, konfirmasi booking, serta maintenance pada tiga lebar). BUBU juga meninjau ulang 52 screenshot aktual yang dirinci dalam `bubu-final-visual-review.json`.
- CIKO memverifikasi 5/5 pemeriksaan terarah setelah perubahan terakhir: konfirmasi booking pada tiga lebar serta teks kosong pada 390/1280 px. Ringkasan CIKO mencatat 35 screenshot terarah yang ditinjau manual; angka ini beririsan dengan kelompok/state di atas dan tidak dijumlahkan sebagai skenario unik.
- BV01–BV05 CLOSED; tidak ada temuan kritis/mayor terbuka dalam cakupan tersebut. Pesan kosong membungkus dengan huruf normal 14 px; konfirmasi booking mempunyai aksi utama biru dan Batal netral; readonly tetap abu-abu, editable putih.
- Root melaporkan 11 hash file bisnis serta snapshot 43 tabel tetap sama. Runtime uji milik root dihentikan setelah browser QC ditutup; Apache tidak diubah.

Capture visual dan GET/open-close tidak membuktikan penyimpanan CRUD, transaksi keuangan, semua role/data, seluruh bagian scroll, WCAG formal, browser/perangkat lain, native Android/iOS, scheduler, atau deployment. Tidak ada transaksi bisnis maupun deployment dijalankan oleh BUBU pada batch ini. Bukti baru tetap terpisah dari bukti historis sebelumnya.
