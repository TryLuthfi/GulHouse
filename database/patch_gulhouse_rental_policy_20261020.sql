-- Versioned opt-in. Defaults do not enroll, reprice, or charge existing residents/bills.
ALTER TABLE gh_resident_cases ADD COLUMN IF NOT EXISTS billing_anchor_date DATE NULL;
ALTER TABLE gh_resident_cases ADD COLUMN IF NOT EXISTS billing_policy_version VARCHAR(30) NULL;
ALTER TABLE gh_resident_cases ADD COLUMN IF NOT EXISTS rolling_monthly TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE gh_checkout_requests ADD COLUMN IF NOT EXISTS overdue_review_id BIGINT UNSIGNED NULL;
CREATE TABLE IF NOT EXISTS gh_lifecycle_bill_policies (
 bill_id INT UNSIGNED NOT NULL PRIMARY KEY, case_id BIGINT UNSIGNED NOT NULL,
 anchor_date DATE NOT NULL, due_date DATE NOT NULL, policy_version VARCHAR(30) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY ix_policy_case(case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gh_lifecycle_penalty_payments (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, case_id BIGINT UNSIGNED NOT NULL,
 bill_id INT UNSIGNED NOT NULL, amount BIGINT UNSIGNED NOT NULL,
 payment_date DATE NOT NULL, reference_no VARCHAR(160) NOT NULL,
 request_key VARCHAR(100) NOT NULL, proof_file_id BIGINT UNSIGNED NULL,
 recorded_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_penalty_request(request_key), KEY ix_penalty_case_bill(case_id,bill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gh_lifecycle_overdue_reviews (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, case_id BIGINT UNSIGNED NOT NULL,
 bill_id INT UNSIGNED NOT NULL, due_date DATE NOT NULL,
 unpaid_snapshot BIGINT UNSIGNED NOT NULL, reviewed_by INT UNSIGNED NOT NULL,
 reason VARCHAR(1000) NOT NULL, retain_debt TINYINT(1) NOT NULL,
 policy_version VARCHAR(30) NOT NULL, reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 actual_exit_date DATE NULL, UNIQUE KEY uq_overdue_case(case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
