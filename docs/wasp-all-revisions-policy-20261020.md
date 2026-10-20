# Backend revisions and rental policy — 20 October 2026

Implementation is local. No deployment, actual resident reset, actual booking classification, or actual financial transaction was performed by WASP. Root owns additive local migration approval and the separate unified resident authentication implementation.

## Delivered behavior

- Booking completion, including the old conversion route, changes only status to `done`. Existing `converted` history remains unchanged. WhatsApp destinations normalize Indonesian prefixes without changing stored phone values.
- Public intake uses source `website`; reservation alone creates administration. Reservation price and deposit come from the locked room master. New cases cannot adopt an existing tenant using a shared phone number.
- One-month boundaries use the original calendar day: 31 January → 28 February → 31 March. A boundary neither checks out the resident nor releases the room. Billing continues for an active versioned monthly stay, and central occupancy-overlap checks continue to protect its room after the first boundary.
- Before check-in, room changes preserve the same case, person, stay, draft contract and initial bill IDs, plus all actual payments and deposit ledger entries. Higher prices expose residuals and clear obsolete payment verification; lower prices expose credits without inventing refunds. Signed documents, facility inspection evidence, active occupancy, overlap and ambiguous multi-invoice accounting block unsafe moves.
- New reservations enroll rental policy version `rent-v1-20261020`; old active agreements and historical bills are not retroactively enrolled. Existing unreserved cases enroll when newly reserved.

## Rental accounting and physical checkout

Payment is requested H-2 from the monthly due date. The first two days after due are free of penalties; H+3 incurs Rp100,000 and every subsequent penalty day adds Rp100,000. Actual rent payments determine settlement, partial payment continues accrual, and full settlement or actual physical exit stops accrual. Deposit payments never count as rent settlement.

Reads do not create charges, bills, transfers or checkout. Penalty receipts use their own immutable ledger with actor, amount, date, reference and request-key validation. Optional proof supports cash payments. Both historical and current outstanding balances limit backdated receipts. Financial reports identify actual penalty income separately from rent.

H+7 unpaid rent creates a review flag only. An authorized administrator explicitly records a durable review and confirmation that debt remains owed. Physical overdue checkout still requires final facility inspection, returned keys, actual date and another retained-debt confirmation. It preserves rent and penalty receivables and does not silently deduct deposits or fabricate payments/refunds. Ordinary checkout retains settlement prerequisites. Existing notice dates remain preserved when upgrading to an overdue review.

New policy relations are included in the proven booking-demo retention graph, encrypted backup and reverse-order transactional deletion; cross-owner safeguards remain enabled. Maintenance roles cannot read penalty proof or financial policy details.

## Integration contract

`Lifecycle_model::administration()` exposes `reservation_quote`, `room_choices`, `room_change` and identity-safe `link_stays`. `detail()` exposes read-only `billing_policy` with anchor/next due/payment deadline, daily grace values, accrued/paid/outstanding totals, per-bill assessments, fee receipts and durable overdue review metadata.

Actions are `change_room` (`room_id`, `reason`, `resident_confirmed`), `penalty_payment` (`bill_id`, `amount`, `payment_date`, `reference_no`, stable `request_key`, optional `proof_file`), `overdue_checkout_review` (`planned_exit`, `reason`, `review_confirmed`, `retain_debt`, optional refund bank/account), and `checkout_overdue` (normal physical-checkout fields plus `retain_debt_confirmed`). Session penalty request keys clear after successful receipt only. Existing lifecycle action authentication, transaction and audit paths are reused.

Migrations: `database/patch_gulhouse_booking_done_20261020.sql` and `database/patch_gulhouse_rental_policy_20261020.sql`. Root applied the booking enum locally with unchanged table-data hashes. Policy DDL was verified twice on isolated schemas; actual policy application remains root-owned and gated by independent QC.

## Verification actually run

Commands use `D:/XAMPP/php/php.exe` from the repository root:

| Test | Result | Scope |
| --- | --- | --- |
| `tests/verify-booking-done.php` | 20/20 PASS | Synthetic transactions and child-table byte hashes |
| `tests/verify-lifecycle-revisions.php` | 24/24 PASS | Synthetic room moves, residuals/credits, immutable accounting, identity and evidence guards |
| `tests/verify-rental-policy.php` | 25/25 PASS | Pure date/accrual math; no database connection |
| `tests/verify-rental-policy-transactions.php` | 28/28 PASS | Dedicated `gulhouse_test_wasp_policy_*` schema, generator, ledger, H7 exit, occupancy and retention |

PHP syntax checks passed for Billing_policy_model, Lifecycle_model, Operations_model, Booking_retention_model, Lifecycle controller and booking helper. Generator tests use the dedicated schema to avoid touching other agents' fixtures. Independent CIKO HTTP/browser evidence is separate; these model tests do not prove native UI behavior, production deployment or real resident operations. No memory or Telegram updates were made.
