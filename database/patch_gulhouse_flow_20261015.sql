-- Additive link: a deposit payment has exactly one corresponding deposit ledger entry.
ALTER TABLE gh_deposit_transactions ADD COLUMN IF NOT EXISTS source_payment_id BIGINT UNSIGNED NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uniq_deposit_source_payment ON gh_deposit_transactions (source_payment_id);
-- Support more than one tenant stay in the same room and month without merging invoices.
ALTER TABLE gh_room_bills ADD COLUMN IF NOT EXISTS stay_id INT UNSIGNED NULL;
-- Keep the period FK indexed when the former room-period unique key is removed.
CREATE INDEX IF NOT EXISTS idx_bill_period_fk ON gh_room_bills (period_id);
ALTER TABLE gh_room_bills DROP INDEX IF EXISTS uniq_period_room;
CREATE UNIQUE INDEX IF NOT EXISTS uniq_period_room_stay ON gh_room_bills (period_id, room_id, stay_id);
ALTER TABLE gh_maintenance_tickets ADD COLUMN IF NOT EXISTS complaint_id BIGINT UNSIGNED NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uniq_maintenance_complaint ON gh_maintenance_tickets (complaint_id);
ALTER TABLE gh_expenses ADD COLUMN IF NOT EXISTS maintenance_ticket_id BIGINT UNSIGNED NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uniq_expense_maintenance ON gh_expenses (maintenance_ticket_id);
