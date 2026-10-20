# CIKO — acceptance independen ringkasan dan tabel admin native GulHouse

Tanggal: 19 Oktober 2026. Status akhir: **QC PASS dalam cakupan utama ringkasan/tabel/filter/rincian/editor. AT01 RESOLVED.** CIKO read-only source; hanya tests/docs/evidence. BUBU memiliki UI, root build/preview/ADB. Semua businesswrites hanya fixture sintetis dengan schema/storage/API yang diotorisasi; tidak ada ADB CIKO/produksi write/memory/Telegram.

## Analisis awal sebelum cross-review

Source awal `mobile/src/screens/Admin.tsx` SHA256 F5566688009AF98AEE1D24063DAA65F4C8654CACBA3018C9E1F797133533287C. Generic Module memakai Card per record, semua schema.columns dipetakan ke KV vertikal; actions berjajar banyak tombol. Data50baris membuat scroll panjang dan perbandingan lintas penghuni sulit. Penambahan tabel memerlukan pemindahan detail/aksi tanpa menghilangkan akses.

API Mobile_service list saat ini menyusun seluruh hasil filter sebelum array_slice/limit, menyediakan count; metrics hanya beberapa modul. Tenants/lifecycle/properties tidak memiliki summary spesifik. Maka summary allfiltered harus berasal dari seluruh hasilfilter sebelum pagination, bukan rows50 yang diterima. Count total benar saja tidak membuktikan count perstatus atau sum benar. Data/status/nominal harus berlabel sesuai domain, nilai null bukan0 palsu; tidak boleh membocorkan field teredaksi role.

Pemetaan penting yang harus dipertahankan: row.actions serverauthoritative dan globalactions, readonlyrole, tenant stay_id/contactmapping, payment request_key, paymentDetail transaksi/bukti, lifecyclecase/linkedcontract, documents privateattachment, contract/template print, upload jenisfile, exportfilters, query+offset dan resetfilter. Table harus memakai satu scrollhorizontal untuk header dan rows dengan ukuran kolom stabil; bukan masing-masing row scroll sendiri. Detail/Aksi sheet menampung fieldpanjang dan nestedobjects secara aman, bukan rawJSON.

## Acceptance matrix

| Area | Acceptance yang diperiksa | Bukti utama yang direncanakan |
|---|---|---|
| Ringkasan Penghuni | Total seluruhfilter, status active/checkout/other sesuai enum actual, occupancy/property jika bermakna, bukan50page | Compare API limit1/50 dan offset, hitung seluruhfiltered independent; q/status/property changing summary |
| Summary lintasmodul | Meaningful metrics/labelIndonesia/Rp vs count, tidakmengarang domain/status jika taktersedia | Inventory25modules response+UI; fixtures empty and allfiltered changes |
| Penghuni tabel | Nama/kamar/status/rent/staydates sesuaiDTO, header aligned, row ringkas, ID/longnames takmerusak |390/768/1180 browser bounds/screenshot; native tabletportrait/landscape root |
| Tabel generik | SatuhorizontalScroll menjaga header+rows aligned, column widths tidak berubah antarrow; clippedtext dapatdibacaDetail | Actualhorizontalscroll+detail text; no wholebodyoverflow |
| Detail/Aksi | Aksi row tepatrecord, title jelas, nesteddata/filepreview accessible, originaledit/forms route intact | Actualclick Detail→Aksi→form/cancel; invalid submit aman; selected recordID match |
| FormCRUD | Existing default/enum/FK/request_key/mapping tidak berubah; safe newlyownedfixture representative save/edit | Syntheticonly; rootauthorization/fixturecoordination, readonly notsave |
| Payments/lifecycle | Transaksi & bukti/detailhistory/checkout/refund navigation tetap; originalprivatedownload/print handlers intact | Click dedicatedroute, inspectAPI/detail noforgedpayment; seededcases read |
| Documents/photos/templates | Thumbnail/attachment/print/rotate fileactions tetap tersedia sesuai role/type | UI action sheet presence/click/cancel; no originalsharedfilewrites |
| Search/filter/export | Filterq/period/status/property preserved; summary follows; clear returns; CSV uses samefilter | GET parameters, compare independentresults, exportempty/onequery |
| Pagination | >50records, next/prevrowidentity, summary unchanged samefilter, correctrange count/empty0 | Rootpreparedsyntheticfixture or scopedresponses clearly labeled ifvisualonly |
| Role/privacy | UI follows serveractions; readonly/maintenance/finance scopedmodules and sensitivefile visibility | Syntheticrole API+browser only ifaccountsavailable; no fieldleak through details |
| States/accessibility | Loading, error, empty, success; sheet Back/Escape; labels/touch/keyboard/tabfocus; amounts noellipsis | Controlledrequestfailure/emptysearch; actualbrowser and rootdeviceevidence |
| Layout | Phone390 plus320stress where applicable; tablet768/1180; native no percentheightunbounded/grid rounding regression | Realdata screenshots afterloaded; native root actualscreenshots |

## Batas PASS

PASS hanya jika kebutuhanutama summaryallfiltered dan compacttable nyata terpenuhi, no critical/major terbuka; source/tsc saja tidakcukup. Browser tidak membuktikan Yoga/tablet. Cakupan25module navigation tidak berarti25CRUDsave. Fixturetertanam4state bukan continuousjourneybaru; iOS/phonefisik/TalkBack terpisah. Hasilfase sebelumtask ini bukan rerun baru.

## API independen awal —62/62 PASS dalam kontrak yang benar

Fresh local schema gulhouse_test_mobile_admintable26_* dengan fixture43tenantunik/83stay, tidakberisi penghuni nyata. `tests/mobile-admin-table-api.cjs` membaca seluruhrowlimit500 dan membandingkan metrik deduptenant/stay/status dengan page limit1/50/offset40. Properti/query/status/emptyfilter, actualpage50+33uniqueIDs,24modul count/metricpageinvariant dan4role accessmetadata lulus. Semua token dibuat/dicabut sendiri, zero businesswrite. Bukti `.e2e/mobile/admin-table-20261019/ciko-api-results.json`. Filterstatus pada suite menggunakan kontrakbenar `stay_status`.

### AT01 — Major RESOLVED — Filter Status Penghuni mengirim field yang tidak diterapkan

- Lokasi: initial Admin.tsx generic Filter NativeForm hardcoded `key:'status'`; Master_model::apply_tenant_filters menerima `stay_status`.
- Repro source+actualHTTP freshsynthetic: GETadmin/tenants?status=ended&limit500 mengembalikan83row berisi active,reserved,ended; query stay_status=ended mengembalikan53row semuanyaended.
- Aktual/harapan: pilihanStatus terlihat ada tetapi statusfilter takberpengaruh; harus menyaring tabel dan ringkasan seluruhfilter sesuai pilihanuser.
- Dampak: kebutuhan utama summaryfilter/tablecomparison gagal dan angka dapatdisalahpahami.
- Alternatif: UI memakai actualfilterkey/modulecontract (tenants stay_status), janganmengubah enum/payloadbisnis umum; cek modul lain tidakmenerapkan schema.status_field membabi-buta bila serverkontrak berbeda.
- Dikirim ke root+BUBU; diperbaiki dengan key stay_status khusus Penghuni. Retest Playwright actualklik Status masa tinggal→Berakhir mengembalikan53row ended saja, active/reserved0 dan summaryended53. **RESOLVED** dengan bukti ciko-ui-results.json dan ciko/02-tenants-ended.png. Ini gapexisting yang terungkap oleh acceptance baru, bukan klaim regresi BUBU.


## Hasil akhir dan status QC

**PASS dalam cakupan perubahan utama. Tidak ada critical/major terbuka. AT01 RESOLVED.**

- API independen62/62:43orangunik/83masatinggal, agregat seluruhfilter dan invariant terhadap limit1/50/offset40, actualpagination50+33 uniqueIDs, statusstay_status/query/property/empty serta4role scope/metadata.
- Source independen10/10: seluruh original API/file/print/rotate/navigation callAST dipertahankan; tenantstay/contactmapping, paymentrequest_key, row.actions, templatekey, satuSheet rowdetail/editor/preview dan constantpagination20 tetap benar.
- Playwright actualGUI33/33 dengan0pageerror:24keluargamodul nonDashboard (Foto tetapgaleri), singlecardtable, header/row aligned dalam satuhorizontalscroll390/768/1180, real20paging/summary43tetap, filterended53, empty0–0, Aksi→rincian→Ubahform→tutup tanpa modalbertumpuk, visibilitytindakan sesuai records yangtersedia. Buktifinal ciko-ui-results.json dan screenshots ciko/. Percobaanharness awal mengasumsikan wrapperDOMberbeda, tabeldesktopwajibscroll padahalsudahmuat, labelEdit belumterjemahanUbah/Billing, no-opsearchmenghasilkanGETbaru, dan dialoglangsunghilang tanpaanimation; dikoreksi pada harness. Ini bukan defectproduk; hasilfinal33PASS dari cleanrun.
- Screenshot Android fisik APK4c3668 yangdioperasikan root ditinjau independen: empatkotakringkasan sejajar,10rowdemodalam satukartu, seluruhheader/row bergeserbersama, rincianfullvalue terbaca. Root memverifikasi hardwareBackmenutupsheet dan actualhorizontalgesture. FixtureAndroid10orang/10stay terpisah dari local43/83; angka tidakdicampur. CIKO tidak mengoperasikanADB.

Berkas implementasi BUBU: mobile/src/screens/Admin.tsx, components/AdminDataTable.tsx dan AdminSummary.tsx. Backend WASP: Mobile_service.php agregattenant sebelum pagination. CIKO hanya menulis tests/mobile-admin-table-{api,source,ui}.cjs dan laporan/evidence sendiri; tidak mengedit implementasi, memory atau produksi.

Batas: bukan transaksi simpan semua modul, seluruhrowaction/privatefile/export/rolecombination end-to-end baru, iOS, phonefisik atau auditTalkBacklengkap. Handlerdipertahankan melaluiAST dan navigasi/visibility/formactual dalamcakupan; tidak mengklaim seluruhCRUDsave hanya dari modulbisa dibuka. Nativebuktioperasi root ditinjau, browser bukan pengganti Yoga.

Seluruh browsercontext/test milik CIKO sudah berakhir pada cleanrun; tidak ada suitebaru diperlukan. Root aman menghentikan preview50126 dan serverlocal59073. Retaineddemoonline milik el tidakdimutasi CIKO; tidakada produksibusinesswrite. Rootlaporan deployment/device menyebut productionAPI dipulihkan/logout dan APKhasilpullidentikrelease4c3668; CIKO tidak melakukan panggilanserverbaru untukmenutup auditini.
