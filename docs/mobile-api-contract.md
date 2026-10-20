# GulHouse native mobile API v1

Base `/master/index.php/api/mobile`. React Native uses native screens, JSON and native document/camera pickers; website remains independent. All writes POST; reads GET. JSON body and multipart fields supported; multipart can carry `payload` JSON for nested item maps. Authorization `Bearer <opaque token>`; never cookies. Tokens SHA256 in DB, expiry and revocation enforced; admin account active/role refreshed on each request. Private files always bearer-authorized.

Envelope `{ok:true,data:...}`; failures HTTP400/401/403/404/405/422/429/503 `{ok:false,error:{code,message,fields:{}}}`. No HTML is required to render a native screen. Native opens printable PDF/CSV via OS share/download, not WebView.

## Public/auth

GET `catalog/rooms`: `{rooms:[...],room_types:[...],properties:[...],house_rules:[...]}`. GET `catalog/room/{slug}` detailed public room.

POST `auth/admin` JSON `{username,password}`. POST `auth/resident` `{access_code}`. POST `resident/intake` multipart `full_name,phone,room_id,planned_start,source,consent=1,ktp_file` required, marriage_file optional. Returns `{token,expires_at,user:{kind,id,name,role},access_code?}`. Access code appears once after intake; bearer token is separate. GET `me` returns user+capabilities; POST `auth/logout` revokes current token. Admin token expires after12h, resident token after7days.

Legacy payment-lookup parity: POST `auth/payment` `{phone,nik_last4}` uses the same existing lookup and returns a 30-minute limited token `{user:{kind:'payment',id:stay_id,name,role:'payment_viewer'}}`. GET `payment/status?period=YYYY-MM` returns minimal context, current_bill,bill_history,bills alias,payments for the selected invoice. `resident/payments` GET is an alias for that limited principal. It cannot open full resident portal, profile, facilities, private files, resident writes or admin modules; me advertises only payments_only. No NIK/profile object is returned by this lookup.

## Admin dynamic modules

GET `admin/modules` -> `{modules:[{key,title,subtitle,columns:{field:label},fields:{field:{label,type,required,options}},readonly,status_field,actions:[{key,label,fields,fileFields,scope,method,endpoint}]}]}`. Includes dashboard,properties,room-types,rooms,tenants,payments,photos,users,lifecycle and all operational modules. Schema IDs select options use `{rooms,tenants,stays,contracts,periods,bills,properties,room_types,roles}`.

GET `admin/{module}` -> `{rows,metrics,options,filters,schema}`; filters are same website field names (q,status,period,period_id,room_id,property_id,...). GET `admin/{module}/{id}` -> `{record,options,schema,...}`. Lists have `count`; `limit` default100/max500 and offset are accepted for large tables. Readonly roles cannot submit actions.

Generic list rows provide `actions`, filtered by record state; an explicit empty array means no row actions. Module actions use `scope:'module'` and do not require selecting a record. Converted bookings omit convert/status; contracts offer activate/cancel for draft and renew/checkout for active/expiring. Contracts linked to a managed case expose `lifecycle_case_id` and omit legacy checkout, directing native navigation to the lifecycle workflow. Action descriptors guide the UI; the server independently revalidates role and state on every write.

Accounts support `q` against fullname/username/email, exact role and `is_active=0|1`, combined before count/limit/offset. The password input descriptor remains in schema.fields; account rows never return password values or hashes. Optional password on editing means unchanged; creating an account still requires the original server validation of 10-72 characters.

POST `admin/{module}/save` uses exact existing form names; id=0 creates. POST `admin/{module}/{id}/{action}` uses action fields below. Original controller commands/domain models are reused, preserving validation and financial guards. Result `{message,...}`; reload list/detail after success.

| Module | Actions | Fields |
|---|---|---|
| properties,room-types,rooms | save,delete; rooms toggle-public | Existing native schema fields; delete is guarded soft archive |
| tenants | save,end-stay | stay_id,tenant_id,fullname,room_id,stay_status,check_in_date,check_out_date,monthly_price,deposit and biodata/contact fields |
| payments | save-bill,save-payment,delete-payment | Exact website payment fields room_bill_id,payment_id,payment_date,amount,payment_type,method,reference_no,etc |
| users | save,delete | id,fullname,username,email,role,is_active,password |
| booking | convert,status | convert room_id,start_date,end_date,stay_status; status status; readonly booking records have no generic save/delete |
| contracts | save,activate,renew,checkout,cancel,draft | Existing contract action fields; managed lifecycle checkout redirected to lifecycle workflow |
| billing | generate | period_key |
| complaints | save,delete,maintenance | Existing complaint fields |
| documents | save,delete | document_file optional PDF/JPG/PNG <=10MiB |
| templates | save,delete | template_file optional PDF <=10MiB |
| payment-proofs | upload,delete | payment id, proof_file PDF/JPG/PNG <=10MiB,description |
| photos | upload-slider,upload-room,rotate,delete | photos[] or photos_0[],upload_rows,scope,property,type,file,degrees/rotated_file; original validation |
| deposits,maintenance,inventory,facilities,housekeeping,audits,finance,notifications | save,delete where allowed | Exact operational schema fields; financial/contract history deletion remains blocked |

GET `admin/payments/{paymentId}/receipt` structured receipt plus original formatted `html`; GET `admin/contracts/{id}/print` structured contract plus original formatted `html`; GET `admin/templates/{keyOrId}/print` template metadata plus `html` or `{file_kind:'template',file_id:key}` for attached PDF. GET `admin/{module}/export` returns `{filename,mime,content}` for native filesystem/OS sharing. These HTML strings are PDF generation sources for expo-print, never WebView UI screens.

Native payment forms must seed one stable request_key on opening, retaining it across failed submissions/retries. This reuses the website's existing idempotency guard; do not create a new key for each retry. Refunds additionally require unique transfer references and proof. JSON numeric/boolean leaves are normalized to HTML-form scalar strings only inside the trusted legacy command adapter so strict original validators behave identically.

## Lifecycle

GET `admin/lifecycle/{id}` is flat lifecycle detail plus options: case,inspections,files,checkout,refunds,history,inventory_items,payment_summary,deposit_balance,maintenance. POST `admin/lifecycle/enroll` requires existing stay_id,ktp_file,marriage_file optional; returns one-time access code. POST `admin/lifecycle/{id}/action` accepts action and exact fields in lifecycle-contract.md (reserve,link_stay,verify_payment,inspection_save/extract/finalize,checkin,maintenance,notice,notice_update,checkout,refund,resolve_dispute). Admin role scopes enforced per action, identity redacted from finance/maintenance.

Admin inspections retain decoded `ocr` text/items/suggestions for manual review; raw `ocr_json` and internal file paths are removed. OCR does not finalize inspections or establish item conditions. Resident responses omit OCR drafts and expose finalized inspections only.

## Resident

GET `resident/portal` -> flat own lifecycle payload, finalized inspections only, private own documents, payment_summary and recurring payments. POST `resident/action`: full_profile,payment_proof,notice only; same field names as lifecycle-contract.md. POST `resident/complaints` -> active own stay only complaint fields. GET `resident/payments` -> own invoices and actual payments. Never accept case/tenant identity from client.

## Files

GET `files/lifecycle/{id}`, `files/document/{id}`, `files/payment-proof/{id}`, `files/template/{key}` streams MIME-validated bytes with no-store. Admin roles checked per kind; resident only own case finalized files. No physical storage path, password hash, access hash or token hash is returned. Camera images must be JPEG/PNG for documents (convert HEIC on native client); photo library accepts JPEG/PNG/WebP. Upload <=10MiB per file, photos <=40MP. Keep PDF picker as well as camera; do not force camera-only capture.

## Isolation and deployment

Additive `patch_gulhouse_mobile_api_20261019.sql` creates token table only. No constructor auto-migration. Test only fresh `gulhouse_test_*` schemas and explicit migrations; no production migration or deployment is implicit. CSRF exemption matches only api/mobile namespace; bearer required for all protected routes. Existing browser routes keep CSRF.

### Deployment preparation; not authorization to deploy

The requested online target is `https://gulhouse.co.id/master/index.php/api/mobile`. Read-only inspection by ZEYN found the API absent (404) during this task. The mobile build cannot operate against that endpoint until a separately authorized backend deployment. Existing website behavior remains available; no deployment is performed by this implementation.

Backend files to include in an authorized deployment:

- New controller `master/application/controllers/Mobile.php`.
- New model `master/application/models/Mobile_service.php`.
- New libraries `master/application/libraries/Mobile_command_bridge.php`, `Mobile_schema.php`, `Mobile_public_data.php`.
- Modified `master/application/controllers/Manage.php`, `Operations.php`, `Users.php`, `PaymentProofs.php`: only an internal redirect adapter is used for shared native commands. Website redirect behavior is unchanged when bridge inactive. API binds existing CI services without re-running controller constructors, independently checks account/role, and restores prior session state in finally.
- Modified `master/application/config/routes.php`, `config.php` and `master/application/hooks/Security_hook.php`: dedicated API namespace, bearer authentication, narrow CSRF exemption; normal website CSRF remains active.
- Resident lifecycle prerequisites from the preceding implementation: `Lifecycle.php`, `Lifecycle_model.php`, original lifecycle route/model guards, 19-item catalogue, private storage and OCR helper if OCR is enabled. Deploy those prerequisites if online server has not received them.
- Additive SQL `database/patch_gulhouse_lifecycle_20261019.sql` (8 lifecycle/catalogue tables) and `database/patch_gulhouse_mobile_api_20261019.sql` (mobile tokens only), plus explicit CLI scripts `scripts/migrate-gulhouse-lifecycle.php` and `scripts/migrate-gulhouse-mobile.php`.

Migration order: verify the existing operational baseline, explicitly migrate lifecycle if missing, then explicitly migrate mobile tokens. The mobile CLI refuses --apply when lifecycle prerequisites are absent. Both scripts default to dry-run, allow only local host and gulhouse_db or gulhouse_test_* schemas, create a full private SQL backup before --apply, and reconcile all prior table counts and content hashes. Tokens table creation is repeatable; applying twice on the isolated root test schema preserved every prior row/hash. Do not copy a local database or test users to production. Use a maintenance/quiescent window for reliable reconciliation; concurrent legitimate writes require investigation rather than automatic rollback.

Keep application/, system/, .deploy/, .e2e/, .env and private document folders blocked by the actual web server. Verify Authorization reaches PHP, HTTPS is active, token table created, JSON unauthenticated401, admin/resident scope, logout, private file authorization, then smoke-check the existing logged-in Dashboard and routes after deployment. Native Android clients do not require browser CORS; Expo-web development preflight permits localhost/127.0.0.1 and optional exact GULHOUSE_MOBILE_CORS_ORIGINS entries, without credentials.

### Backend verification during local implementation

`php tests/verify-mobile-api.php` passed 100 assertions against the isolated loopback `gulhouse_test_*` runtime after the account-filter/action, actual DTO card columns and photo URL fixes. It checks all 25 module JSON reads, opaque authentication/revocation/expiry, roles, shared original-controller commands, no browser-session privilege leakage, KTP intake/private bytes, limited legacy payment lookup, user schema redaction/search, canonical private photo URLs and local CORS preflight. `php tests/verify-mobile-sanitizer.php` passed four serializer checks covering safe password descriptors, removal of credential values, preservation of OCR review text/items and stripping raw OCR/storage paths. These backend tests do not prove Android device interaction, production connectivity or deployment.
