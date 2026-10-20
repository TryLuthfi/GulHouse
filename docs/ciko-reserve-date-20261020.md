# CIKO — Tanggal mulai reservasi dan periode otomatis, 20 Oktober2026

**QC PASS:12/12 pemeriksaan aktual, tanpa critical/major terbuka dalam cakupan revisi ini.** Browser ditutup melalui finally; root aman menghentikan serveruji miliknya. Tidak ada pengujian CIKO aktif.

## Lingkungan dan cakupan

Actual Chrome/Playwright dan POSTHTTP ke runtime52873, schema guarded gulhouse_test_mobile_bookingretain26_ dengan contains_real_occupants=false. CIKO memiliki tests/docs/evidence saja. Fixture dua kamar/case baru unik dan satu reservedstay penghalang dibuat langsung pada schema sintetis untuk mengisolasi overlap. Tidak ada data operasional asli/produksi atau deployment yang diubah CIKO; tidak mengklaim fixturecase merupakan pengujian ulang formulirKTP.

Implementasi yang dibaca: Lifecycle_model.php::reserve, lifecycle/reserve.php, assets/master/js/reserve-period.js. WASPbackend/BUBUUI memiliki implementasi.

## Hasil aktual

Tests/mobile-reserve-date.cjs exit0:12PASS/0FAIL, pageerror0.

- Pada390/768/1280, start_date mempunyai name, required, dan dapat diubah; end_date readonly. Input2030-01-31 menghasilkan2030-02-28;2032-01-31 menghasilkan2032-02-29;2030-12-31 menghasilkan2031-01-31. Tidak ada bodyoverflow.
- Pengosongan tanggal mengosongkan akhir dan menggagalkan nativeformvalidation. Kliksubmit saatkosong tidak mengirim POST. Invalidcalendar pada dateinput dibersihkan ke kosong dan akhir kosong.
- ActualserverPOST emptydate,2031-02-29 dan start_datearray ditolak tanpa PHPwarning/partialcase/stay/contract/bill/policy mutation.
- Tanggal selected2031-12-31 diperiksa terhadap reservedstay existing yang berbentrok, meskipun planned_start lama2030 dan endpayload dipalsukan menjadi2030-02-15. Tidak ada perubahan data saat ditolak.
- Actualtombolreservasi dengan start2032-01-31 serta endreadonly yang dipalsukan diDOM menjadi2099-12-31 tetap menghasilkan2032-02-29. Caseplannedstart/billinganchor, staystart/end, contractstart/end, invoiceperiod/due dan policyanchor/due seluruhnya konsisten dengan tanggal efektif.
- ActualserverPOST start2033-12-31 plusendpalsu2040 menghasilkan akhir2034-01-31 dan periodeinvoice2033-12; tarif/deposit payload1 tetap diabaikan demi master.
- Pengirimanreserve berulang pada case yang sudah mempunyai reservasi tidak menimpa tanggal atau recordexisting; snapshot seluruhcase/stay/contract/bill/policy/room tetap.

Screenshotreserve-390.png dibuka serta diperiksa visual: mulai dapat diedit, akhir29Feb2032 tampak readonly, labelSewa bulanan/Deposit tetap sederhana dan nilai masterreadonly. Screenshot768/1280 tersimpan sebagai bukti ukuran lain.

## Bukti dan batas

- tests/mobile-reserve-date-fixture.php; tests/mobile-reserve-date.cjs.
- .e2e/reserve-date-20261020/ciko/results.json dan reserve-390/768/1280.png.
- IDsfixture dan hashakses tetap dalam fileprivat; tidak dicetak dalam laporan.

Tidak ada kebutuhan lama atau masalah baru ditemukan dalam run ini. Tidak mengulang semua lifecycle, autentikasi atau modul lain. Omittedstart fallback untuk klienlama adalah kontrakbackend; run ini menguji explicitselected/empty/invalid dates. Boundary9999-12-31 diproteksi WASP pada finalguard dan tercakup suite37 miliknya, tidak diklaim sebagai actualbrowserrerun CIKO. Tidak menguji nativeAPK/iOS, banknyata atau deploymentproduksi.
