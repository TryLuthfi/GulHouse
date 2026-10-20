# Exact remaining payment settlement

20 October 2026. Local backend implementation; no schema change, deployment or actual payment write. Backups: `.e2e/payment-settlement-20261020/`.

Backend ownership: `master/application/models/Master_model.php` and `master/application/controllers/Manage.php`. BUBU owns the separate payment view/JavaScript changes.

New payment records accept only rent or deposit, with method Transfer. Amount must exactly match the remaining obligation under transaction locks; underpayments, overpayments and stale quotes reject without writing. Rent sums actual rent transactions instead of cached paid_total. Deposit uses the proven stay obligation and shared stay ledger across all monthly invoices, also counting imported deposit payments missing a source ledger row. Missing/ambiguous stay identity or an ended/refunded stay cannot receive a new deposit.

Locks acquire the stay before its bill, then read current payment/ledger rows with locking reads. Request keys provide metadata-checked idempotent retries before the zero-balance check. Cross-invoice/type request-key reuse rejects. New transfers need no manually entered reference. No fake rent transaction is used for deposit accounting.

Historical payment edits are notes-only. The original amount, type, invoice, method, dates, reference and source key stay unchanged, and the historical deposit ledger is not recreated. Supplied changes to locked fields reject. Cash, partial and other historical records remain intact.

`Manage::payments` supplies a batched `payment_quotes[bill_id]` map for visible rows and invoice options. Fields: `rent_remaining`, `deposit_remaining`, `can_pay_rent`, `can_pay_deposit`, `rent_available`, `deposit_available`, `rent_reason`, `deposit_reason`, `deposit_balance`, `deposit_required`, `method`. Availability/reasons distinguish an unsupported quote from a settled balance. New form fields: payment_id=0, room_bill_id, payment_type, exact amount, payment_date, stable request_key, method=Transfer, notes. Historical notes-only fields: payment_id, room_bill_id, notes; absent historical fields are not synthesized as empty replacements.

Verification actually executed using D:/XAMPP/php/php.exe:

- `tests/verify-payment-settlement.php`: **28/28 PASS** on guarded synthetic fixtures. Includes stale cached totals, partial remaining amounts, under/over/cash/other rejection, repeated keys and stale forms, cross-month deposit protection, historical immutability/reference forgery, ended/refunded stay protection, imported unlinked deposits, missing stay availability and unknown deposit obligation availability.
- `tests/verify-payment-settlement-concurrency.php`: **4/4 PASS** using four independent CLI worker processes/database connections. Both simultaneous same-invoice rent submissions and different-month same-stay deposit submissions yield exactly one successful receipt, with no excess amount or duplicate deposit ledger row. This is not a concurrency claim based on PHP's single-thread HTTP development server.
- PHP syntax checks passed for both backend source files and both tests.

Receipt: `.e2e/payment-settlement-20261020/wasp-results.json`. Independent CIKO HTTP/browser testing is separate. No real browser cookie or actual payment transaction was used.
