# CIKO — verifikasi demo langsung dari web

20 Oktober2026. **QC PASS dalam cakupan delta verifikasi web. Tidak ada critical/major terbuka.** Kebutuhan terbaru el mengizinkan verifikasi manusia melalui web; pengguna tidak perlu CLI, mengetik ID relasi atau menyalin fingerprint. BUBU memiliki view/CSS, WASP memiliki kandidat/POST/auth/transaction; CIKO read-only implementasi dan menulis tests/evidence ini saja.

## Pengujian aktual16/16 PASS

`tests/mobile-booking-web-demo.cjs` melalui Playwright actualPHP52873, memakai schema bookingretain26 lama yang sudah diotorisasi dan enam bookingfixture baru CIKO. Fixtures dibuat melalui SQLsetup khusus test; **operasi verifikasi pengguna benar-benar dilakukan dengan checkbox, keterangan dan tombol web**, bukan CLIregistrasi. Dataactualroot#19/#20, sourceDB dan produksi tidakdisentuh.

- Kandidatfresh danlegacy exacthint ditampilkan dengan formweb390/1280. Tidakada inputmanual tenant/stay/contractID atau fingerprint; hash/token disediakan hiddenolehserver.
- GETcandidate tidakmembuat registry atau menyimpan bindinglegacy. NamabermuatanDEMO bukanautoverifikasi.
- Legacyduakontrakberhintidentik, phone-onlytanpahint, serta tenantdenganstaybersama ditolak tanpaformverify.
- MissingCSRF menghasilkan403. Dengan tokenreview/CSRFvalid, uncheckedconfirmation dan keterangan kosong tetapditolakserver.
- Bookingfixturediubah setelahpreview; staleverification ditolakserver, tidakterdaftar.
- Actualfresh danlegacy verified melalui checkbox+pernyataan20–500char; formverify hilang, statusregistryterlihat, tombolpurge baru tersedia. Verifikasi tidakotomatis menghapusdata.
- Setelahlegacyverified, actualexistingpurgeform denganalasandanfrasapersis berhasil. TidakperluCLI/IDs/fingerprintinput untuk mencapaiakhirflow.
- AkunCIKO denganroleadmin yangdiverifikasiDB, bukanruntimeyangsuper_admin, mendapat403 pada POSTverify denganCSRFvalid.
- Independentreadback menunjukkanfresh tetapada danregistrykindweb_fresh; legacybooking+tenanttest terhapus setelahpurgeterpisah; ambiguous/phone_only/shared/stale tetapada dan tidakterdaftar. Zero pageerror.

Bukti: `.e2e/mobile/bookingretain26/ciko-web-demo-results.json` (16PASS), ciko-web-demo-readback.json dan ciko-web-demo/{fresh,legacy}-{390,1280}.png. IDs/usernames fixture disimpanprivat; password/key/token tidakdicetak. Screenshotform390 danlegacy1280 ditinjau langsung: checkbox/pernyataan/tombol jelas, no bodyoverflow. Warningpurge yangmasihterblokir sebelumverifikasi benar dan terpisah dari kelayakanverifikasi.

## Sumber dan batas

Servermengusulkan kandidatlegacy dari exactcontractnotes+tenantlabel yangunik, memvalidasi identitas/room/date dan seluruhgraph, lalu meminta attestation manusia; hintbukan buktiyangmengklasifikasiotomatis. PurposeHMACverifikasiterpisah, currentDBsuperadmin+CSRF, hashreview/expiry, lock/revalidate/transaction tetap wajib. CandidateGET virtualbinding tidakmenulisSQL. Legacyambigu tetapfailclosed, bukan memaksauser memasukkanIDmanual.

Positivefixture delta ini sudahdiarsipkan saatsetup. Karena itu hasilCIKO membuktikan verificationtidakautodelete dan purgeterpisahsetelahverified, bukan uji baru bahwa verificationpadaactive tidakautoarchive. Source dan suiteWASP20 membahas preservation root/timestamps/noautarchive; itu buktiterpisah, bukan rerunCIKO. Archive/restore, rollback, graph/FK/engine/fileGC lama36+5+17 tetap historis dan tidakdiulang. Concurrentwriterstress, seluruh kombinasi relasi/roles/expiry, iOS/native tidakdiulang pada delta ini.

RegistrasibatasCLI pada laporanlama kini historical untukflowyangdiminta sebelumnya; webhumanverification ini menggantikan kebutuhanuser harusmenjalankanCLI. ExistingCLIoperator bolehtetapada sebagai alatoperator, bukan keharusanpengguna. Tidakada deployment/realverification/realpurge yangdilakukanCIKO. Browsercontext ditutup melaluifinally dan tidakada tesbaru yangdiperlukan untuk menutupcakupan ini.
