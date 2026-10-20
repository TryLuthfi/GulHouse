# Backend implementation and verification

2026-10-19, local GulHouse only. No push, deployment, or production writes performed by WASP.

## Delivered

- Dedicated resident cases with SHA256-hashed high-entropy access capabilities, one-hour resident sessions, staged intake with mandatory private KTP and optional marriage document, actual initial-rent verification before full biodata.
- Explicit existing-active/reserved onboarding that preserves original stay/tenant data and prior optional biodata; no automatic conversion of existing occupants.
- Resident portal exposes agreed rent/deposit and initial invoice actual payments/outstanding, with private payment-proof upload that never auto-verifies or creates financial entries. Maintenance cannot read financial proofs.
- Reserve creates draft contract plus an initial invoice before activation, avoiding a billing/check-in dependency loop. Activation enforces actual initial rent, complete biodata/KTP, deposit and finalized signed facility handover; old direct routes enforce managed-case gates too.
- Dedicated 19-item catalogue matching original facility forms; signed scans, private files, physical-signature human confirmation, explicit observed quantities/conditions, immutable final inspections with new revisions and scan duplicate protection. All readings initially uninspected. Maintenance links and stay-scoped history.
- Real local OCR adapter with bounded process lifetime/output; saved suggestions only, never automatic final status or charges. Root owns the OCR Python helper/dependencies and its separate verification.
- H-15 calendar notice flag, recomputed against actual exit; no automatic penalty. Admin-only pre-checkout notice correction preserves original notice date and records old/new plans plus revision reason; resident and post-checkout edits are rejected. Zero arrears plus same-day finalized exit inspection and keys confirmation before checkout. Refund obligation separated from actual payment, H+2 calendar, undisputed balance remains payable while a disputed balance is retained. Actual proof/date/reference mandatory; one ledger entry and no duplicate or overspend. Post-checkout resident portal retained.
- New controller RBAC/POST rules, private file authorization, finance/maintenance identity redaction and direct file denials, guards on legacy deposit/stay/payment paths for managed cases.

## Evidence

`tests/verify-lifecycle-backend.php`: 44 actual HTTP + isolated MariaDB assertions PASS against synthetic gulhouse_test_* runtime. Covers mandatory KTP, private resident payment-proof upload without fake verification, blocking proofs after checkout, profile/payment gates, initial invoice/reserve, check-in, all three signed inspection stages, immutable finals, notice correction authorization/audit/original-date retention and actual checkout SLA, pending vs actual refunds, proof requirement, disputed/undisputed balance, duplicate transfer rejection, post-checkout access, explicit existing onboarding/KTP/duplicate protection and original stay/biodata preservation.

`scripts/migrate-gulhouse-lifecycle.php`: CLI-only local allowlist, dry-run default, complete private SQL backup before --apply, before/after hashes and counts for every pre-existing gh_* table. Tested additive application on synthetic schema, all existing row hashes/counts unchanged. Real local schema application remains ZEYN-owned after final checks.

PHP lint PASS for 11 backend/controller/config/migration/test files. Independent CIKO reported 15/15 privacy/RBAC assertions PASS after identity redaction (separate report).

## Boundaries

These backend checks use synthetic identities/documents and a local isolated database. They do not establish accuracy on arbitrary handwriting, signature authenticity, bank transfer execution or production E2E behavior. Transfer entries record a verified real-world transfer; no bank API is invoked. Final browser/Playwright and visual QC are coordinated independently by ZEYN/CIKO. No memory files were updated.
