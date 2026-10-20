# CIKO — Master stale session alert, 20 Oktober2026

**Final QC: PASS. MF01 Major CLOSED setelah perbaikan sumber dan retest actualbrowser.** Tidak ada kritis/mayor tersisa dalam cakupan ini. Semua browser CIKO ditutupfinally; root aman menghentikan serveruji miliknya. Tidak ada tes/proses CIKO aktif.

## Reproduksi independen sebelum perbaikan

Actual isolated Chrome/Playwright runtime52873, guarded schema gulhouse_test_mobile_bookingretain26_ dengan contains_real_occupants=false. Hanya login/logout dan GET; tidak ada perubahan penghuni atau transaksi bisnis.

Baseline tests/mobile-master-session-flash.cjs: **8PASS/4FAIL**. Freshunauth/dashboard atau/booking mengarahkan keLogin dan menampilkan `Sesi belum aktif. Silakan login terlebih dahulu.` Loginvalid berhasil, tetapi lifecycle detail sendiri tetap menampilkan pesan lama pada refresh serta navigasiulang. Directunauth/lifecycledetail hanya redirect tanpa membuat pesan, sehingga setelahlogin tetapbersih. Sesi authenticated sebenarnya ada; ini flasherror yang tidak kedaluwarsa.

MF01 lokasi: master dan landing/system/libraries/Session/Session.php::_ci_init_vars. Kondisi lama membandingkan stringmarker old dengan integer time; perilaku PHP8 membuat old tidak dihapus. Dampak: pesan keamanan palsu berulang pada sesi yang valid. Alternatif aman yang diterapkan WASP: hapusmarker old secara eksplisit, pertahankan new->old dan expiryinteger tempdata. Authguard/errorview tidak dihilangkan, tidak dilakukan blanketclear semuaerror.

## Retest aktual setelah sourcefreeze

Tests/mobile-master-session-flash-after.cjs: **17/17PASS**, exit0.

- Freshunauth/dashboard, booking dan lifecycledetail tetap redirectlogin.
- Loginvalid dari masing-masing jalur membuka sesi; lifecycle detail tidak membawa stalealert.
- Repeatedrefresh, booking/dashboard navigation dan kembali detail tetapauthenticated serta bebas stalealert.
- Loginpasswordsalah tetap menampilkan error asli pada login yang diredirect. Errorflash hilang pada requestberikutnya, lalu loginvalid/detailbersih.
- Logout melalui formPOST menghapus akses; GETdetail sesudahnya kembali ditolak/redirectlogin.
- Label reservasi sekarang Sewa bulanan dan Deposit tanpa frasa dari master. Inputtetapreadonly dan tidak mengirim fieldmonthly_price/deposit dari form.

Screenshot sebelum/sesudah dashboard-detail.png dibuka dan diperiksa visual: banner sesi lama yang tampak sebelum patch tidak muncul setelahpatch; detail tetap dirender dengan sesi benar.

## Bukti dan batas

- .e2e/master-session-flash-20261020/ciko/baseline-results.json dan screenshot sebelumpatch.
- .e2e/master-session-flash-20261020/ciko/after/baseline-results.json dan screenshot sesudahpatch (namafilebaseline dipertahankan harness, direktoriafter menandai retest).
- tests/mobile-master-session-flash.cjs; tests/mobile-master-session-flash-after.cjs; tests/mobile-master-session-flash-build.cjs.

Credentials/cookies/token tidak dicatat dalam hasil. Sourceimplementation hanya dibaca CIKO. WASP melaporkan pure6 dan actualHTTP15 secara terpisah; root mengulang pure6/lint. Angka itu tidak diklaim sebagai rerunCIKO. Tidak menguji browser/perangkat pengguna asli, membaca cookiepengguna asli, sesi lintashost, atau deployproduksi. Cakupan landingcore mendapat sourcefix yang sama; browser CI statefulflashdata diukur pada Master, bukan pengulangan seluruhflowlanding.
