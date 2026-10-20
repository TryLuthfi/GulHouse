# CIKO — Pembayaran tepat sisa, 20 Oktober2026

**Status akhir: QC PASS untuk pembayaran baru tepat sisa dan perlindungan transaksi historis yang diuji. PE01 Minor CLOSED; tidak ada critical/major terbuka dalam cakupan ini.** Seluruh browser CIKO sudah ditutupfinally; root aman menghentikan serveruji miliknya.

## Cakupan dan keselamatan

Runtime52873, schema gulhouse_test_mobile_bookingretain26_ dengan contains_real_occupants=false. Semua mutasi fixture sendiri: satu kamar/stay, dua invoice, transaksi legacy rentpartial300k dan deposit100k+manual100k. Tidak ada perubahan penghuni/transaksi operasional asli/produksi, transfer bank nyata atau deployment oleh CIKO. CIKO read-only implementasi; tests/docs/evidence milik CIKO.

## Bukti aktual

Run tests/mobile-payment-exact.cjs:11PASS/2FAIL. Kedua kegagalan ditriase: satu overflowfilterdesktop nyataPE01, satu assertion metadata yang ikut membandingkan updated_at saat catatan memang diedit (harness terlalu ketat, bukan perubahan finansial).

ActualGUI390/768: nominalreadonly otomatis900k untuk sewa (base1.2m dikurangi actualrent300k, meskipun cachedpaid_total777777) dan300k deposit (kebutuhan500k dikurangi ledgerlinked100k+manual100k). MetodeTransfer tetap, pilihan hanya rent/deposit, referenceinput tidak ada. Modal1280 juga menunjukkan nilai dan kontrol yang benar, tetapi assertionoverflowpage gagal karena filter luar modal.

ActualHTTP/GUI yang PASS:

- Forgedamount1, typeother danmethodCash ditolak tanpa write.
- Upaya mengubah amount/type/invoice/method/reference legacy ditolak; originalpartial/Cash/ref tetap.
- Actualbutton+confirmation menyimpan rent900k, billpaid_total1.2m dan referenceNULL/metodeTransfer.
- Retryrequestkey sama idempotent, retrychangedpayload ditolak.
- Actualbutton menyimpan deposit300k dengan satu ledgerlinked; saldo tinggal500k.
- Stalepayloaddeposit dari invoice lain dalam stay yang sama serta stalerent tidak membuat pembayaran ganda.
- Kondisi lunas menampilkan nominal0 dan tombolsave disabled.
- Dua prosesPHP CLI independen dengan koneksiDB sendiri dan startbarrier bersamaan menagih billkedua: tepat satu sukses dan satu gagal, hanya satu receipt1.2m. Ini uji konkurensi model/DB nyata; bukan klaim PHPbuiltinserver multithread.
- Pageerror0.

Finalreadonly tests/mobile-payment-exact-readback.cjs:2/2PASS. Legacy300k/Cash/ref/date/type/bill/sourcekey tetap, notes baru benar; updated_at boleh berubah sebagai timestamp editmetadata. Olddeposit100k/refCash tetap. Dua bill masing-masing actualrentsum1.2m; staydeposit500k, tepat3ledgerrows, tidak ada duplikasi.

## PE01 Minor — overflow filter pada1280

Lokasi .payment-filter-form > button Tampilkan. Sebelum dan sesudah modal, body/root1292 pada viewport1280; tombol kanan1291.8px. Modal sendiri muat penuh. Tablewide berada dalam wrapper scroll sebagaimana desain, bukan penyebab yang perlu disembunyikan. Dampak: pagehorizontal scroll12px pada desktop. Bukti overflow-diagnosis.json dan overflow-diagnosis-1280.png. Dikirim ke BUBU untuk gridwrap minimal, tanpa maskbodyoverflow. BUBU menambah payments-layout.css yang scoped ke filtergrid saja (tidak menyembunyikanoverflowbody atau mengubahtablewrapper). ActualGETretest390/768/1280 before/aftermodal: rootwidth tepat390/768/1280; Tampilkanright328/377/1218. PE01 CLOSED.

## Artefak

.e2e/payment-exact-20261020/ciko/results.json, concurrency-workers.json, final-readback-results.json, modal-390/768/1280.png, overflow-diagnosis.json. Screenshotmodal1280 telah dibuka dan diverifikasi visual. Tests mobile-payment-exact-fixture.php, mobile-payment-exact.cjs, mobile-payment-exact-worker.php, mobile-payment-exact-overflow.cjs, mobile-payment-exact-readback.cjs.

Batas: tidak menguji realbank, nativeAPK/iOS atau seluruhCRUD. WASPfocused28/concurrent4 merupakan bukti terpisah, tidak dijumlah sebagai runCIKO. UnknownNULLquote baru diliput WASP; CIKO tidak mengklaim browserfixtureNULL. Historicaledit hanya catatan; tanggal/amount/type/method/reference tetapimmutable. Semua browserditutupfinally; tidak ada prosesQC aktif saat checkpoint ini.


## Penutupan retest GUI

FinalGUI tiga viewport dan scopedCSS load menghasilkan4PASS. Extra assertion histori pada run tersebut sempat salah memilih IDmodal (aktual compound payment-edit-BILL-PAYMENT), lalu salah membaca nilai readonlyinput melalui innerText. Keduanya koreksi harness, bukan defect aplikasi; tidak ada POST tambahan. Narrowtests/mobile-payment-exact-historical-form.cjs **2/2PASS** membuktikan fieldamount/method/reference/type/date tidak dikirim formcatatan, readonlyCashlegacy tetap, referensilama tetapditampilkan, textarea catatan ada, dan CSS200. Bukti historical-form-readback-results.json. Hasilawal tetap disimpan, tidak dianggap semuaassertionnya PASS.

Finalmodal1280.png dibuka untuk visualreview setelahpatch: nominal0/lunas, Transferreadonly, tanpa referenceinput, tombol disabled danlayoutmuat. Screenshot390/768 juga tersimpan. Kondisi nominalpositif900k/300k sudah dibuktikan pada runactualsave sebelumnya; tidak dibuat transaksi lagi demi mengulang screenshot.

Tidak ada prosesbrowser/testCIKO yang masihaktif. StatusPASS mencakup actualGUI/HTTP, financialreadback dan dua independentDBworkers di atas; bukan klaim semuaCRUDatau deploymentproduksi.

## Pemeriksaan warna readonly — revisi20261020-2

Scoped source+actual screenshot QC **PASS**, tanpa harness/server/login/POST baru oleh CIKO. CSS hanya .modal[id^="payment-"] dan readonly/disabled/editable state; filter layout tidak diubah oleh blokwarna. Versi stylesheet payments.php=20261020-2. Screenshot actualroot .e2e/live-payment-readonly-colors-20261020.png dibuka: periode/invoice, nominal dan metode readonly berwarnaabu-hijau; tanggal/jenis/catatan editable tetapputih. Tidak ada clipping baru tampak pada screenshot.

Kontras sRGB yang dihitung dari warna CSS: teksreadonly#354c40 di#edf1ee **8.15:1**, editable#203b32 diputih **12.12:1**. Ini mendukung keterbacaan teks dan pembedaan state yang diminta, bukan klaim auditWCAGlengkap. Tidak ada masalah mayor yang ditemukan dalam delta warna ini. Tidak ada transaksi disimpan pada pemeriksaan screenshot root menurut handoff; CIKO sendiri hanya membaca source dan gambar.
