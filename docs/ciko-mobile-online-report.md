# CIKO — acceptance deployment online dan Android fisik

Status akhir: **QC PASS untuk journey utama Android admin/penghuni dan website/API dalam cakupan yang dibuktikan; RN-ONLINE01/02/03/04 dan RN-WEB01 CLOSED**. Otorisasi terbaru mencakup deployment online, migrasi dengan backup, lalu pengujian Android tablet. ZEYN/root memiliki seluruh perubahan produksi dan kontrol ADB; CIKO membaca source serta melakukan pemeriksaan independen endpoint/Playwright. Perangkat RR2YA006P7W tidak dioperasikan bersamaan oleh CIKO.

| Hasil final | Bukti aktual |
|---|---|
| Produksi baca/login | CIKO40/40, tanpa businessPOST |
| Website produksi | CIKO Playwright26/26; carousel firstroom5/5; cacheversion2/2 |
| Online sintetis API | CIKO fresh28/28, termasuk sengketa400k+100k tanpa menahan hak yangtidakdisengketakan |
| Android perangkat nyata | Root mencatat41PASScheckpoint final (termasuk setupulang dan pemeriksaanproduksi); CIKO mereview log/screenshot dan mengonfirmasi hasil akhir case3 melalui17/17 readonlyassertions |
| Artifact release | FinalAPK4d2ad0…70174, CIKO14/14 serta actualapksigner valid/sertifikat cocok |

**Batas final:** cakupan native utama selesai dari identitas/KTP sampai refund; seluruhCRUD25modul tidak diuji satu-persatu pada Android produksi. Produksi hanyalogin/baca; seluruhtransaksiQA memakai DB/storageonline terpisah. Uang dan dokumen/bank/identitas adalah fixture; tidak ada transferbanknyata, validasi keaslian tanda tangan atau bukti akurasi OCR tulisan tangan. Kamera/share/print diuji buka+batal; pengiriman ke penerima/printerfisik tidak diuji. iOS dan seluruhrole nativeonline belum diuji. Tidak ada critical/major terbuka dalam cakupan utama; minor discoverability adminaction sebelumprasyarat tetapdilindungi backend. Root telah melakukan cleanupakunQA/servicetest dan CIKO mereview receiptsakhir; CIKO tidak menghentikanserver.

Hasil lokal terdahulu (API, Expo React Native Web dan build APK) bukan bukti bahwa API online atau layar Android pada perangkat sudah bekerja.

## Pembagian data dan batas pengujian

| Target | Diperbolehkan | Tidak dilakukan CIKO |
|---|---|---|
| API produksi | HTTPS, katalog publik, login/logout yang secara khusus diizinkan, baca modul sesuai hak, validasi penolakan akses anonim | Intake, reservasi, biodata, unggah dokumen, perubahan kamar, pencatatan pembayaran/refund atau CRUD penghuni/keuangan produksi |
| API online sintetis terisolasi | Journey penuh dan uji negatif dengan akun/data sintetis | Memakai koneksi DB atau direktori dokumen/foto bersama produksi |
| Tablet Android | Root menguji login/navigasi dan journey sintetis nyata, bukti screenshot/log tanpa token/password | CIKO mengirim input ADB bersamaan atau mengklaim bukti perangkat dari Expo Web |

Journey online sintetis harus memiliki DB terpisah, akun sintetis, direktori dokumen/foto/token/session terpisah, path API berbeda dari produksi, dan bukti konfigurasi aktifnya. Menukar alamat aplikasi saja tidak membuktikan isolasi. Semua fixture diberi penanda QC; gambar identitas/tanda tangan/transfer sintetis bukan dokumen sah.

## Gate sebelum pengujian

1. Backup produksi privat mencakup DB, source yang akan diganti, konfigurasi relevan dan inventaris/hash dokumen existing. Reconciliation memisahkan perubahan sah bersamaan; tidak otomatis mereset atau menghapus data.
2. Verifikasi seluruh dependency runtime deployment: controller/model/library/hook/config/landing-model/lifecycle catalogue19 serta route/file terkait, bukan hanya Mobile.php. Case-sensitive filename Linux diperiksa.
3. Lint memakai PHP8.1 actual server, lalu uji melalui PHP-FPM web user; CLI lint saja tidak membuktikan extension/permission/FPM env.
4. Migrasi additive lifecycle8table lalu token. Tidak menyalin DB atau akun demo lokal ke produksi. Seluruh hash/count tabel lama diperiksa sesudah apply.
5. Web server meneruskan Authorization ke PHP; HTTPS valid; file privat/application/system/.env/.deploy/.e2e diblokir. Sumber API tetap JSON, tidak bocor warning PHP atau HTML login.
6. Produksi smoke read-only lebih dahulu; fulljourney hanya setelah runtime sintetis online dan penyimpanannya terbukti terisolasi.

## Prasyarat PHP8.1/Linux yang ditinjau

Source API memakai fitur yang tersedia pada PHP8.1, termasuk Throwable, null coalescing, variadic, JSON_INVALID_UTF8_SUBSTITUTE dan session_abort. Verifikasi nyata tetap perlu pada server.

- mysqli beserta mysqlnd/get_result, mbstring, fileinfo, session, JSON, secure random, dan direktori sesi yang dapat dipakai FPM.
- Upload/post_max dan batas Nginx/Apache mengizinkan file10MiB beserta overhead multipart; batas client/server tidak hanya diuji dengan fixture1pixel.
- Private lifecycle/documents/payment-proof directories writable oleh FPM, ditolak dari HTTP langsung; native file endpoint tetap menerapkan bearer dan scope.
- GD diperlukan untuk jalur rotasi web existing; native memakai hasil ImageManipulator dan validasi MIME/dimensi server. Foto asli tidak dipakai untuk uji rotasi.
- **OCR Linux belum otomatis siap:** default Lifecycle_model menunjuk Python Windows. Perlu GULHOUSE_OCR_PYTHON ke Pythonvenv Linux, dependency Linux numpy/Pillow/rapidocr_onnxruntime/onnxruntime/pypdfium2, proc_open diizinkan, model OCR lokal tersedia serta izin FPM. Wheel Windows dalam .tools/ocr-python tidak dapat dipindah sebagai runtime Linux.
- OCR gagal harus menampilkan fallback verifikasi manual yang jujur; hasil OCR tidak boleh mengubah kondisi/finalisasi otomatis. Verifikasi OCR pada server menggunakan scan sintetis yang dapat dibaca, bukan menyimpulkan akurasi tulisan tangan dari upload berhasil.

## Acceptance matrix yang harus diberi bukti

| Area | API online | Android fisik |
|---|---|---|
| Koneksi/login | JSON401 anonim, GET auth405, login tepat, token bisa me dan logout/revoke | API produksi dapat dijangkau tanpa localhost/ADB-reverse, loading/error/sukses, login tidak ter-reset |
| Scope | Resident/admin/finance/maintenance/viewer; file privat dan cross-case ditolak | Menu dan aksi mengikuti role; tidak ada akses silang dokumen |
| Katalog | Public room/detail/photo URL HTTPS benar | Foto, pencarian/filter, room detail dan form booking dapat dibuka |
| Identitas | KTP wajib; marriage opsional; upload MIME/size | Picker PDF/JPG/PNG, camera permission+cancel, foto aktual, keyboard dan submit; kode pribadi disimpan aman |
| Pembayaran | Proof tidak otomatis bayar; transaksi rent/deposit nyata; retry idempotent | Tombol pembayaran, nominal, jenis, bukti, kuitansi; kembali/refresh tidak menggandakan transaksi |
| Biodata | Sebelum verifikasi ditolak; sesudahnya tersimpan ke stay yang benar | Form lengkap, NIK/kontak darurat, validasi dan keyboard |
| Fasilitas | 19item, scan wajib, OCR hanya saran, final immutable/revisi | Upload/camera, scroll19item, input jumlah aktual, signature confirmations, review OCR, final/history/download/print |
| Operasional | CRUD sintetis, permission, dokumen privat, DATETIME tidak terpotong | Navigasi25modul, editor/select/toggle0, filter, upload/download, rotate image, back/scroll/touch |
| Keluar/refund | H−15 calendar, no auto penalty, final exit, H+2, proof aktual, undisputed terbayar walau sengketa | Notice, exit inspection, checkout, transfer400k saat100kdisputed, release100k, payout kedua, riwayat penghuni |
| Nonregresi | Website login Dashboard+CSRF tetap berfungsi; resource privat tertutup | App restart/restore/logout, Android back, kamera/file chooser, share/print, minimisasi/resume |

## Bukti dan hasil

Pengujian independen `tests/mobile-online-readonly.cjs` sudah berjalan terhadap `https://gulhouse.co.id/master/index.php/api/mobile` dengan akun TEMPQA khusus yang disediakan root: **40/40 PASS**. Ini mencakup katalog HTTPS/mediaHTTPS,401JSON anonim+bearer palsu,GETauth405,preflight origin tak disetujui403,6resource privat403/404,loginwebsite existing dan Dashboard200 yang benar-benar terautentikasi,login API+no-store,25modulGET limit1 dengan redaksi field privat,foto bearer berupaimageHTTPS aktual,logout+revoke. Tidak ada intake/reservasi/CRUD/pembayaran/refund bisnis produksi oleh CIKO.

Hanya status pemeriksaan yang disimpan ke `.e2e/mobile/online/ciko/production-readonly-results.json`, tanpa isi rows produksi, password, token, cookie, NIK atau dokumen penghuni. Login/logout menghasilkan perubahan autentikasi yang memang diizinkan (token/lastlogin/sesi); bukan transaksi bisnis.

Root melaporkan deployment53file,migrasi additive8lifecycle+1token serta rekonsiliasi hash/count legacy lulus. Backup,migrasi,perubahan permission dan SQL produksi merupakan tindakan root; CIKO tidak mengklaim menjalankannya.

Online sintetis sudah dijalankan setelah root memverifikasi isolasi DB/path/storage dan room khusus; hasil fresh28/28 dijelaskan di bawah. Tablet tidak dioperasikan CIKO; bukti UI fisik diambil root, dinilai terpisah dari hasilHTTP/ExpoWeb.

## RN-ONLINE01 — Major, OCR online mengembalikan500

Lingkungan sintetis online sudah diattestasi root: copied559file di rootQC terpisah, DB gulhouse_test_online_20261019_122114 dengan user khusus hanya schema tersebut, uploads/private/env/sesi terpisah tanpa symlink ke aplikasi produksi. CIKO menjalankan APIjourney pada kamar khusus yang berbeda dari tablet.

Lima belas pemeriksaan awal PASS: HTTPSauth,KTPmandatory intake,lockbiodata sebelumbayar,reservasi/invoice,proof tidakautobayar,stablepaymentretry tepat1transaksi,sewa+deposit+verifikasi,biodata lengkap,19itemscan/final awal,checkinaktif,19itemroutine draft. Kemudian `inspection_extract` case1/inspection2 mendapatkan HTTP500 JSON INTERNAL. Percobaan ulang hanya aksiOCR pada case yang sama juga500.

Harapan: OCR berjalan lalu status tetapdraft+review, atau error422 terjelaskan bila runtime belum tersedia. Aktual500 menghambat fungsi pembacaan scan; penyebab menunggu log runtime privat milik root. Alternatif perbaikan: siapkan runtime/dependency/processpermission yang benar dan uji ulang; fallback manual harus eksplisit dan tidak dilabeli OCR sukses. Produksi tidak mengalami mutasi bisnis dari pengujian ini.

Bukti asli dipertahankan di `synthetic-api-first-failure.json`; runner mendukung resume yang memastikan schema/base/room/case sama sebelum POST berikutnya. Privatepath probe terhadap .env,application/config serta log diagnostik privat pada QC prefix semuanya404; ini tidak berarti dummyfile404 membuktikan seluruhfileDOCUMENTS terlindungi.

## Penyelesaian RN-ONLINE01 dan verifikasi ulang

Root menemukan `proc_open` diblokir konfigurasi php.ini web SAPI. Root menyiapkan FPM GulHouse terpisah dengan INI/socket tersendiri, mengaktifkan hanya keluarga proc yang diperlukan, exec/system/popen tetap diblokir, tanpa mengubah baselinehostPHP lain. Guard keberadaan keempat fungsi ditambahkan sehingga runtime yang tidak siap memberi fallback422 terjelaskan. Detail perubahan server/probe fungsi adalah bukti milik root; CIKO memverifikasi perilaku endpoint aktual.

- Existing case1/inspection2: OCR kembali200 RapidOCR ONNX, tetapdraft. Fixture1pixel menghasilkan0item; hanya membuktikan engine invocation. Case1 kemudian diselesaikan melalui resume18pemeriksaan sampai refund500ribu; **bukan dilabeli fresh fullPASS**.
- Kamar case1 berstatusmaintenance setelah checkout sesuai cleaning gate; percobaan fresh berikutnya berhenti aman sebelumintake. Root mengalokasikan kamar available lain yang berbeda dari tablet.
- Fresh case2, **28/28 PASS one-shot** dari intake baru sampai dua refund dengan tiga PDFscan sintetis19fasilitas. FixtureprintedPDF diperiksa satuA4,19label dan penanda synthetic; actualLinuxRapidOCR mengidentifikasi tepat19label. Hasil OCR masihdraft sampai adminfinalisasi, kondisiACrusak manual tidak ditimpaOCR dan Bantal2 bertahan di ketigastage. Ini tidak membuktikan akurasi tulisan tangan, kondisi yang diisi manual atau keaslian tanda tangan.
- Actualrefund400ribu saat100ribu masihsengketa tetapberhasil, kemudian release100ribu+transferkedua dengan bukti sendiri, total500ribu/sengketa0. Resident mempertahankan riwayatrefundsendiri.
- Pemeriksaan readonlytambahan: manipulasiquerycase_id diabaikan, KTPantarcase404, bearerQC yang masihlive tidak bisa mengautentikasi produksi401. KetigaPDFscan/mime dan tiga finalinspection×19row diverifikasi melalui responseaktual.
- Produksi40/40 readonly/login/Dashboard/25module diulang sesudah perubahanFPM+modelguard, tetapPASS. Tidak ada transaksi bisnis produksi olehCIKO.

Artefak privat: `production-readonly-results.json`, `synthetic-api-first-failure.json`, `ocr-existing-results.json`, `synthetic-api-resume-results.json`, `synthetic-api-results.json` (fresh28), `final-online-evidence.json`, `synthetic-private-path-results.json` dalam `.e2e/mobile/online/ciko/`. Password/token/access_code hanya ada dalam privatecontext/fixtureprivate dan tidak disalin ke laporan hasil.

**Status APIonline: PASS; RN-ONLINE01 CLOSED.** Nativephysical UI tetapmilikpengujianroot pada tablet, belum diklaimCIKO dari HTTP atau ExpoWeb. Menunggu screenshot/logroot untuk independentreview tanpa mengirim inputADB.

## Review bukti Android fisik dari root

CIKO membaca `.e2e/mobile/online/tablet-production-modules.json`: tepat25hasil,25 `opened:true`,0gagal. Scope log adalah navigasi/baca produksi tanpa transaksi bisnis. Screenshot `production-admin-login.png` menunjukkan aplikasi pada tablet Android landscape dengan statusbar/taskbar sistem, halaman pengelola, kartu modul serta bottom navigation yang terbaca dan tidak terpotong. Bukti ini mendukung login/navigasi nyata; bukan bukti seluruh tombol CRUD atau journey resident.

Screenshot `tablet-intake-before-file.png` menunjukkan form sintetis dengan tanggal/survey/kamar, kontrol KTP wajib, buku nikah opsional, pilihan dokumen/kamera, persetujuan dan tombol kirim. State ini masih sebelum pemilihan file/submission sehingga tidak membuktikan file chooser selesai, camera permission atau intake tersimpan. Journey native lanjut tetap menunggu bukti root; CIKO tidak mengirim perintah ADB.

Temuan minor RN-ONLINE02: placeholder pencarian modul hampir tidak terbaca pada screenshot Android (teks sangat pucat di atas input putih). `mobile/src/screens/Admin.tsx` TextInput memiliki `s.input` dengan warna teks gelap, tetapi tidak menetapkan `placeholderTextColor`, sehingga warna placeholder mengikuti default platform/theme. Dampak: petunjuk pencarian sulit ditemukan, bukan kegagalan fungsi pencarian. Alternatif: tetapkan warna muted eksplisit untuk placeholder input native dan verifikasi screenshot Android setelah rebuild. Dilaporkan kepada root; belum diklaim fixed.

BUBU kemudian menambahkan `placeholderTextColor={c.placeholder}` pada seluruh5TextInput, warna #5E726C, serta label aksesibilitas Button dan pengecualian Icon dekoratif. CIKO membaca ulang source aktual Admin/NativeForm/UI dan mengonfirmasi perubahan tersebut. Status RN-ONLINE02: **source diperbaiki, verifikasi APK Android baru masih tertunda**; screenshot sebelum rebuild tidak digunakan sebagai bukti perbaikan runtime.

CIKO kemudian meninjau screenshot actual APK e332 `tablet-admin-search-fixed.png`: placeholder kosong “Cari modul…” sekarang jelas terbaca dengan warna muted gelap pada input putih. Layout kartu/bottomnav tidak terpotong. **RN-ONLINE02 CLOSED untuk temuan Admin tersebut**. Choice-search memakai shared warna yang sama pada source, tetapi screenshot runtime choice-search/aksesibilitas screenreader belum diberikan sehingga tidak ikut diklaim terverifikasi.

`picker-state.png` juga ditinjau: system DocumentPicker Android benar-benar terbuka. Ini membuktikan integrasi open-picker native, belum membuktikan file dipilih/kembali/upload berhasil. CIKO tidak membaca atau menyalin isi galeri pribadi sebagai fixture.

## RN-ONLINE03 — Major, multipart Android berhenti sebelum HTTP

Root menemukan melalui perangkat nyata: DocumentPicker mengembalikan `qa-ktp.png`, tetapi submit intake menghasilkan NETWORK_ERROR tanpa request mencapai server. Lokasi: native branch `mobile/src/api/client.ts::apiUpload`, bentuk lama `{uri,name,type}`. Dampak: KTP wajib tidak dapat diunggah dari Android sehingga onboarding utama terblokir; HTTP/ExpoWeb PASS tidak menutupi masalah ini.

CIKO membaca converter actual terpasang `mobile/node_modules/expo/src/winter/fetch/convertFormData.ts` dan regression upstream `__tests__/convertFormData-test.native.ts`: Expo58 secara eksplisit menolak legacy URI part dengan `Unsupported FormDataPart implementation`; converter menerima part `bytes()`. Alternatif yang diterapkan root: `expo-file-system File(uri)` lalu part `{name,type,bytes:()=>localFile.bytes()}`, web tetap menggunakan Blob.

Pemeriksaan independen `tests/mobile-native-multipart.cjs` **10/10 PASS** menjalankan converter Expo dan FormData patch actual terpasang, ditambah `apiUpload` source actual dalam branch Android. Hanya boundary NativeFile/JSI diganti adapter baca file lokal karena tidak tersedia di Node. Uji membuktikan legacy URI ditolak; PNG dan PDF baru mempertahankan seluruh byte biner, MIME, filename encoding, nested JSON/bracketfields serta boolean0/1; Content-Type dibiarkan kepada fetch agar boundary konsisten; kegagalan baca byte menolak conversion. Tidak ada request jaringan atau perubahan database/perangkat dalam regression ini.

Bukti: `.e2e/mobile/online/ciko/native-multipart-results.json`. **Status RN-ONLINE03: source patch compatibility PASS, runtime Android masih NEEDS FIX sampai APK rebuild terpasang dan intake KTP nyata mencapai server serta tersimpan**. Perbaikan source/Node tidak dilabeli sukses perangkat.

### Verifikasi perangkat dan penyimpanan setelah APK baru

Root memasang APK SHA256 `e3324f2c99e384163f496b39d6db1997be3bc5ed199e123dc0cb130ed5aeac9b`, menjalankan picker serta submit intake pada Android nyata; `tablet-journey-results.json` merekam sukses identitas+KTP+resident session. Screenshot completion mengandung kode sekali tampil, sehingga tidak diterbitkan/disalin ke laporan publik.

CIKO kemudian menjalankan `tests/mobile-online-tablet-ktp.cjs`, **4/4 PASS**, melalui HTTPS read-only selain login/logout yang diizinkan. Case3 memiliki nama fixture tablet dan room khusus tablet yang berbeda dari roomCIKO. KTP terbaru tersimpan sebagai `image/png`; kode hasil intake benar-benar mengautentikasi portal caseyangsama. Download KTP privat menggunakan bearer resident mengembalikan200 dan4443byte, SHA256 `46b76a15eac27bcd687416d8397fa1b2ccbd2a0827c97a9c471c6c2c198f6d32`, tepat sama dengan file `qa-ktp.png` yang dipilih di Android. Token pemeriksaan dilogout; CIKO tidak menulis data bisnis atau mengoperasikan ADB.

Bukti tambahan `.e2e/mobile/online/ciko/tablet-ktp-readonly-results.json`. **RN-ONLINE03 CLOSED** berdasarkan native interaction root ditambah corroboration penyimpanan independen, bukan Node regression semata. Ini menutup kegagalan multipart onboarding; belum menggantikan bukti seluruh flow pembayaran/fasilitas/checkout/refund pada perangkat.

Pemeriksaan native berikutnya yang masih direncanakan: simpan scan PDF sebagai draft19item dengan jumlahaktual kosong/uninspected, jalankanOCR/review, lalu pastikan finalisasi tetap ditolak jika verifikasi manual belumlengkap dan status bertahan draft. BUBU mengubah qty menjadi optional pada native draft sesuai model; finalguard tetap diwajibkan. Ini belum diberi PASS dari perubahan source saja. Root juga sedang menguji PDFproof resident dan transaksi pembayaran native; checkpoint reservasi yang dilaporkan root tidak disamakan dengan seluruh journey selesai.

## Coverage checkpoint native berikutnya

Verifier readonly `tests/mobile-online-tablet-lifecycle.cjs` dibuat untuk menguji case tablet yang tepat tanpa menyelesaikan langkah melalui API. `--checkpoint` menghasilkan IN_PROGRESS; mode default memerlukan seluruh finalassertion sebelum PASS. Snapshot case3 masih paid, dengan tepat1sewa1,2juta dan1deposit500ribu, verifikasi pembayaran serta PDFproof resident terverifikasi. Empat dari17assertion tersedia; sisanya belum dilabeli defect karena root masih menjalankan alur pada perangkat. Tidak ada data pribadi atau kode portal disimpan dalam output status.

| Cakupan | Bukti sekarang | Belum dibuktikan pada Android fisik |
|---|---|---|
| Produksi | Login nyata+25modul nav; independen API40reads/auth dan Dashboardweb | CRUD seluruhmodul produksi sengaja tidak dilakukan |
| Identitas/bayar | Android KTPpicker+intake; hashdownload sama; reservasi, PDFproof,1sewa+1deposit, verifikasi di checkpointroot/APIreadback | Fullprofile, kamera permission/cancel, semua varian dokumen10MiB |
| Fasilitas | APIonline sintetis3final×19+OCR19, PHPengine aktual | Native blankdraft/OCR/review, finalincomplete reject,3scan/final/history dan revisi masih berjalan |
| Keluar/refund | APIonline H+2, undisputed400k+release100k+proof pembayaran | Native notice/exit/checkout/refund/residenthistory belum selesai |
| Native OS | APK terpasang, nativepicker terbuka, placeholder kontras diperbaiki | HardwareBack major RN-ONLINE04 masih terbuka; ModalBack, rootresume, restartrestore, share/print/download/camera belum seluruhnya dibuktikan |
| Role & platform | Role restrictions diuji lokal API sebelumnya; scopeonline antarcase+environment teruji | Seluruh role native pada serveronline belum diuji; iOS tidak diuji |

Review source checkout/refund tidak menemukan pelanggaran utama: payout bergantung pada nominal tidak disengketakan yang tersisa, resolusisengketa memiliki tombol independen, selesai hanya setelah sengketa0, noticeH15/refundH2 memakai hari kalender dan tidak ada potongan otomatis. Minor discoverability: admin menawarkan notice/checkout kepada reserved/paid yang sudah memiliki stay_id; backend menolak sebelumactive/finalexit. Disarankan gating/tulisan prerequisite pada UI, bukan melemahkan guard backend.

RN-ONLINE04: root melaporkan HardwareBack menutup Activity pada depth2 di APK e332/percobaan JS berikutnya. BUBU menyiapkan native AndroidX callback setelah super.onCreate. CIKO membaca RN ReactActivity actual terpasang: callback RN memang terdaftar dalam super.onCreate, lalu callback plugin ditambahkan setelahnya; ReactDelegate.onBackPressed mengembalikanBoolean dan dispatch ke ReactHost. Sheet tetap memakai Modal.onRequestClose. Ini hanya source compatibility; **status native QC tetap NEEDS FIX sampai root membuktikan navigasi Back Android nyata, penutupan Modal, serta perilaku root/resume**.

APK callback baru SHA256 `15ed626c2dbf79d51e28c50756a8ae23942eb44f19d80c1cba3296ddd386bed2` diperiksa CIKO menggunakan Python existing dan `tests/verify-native-apk.py`: **14/14 PASS** (ZIP,2ABI,bundlemandiri,productionURL,noQA/privatefiles,packageversion,nondebuggable,cleartextfalse,backupfalse,receipthash/signaturemetadata). Selain itu CIKO menjalankan `apksigner verify --verbose --print-certs` actual dengan JDK21: APK Verifies, signatureschemev2true,1signer, certificateSHA256 `ee80504f0c58e785b210f41fcfe2d237de7dcfb3db50c2ea3eeb810cc59ed11c` cocok receipt. Pemeriksaan tambahan signature ini tidak hanya mempercayai boolean receipt. Belum membuktikan HardwareBack runtime sebelum pemasangan/pengujian root. Tidak ada inputADB.

### Penyelesaian RN-ONLINE04 dan draft guard native

Root memasang APK15ed dan menjalankan Android actual: `tablet-journey-results.json` menyimpan DashboardhardwareBack kembali ke adminhub pada06:40:28UTC, rootBack background lalu resume ke authenticatedhub yangsama pada06:40:45UTC, dan Back menutup modal finalisasitanpa meninggalkan casedetail pada06:43:21UTC. CIKO juga membaca file `android-back-native-log.txt`: `GH_BACK_NATIVE delegated=true` pada13:40:25.956 dan13:40:42.170WIB. CIKO membaca checkpoint tersimpan, meninjau screenshot portrait `tablet-back-fixed-before.png` serta source dispatch/plugin installedRN; tidak mengirim inputADB. **RN-ONLINE04 CLOSED berdasarkan pengujian perangkat milik root yang direview, bukan klaim CIKO mengoperasikan Android sendiri**.

Checkpoint16 mencatat finalisasi inspection belumlengkap ditolak walaupun konfirmasi tanda tangan sudahdiisi. Readback HTTPS independen CIKO sesudah checkpoint mengonfirmasi draft7 case3:19row,19jumlahaktualNULL,19kondisiuninspected,OCR19label,belumfinal. Ini menguatkan draftscanblank/OCRtidakmengira kondisi dan guardfinal tidak terlewati. Root selanjutnya masih mengisi hasil pemeriksaanmanual; fulljourney belum diberiPASS.

Minor tambahan screenshot Dashboard portrait: nilai monthlypotential123700000 membungkus digit terakhir ke barisbaru; beberapa nominal masih digitmentah tanpaRp/pemisahribuan dan collectionrate tanpa%. Fungsi tidak terblokir; saran formatting/mingridcardwidth untuk keterbacaan telah dikirimroot, bukan masalah mayor.

### Final artifact dan review perubahan scan-first/Dashboard

Root membangun dan memasang APK berikutnya SHA256 `4d2ad0f92c8bad34796cb25fbc825bdfacacc75c298bfaef8a40b9cea5b70174`. CIKO mengulang actualartifact **14/14 PASS** dan actual `apksigner`: Verifies/v2true/sertifikat sama `ee80504f…59ed11c`. Tidak ada inputADB. HardwareBack plugin yang telah diuji sebelumnya tidak berubah; root tetap melanjutkan pengujian pada APK terbaru.

CIKO membaca sourcefrozen dan menjalankan `tests/mobile-native-ui-semantics.cjs`, **14behaviorassertions PASS** terhadap fungsi actual yang diekstrak melalui TypeScript AST, tanpa mengubah source: currency summary termasuk potential/total/deposit memakaiRp, unpaid_bills/payment_count tetapcount, rate memakai%, phone/code/date mempertahankan identitas/leadingzero, blankmenjadi—. Ini source behavior, bukan screenshot rendering.

Form inspectionbaru tidak merender57kontrol kosong sebelumscan, tetapi tetapmembawa semua19initialvalue qtykosong/uninspected/notes kosong untuk payload. Koreksi draft mempertahankan57kontrol manual dan actualvalues, termasuk Bantal2/kondisi rusak; revisi tetaprecordbaru dengan revision_of, scanbaru danalasanwajib. Guardfinalbackend tidakdiubah. Semantics regression tersebutPASS; actualnative routine/checkoutscanfirst dan layoutDashboard final masih menunggu buktiroot. Artefak `.e2e/mobile/online/ciko/native-ui-semantics-results.json`.

## Playwright website produksi: diagnosis responsif

`tests/mobile-online-web-playwright.cjs` memakai Chromeheadless actual, publicroot HTTPS `/` (bukan pathlokal `/landing/index.php`) dan akun QA sementara yang disediakanroot. Router pengujian menolak semuaPOST bisnis; hanya login/logoutwebsite diizinkan dengan formCSRF actual. Tidak ada formbisnis dikirim atau data penghuni/keuangan diubah. Screenshot Dashboard berisi data produksi sehingga disimpan privat, tidak diterbitkan.

Run refined **13/14 PASS**: landing390/768/1440 merender publiccatalog dan gambaraktual;390/1440 tanpa bodyoverflow; masterlogin desktopDashboardactual200 dan freshmobileDashboard390; POSTlogout menghapus aksesDashboard; tidak ada pageerror,consoleerror,firstpartyCSS/JS/networkHTTPfailure (`issues:[]`). Snapshot awal setelah desktop→mobile menunjukkan1081px karena canvasChart.js masihresize; setelah geometrysettle≤5detik dan freshmobileload, body tepat390 dan takadaelemen meluber. Ini **harness timing falsealarm**, bukan masalah masterpersisten; tabeldiwrapper scrollinternal sengaja dikecualikan.

Satu minor actual stabil: landing768 memiliki body775, `.site-header .header-action` right775.09 (left680.91,width94.19), melewati viewport7px. Elemen carousel/hero yanglebihluas berada dalam clipping/scroll yangdisengaja dan bukanpenyebab. Lokasi sumber `landing/assets/landing/css/landing.css`; bukti `ciko/web/landing-768-overflow.json` dan `landing-768.png`. Detail diteruskan ke BUBU untuk patchCSSminimal; root memiliki deployment. Status websmoke belumfullPASS sampai rerun sesudahpatch. QA sessionbrowser dilogout; akunsementara menunggu cleanuproot sesudah reverify.

Root menambahkanhanya blokheader681–980 keCSSproduksi dengan backup. CIKO actualHTTPSreadback200 menghasilkan SHA256 `c233442ce3a2eb98ab30a5e1fd06097595b65c608ba6acd81bdadab58cf5dc10`, cocokreceiptroot. Independent Playwright sesudahdeploy **25/26 PASS**, termasuk header10neighbor390/680/681/767/768/769/980/981/1024/1440, actualmobilemenuopen/close dan freshmastermobile+logout. Headerissue CLOSED; network/pageerrors tetapkosong. Satu tambahanactualroomdetailclick mengungkap defectbaru di bawah.

### RN-WEB01 — Major, kartu kamar pertama mobile menyusut2px

Repro aktual public390: buka `/`, scroll ke `.room-carousel-section`, carousel scrollLeft0/clientWidth350. Kartupertama `.room-card` memiliki bounds x20..22 (width2px), sedangkan kedua x40..341 (301px) dan ketiga x359..660 (301px). Isi/linkkartupertama keluar dari2pxtrack dan tertutupkartu berikutnya; kliknormal “Lihat detail” pertama gagal dengan pointer interception, termasuk sesudahklikdotpertama. Tidak memakai forceclick. Bukti `carousel-probe.png`, hasil25/26 dan loggeometry actual. Dampak: pilihan kamar pertama pada mobile tidak dapat dibaca/diklik dengan benar, jadi webQC NEEDS FIX.

Source `landing/assets/landing/css/landing.css`: max680 memasukkan `.room-carousel` kegrup `grid-template-columns:1fr`, sementara horizontalgrid memakai implicit `grid-auto-columns`. Track eksplisitpertama kolaps setelah implicittrack menghabiskanspace. Alternatifminimal: hilangkan explicittemplatecolumns khususcarouselmobile dan pertahankan horizontal implicittracks. Root/BUBU menerima temuan; menunggu patchproduksi dan verifikasi firstroomactualclick. `tests/mobile-online-carousel.cjs` disiapkan untuk assert semua cardwidth>100, takadafirsttrackcollapsed, bodyfit dan unforced firstroomclick pada320/390/680/681/768. Tidak cukup melabeli selesai hanya karena kartukedua dapatdiklik.

### RN-WEB01 CLOSED dan final Playwright produksi PASS

Root menerapkan hanya append mobilecarouselrule dengan backup CSSsebelumnya. CIKO actualHTTPSreadback200/64810byte menghasilkan SHA256 `3baba9b9c906add522142615ddd99b5377293b54956d339efcf28f51ecbcc01c`, tepatcocokreceipt deployment. Tidak ada source mobile/APK yangberubah untuk patchwebsite.

Independent Playwright final **26/26 PASS** pada websiteproduksi: landing390/768/1440,actualmobilemenuopen/close,actualfirstroomdetailclick,header10neighbor,desktopDashboard setelahQAlogin,freshmobileDashboard390,POSTlogoutCSRF dan pengecekanproteksi sesudahlogout. `issues:[]`, tanpa uncaughtJS/consoleerror/networkHTTPfailure, tanpa businessPOST. Geometrysettle sesudahresize menghindari falsealarmcanvasChart.js sementara; internal horizontal table scroll tetapdiizinkan.

Independent regressioncarousel tambahan **5/5 PASS** pada320/390/680/681/768: semua9cards punya lebarpositif yangsetara, bodyfit, unforcedclickfirstroom menuju `/rooms/apt-mejestic`. Pada390 firstcard301px sama denganlainnya, menggantikan width2px yangrusak. **RN-WEB01 CLOSED; website smoke QC PASS dalam cakupan ini.** Tidak menyatakan seluruh bisniswebsiteproduksi telahdikirim/diubah; seluruhbisnisjourney tetap pada schemaonline sintetis.

Artefak privat `.e2e/mobile/online/ciko/web/results.json`, `carousel-final-results.json`, `final-css-readback.json`, `landing-neighbors-final.json` serta screenshots. Dashboard berisi data produksi tidakditerbitkan. Seluruhsession QAwebsite milikCIKO sudahlogout; cleanupakunsementara tetaproot karena masihdipakai pengujianAndroidterakhir. Tidak ada inputADB, transferbanknyata atau pollAPIjourney dalam smokeweb.

Root kemudian mengganti hanya querycache CSS empatviewpublik menjadi `20261019-native-qc-r3`. Independent actualHTMLreadback home `/` dan detail `/rooms/apt-mejestic` **2/2 PASS**: HTML200,URLstylesheet memakai v=r3,CSS200 dan hash3baba9…bcc01c tetap sama. Bukti `cache-version-readback.json`. Tidak mengulang seluruhsuite untuk perubahanquerycache tanpa perubahantampilan. Screenshot carousel final juga diperiksa setelah revealopacity mencapai1 agar frameanimasi awal tidak disalahartikan sebagai masalahkontras.

## Verifikasi final Android case3 — 17/17 PASS

Setelah rootmenyelesaikan nativeflow, CIKO menjalankan modeFINAL `tests/mobile-online-tablet-lifecycle.cjs`; statusPASS,case3checked_out,17/17,34checkpointdevice tersimpan. Pemeriksaan diperkuat untuk memastikan kondisiAC awal tetapgood, bulanan damaged dan punya maintenance_ticket_id, Bantal2 di ketigastage, masing-masing19manualrows/final/signatureconfirmations dan3PDFscan. Ketiga pemeriksaan memiliki hasilOCR19printedlabels. Konfirmasi tanda tangan merupakan attestasi checkbox terhadap dokumenfisik sintetis; bukanverifikasi autentisitas tanda tangan.

Tepat1transaksi sewa1,2juta dan1deposit500ribu tersimpan, PDFproofresident tersedia, paymentverified mendahului completedprofile. Rencanakeluar minimal15harikalender dari noticedate; actualexit lebihawal menyebabkan lateflag ditinjauulang tanpa potonganotomatis. Actualcheckout menghitung refund_due tepat+2harikalender. Totalrefund500ribu,dispute0,deduction0 dengan tanggal/referensi/bukti yangtersimpan; CIKO mendownload buktiprivat memakai bearerresident dan memeriksa HTTP200,MIMEapplication/pdf,signaturebyte%PDF. Resident mempertahankan3riwayatfinal danrefundsendiri.

CIKO meninjau screenshot `tablet-refund-complete.png`, `tablet-resident-refund-history.png`, `tablet-resident-monthly-damage.png`: tiga inspectionfinal, badgeSudahkeluar/Refundselesai, H+2, saldo500ribu dibayar500ribu,sisa0,riwayatACrusakdan tindaklanjut terlihat konsisten tanpa clipping halaman. Rootcheckpoint juga mencatat cameraopen/cancel, PDFprivatesharechooser/cancel, generatePDFprintchooser/cancel; bukanpengiriman/printerfisik.

Perjalanan native berlangsung selama siklus APKperbaikan; intakeupload diverifikasi pada e332, HardwareBack pada15ed, rutin/keluar/refund dilanjutkan pada final4d2 dengan sourceupload/back tidakberubah. Ini tidakdilabeli freshone-shot seluruhjourney pada satuAPKdariinstalasibaru. Root mengoperasikan Android; CIKO memberikan reviewindependen melalui source, log danreadbackHTTP. Tidak ada UIinputADB atau businessPOST oleh CIKO untuk menyelesaikan langkahcase3. Hasil `.e2e/mobile/online/ciko/tablet-lifecycle-final-results.json`. **Tidak ada critical/major tersisa dalam cakupan ini; tidak ada cleanupserver oleh CIKO.**

## Receipt cleanup dan audit akhir

CIKO membaca receipt lokal `mobile-final-audit.json`, `online-qc-disabled.json`, `final-http-health.json`, `tablet-install-final.json` serta source `.deploy/mobile-final-audit.php`, tanpa memanggil endpointQA setelahdinonaktifkan. Auditroot memakai transaksi read-only untuk membandingkan tabel dengan backupbaseline dan hash manifestfile final. Hasil tepatnya:

- **28tabel original identik seluruhhashnya; seluruhbaris original dari29tabel dipertahankan.** Ini bukan klaim29whole-tablehashidentik.
- Satu pengecualian append-only `gh_activity_logs`: baseline24baris mempunyai hashprefix identik,15barislogin autentikasi tambahan dipertahankan. Foreignkey ON DELETE SET NULL menjelaskan actorNULL setelahakunQA dihapus; tidakada logaudit dihapus. Daftar changed_original_tables kosong.
- **58sourcefiles cocok hashmanifestfinal**, mismatcheskosong. BackupDB+code mempunyaiSHA256 dalamreceipt; maintenanceflag dilepas. Receipt `ok:true`.
- RouteHTTPSQA sementara dihapus, serviceQA dihentikan, dedicatedproductionFPM dipertahankan dan buktiQA disimpanprivat. Healthreceipt menunjukkan home200/catalog200/retiredQA404. Ini reviewreceipt milikroot, bukan CIKO melakukan aksi server tersebut.
- Endpointproduksi dikembalikan pada tablet, sessionQAlogout, rotasifree. CIKO menghashfileAPK hasilpull actual `tablet-installed-final.apk` secaraindependen dan membandingkan release: keduanya **4d2ad0f92c8bad34796cb25fbc825bdfacacc75c298bfaef8a40b9cea5b70174**.

CIKO juga meninjau finalproductionDashboard portrait danlandscape: nominalRp terformat, unpaidbillscount tidak menjadiRp, rates memakai%, digitbesar tidakpecah, cards terbaca. Screenshot katalogfinal menunjukkan foto/privateplaceholder dan navigasi konsisten. Screenshot produksi berisi agregatbisnis disimpanprivat, tidakdipublikasikan.

Receipt tambahan `qa-cleanup-result.json` telah dibacaCIKO: owned_temporary_qa_accounts_remaining=0, owned_temporary_qa_admin_tokens_remaining=0, cleanup_verified=true. Source `.deploy/verify-qa-cleanup.php` memverifikasi IDakunQA privat+usernameguard+schemaproduksi dengan SELECTCOUNT dalam transaksi read-only; tidak mencetak ID/password. Dengan demikian cleanupakun/token0 sudah memiliki receipt khusus, bukan sekadar inferensi hash29baseline atau attestasi verbal. Pemeriksaanserver dilakukanroot; CIKO mereview receipt/source tanpa queryserver baru. Finalrootlog kini41PASScheckpoint, termasuk endpointproduksi restored/login finalAPK/portrait-landscape/Back/logout/katalogpublik. **StatusQC tetap scopedPASS; tidakada rerunjourney/aksesQA setelahshutdown atau cleanupserver olehCIKO.**
