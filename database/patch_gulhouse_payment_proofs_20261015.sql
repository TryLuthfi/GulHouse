CREATE TABLE IF NOT EXISTS gh_payment_proofs (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 payment_id BIGINT UNSIGNED NOT NULL,
 stored_filename VARCHAR(100) NOT NULL,
 original_filename VARCHAR(255) NOT NULL,
 mime_type VARCHAR(40) NOT NULL,
 file_size INT UNSIGNED NOT NULL,
 description VARCHAR(500) NULL,
 uploaded_by INT UNSIGNED NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id),
 UNIQUE KEY uniq_payment_proof_filename (stored_filename),
 KEY idx_payment_proof_payment (payment_id),
 CONSTRAINT fk_payment_proof_payment FOREIGN KEY (payment_id) REFERENCES gh_payments(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
