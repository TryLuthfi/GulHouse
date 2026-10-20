-- Additive role expansion; preserves existing accounts and passwords.
ALTER TABLE gh_admin_users MODIFY role ENUM('owner','super_admin','admin','manager','finance','maintenance','viewer') NOT NULL DEFAULT 'admin';
