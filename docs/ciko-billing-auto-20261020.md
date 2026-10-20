# CIKO — Automatic billing dan Bill readonly, 20 Oktober2026

**Final scoped QC: PASS, tanpa critical/major terbuka pada cakupan yang diuji.** Semua browser ditutup melalui finally; tidak ada prosesQC aktif. Root aman menghentikan server dedicated setelah kebutuhan lain selesai.

## Lingkungan dan keselamatan

Dedicated runtime52874 dan schema guarded gulhouse_test_auto_billing_* dengan contains_real_occupants=false. Generatorglobal tidak pernah dijalankan CIKO pada sharedbookingretain26 atau schema operasional asli. Fixture hanya katalog/admin sintetis dan delapan case/stay unik milik CIKO. CIKO memiliki tests/docs/evidence dan read-only terhadap implementasi; tidak menginstal scheduler atau melakukan deployment/datawrites produksi.

Kontrak: automatic_billing(asOf,leadDays7,apply), hanya versionrecognized+rolling+active/notice dengan stayactive dan hubunganidentitas/kamar yang konsisten. Offsetrecurring dimulai1bulan sesudahoriginalanchor; batasissue due<=asOf+7hari; recurringdeposit0 dan harga dari kesepakatanstay. Existingbills dilewati tanpa penulisanulang.

## Hasil independen aktual

| Bukti JSON di .e2e/billing-auto-20261020/ciko/ | Hasil |
|---|---|
| gui-results.json |6/6PASS actualGET390/768/1280, forgedsavebillPOST, manualGenerateUIabsent danJobsHTTP404 |
| model-results.json |18/18PASS actualDB/model untukhorizon/preview/snapshot/calendar/catchup/scope/history |
| concurrency-cli-results.json |2/2PASS true2prosesDB dan officialCLIpreview/apply dengan targetownschema |
| final-model-results.json |10/10PASS actualexitstop, calendarrecover, legacybill+transferhash dan noduplicates |
| gui-final-results.json |3/3PASS finalCSSv4 actualBill390/765/1280 readonly/roomlabelfit/nooverflow |

Angka perrun sebagian beririsan; bukan jumlah alur unik. Semua run di atas exit0/0FAIL.

## Perilaku yang terverifikasi

- PadaH-8, preview maupunapply tidak mengeluarkanbill terlaluawal dan tidak mengubahsnapshotDB. PadaH-7, preview memprediksi1bill tanpa write; apply membuat billFeb28 untukanchorJan31.
- Tarifbill1600000 tetap mengikuti snapshotstay walau masterkamar9999999. Initialinvoice rent1600000/deposit400000 tetap utuh. Recurringdeposit_amount0 dan tidak membuat actualpayment/depositledger.
- Dailyapplyulang tidak menambah/menulis ulang existinginvoice. Catchup2031Apr25 membuat bulanMar31 danApr30 yang terlewat, tidak kehilangananchor31 sesudahFeb28. Past/futureduedibedakan berdasarkanasOf.
- Jan31leap menghasilkanFeb29. Decemberanchor melewati tahun keJan31. Februari2033clamp28 kemudianMaret kembali31 untukkeduaanchorfixture.
- Recognizedactive dannotice tetaprecurring. Legacytanpaversi, unknownversion, reserved, ended, dan staleactive yang telahmempunyai actualcheckoutdate tidak dibuatkaninvoice baru; existingbillrows kelompoktersebut identik sebelum/sesudahjobs.
- Actualexit evidence yang ditambahkan pada fixture setelahinvoiceFeb menghentikan bulanberikutnya walaupun case/staystatus masihstaleactive/notice. Activecase lain tetapditagih.
- Dua prosesPHP independen/koneksiDB sendiri denganstartbarrier mengajukanrun yang sama: total3bill baru untuk3stayactive, masing-masing tepat1 perperiode. Hasilsetelahcatchup/retry/concurrency tidak mempunyai duplicatestay-periodinvoice.
- Seluruh originalactualpayments dan depositledger pada dedicatedschema tetap hash-identical sepanjangjobs. Initialbill aktif tetapidentik, bukan direprice atau ditagihdepositlagi.
- Official scripts/run-gulhouse-billing.php --automatic dan --automatic --apply benar-benar dieksekusi dengan environment targetdedicatedschema, outputJSON.ok=true. Karena tanggalriil lebihawal dari anchortest2031+, wrapper tidak menambahrecord; simulatedasOf/catchup dibuktikan melalui actualmodel di atas. Ini bukan klaim wrappermenjalankan simulatedasOf atau telah berjalan besok.

## Bill readonly dan UI

Actual RincianBill tidak mempunyai form atau fieldname yang mengirim perubahan, seluruhinput/textarea readonly, tidak ada selecteditable/tombolSimpan. ForgedPOST payments/save-bill dengan CSRFvalid tidak mengubah snapshotfinancial/case/stay/kamar. ManualGenerateUI dihalamanbilling telah dihapus, automationnote ada; Jobsbilling danautomatic_billing melaluiHTTP mengembalikan404.

Root menemukan perincianBill sempit pada765 dan BUBU menambahkanCSSterbatas: <=980dua kolom, <=640satu kolom. FinalGET390/765/1280 PASS, bodytidakoverflow, roomlabelutuh menurut pengukuranfont+padding. Screenshotbill-readonly-765.png dibuka dan diperiksa visual: readonlyvalues jelas, roomlabel dan nominal muat, tidak ada savecontrol. Ini tidak mengubah transaksi atau keseluruhanformpembayaran.

## Artefak

- tests/mobile-billing-auto-fixture.php; mobile-billing-auto-model.php; mobile-billing-auto-final-model.php.
- tests/mobile-billing-auto-worker.php; mobile-billing-auto-concurrency.cjs.
- tests/mobile-billing-auto-ui.cjs; mobile-billing-auto-ui-final.cjs.
- concurrency-workers.json serta screenshotbill-readonly-390/765/1280.png pada folderbuktiCIKO.

## Batas dan atribusi

Tidak mengklaim jobSchedulerWindows sudah terinstal/berjalan dari tesini. Root memiliki installer/daily09WIB/firsttomorrow dan pemeriksaan43tabelactual yang dilaporkan terpisah; reviewreceipt/activation adalah pekerjaanroot, bukan mutasiCIKO. WASP19model/4concurrency merupakan bukti terpisah, tidak dijumlahkan sebagai runCIKO. Tidak menguji semua failureOS/restartMySQL/crashdurability/100worker, nativeAPK/iOS, banknyata atau deploymentonline. Ambiguousunboundchargedbill failsclosed menurut source+WASP; tidak ada caseambiguity baru pada fixtureCIKO.
