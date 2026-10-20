# Automatic agreement-based monthly billing

20 October 2026. Local backend only; no actual bulk generation or real payment write. Root owns scheduler wrappers, installation, activation and receipts. No schema migration is required.

## Timing and accounting contract

`Operations_model::automatic_billing($asOf = NULL, $leadDays = 7, $apply = FALSE)` previews by default. Production callers use today's date in Asia/Jakarta; the optional model date supports isolated deterministic tests. Lead days are 2–31, default seven. Missing recurring bills are issued when their due date is at or before today plus the lead window, so the default H-7 issuance precedes the H-2 requested-payment deadline. Catch-up includes older missing periods and skips existing invoices without altering them.

Only recognized versioned, rolling lifecycle cases in active/notice status with a proven active stay qualify. Reserved, ended, unrecognized and unversioned legacy cases are excluded. Any actual checkout date also excludes a case even if its status is stale. Legacy accounts are not silently enrolled.

The initial reservation retains its rent-plus-deposit bill. Recurring offsets begin one calendar month after the original billing anchor; month-end clamping recovers the anchor in later months. New recurring bills use the agreed stay monthly-price snapshot, never a later room-master price, and their deposit_amount is zero. The CLI compatibility monthly generator also sets deposit to zero on newly generated later-period bills; existing historical rows keep their original values.

Read-only preview does not create periods, bills, fees or transfers. Apply holds a schema-scoped issuance lock shared with the compatibility generator, then locks each case/stay and revalidates eligibility. Existing chargeable invoices remain unchanged. An ambiguous unbound historical charge in the target room/period blocks that case for reconciliation. Successful issuance creates one invoice and its original-anchor policy relation. Repeated/overlapping runs are idempotent.

## Routes and immutable bills

`Jobs::automatic_billing($mode = 'preview', $leadDays = '7')` is CLI-only, preserves the constructor's exact synthetic/real-database opt-in safeguards, and does not accept an arbitrary external as-of date. It returns JSON with ok, mode, timezone, as_of, lead_days, horizon, candidate_cases, eligible_cases, planned_bills, created, existing, skipped, errors and database. Root's CLI wrapper calls this method with `--automatic`; daily scheduler installation is separately root-owned.

The web/native compatibility manual-generation controller now rejects with an automatic-billing explanation. `Master_model::save_payment_bill` always rejects, and `Manage::save_payment_bill` returns a clear read-only message without parsing financial fields. This blocks hidden/manual invoice editing through those existing endpoints; payment settlement remains a separate guarded operation.

## Source and verification

Changed backend files: Operations_model.php, Master_model.php, Manage.php, Operations.php and Jobs.php. Backups are in `.e2e/auto-billing-20261020/wasp-before/`. Root separately amended the CLI Security_hook allowlist and owns runner scripts; BUBU owns bill display/read-only UI.

Actual tests used the dedicated guarded WASP synthetic schema, never the shared QC schema for global billing:

- `tests/verify-automatic-billing.php`: **19/19 PASS** — preview no writes, H-8/H-7 timing, snapshot rates despite changed room master, no repeated deposit, historical rows/payment hashes unchanged, idempotency, eligibility exclusions, catch-up/month clamp recovery, policy due/anchor, read-only bill saves, compatibility generator and checkout stop.
- `tests/verify-automatic-billing-concurrency.php`: **4/4 PASS**, with two independent CLI processes/database connections. Overlapping jobs produce exactly one recurring invoice and one policy row using the agreed price and zero deposit.
- Actual `scripts/run-gulhouse-billing.php --automatic` preview on the dedicated schema returned valid JSON ok=true, WIB date and seven-day horizon, with created=0.
- Existing exact-payment regressions rerun after the read-only invoice change: **28/28 PASS**, plus true independent-process payment concurrency **4/4 PASS**.
- All five changed backend PHP files passed syntax checks.

These are backend/CLI test results, separate from CIKO's independent HTTP/browser checks and root's scheduler installation. No actual database-wide generation, production deployment or real resident charge was performed by WASP.
