# Audit visual seluruh admin GulHouse — CIKO

**20 Oktober 2026 · FINAL QC PASS untuk cakupan yang diperiksa.**

BV01–BV05 sudah diperbaiki dan diverifikasi ulang. Tidak ada masalah kritis atau mayor terbuka. Seluruh browser dan proses pengujian milik CIKO telah ditutup.

## Cakupan dan cara pemeriksaan

Audit diulang setelah el menunjukkan bahwa hasil pemeriksaan geometri sebelumnya belum menjamin konsistensi visual header. CIKO benar-benar membuka **75 screenshot baseline: 25 rute × 390, 768, dan 1280 px**. BUBU melakukan peninjauan visual independen terpisah dan memiliki perubahan implementasi. Peninjauan BUBU tidak menggantikan peninjauan CIKO.

Setelah perbaikan, seluruh 75 kombinasi rute dan ukuran diperiksa kembali melalui browser. CIKO meninjau manual **35 screenshot target** yang mencakup area terdampak dan state penting. Angka ini dibedakan dari 75 screenshot baseline; tidak diklaim bahwa seluruh 75 gambar final diulang secara manual.

Browser memakai server uji port 52874 dan schema khusus `gulhouse_test_auto_billing_*`, yang berisi katalog, akun, serta kasus sintetis. Audit ulang ini tidak menulis data aplikasi. Login akun uji diperbolehkan; formulir bisnis, tombol konfirmasi, pembayaran, unggahan, logout, dan pengiriman WhatsApp tidak disubmit. CIKO tidak mengedit implementasi aplikasi.

## Matriks halaman yang ditinjau

Setiap baris berikut telah ditinjau langsung oleh CIKO pada **ketiga ukuran layar**. State kosong yang sah tidak dianggap sebagai bukti keberhasilan aksi dengan data terisi.

| Halaman / rute | State pada fixture | Catatan peninjauan |
|---|---|---|
| Dashboard `/dashboard` | Ringkasan dan katalog terisi | Header, pencarian, angka, filter, serta konten saat menggulir |
| Properti `/properties` | 4 properti | Ringkasan, filter, tabel, dan aksi |
| Tipe Kamar `/room-types` | 7 tipe | Tabel horizontal dan susunan aksi |
| Kamar `/rooms` | 151 kamar katalog dan sintetis | Kepadatan baris, judul kolom, serta aksi |
| Penghuni `/tenants` | 8 stay sintetis | Nama panjang, tabel, dan susunan aksi |
| Foto `/photos` | Slider terisi; galeri belum dipilih | Kartu foto, pemilihan galeri, dan kontrol unggah |
| Siklus Penghuni `/lifecycle` | Daftar kasus terisi | Ringkasan, disclosure, filter, dan navigasi detail |
| Booking `/booking` | 1 booking netral | Filter, aksi, dan dialog konfirmasi |
| Kontrak `/contracts` | Kosong | Filter dan pesan kosong |
| Dokumen `/documents` | Kosong | Filter dan pesan kosong |
| Template Dokumen `/templates` | 5 template aktif | Makna ringkasan, tabel, serta aksi |
| Billing `/billing` | 50 tagihan ditampilkan | Cakupan ringkasan, nominal, dan tabel |
| Pembayaran `/payments` | Bill dan histori sintetis | Filter, kepadatan tabel, serta dialog |
| Piutang `/receivables` | 42 baris | Cakupan ringkasan dan tabel |
| Deposit `/deposits` | 8 entry ledger sintetis | Ringkasan dan tabel |
| Keuangan `/finance` | Kosong | Filter dan pesan kosong |
| Laporan `/reports` | Tabel metrik; nilai periode nol | Relevansi ringkasan dan keterbacaan tabel |
| Maintenance `/maintenance` | Kosong | Pesan kosong dan contoh form tambah |
| Inventaris `/inventory` | Kosong | Filter dan pesan kosong |
| Komplain `/complaints` | Kosong | Filter dan pesan kosong |
| Fasilitas `/facilities` | Kosong | Filter dan pesan kosong |
| Housekeeping `/housekeeping` | Kosong | Filter dan pesan kosong |
| Audit Bulanan `/audits` | Kosong | Filter dan pesan kosong |
| WhatsApp `/notifications` | Kosong | Filter dan pesan kosong; tidak mengirim pesan |
| Admin & Role `/users` | Form kosong dan akun sintetis | Kontrol inline serta disclosure; tidak mengisi kredensial |

Catatan fixture: tanggal `0000` pada booking berasal dari fixture netral, bukan temuan bug produk. Thumbnail slider ketiga tampak kosong pada capture, tetapi penyebab aset belum dibuktikan sehingga tidak dinyatakan sebagai bug aplikasi. Galeri belum dipilih juga tidak membuktikan semua aksi galeri terisi.

## Temuan dan perbaikan

BV01–BV05 merupakan konsolidasi temuan BUBU dan CIKO. Kepadatan baris diamati CIKO sebelum menerima kesimpulan BUBU. Masalah header saat menggulir kemudian dikonfirmasi melalui screenshot tabel. Temuan lain diperiksa dalam peninjauan 75 gambar baseline.

| ID | Severity dan bukti awal | Perbaikan dan verifikasi | Status |
|---|---|---|---|
| BV01 | Minor UX: header sticky menutupi judul kolom ketika tabel digulir pada ponsel | Header dibuat statis. Screenshot tabel Dashboard dan Kamar pada tiga ukuran memperlihatkan konten tanpa tertutup header | **CLOSED** |
| BV02 | Minor visual: bentuk tombol, radius, dan palette aksi belum seragam | Primary biru, secondary outline, radius 8 px; status dan danger tetap dibedakan. Header, halaman terdampak, dan contoh form ditinjau ulang | **CLOSED** |
| BV03 | Minor UX: tombol bertumpuk membuat baris Kamar/Penghuni sekitar 177–194 px meskipun datanya satu baris | Aksi plain dibuat tidak membungkus, dengan tabel tetap dapat digulir horizontal. Baris Kamar dan Template kembali padat pada screenshot aktual | **CLOSED** |
| BV04 | Minor copy: data kosong yang sah menyuruh pengguna memastikan patch database | Pesan diganti menjadi instruksi netral. Pembungkusan dan gaya teks diperiksa pada 390 dan 1280 px | **CLOSED** |
| BV05 | Minor makna UI: label universal Open/Aktif, Paid, dan Nominal tidak sesuai semua modul | Label disesuaikan dengan domain dan cakupan data; metrik tidak relevan disembunyikan. Template tidak lagi menampilkan Aktif/Paid yang menyesatkan; Billing menyatakan “Data ditampilkan” | **CLOSED** |

Koreksi mikro terakhir juga selesai: tombol “Ya, Tandai Selesai” kini menjadi primary, berbeda dari “Batal”. Pesan kosong tidak lagi mewarisi huruf kapital kecil dari gaya label tabel. Keduanya diperiksa ulang pada screenshot aktual, tanpa menekan konfirmasi.

## Hasil pengujian aktual

- **75/75 GET final**: seluruh rute pada ketiga ukuran mengembalikan HTTP 200. Tidak ditemukan body overflow, pageerror JavaScript, atau teks peringatan PHP pada capture.
- **15/15 pemeriksaan state**: Rincian Bill, tambah pembayaran, reservasi, konfirmasi Booking, dan form Maintenance pada tiga ukuran. Semua dibuka dan ditutup tanpa submit.
- **5/5 pemeriksaan mikro terakhir**: dialog Booking pada tiga ukuran serta pesan kosong pada 390 dan 1280 px.
- **75 screenshot baseline ditinjau manual oleh CIKO**, dengan ledger per rute dan ukuran. **35 screenshot target final atau bagian yang tidak berubah** juga ditinjau manual. Tidak semua screenshot tambahan tabel dan kontrol diklaim telah ditinjau satu per satu.

Rincian Bill tetap readonly tanpa form Save. Pembayaran mempertahankan nominal readonly, metode Transfer, dan tidak memiliki input referensi; pilihan jenis dapat diubah tanpa menyimpan. Pada reservasi, tanggal mulai editable `31 Januari 2032` menghitung akhir readonly `29 Februari 2032`. Warna readonly abu-abu dan editable putih tetap jelas.

Pada koreksi terakhir, tombol konfirmasi Booking dan Batal sama-sama setinggi 44 px, tetapi berbeda prioritas visual. Pesan kosong terbaca utuh pada lebar 270 px di layar ponsel, memakai font 14 px, bobot 400, dan huruf normal. Dialog dapat ditutup dengan Escape. Form logout tetap POST dengan hidden CSRF; nilainya tidak dibaca atau dicatat dan logout tidak disubmit.

## Lokasi bukti

Direktori: `.e2e/admin-visual-all-20261020/ciko/`.

- `manual-baseline-ledger.json`: daftar 75 screenshot yang dibuka langsung oleh CIKO.
- `capture-results.json`: baseline render; screenshot `ROUTE-WIDTH-top.png`, `controls.png`, dan `table.png`.
- `final/capture-results.json`: hasil 75 GET sesudah perbaikan utama.
- `final/manual-final-ledger.json`: daftar persis 35 screenshot target yang ditinjau manual.
- `final/state-results.json`: hasil 15 pemeriksaan state.
- `final/last-v6-results.json`: hasil lima pemeriksaan mikro terakhir.
- `final/dashboard-1280-top.png`: screenshot Dashboard bersih untuk ilustrasi, dengan **data sintetis**.

Baseline tetap disimpan ketika implementasi berubah agar bukti sebelum dan sesudah tidak bercampur. Pemeriksaan hash kode bisnis dan data operasional dilakukan ZEYN secara terpisah; CIKO tidak mengklaim menjalankan ulang pemeriksaan hash 43 tabel atau tes backend keuangan.

## Batas hasil

PASS ini berlaku untuk visual, render, dan kontrol baca-saja yang dijelaskan. Bukan bukti simpan seluruh CRUD, seluruh role, setiap baris atau data ekstrem, unggahan/scan OCR, unduhan file, cetak PDF, transfer bank/refund, scheduler, WhatsApp terkirim, native Android/iOS, atau audit WCAG formal.

Tabel horizontal dapat memerlukan scroll untuk kolom kanan; hal itu dibedakan dari body yang meluber. State kosong dan fixture tidak menggantikan pengujian transaksi nyata. Tidak ada masalah kritis atau mayor terbuka dalam cakupan audit ini.
