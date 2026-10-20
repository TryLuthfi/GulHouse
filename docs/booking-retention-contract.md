# Booking retention — 20 Oktober 2026

Backend: `Booking_retention_model`. No automatic migrations or demo classification. Existing converted records remain archive-only until an exclusive candidate graph has been reviewed and explicitly attested by a super admin. Names, phones, source labels and booking notes never classify records as demo on their own.

## Web contract

- GET `booking/retention/{id}`: current active database `super_admin` only, HTML `operations/booking_retention.php`. Variables `title,admin_name,booking,plan,error`.
- POST `booking/archive/{id}`, `booking/restore/{id}`: existing CSRF enforcement, current database roles owner/super_admin/admin/manager; `reason` 5–500 characters, `confirmed=1`. HTTP303 to `booking?archive=all` with flash. Neither changes original business status.
- POST `booking/purge/{id}`: current active database super_admin only; `reason` 10–500, exact `confirmation=HAPUS DEMO #ID`, `plan_hash`, `review_token`. HTTP303 with flash. Actor row locked through transaction. Other roles denied403; GET mutations405. No native API purge or registration endpoint.
- POST `booking/verify-demo/{id}`: active database super_admin only, CSRF, `confirmed_demo=1`, `attestation`20–500, server-generated hidden `verification_hash` and `verification_token`. HTTP303 back to retention review. Verifies provenance only; never archives or deletes. User does not supply IDs, CLI commands, or fingerprints manually.
- List `archive=active` default, `archived`, `all`; existing q/status filters retained. `can_manage_retention`, `can_purge_demo`; `retention_counts={active,archived,total}` follows q/status before archive selector. Archived rows cannot follow-up/convert through direct web or mobile actions. Dashboard counts/recent/chart exclude archive.

`plan={booking_id,is_archived,is_demo,can_purge,blockers:[string],nodes:[{table,label,count,ids}],counts:{table:count},files:{count,items:[{kind,reference_id,name}]},confirmation_phrase,plan_hash,review_token,expires_at}`. Token is HMAC actor+ID+full graph/file hash+5-minute expiry. Real storage paths are not returned. `can_purge` requires archived+valid registry+all safety checks. `is_demo` alone does not claim complete graph safety.

Unregistered previews additionally include `can_verify_demo` and `verification={hash,token,expires_at,blockers,nodes,counts,files,mapping_summary}`. The top-level scope displays the complete candidate graph. Legacy discovery requires one exact anchored contract note `Dibuat dari booking #ID`, an exact tenant label `Booking #ID`, matching booking/tenant identity, consistent contract/stay room and dates, and exclusive complete graph safety. Missing, multiple, contradictory or shared mappings block verification. These hints establish candidates only: the explicit human attestation is the authorization to register the scoped graph. GET discovery uses virtual roots in PHP and performs no mutating SQL. POST locks actor and rows, rebuilds the candidate and validates a purpose-separated schema/actor/booking/graph HMAC with five-minute expiry. Exact root links are persisted without changing historical updated_at values. Already registered cases cannot be reverified.

## Registration boundary

Initialize a retention-only secret once, without changing the application's existing auth/encryption key:
```
php scripts/booking-retention.php --schema=OWNED_TEST_SCHEMA --command=init-key --apply
```
It writes CSPRNG64hex atomically to application/private_booking_backups/<schemahash>/retention.key, file0600, and never prints its value. No GET/runtime request creates keys. Optional configured GULHOUSE_BOOKING_RETENTION_KEY takes precedence; a strong existing application key remains a compatibility fallback. Back up the retention key securely with the encrypted backup directory. Real schema init also requires the explicit real-schema flag below. HMAC includes schema so tokens cannot cross databases.

The web verification flow above is the normal super-admin workflow. Existing trusted `scripts/booking-retention.php` remains an operator alternative and loads database credentials opaquely. No CLI purge command exists. Specify exactly one booking ID. Synthetic schemas must match `gulhouse_test_*`; a configured real schema additionally requires `--allow-real-registration` and explicit human authorization. The commands below are examples only; none registers existing occupants automatically.

Fresh booking, before conversion:
```
php scripts/booking-retention.php --schema=OWNED_TEST_SCHEMA --command=register --booking=EXACT_ID --operator=reviewer --attestation="This exact synthetic booking was created solely as demo." --apply
```
Legacy converted booking requires explicit independently verified booking/tenant/stay/contract IDs. A reversible transaction computes the graph and rolls back all temporary binding:
```
php scripts/booking-retention.php --schema=OWNED_TEST_SCHEMA --command=plan-legacy --booking=EXACT_ID --tenant=EXACT_ID --stay=EXACT_ID --contract=EXACT_ID
```
Review exact node IDs/blockers; only if `can_register=true`, rerun register with same IDs plus `--fingerprint=RETURNED_FINGERPRINT`, operator, attestation, and `--apply`. The fingerprint binds all row content. Shared or ambiguous graphs remain rejected. No mapping inferred from a contract number or notes. Fresh demo conversions reject phone reuse of any existing tenant before changing that tenant. New normal conversions set exact source_booking_id for newly created tenant and new stay/contract; existing reused tenants are not adopted.

## Transaction and file safety

All touched tables must be InnoDB. Transaction locks actor, booking, candidate rows and rooms; rebuilds the graph; compares review fingerprint; creates and reads back a scoped AES-256-GCM backup of rows plus actual attachment bytes; deletes only proven IDs; retains global activity logs/import history/audit and all room/property/catalog masters. Room status releases only if no other active/reserved/internal stay exists. Unknown incoming FKs, unexpected relation columns, missing/outside ownership dependencies and shared document aliases block the entire purge. Cross references (proof/ledger/revision/payment/maintenance) never establish ownership.

Backup: denied application directory `private_booking_backups/<schema-hash>/`, random `.enc`, file0600; maximum32MiB attachment bytes per purge. Audit stores backup filename/hash and counts, never raw PII. Requires a strong retention secret >=32characters with >=8 distinct characters, initialized by the dedicated CLI above or explicitly configured. The existing strong application key is a compatibility fallback; shared auth configuration is never rotated by this feature. Preserve the key used for backup recovery. Known fallback, weak key, unavailable encryption, missing/unsafe files, failed write/readback block purge. Payment-proof paths must additionally match the current database's filename namespace, consistent with Payment_proof_model; foreign namespace files are protected.

Files are never unlinked inside the delete transaction. Commit records expected content hashes in gh_booking_file_gc, then controller attempts per-booking GC. Every unlink revalidates canonical safe path, no remaining references (including document aliases), and unchanged content hash. Failure leaves protected pending entries and visible operator notice. Retry explicitly:
```
php scripts/booking-retention.php --schema=OWNED_TEST_SCHEMA --command=gc --booking=EXACT_ID --apply
```
Backups/audit are retained; physical files referenced elsewhere are protected, not forcibly removed.

## Verification boundary

`tests/verify-booking-retention.php` executes actual model/database transactions only on the dedicated `.e2e/mobile/bookingretain26/runtime.json` synthetic schema. CIKO owns independent adversarial/HTTP checks. No production migration, real registration, archive, purge or file removal is part of this implementation task.
