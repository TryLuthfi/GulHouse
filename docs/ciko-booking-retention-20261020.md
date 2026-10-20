# CIKO — keamanan arsip dan hapus permanen booking demo

Tanggal20Oktober2026. Status akhir: **QC PASS dalam cakupan arsip dan hapus permanen demo yang diuji. Tidak ada critical/major terbuka.** CIKO read-only implementasi, owns tests/docs/evidence. Rootruntime/migration/server, WASPbackend, BUBUUI. Hanya databasefixture baru bookingretain26 tanpa realoccupants; tidak ada produksidata/DB atau fileaslihapus/Telegram/memorywrites.

## Analisis independen actualschema sebelum cross-review

convert_booking awal memakai tenant yang ditemukan melalui phone, memperbarui source_label menjadi Booking#id, membuat stay+contract, tetapi tidak menyimpan linkbooking_id eksplisit. Source_label/contractnotes/namaDEMO/nomorHP bukan provenance kepemilikan. Legacyconverted harus failclosed jikaambigu, dan newconversion memerlukan persistedexplicitlink. Tenant dapatdibagikan olehstaylain; kamar/properti/tipekamar/inventorycatalog adalahmastershared dan tidakboleh menjadi descendants.

gh_room_bills→gh_payments dan payment→proof memiliki CASCADE. Tanpa validasi graph, deletebill dapatmenghapus finance diluardugaan. Sebagianbesar operations/lifecycle links tidakmemilikiFK: residentcase/files/inspections/rows/revision/checkout/refund/ledger/events, contractdocument, depositsourcepayment, maintenancecomplaint. Tokenresident subject_kind/id tidakFK dan harusdicabut tanpa menghapusadmintoken. Expenses/roomchecks/notifikasi yanghanyaterhubungroom tidakboleh diasumsikankepunyaanbooking.

FileGC harus aftercommit, pathvalidated, retry/audit jikaunlinkgagal; janganhapusberkasdenganpathsharedatauunknown. Auditpurge berada diluar graph terhapus; akuntabilitas tetapada tanpa menaruhPII/secret berlebihan. Super_admin checkfreshDB, bukan UI/rolecached saja, harus sama diweb/API/model. Archive harusmempertahankan businessstatus/descendants dan memblokir conversion/followup yangtidakdiizinkan.

## Acceptance/adversarialmatrix

| Risiko | Pemeriksaan independen | Harapan |
|---|---|---|
| Provenance | Editable DEMOname/phone/source, legacyconverted noexplicitlink, forgedflag | Tolakpurge dan tidakauto-promote datareal |
| Auth | super_admin vsowner/admin/manager/finance/maintenance/viewer/unauth, directPOST, roleDBdowngrade | Hanyasuper_admin aktif dapatpurge; archivedemo tidakmengubahauth |
| Sharedtenant | Demo staytenant punya staylain/non-demo/documents/deposits | Failclosed atau scopesafe preserving sharedperson, tidakbroadcascade |
| Sharedroom | Staylain/ticket/expense/catalog roomyangsama | Semuamaster/sharedrows preserved |
| Financial | Bill+payment+proof+deposit, crosslinksourcepayment, refundledgercrosscase | Pureown graph saja; crossrelation block |
| Unknownedge | ExtraFK/nonFK/tablecolumn relation keplannedrows | Unknowninbound blocked, tidakmenebakdelete |
| Preview/execute | Hash/nonce bookingbound, stalegraph/demoflag/archive/status/amount/filechange, doublesubmit | Recompute+lock+rejectstale/replayed plan beforedelete |
| Atomicity | DBtriggerdeliberatefail middelete/auditinsertfail | Fullrollback allrows/audit/files unchanged |
| FileGC | Missing/shared/pathtraversal/filelocked/unlinkfailure | No outofrootunlink/precommitdelete; retryreporthonest |
| Audit/token | Purgesuccess recordsactor/reason/counts, eventpreservationpolicy, ownresidenttoken revocation | Auditretained, nonownedtokens untouched |
| Archive/UI | active/archive/all filters/count, reasonrequired, convertedarchive, restore | No derivedbusinessmutation or conversionfromarchive |
| Userflow | Previewblockers/counts, exactphrase, buttonrole, error/stalepreview, successreturn | Clear irreversibleeffects; serverguards authoritative |
| Relationalbaseline | Ownfixtures pre/post hashes/counts unrelatedrows/allsharedrooms | Zero unplanneddeletes/updates |

Semua destructive tests wajibsyntheticguard dan isolatedstorage. Jangan menggunakan sharedrealuploads meskipunDBtestcopy. Rootakanmemberikankontrak lalu migrationscope. Browserharness/UI dan sourcechecks bukan bukti transaksiSQLrollback atau concurrency; catatmasing-masing hasil/batas.


## Hasil independen actual — final

### Backend dan relasi:36/36 PASS

`tests/mobile-booking-retention-qc.php` memakai schema bookingretain26 yang di-guard contains_real_occupants=false, hanya actor/booking/tenant/stay/room/file baru dengan prefixCIKOunik. Memakai actualmodel+driverSQL, bukan mockdelete.

- Enam role non-super (owner/admin/manager/finance/maintenance/viewer) tidak dapatpurge; actor-HMAC salah, frasatidakpersis, currentDBrole downgrade, stale nonidentityrow dan non-demo meskibernamaDEMO ditolak.
- Sharedtenant dengan staytakdimiliki memblokir; hashrecordsentinel tetap. Outgoing inspection→maintenance nullableanchor tidakdiadopsi, sentinelhash tetap.
- Dua rawpathdokumen berbeda yangmengarahfilefisiksama memblokir; filebytes berubah sesudahpreview membatalkanpurge.
- TrustedDemo conversion dengan phoneyangsama dengantenantsentinelREAL ditolak; hashfullname/source_label/semuarowidentity tetap, tidakadastaybaru.
- SQLtriggerbersyarat hanyaIDtenantfixtureCIKO sengajamenggagalkan delete setelah descendantsmulaiterhapus. Fullcore rowsrollback identik, filetetap, tidakada committedpurgeaudit. Triggerdihapusakhirtest.
- Positivepureprovenance graph menghapus booking/tenant/stay/contract/documents yangdirencanakan; roommasterhash tetap. Audit/backuplink dipertahankan; blobGHBR1+checksum dibacabalik; fileGCpendingaftercommit; doublesubmit ditolak.

Bukti `ciko-adversarial-results.json`; ownfixtureIDs/actorname disimpan privat di ciko-fixtures-private.json, tidak didaftarkan sebagai datareal.

### Exclusive DDL:5/5 PASS

`tests/mobile-booking-retention-ddl.php`: unknownrelationcolumntanpaFK, unknownFKkolomnonstandar, knownchildcolumn dengan actualFKparent yangsalah, dan GCtableMyISAM seluruhnya failclosed. Semua temporarytable/constraint/engine dipulihkan melalui finally; setelahrestore planfixture kembalieligible. Root/WASP menunda operasi lain selama jendelaDDL. Drivercachemetadatadireset untuk meniru requestbaru.

Sourcecritique awal FKparentmismatch, outgoingadoption dan publicfallbackencryptionkey ditindaklanjutiWASP sebelumretet. FK exactparent/refcolumn guard, incomingownershipclosure/outgoingvalidation, transactionalengine guard dan secretretention schemascoped terpisah sekarangada. Retentionkeydiinisialisasiroot tanpa mencetaksecret/merotasiappauthkey. Ketiadaanengineaman dapatditolakolehready()exception, bukan harusblockersarray; harness menerima keduanya sebagai failclosed. Bukan defectproduk.

### Actual Playwright web:17/17 PASS

`tests/mobile-booking-retention-web.cjs` dan narrowfollowup actualApachePHP52873 padafixturetersendiri. Lulus daftararchivefilter/count+previewblocked390/768/1280, superadmin restore/archive nyata, GETmutation405/missingCSRF403, archivedfollowupnativeAPI ditolak dan nativepurge tidaktersedia, wrongphrasebuttondisabled, scopeIDs/rincian terlihat, dan positiveownconverted permanentdelete viaactualform. Rootmigrationsource tidak disentuhCIKO.

Lima non-superrole list/preview guard diperiksa. Koreksiharness penting: account runtime ternyata super_admin, sehingga labeladmin pada satu percobaan keliru menghasilkan200yangbenaruntuksuper_admin. CIKO memeriksaDBrole+passwordbool milik actorCIKOadmin yangbenar, loginulangnarrow, membuktikan preview403 dan reviewlinkabsent. Hasilfinal17 adalah16actualPASS sebelumnya +1replacementnarrowactualadmin, bukan klaimsemuasinglefreshjourney. Bukan privilegebypass.

Wrongphrase memangmen-disablebutton; harnessawal kelirumencobaklikbuttondisabled. Diperbaiki untukassertdisabled/tanpaPOST, lalufrasabenar dan submitpositive. Tidakada apppatch dari kegagalanharness ini. Screenshotmobileawal hanya memuatlegacysidebar; recapture390/768/1280 discrollke maincontent. Sidebarlegacy panjang bukan perubahanbaru taskini. Actualmaincontent/blockedplan/eligiblephrase screenshots ditinjau: tablewrap/internalhorizontal scroll, reason/phrase/button/readability memadai; no bodyoverflow/no pageerror dalamcakupan.

`tests/mobile-booking-retention-web-readback.php` membuktikan pascaUI ownbooking/tenant/stay/contract hilang, purgeaudit tetap, GCstatus=removed. OwnuniquePDF fisik juga tidakada lagi, sehingga controllerGCpostcommit bukan sekadar tombol sukses. Actoradminproof disimpan privat; password/CSRF/HMAC/key tidakdicetak.

## Putusan, file, dan batas

**PASS** untuk cakupan utama: archive/restore, trusted-demo-only superadmin purge, failclosed ambiguous/shared/unknownrelations, staleplan, actualrollback, audit/backup/GC dan UI/HTTPguard yang diuji. Tidakada critical/major terbuka. Proofinitialsourcenya ditandai risiko sampai diperbaiki/diuji; tidak diberi fabricatedbugseverity atau klaimexploitlive.

ImplementationWASP: Booking_retention_model, Operations_model/controller/routes/security integration, additiveSQL dan trustedCLI/bootstrap. BUBU: operations/index.php, booking_retention.php, master.css/master.js. CIKO owns tests/mobile-booking-retention-{qc,ddl,web,web-followup,web-readback} dan dokumen/evidence saja. Rootmelaporkan suitebackend31/lint/mobilegallery77 terpisah; CIKO tidakmengklaim menjalankansuiteWASP/root itu.

Batas: simultaneousparallelwriter stress belum dijalankan; stalechange nyata antarpreview/execute dan actorDBdowngrade diuji. Independentpositivefullgraph mencakup core+documents, bukan setiappayment/refund/ledgerkombinasi; testfullgraphWASP tetap buktiterpisah. CLIlegacyreview, 32MiBboundary, backupdiskfull/OSACL Windows, GCpermissionfailure/retrycrash tidakexhaustive padaCIKO. UIroleHTTP membuktikan preview403/linkhidden; actualpurgeauthorization6role dibuktikan pada modelactualDB. Tidak ada real-registration, productionmigration/deploy, realrowarchive/purge, atau originalsharedfile dihapus olehCIKO. Native/iOS/lifecyclelain tidakdiulang.

Bukti privat di `.e2e/mobile/bookingretain26/`: ciko-adversarial-results.json(36), ciko-ddl-results.json(5), ciko-web-results.json(17), ciko-web-db-readback.json dan ciko/*.png. Seluruh browser/testcontext sudahditutup. Tidak ada alasan menahan penutupan cakupan ini untuk suite tambahan.

## Delta berikutnya — verifikasi web tanpa input ID/CLI pengguna

Permintaan el selanjutnya mengizinkan verifikasi demo melalui web. Laporan ini adalah auditbaseline arsip/purge sebelum delta tersebut; ketentuanCLI-only sebagai keharusanpengguna bersifat historical. Hasil delta terbaru16actualPlaywrightPASS dan batasnya ada di docs/ciko-booking-web-verification-20261020.md. Hintlegacy hanya membentuk kandidat exact+aman untukditinjau manusia; tidakauto-markDEMO. CIKO tidakmengulangi seluruhsuite lama atau mengubah dataactual#19/#20.
