-- Apply once to the existing competition database before uploading the new PHP files.
ALTER TABLE registrations
  ADD COLUMN entry_type VARCHAR(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'solo' AFTER reference,
  ADD COLUMN team_members_json TEXT NULL AFTER entry_type;

CREATE TABLE IF NOT EXISTS sponsors (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    logo_path VARCHAR(180) NOT NULL,
    website_url VARCHAR(300) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_sponsors_order (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
