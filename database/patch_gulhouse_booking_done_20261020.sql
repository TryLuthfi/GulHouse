-- Explicit additive enum expansion only. Existing converted rows retain their legacy meaning.
ALTER TABLE gh_booking_requests MODIFY COLUMN status ENUM('new','contacted','survey','converted','cancelled','done') NOT NULL DEFAULT 'new';
