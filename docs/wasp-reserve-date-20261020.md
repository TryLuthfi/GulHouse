# Reservation start-date correction

20 October 2026, local backend only. This supersedes the earlier editable reservation-end behavior.

New reservation action accepts editable `start_date` initialized from the intake plan. When omitted, older clients retain the existing planned start. When supplied, it must be a valid YYYY-MM-DD string; empty, malformed calendar dates, arrays, null and other non-string values are rejected before conversion. The server derives the end boundary as one calendar month, clamped at month end, and ignores any supplied `end_date`.

Within the existing transaction and case/room locks, the effective start is used for occupancy conflict detection, then persisted consistently as case planned start, stay check-in, contract start, initial invoice period/due and original rental-policy anchor. End dates on the newly created stay/contract use the derived boundary. Already-reserved cases reject a repeat reservation without changing dates, payments or history. The boundary is not physical checkout.

Only `master/application/models/Lifecycle_model.php` changed in backend source. Backup: `.e2e/reserve-date-20261020/Lifecycle_model-before.php`. No migration, mobile source change, deployment or actual resident write.

Actual verification: `D:/XAMPP/php/php.exe tests/verify-lifecycle-revisions.php` **37/37 PASS** on guarded synthetic fixtures, covering the new effective-date behavior, derived-end year overflow rejection (start 9999-12-31), and previous room-change/financial safety checks. PHP lint passed. Browser and independent HTTP verification remain CIKO-owned.
