-- Import this file into the competition database with phpMyAdmin.
-- This database is independent of the main DezignBank platform.
CREATE TABLE IF NOT EXISTS registrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    email_normalized VARCHAR(254) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    mobile_e164 VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    college_name VARCHAR(160) NOT NULL,
    college_city VARCHAR(100) NOT NULL,
    year_of_study TINYINT UNSIGNED NOT NULL,
    consented_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_registrations_reference (reference),
    UNIQUE KEY uq_registrations_email (email_normalized),
    KEY idx_registrations_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One owner account. Only a salted password hash is stored here.
CREATE TABLE IF NOT EXISTS admin_auth (
    id TINYINT UNSIGNED NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    last_login_at DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    purpose VARCHAR(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    attempted_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_admin_attempts (ip_hash, purpose, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
