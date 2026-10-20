# Audit regresi WASP: redesign native GulHouse

Tanggal: 19 Oktober 2026. Cakupan: inspeksi baca-saja baseline vs source redesign, eksekusi fungsi UI/transport terisolasi, dan GET API sintetis HTTPS. WASP tidak mengubah mobile/backend/deploy, tidak memakai ADB, tidak mengirim transaksi, dan tidak menulis memory/Telegram.

## Hasil

**Final batch: PASS pada cakupan audit sumber/perilaku; tidak ada temuan terbuka.** Temuan minor foto R26-WASP-01 telah diperbaiki BUBU dan diverifikasi ulang. Hasil ini tidak membuktikan seluruh flow APK atau rendering tablet. ZEYN mengoperasikan perangkat; CIKO memeriksa browser secara independen.

Baseline: `.e2e/mobile/redesign-20261019/mobile-before-redesign.tar.gz`, SHA256 `5f1ee534a83cae144551bfc55cbe068c29650ef35cd10c231c3f593fc3b20e5e`. Arsip dibaca langsung melalui tar/stream; tidak diekstrak ke source atau diubah. Tidak ditemukan AGENTS.md tambahan di repo melalui pencarian `rg --files -g AGENTS.md`; profil WASP dan instruksi workspace dibaca.

## Riwayat minor R26-WASP-01: foto tidak pulih setelah URL diperbarui — CLOSED

- Lokasi: `mobile/src/screens/Catalog.tsx`, fungsi RoomPhoto, sekitar baris39-49 pada source yang dibekukan.
- Pemicu: foto pertama gagal dimuat; onError mengubah failed menjadi true. Pull-refresh kemudian memberikan cover_image baru pada kamar dengan slug/key yang sama.
- Aktual: state failed tidak direset ketika uri berubah, sehingga komponen tetap merender placeholder dan tidak mencoba gambar valid yang baru.
- Harapan: URI baru mendapat kesempatan dimuat tanpa keluar/masuk ulang layar.
- Dampak: pemulihan tampilan foto setelah gangguan jaringan atau pembaruan foto pengelola; tidak mengubah booking, pembayaran atau akses privat.
- Bukti: fungsi RoomPhoto aktual diekstrak melalui AST TypeScript dan dijalankan dengan state/React element stub terisolasi. Render pertama adalah Image, setelah onError dan URI baru render tetap View. Ini bukti fungsi/state, bukan screenshot perangkat.
- Alternatif: reset failed saat uri berubah atau gunakan wrapper berkunci uri. Temuan disampaikan ke BUBU dan ZEYN. ZEYN meminta penahanan source sampai batch temuan CIKO; WASP tidak memperbaikinya sendiri.
- Perbaikan batch final: BUBU menambahkan useEffect untuk reset failed saat uri berubah. Retest fungsi aktual dengan antrean effect membuktikan kegagalan pada URI lama tidak membuat retry loop, sedangkan URI baru kembali merender Image setelah effect. Minor ditutup pada source batch final.

## Kontrak dan fungsi sensitif yang dipertahankan

Sembilan berkas berikut identik byte-for-byte dengan baseline:

| Berkas | SHA256 source yang diperiksa |
|---|---|
| src/api/client.ts | 6d4a70821feed52a7fbb2756e9df9fd9c09c26ed7122b640c9aab81c60bc0493 |
| plugins/withNativeBack.js | e6e52428012f15c5683d57e025e7993e7ab24ceea55113480fccb89054be0b22 |
| plugins/withReleaseSigning.js | 7ec8cfa3a01a9252f87e6fd670fc91cf07224b113ddebbf4c73e0d58ab5f55ca |
| app.json | d9d867a31f78777fa720e958b5ef01fa5ffdb88ddaad0b5788fc3035a1869127 |
| src/theme/calendar.ts | a34a09b2a7d650c3dc8c2a48a26e154e672c2acb7c87837e5f1b501527233b4b |
| src/components/RequestKey.ts | a4f8279f7f0721a349521e5aefc6ecf2ac7d2252bbfc9c383c6ba8b0499a8abc |
| src/components/PhotoRotation.ts | 5518ce85bd46957aa7678e819a583161f7a0d33e0445355f409ff2137ced8b4a |
| src/components/Printing.ts | d89044158a9af2759b8276b778bda606e38a1fc1ad078271b6b3eebc580f37cd |
| src/components/SecureImage.tsx | bf7366ac78d27ac27f6e057e0662be2fcef6a57d194a7b3b651f6b89ea182149 |

Pemindaian AST pada Admin, Catalog, Dashboard, Lifecycle, Payments, Resident, PaymentLookup, App dan NativeForm menemukan **62 pemanggilan bisnis/API/navigasi** yang sama: tidak ada pemanggilan yang hilang atau berubah untuk apiGet/apiPost/apiUpload, file privat, printing, rotasi, request key, navigate, setAuthToken, atau persistSession. Pemindaian ini membandingkan ekspresi AST terformat, bukan hanya grep nama endpoint. Event binding dan tampilan tetap memerlukan pemeriksaan UI nyata.

- Multipart native tetap memakai byte-backed NativeFile adapter, nama/MIME asli dan payload JSON bersarang. Tidak kembali ke objek uri-only yang sebelumnya bermasalah pada Expo native.
- Auth tetap menangkap bearer per request, menolak URL server dengan credential, mengabaikan401 lama ketika login baru sudah ada, menyerialkan SecureStore, membersihkan token saat logout/expiry, serta memakai credentials omit.
- File privat tetap melalui bearer header, identifier string template terjaga, ekstensi/MIME mengikuti respons, cache dibersihkan. SecureImage dan PhotoRotation tetap memeriksa API base.
- Plugin Android Back dan release signing tidak berubah. Keberhasilan native Back setelah build tidak disimpulkan hanya dari identitas source ini.
- Lifecycle masih membentuk item rows menurut katalog19 fasilitas; tidak membuat kondisi good otomatis. Nilai awal uninspected/qty kosong, edit draf serta revisi mempertahankan nilai dan alasan/scan wajib. Konfirmasi tanda tangan penghuni/pengelola dan persyaratan check-in tetap pada form/handler existing.
- Request key pembayaran tetap dibuat sekali ketika form dibuka, termasuk form generic dan detail. H−15/H+2 tetap hari kalender WIB; koreksi notice, checkout, refund/dispute, nominal/proof dan pembatasan role tetap menggunakan aksi backend yang sama. Progress baru memakai payment_verified_at, profile_completed_at, actual_date, saldo refund dan disputed_amount; tidak menganggap upload bukti sebagai verifikasi pembayaran.
- NativeForm hanya menambah fokus/style pada diff yang ditinjau; validasi required, kamera/file picker, submit lock dan pengiriman field tetap. App menambah font loading dan gaya navigasi; cabang sesi/role/Back/logout tetap.
- Semua25 modul admin tetap dijangkau melalui kelompok yang dikenal dan kelompok fallback. Preview nested record diganti RecordDetails; bidang schema/options/actions tidak dirender sebagai data. Cetak dan download tetap memakai handler yang sama.

## Pemeriksaan yang benar-benar dijalankan

Pada checkpoint awal dan setelah ZEYN menyatakan freeze, **41 pemeriksaan perilaku lulus**:

| Suite existing yang dieksekusi | Pemeriksaan | Cakupan |
|---|---:|---|
| tests/native-transport.cjs | 17 | Sesi/storage/401, kalender WIB, OCR timeout, byte upload, MIME dan file privat |
| tests/mobile-native-multipart.cjs | 10 | Converter FormData Expo terpasang, nama/MIME dan byte biner utuh, kegagalan baca ditolak |
| tests/mobile-native-ui-semantics.cjs | 14 | Format keuangan/identitas dan form inspeksi baru/draf/revisi19 item |

Ketiganya dijalankan melalui inline Node wrapper: fs.writeFileSync untuk artifact `.e2e` dicegat dan hasil hanya dicetak; fs.mkdirSync artifact dinonaktifkan pada suite yang memerlukannya. Penulisan lain ditolak. Jadi file bukti milik root/CIKO tidak ditimpa. Contoh bentuk command yang dijalankan:

```powershell
@'
const fs=require('fs');
fs.writeFileSync=(p,d)=>{if(!String(p).includes('.e2e'))throw Error('Write forbidden');const v=JSON.parse(String(d));console.log({count:v.count??v.length,pass:v.pass??v.status??true});};
fs.mkdirSync=()=>{};
require('./tests/native-transport.cjs');
'@ | node
```

Wrapper yang sama digunakan untuk dua suite lain. Perbandingan baseline memakai Python tarfile/Node execFileSync tar -xOf dan TypeScript createSourceFile/createPrinter; hash memakai SHA256. Semua command dijalankan dari root repo. Simulasi fungsi tidak menggantikan JSI NativeFile atau Android fisik; suite multipart memakai disk adapter hanya pada batas JSI.

## Pembacaan HTTPS sintetis

Sebelum GET, runtime privat diperiksa di memori: schema gulhouse_test_*, isolation approved, tidak berisi occupant asli, storage isolated, schema verified, base demo cocok dengan runtime, HTTPS dan prefix staging terpisah dari API produksi. Tidak ada token/kode/nama penghuni dicetak.

Hasil GET: admin/modules menyediakan25 modul; empat fixture tahap active/intake/profile/refund dapat dibaca, masing-masing mengembalikan katalog19 item dan Bantal standar2. Fixture active mempunyai2 inspeksi; intake dan profile belum mempunyai inspeksi; refund berstatus checked_out dengan checkout record. Ini hanya memeriksa kompatibilitas data yang akan dirender, bukan mengeksekusi ulang transaksi atau flow checkout/refund.

## Batas dan handoff

Status audit bisnis sumber final: **PASS, tanpa temuan terbuka pada cakupan ini**. Tidak ada kritis/mayor yang teridentifikasi. R26-WASP-01 ditutup setelah perbaikan BUBU dan retest URI recovery. Source berubah setelah checkpoint membatalkan bagian bukti hash yang terkait dan harus dicek ulang.

ZEYN/CIKO tetap memverifikasi web export baru, screenshot/interaksi, font dan layout, scroll/keyboard/modal, build APK aktual, Android Back, kamera/upload/printing di perangkat, serta keseluruhan perjalanan penghuni/admin. WASP tidak mengklaim semua flow native telah terbukti dari41 tes sumber atau lima GET sintetis.

## Verifikasi tambahan batch final yang dibekukan

Batch terakhir menambah copy.ts untuk caption Indonesia, pemetaan label NativeForm, perbaikan foto, format nominal dashboard/admin dan atribut aksesibilitas Icon/Button. Tidak ada penulisan source/test/deploy oleh WASP.

**57 pemeriksaan perilaku total lulus pada batch final:**17 transport,10 multipart Expo,14 semantik UI existing, dan16 tambahan presentasi/enum/foto/AST. Sembilan berkas sensitif dalam tabel di atas kembali diperiksa dan tetap identik dengan baseline;62 pemanggilan bisnis kembali dibandingkan dan tidak ada perubahan/hilang.

Pemeriksaan tambahan membuktikan:

- uiOptions mempertahankan key enum asli; caption Male berubah menjadi Laki-laki tetapi objek enum input tidak dimutasi. Nilai option/ID yang dikirim tetap male/female/reserved/finance/rent, bukan caption Indonesia.
- Pilihan FK berbentuk array tetap identik dengan input; opsi nested object tidak diterjemahkan atau diganti identitasnya. Label yang tidak dikenal tetap dipertahankan.
- Transformasi sourceFields NativeForm adalah shallow clone yang mengubah label/options saja. key, type, required, value enum dan hidden request_key tetap sama; request key tidak dibuat ulang saat caption dirender.
- RoomPhoto mempertahankan placeholder untuk kegagalan URI yang sama, lalu mencoba Image untuk URI baru sesudah reset effect. Ini menutup minor tanpa menambahkan loop retry.
- Dashboard memakai font18 dan lebar penuh pada nominal di layar kecil; pembatas numberOfLines pada primary metrics telah dilepas. Admin memakai metricValue yang sama; field identitas/telepon tetap tidak diformat sebagai rupiah. Ini pemeriksaan source/formatter, bukan bukti visual pada ukuran perangkat.
- Icon bersifat dekoratif melalui accessible=false/aria-hidden, sementara tombol memakai accessibilityLabel eksplisit yang diterjemahkan untuk presentasi saja. Handler dan identitas aksi tidak berganti.

Suite mobile-native-ui-semantics existing sempat gagal pada harness ekstraksi teks: penambahan export isMoneyMetric/metricValue menyebabkan slice helper menyisipkan `export export function`. Itu bukan error aplikasi. Untuk retest14 pemeriksaan tanpa mengubah file, runner VM hanya menghapus marker module export dari snippet yang sudah diekstrak, lalu menjalankan fungsi/konstanta aktual yang sama. Seluruh14 lulus; artifact write tetap dicegat. Temuan kompatibilitas harness disampaikan kepada pemilik QC. Typecheck/build aktual tetap menjadi pemeriksaan root/BUBU.

SHA256 berkas batch final yang ditinjau:

| Berkas | SHA256 |
|---|---|
| src/screens/Catalog.tsx | 436fcc0ec798d896cdab7fbc8a710b60dcf068b65ef5759dfdb15a979c407172 |
| src/components/NativeForm.tsx | 8a7ff3c4cdde3f27418fc0e466a315a05258f59527d9ffeb26c54ac5d0472d5c |
| src/screens/Dashboard.tsx | 63646aad782e06389581ea11ea32e7357740d7bb9562c7f010d647b906722f0a |
| src/screens/Admin.tsx | 9221b22d8b2e6fdedd50610069ae1aeaf88c6472954ec36fbaadb3082c7aa745 |
| src/components/UI.tsx | 3aac04dd3dae0623e92d95f007965c518e5fa2e4f20de8916cfa725def2045b6 |
| src/theme/copy.ts | cf6b85f89136cdc3a4ad6924dd3d1fffebb749801bf5de1d25a830062b914491 |

Tidak ada ADB atau API writes pada retest final. Build lama yang dimulai sebelum batch final tidak menjadi bukti source ini; ZEYN membangun ulang setelah source dibekukan.

## Batas terbaru: APK 23b09200 dan perbaikan grid native RD03

ZEYN menugaskan audit delta baca-saja setelah perbaikan RD03 dan menyampaikan bahwa build/install serta screenshot native sudah diverifikasi pemilik perangkat. Receipt lokal mencatat APK SHA256 `23b0920081cc6e2b2c769250a534c0945a49c1508ce8c081df7df7b46d39e1e3`. WASP membaca receipt; tidak mengoperasikan ADB atau mengulang klaim screenshot perangkat.

Delta source terbatas pada useGridWidth.ts baru dan Admin/Dashboard/Catalog: ukuran grid memakai onLayout aktual, pembagian lebar dibulatkan ke bawah dengan buffer, dan tinggi kartu admin100% dihapus. **12 pemeriksaan delta lulus**: delapan kombinasi lebar/kolom/gap muat dalam lebar tersedia, onLayout memperbarui lebar terukur, jitter0,2px diabaikan, width0 diabaikan, serta tinggi100% admin tidak ada. Fungsi dan hook aktual dijalankan melalui transpile/VM tanpa source/test/artifact writes; tidak ada klaim rendering dari tes ini.

Sembilan berkas sensitif dalam tabel utama kembali cocok byte-for-byte dengan baseline. AST kembali menunjukkan62 pemanggilan bisnis tanpa perubahan/hilang. Tidak ada delta API/client/plugins/config, enum, pembayaran, auth atau payload; pemeriksaan keuangan tidak diperluas atau diklaim diulang tanpa kebutuhan. Hasil57 pemeriksaan batch sebelumnya tetap menjadi konteks, sedangkan12 di atas adalah pemeriksaan baru khusus delta grid.

| Berkas grid terbaru | SHA256 |
|---|---|
| src/components/useGridWidth.ts | a65af45c3b1c70c41d6bd6cca9c9652d83db01e5a841c29ebed031744d1818ee |
| src/screens/Admin.tsx | f5566688009af98aee1d24063daa65f4c8654cacba3018c9e1f797133533287c |
| src/screens/Dashboard.tsx | ec4eaf9328f2b1c52bb3bed809cafb33072082714a8b5e975efaf40ffcd95deb |
| src/screens/Catalog.tsx | b2e43eda4c222676e002f7625f354aee0a358aa03e2a72e7afa33d8f94e545ec |

Status audit delta WASP: **PASS; tidak ada temuan baru**. Minor foto tetap ditutup. Penutupan mayor RD03 di perangkat adalah hasil ZEYN/CIKO, bukan disimpulkan WASP dari kode. Tidak ada perubahan produksi, source aplikasi, ADB, atau API writes oleh WASP.
