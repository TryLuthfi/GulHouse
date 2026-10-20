# CIKO — QC semua revisi GulHouse, 20 Oktober2026

**Status akhir: PASS untuk core6revisi, kebijakan pembayaran/denda/H7, dan autentikasi web/API yang diuji. Tidak ada critical/major terbuka pada cakupan ini.** Semua browser CIKO ditutup melalui finally. Tidak ada pengujian tambahan yang sedang berjalan; root aman menghentikan server uji miliknya setelah kebutuhan lain selesai.

## Cakupan, lingkungan dan keselamatan

Acuan core docs/DAFTAR REVISI GULHOUSE.txt ditambah keputusan el melalui ZEYN: periode1bulan kalender berjangkar tanggal awal, deadlineH-2, grace2hari, Rp100.000/hari mulaiH3, peninjauanH7 manual, dan login terpadu HP+kode dengan reset superadmin.

Actual Chrome/Playwright dan POSTHTTP terhadap runtime52873, schema guarded gulhouse_test_mobile_bookingretain26_, contains_real_occupants=false. Semua pendaftaran, booking, pembayaran fixture, perubahan kamar, peninjauan, checkout dan reset kode hanya milik data sintetis CIKO. Tidak ada mutasi database operasional asli/produksi, pengiriman WhatsApp, deployment atau edit implementasi oleh CIKO. Root memiliki penerapan migrasi lokal dan receipt integritas terpisah; laporan ini tidak mengubahnya menjadi pengujian ulang CIKO.

CIKO memiliki tests/mobile-all-revisions-* dan bukti .e2e/all-revisions-20261020/ciko/. Implementasi dimiliki WASP, BUBU dan ZEYN. Area yang diperiksa meliputi Operations_model/Operations, Lifecycle_model/Lifecycle, Billing_policy_model, Resident_access_model/Access, Mobile_service, Home/Resident publik, tampilan booking/intake/lifecycle/portal, CSS/JS terkait. Daftar file deployment lengkap mengikuti manifest root; CIKO tidak mengklaim seluruh dirtyrepo berasal dari revisi ini.

## Hasil independen aktual

Angka berikut adalah pemeriksaan per run, sebagian beririsan; jangan dijumlahkan sebagai jumlah alur unik.

| Run/bukti JSON dalam folder CIKO | Hasil dan cakupan |
|---|---|
| booking-results.json |7PASS actualweb: WAvariants, aksi booking390/768/1280, konfirmasiDone danaliasconvert hanya status, tanpa tenant/stay/contract baru atau perubahan kamar, CSRF/GET/viewer guards |
| api-results.json |4PASS: oldconvertalias/newdone API, filterdone, legacyconverted dengan tiga jenis descendants tetap utuh, financial/viewer roles403 |
| lifecycle-results.json |20PASS actualHTTP: sourcewebsite tamperguard, KTP/marriage, reservasi masterpricing, snapshotkesepakatan, prospect/unpaid/paidmoves dan ownership/role/CSRF/privacy guards |
| calendar-results.json |8PASS actualhelper: Jan31-Feb28-Mar31, leapFeb29, Apr30-May31, yearrollover, tanggal15; offset dari originalanchor |
| lifecycle-ui-results.json |12PASS dan1assertionharness390 yang kemudian dikoreksi: actualintake submit, fileclear/cards, leapmonthreserve/masterreadonly/emptylink, actualmovebutton dan finansial |
| ui-followup-results.json |6PASS: minimum Lihat70x44/nowrap dan longnames390/768/1280; adminPNG200, finance/maintenance identity403 |
| booking-actions-visible-results.json |PASS GET-only: tombol aksi>=44px dan body tidak overflow setelah scroll internal tabel |
| policy-results.json |16PASS sebelum harness berhenti karena meminta CSRF dari halaman yang tidak diizinkan maintenance |
| policy-followup-results.json |13PASS setelah koreksi halamanCSRF/kontrakviewer; assertionakhir mengharapkan roomavailable padahal checkout existing menggunakan maintenance |
| policy-final-readback.json |3PASS readonly: actualforcedexit checkedout/ended, roommaintenance, receivable tetap, tidak ada fakeledger, fee freeze pada actualexit |
| rolling-overlap-results.json |3PASS actualSQLmodel: rollingactive menahan kamar melewati batas periode pertama, excludesownstay tidak self-conflict, actualended tidak menahan masa depan |
| auth-results.json |17PASS, satu overflow390 teridentifikasi sebagai PHPwarning produk dan ditutup dengan run final di bawah |
| auth-final-render-results.json |4PASS: publicaccess/start200, portal/tagihan/policy sebenarnya390/768/1280, tanpa PHPwarning/bodyoverflow, no-store dan residentreadonly |

## Core6 yang terbukti

1. Booking: SimpanData, TandaiSelesai dan linkWA tersedia. Normalisasi08/+628/+6208/6208/628 benar tanpa mengubah nomor tersimpan; invalid/kosong tidak menghasilkan linkWA. Tidak ada WAeksternal diklik. ActualDone/oldconvert tidak membuat administrasi penghuni. Existingconverted fixture beserta tenant/stay/contract tetapimmutable. Tabel lebar tetap menggunakan scroll internal; tidak mengklaim semua kolom sekaligus terlihat di390.
2. Jalur pendaftaran terpisah dari penyelesaian booking. Actualpublicintake tetap menghasilkan case sendiri dengan KTP wajib dan marriage opsional; sourcewalkin yang dikirim paksa dari publik disimpanwebsite. Tidak ada dropdownsurveypublik.
3. Filepicker identitas mempunyai dua kartu, filenamefeedback dan hapuspilihan yang berfungsi pada390/768/1280. UploadmultipartPNG sebenarnya disimpan privat; admin mengunduh bytesPNG, finance/maintenance tidak dapat membaca identitas.
4. Reservasi memakai tarif/deposit master di server meskipun payload berisi1 atau nilai lain. Stay/draft/invoice konsisten; perubahan master setelahnya tidak menulis ulang kesepakatan. Default1bulanJan31-Feb28/leapFeb29; tanggalakhir dapat diganti admin. Form readonly tidak mengirim nilai monetary; emptylinkstate jelas. Samephone+room tanpa hubungan tenant tidak dapat menautkan orang lain.
5. Prospectmove tidak membuat tenant/stay. Reserved unpaidmove menyelaraskan case/stay/contract/invoice dengan IDs tetap dan melepas kamar asal secara aman pada fixture. Paidmove mempertahankan semua payment/deposit rows byte-identical, menghitung overpaidcredit tanpa fake refund. ActualGUI move ke tariflebihbesar menghapus verifikasi pembayaran saat kurang. Consent, signedcontract, activecheckin, dateoverlap, viewer danCSRF guards teruji.
6. KTP+marriage dengan nama panjang membungkus tanpa memecah Lihat atau meluber; tombol min70x44px pada mobile,76px pada ukuran lebih besar. Screenshotprivatedoc390 dibuka dan diperiksa.

## Kebijakan pembayaran/denda/H7

Actualcalculator H0/H1/H2=0,H3=100k,H4=200k,H7=500k. H-2 adalah deadline terpisah dari jatuh tempo. RentlunasH3 menghentikan fee100k; partial masih accrues dan eligibleH7. GETulang tidak membuat tagihan/charge/checkout/ledger atau mengubah kamar pada fixture yang disnapshot.

ActualHTTP pencatatan fee mempunyai ledger sendiri: kas tanpa bukti opsional diterima; PNGproof bila diberikan disimpan privat. Nominal/ref/date/actor/nonce teruji; replaynonce sama idempotent, changedamount danoverpayment ditolak. Rent/bill/deposit tidak berubah saat fee dicatat. Maintenance tidak melihat feeproof/financialform; finance dan viewer boleh membaca proof sesuai hakbaca, tetapi finance/maintenance/viewer tidak bisa memulai reviewH7.

AdminH7 tanpa notice penghuni membuat rencana+durablereview saja: belumactualexit, stayaktif/kamaroccupied, piutang tetap. Duplicate review tidak menambahrecord. Settledrent tidak eligible. Normalcheckout tetap menolak tunggakan. Forcedcheckout tetap memerlukan finalinspection dan retain-debtconfirmation. Sesudah actualforcedexit, stayended/kamarmaintenance untuk pembersihan; receivable1m tetap unpaid, rent/deposit/refund tidak dipalsukan, feeledger tetap. Actualexit membekukan accrual.

Fixture pembayaran dan finalinspection disiapkan secara sintetis untuk mengisolasi guard; ini bukan klaim transfer bank nyata, tanda tangan fisik nyata, atau pengulangan seluruh19iteminspectionflow. WASP melaporkan pure25/dedicated28 termasuk backdatedfeeoverpayment; angka itu tidak diklaim sebagai rerun CIKO. CIKO tidak mengulang skenario backdated terakhir tersebut.

## Autentikasi terpadu dan reset

Actualweb phonecanonical+code membuka portal sendiri yang memuat #tagihan dan kebijakan. Wrongphone/oldcode ditolak generik. APIauthresident danauthpayment membutuhkan HP+kode; NIKlama/missingphone ditolak. Superadminreset lewat form aktual: codebaru ditampilkan sekali, hash cocok, codeplaintext tidak ditemukan dalam audit. Resetkedua membatalkan sesiweb lama serta tokenresident+payment lama401; codebaru masuk recordyangsama. Hash seluruh financial/history/document snapshot tetap.

GET/missingCSRF/missingidentitycheckbox/identity_verifiedarray ditolak tanpa mengganti hash. Owner/admin/finance/viewer tidak mendapatUIreset dan actualPOST403. Oldpayment/NIKwebroute tidak membentuk sesi. Logout mencabut aksesweb terpadu. Tidak mencetak code/hash/token/password ke hasil; kredensial hanya fileprivat atau memori.

## Temuan produk dan penyelesaian

| ID/severity | Reproduksi, aktual, dampak, perbaikan dan retest |
|---|---|
| AR-AUTH01 Major — CLOSED | Publicaccess/start sempatHTTP500 karena sharedLifecycle_model memerlukan Billing_policy_model melalui APPPATHlanding yang salah. Ini memblokir login/intake. Root menemukan cause, WASP mengubahrequire menjadi relatif__DIR__. ActualpublicGET200+phone dan seluruhauthflow serta finalrender4PASS menutupnya. |
| AR-AUTH02 Major — CLOSED | Portal390 memiliki body/root507px karena PHP8192 `Billing_policy_model::$db` dynamicproperty warning sebelumHTML; screenshot portal-overflow-390.png memperlihatkan backtrace, bukan sekadar tableoverflow. SourceLifecycle_model::policy langsung mengisi properti yang belum dideklarasikan. WASP mendeklarasikan dependencydb dan menginjeksi melalui constructor. Tidak ada CSSmask/error suppression. Actualportal390/768/1280 finalrender4PASS, noPHPwarning/overflow; screenshot portal-final-policy-390.png dibuka dan bersih. |

Koreksi harness yang bukan defect: selector nav tersembunyi pada tugas terdahulu tidak dipakai lagi; thresholdLihat75px dikoreksi menjadi minimum mobile70px; CSRFpagebooking tidak tersedia untukmaintenance sehingga memakai lc/detail; viewer adalah rolebaca yang memang bolehfinancialproof; room sesudah checkout adalahmaintenance, bukanavailable. Bukti awal tidak dihapus dan tidak dihitung sebagai PASS. Narrowreadback/retest dilakukan tanpa mengulangi actualforcedexit.

## Batas akhir

PASS hanya mencakup webbrowser, HTTP/API, helper/SQLmodel dan state yang disebut. Tidak ada APK baru dibangun/dipasang atau interaksiAndroid/iOS baru oleh CIKO; dua formRN baru hanya memiliki bukti source/TSC dari root/BUBU terpisah. Tidak menguji semua kombinasiCRUD, semua browser, bank nyata, OCR/tanda tangan nyata, load/concurrency/race menyeluruh, atau deploymentproduksi. Source/tokenissuance locking terhadap reset ditinjau, tetapi tidak dilakukan stressrace multiworker. Setelah policy/auth integrasi, corelama tidak diulang seluruhnya; dilakukan regresi actualpublicportal dan rollingroomguard yang relevan.
