-- ============================================================================
-- Lab Automation System — Complete Database Setup (single-file installer)
-- Engine: InnoDB · Charset: utf8mb4 · Import via phpMyAdmin or the mysql CLI.
--
-- This file is the ONLY file required to initialize the project on a fresh
-- MySQL/MariaDB server. It supersedes and consolidates:
--   - lab_automation.sql              (base schema + base seed data)
--   - migration_workspaces.sql        (multi-tenant workspace columns)
--   - migration_invitation_tokens.sql (invitation flow)
--   - migration_otp.sql               (email OTP verification flow)
--   - seed_data.sql                   (extended demo data)
--   - password_resets table           (forgot-password flow)
--
-- Usage:
--   mysql -u root -p < lab_automation_complete.sql
--   -- or import this single file through phpMyAdmin --
--
-- Default password for every seed user is: ChangeMe123!
-- ============================================================================

-- If importing into an existing database (e.g. shared hosting),
-- select the target database in phpMyAdmin first, then import this file.

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Drop tables (children first) so this file is safe to re-run on a
-- non-empty database, not just a brand-new one.
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS email_verifications;
DROP TABLE IF EXISTS pending_signups;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS invitation_tokens;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS cpri_records;
DROP TABLE IF EXISTS measurements;
DROP TABLE IF EXISTS testing_records;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS testing_type_parameters;
DROP TABLE IF EXISTS testing_types;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS workspaces;

-- ----------------------------------------------------------------------------
-- 1. workspaces  (multi-tenant root — every tenant-scoped table below hangs
--                 off workspace_id. owner_id -> users(id) is added later via
--                 ALTER TABLE, once the users table exists, to break the
--                 workspaces <-> users circular dependency.)
-- ----------------------------------------------------------------------------
CREATE TABLE workspaces (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    slug        VARCHAR(100) NOT NULL,
    owner_id    INT UNSIGNED NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_workspaces_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. departments
-- ----------------------------------------------------------------------------
CREATE TABLE departments (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id  INT UNSIGNED NOT NULL,
    name          VARCHAR(100) NOT NULL,
    description   VARCHAR(255) NULL,
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_departments_ws_name (workspace_id, name),
    KEY idx_departments_workspace (workspace_id),
    CONSTRAINT fk_departments_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. users  (a "Tester" is simply a user with role = testing_engineer |
--            lab_technician, scoped by department_id)
-- ----------------------------------------------------------------------------
CREATE TABLE users (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id           INT UNSIGNED NOT NULL,
    name                   VARCHAR(120) NOT NULL,
    email                  VARCHAR(150) NOT NULL,
    email_verified         TINYINT(1) NOT NULL DEFAULT 0,
    password_hash          VARCHAR(255) NOT NULL,
    role                   ENUM('administrator','testing_engineer','lab_technician','quality_manager','auditor') NOT NULL,
    department_id          INT UNSIGNED NULL,
    avatar                 VARCHAR(500) NULL,
    status                 ENUM('active','inactive') NOT NULL DEFAULT 'active',
    invitation_status      ENUM('pending','activated') NULL DEFAULT NULL,
    failed_login_attempts  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until           DATETIME NULL,
    last_login_at          DATETIME NULL,
    password_changed_at    DATETIME NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_workspace (workspace_id),
    KEY idx_users_department (department_id),
    KEY idx_users_role (role),
    KEY idx_users_status (status),
    CONSTRAINT fk_users_workspace   FOREIGN KEY (workspace_id) REFERENCES workspaces(id),
    CONSTRAINT fk_users_department  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Close the workspaces <-> users circular dependency now that users exists.
ALTER TABLE workspaces
    ADD CONSTRAINT fk_workspaces_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL;

-- ----------------------------------------------------------------------------
-- 4. user_preferences  (per-user UI prefs: sidebar state, theme, notifications)
-- ----------------------------------------------------------------------------
CREATE TABLE user_preferences (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    pref_key    VARCHAR(80) NOT NULL,
    pref_value  VARCHAR(500) NULL,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_pref (user_id, pref_key),
    CONSTRAINT fk_user_pref_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. testing_types  (versioned — editing tolerances on a type with active
--                    assignments creates a new row)
-- ----------------------------------------------------------------------------
CREATE TABLE testing_types (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id  INT UNSIGNED NOT NULL,
    name          VARCHAR(150) NOT NULL,
    category      VARCHAR(100) NULL,
    description   TEXT NULL,
    version       INT UNSIGNED NOT NULL DEFAULT 1,
    status        ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_by    INT UNSIGNED NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_testing_types_workspace (workspace_id),
    KEY idx_testing_types_name_version (name, version),
    KEY idx_testing_types_status (status),
    KEY idx_testing_types_category (category),
    CONSTRAINT fk_testing_types_workspace  FOREIGN KEY (workspace_id) REFERENCES workspaces(id),
    CONSTRAINT fk_testing_types_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. testing_type_parameters
-- ----------------------------------------------------------------------------
CREATE TABLE testing_type_parameters (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    testing_type_id      INT UNSIGNED NOT NULL,
    name                 VARCHAR(150) NOT NULL,
    unit                 VARCHAR(30) NULL,
    min_value            DECIMAL(12,4) NULL,
    nominal_value        DECIMAL(12,4) NULL,
    max_value            DECIMAL(12,4) NULL,
    required_instrument  VARCHAR(150) NULL,
    sort_order           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ttp_testing_type (testing_type_id),
    CONSTRAINT fk_ttp_testing_type FOREIGN KEY (testing_type_id) REFERENCES testing_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. products
-- ----------------------------------------------------------------------------
CREATE TABLE products (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id     INT UNSIGNED NOT NULL,
    serial_number    VARCHAR(80) NOT NULL,
    spec_model       VARCHAR(150) NOT NULL,
    department_id    INT UNSIGNED NOT NULL,
    manufactured_at  DATE NULL,
    status           ENUM(
                        'registered','assigned','in_testing','submitted_for_review',
                        'failed_pending_rework','pending_cpri_approval','approved','released',
                        'on_hold','rejected','cancelled'
                     ) NOT NULL DEFAULT 'registered',
    attempt_count    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    registered_by    INT UNSIGNED NOT NULL,
    notes            TEXT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_products_ws_serial (workspace_id, serial_number),
    KEY idx_products_workspace (workspace_id),
    KEY idx_products_department (department_id),
    KEY idx_products_status (status),
    KEY idx_products_registered_by (registered_by),
    KEY idx_products_created_at (created_at),
    CONSTRAINT fk_products_workspace     FOREIGN KEY (workspace_id) REFERENCES workspaces(id),
    CONSTRAINT fk_products_department    FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_products_registered_by FOREIGN KEY (registered_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. testing_records  (the entity behind a Testing Record / measurement-entry page)
-- ----------------------------------------------------------------------------
CREATE TABLE testing_records (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id     INT UNSIGNED NOT NULL,
    product_id       INT UNSIGNED NOT NULL,
    testing_type_id  INT UNSIGNED NOT NULL,
    department_id    INT UNSIGNED NOT NULL,
    tester_id        INT UNSIGNED NOT NULL,
    assigned_by      INT UNSIGNED NULL,
    status           ENUM('assigned','in_testing','submitted_for_review','passed','failed','on_hold') NOT NULL DEFAULT 'assigned',
    attempt_number   TINYINT UNSIGNED NOT NULL DEFAULT 1,
    due_date         DATE NULL,
    started_at       DATETIME NULL,
    submitted_at     DATETIME NULL,
    reviewed_by      INT UNSIGNED NULL,
    review_decision  ENUM('pass','fail') NULL,
    review_reason    TEXT NULL,
    reviewed_at      DATETIME NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_testing_records_workspace (workspace_id),
    KEY idx_tr_product (product_id),
    KEY idx_tr_testing_type (testing_type_id),
    KEY idx_tr_department (department_id),
    KEY idx_tr_tester (tester_id),
    KEY idx_tr_status (status),
    KEY idx_tr_assigned_by (assigned_by),
    KEY idx_tr_due_date (due_date),
    CONSTRAINT fk_testing_records_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id),
    CONSTRAINT fk_tr_product      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_tr_testing_type FOREIGN KEY (testing_type_id) REFERENCES testing_types(id),
    CONSTRAINT fk_tr_department   FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_tr_tester       FOREIGN KEY (tester_id) REFERENCES users(id),
    CONSTRAINT fk_tr_assigned_by  FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_tr_reviewed_by  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. measurements  (immutable once locked_at is set)
-- ----------------------------------------------------------------------------
CREATE TABLE measurements (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    testing_record_id  INT UNSIGNED NOT NULL,
    parameter_id       INT UNSIGNED NOT NULL,
    recorded_value     DECIMAL(12,4) NOT NULL,
    unit               VARCHAR(30) NULL,
    instrument         VARCHAR(150) NULL,
    in_tolerance       TINYINT(1) NOT NULL DEFAULT 1,
    recorded_by        INT UNSIGNED NOT NULL,
    recorded_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    locked_at          DATETIME NULL,
    KEY idx_m_testing_record (testing_record_id),
    KEY idx_m_parameter (parameter_id),
    CONSTRAINT fk_m_testing_record FOREIGN KEY (testing_record_id) REFERENCES testing_records(id) ON DELETE CASCADE,
    CONSTRAINT fk_m_parameter      FOREIGN KEY (parameter_id) REFERENCES testing_type_parameters(id),
    CONSTRAINT fk_m_recorded_by    FOREIGN KEY (recorded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. cpri_records  (one row per decision — a rejection then a later approval
--                    is two rows, never an overwrite)
-- ----------------------------------------------------------------------------
CREATE TABLE cpri_records (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id          INT UNSIGNED NOT NULL,
    decision            ENUM('approved','rejected','on_hold') NOT NULL,
    decided_by          INT UNSIGNED NOT NULL,
    reason              TEXT NULL,
    certificate_number  VARCHAR(60) NULL,
    decided_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cpri_product (product_id),
    KEY idx_cpri_decided_by (decided_by),
    KEY idx_cpri_decided_at (decided_at),
    CONSTRAINT fk_cpri_product    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_cpri_decided_by FOREIGN KEY (decided_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11. attachments  (polymorphic: testing_record | product | cpri_record)
-- ----------------------------------------------------------------------------
CREATE TABLE attachments (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attachable_type  ENUM('testing_record','product','cpri_record') NOT NULL,
    attachable_id    INT UNSIGNED NOT NULL,
    file_name        VARCHAR(255) NOT NULL,
    file_path        VARCHAR(500) NOT NULL,
    file_type        VARCHAR(100) NULL,
    file_size        INT UNSIGNED NULL,
    uploaded_by      INT UNSIGNED NOT NULL,
    uploaded_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_attachments_polymorphic (attachable_type, attachable_id),
    CONSTRAINT fk_attachments_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 12. notifications  (in-app notifications — dashboard bell)
-- ----------------------------------------------------------------------------
CREATE TABLE notifications (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id  INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    title         VARCHAR(200) NOT NULL,
    message       TEXT NULL,
    type          ENUM('info','success','warning','error','assignment') NOT NULL DEFAULT 'info',
    link          VARCHAR(500) NULL,
    is_read       TINYINT(1) NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_workspace (workspace_id),
    KEY idx_notif_user_read (user_id, is_read),
    KEY idx_notif_created_at (created_at),
    CONSTRAINT fk_notifications_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 13. settings  (system-level configuration, per workspace)
-- ----------------------------------------------------------------------------
CREATE TABLE settings (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id   INT UNSIGNED NOT NULL,
    setting_key    VARCHAR(80) NOT NULL,
    setting_value  TEXT NULL,
    category       VARCHAR(50) NOT NULL DEFAULT 'general',
    updated_by     INT UNSIGNED NULL,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_settings_ws_key (workspace_id, setting_key),
    KEY idx_settings_workspace (workspace_id),
    KEY idx_settings_category (category),
    CONSTRAINT fk_settings_workspace  FOREIGN KEY (workspace_id) REFERENCES workspaces(id),
    CONSTRAINT fk_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 14. audit_log  ("History" — append-only, no update/delete path in the app layer)
-- ----------------------------------------------------------------------------
CREATE TABLE audit_log (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id  INT UNSIGNED NOT NULL,
    actor_id      INT UNSIGNED NULL,
    action        VARCHAR(100) NOT NULL,
    entity_type   VARCHAR(60) NOT NULL,
    entity_id     INT UNSIGNED NOT NULL,
    before_json   JSON NULL,
    after_json    JSON NULL,
    ip_address    VARCHAR(45) NULL,
    user_agent    VARCHAR(300) NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_log_workspace (workspace_id),
    KEY idx_audit_actor (actor_id),
    KEY idx_audit_entity (entity_type, entity_id),
    KEY idx_audit_created_at (created_at),
    KEY idx_audit_action (action),
    CONSTRAINT fk_audit_log_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id),
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 15. invitation_tokens  (admin invites a user; user activates via emailed link)
-- ----------------------------------------------------------------------------
CREATE TABLE invitation_tokens (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id  INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    token         VARCHAR(128) NOT NULL,
    expires_at    DATETIME NOT NULL,
    used_at       DATETIME NULL,
    created_by    INT UNSIGNED NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_invitation_token (token),
    KEY idx_invitation_workspace (workspace_id),
    KEY idx_invitation_user (user_id),
    KEY idx_invitation_expires (expires_at),
    CONSTRAINT fk_invitation_tokens_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id),
    CONSTRAINT fk_invitation_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_invitation_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 16. pending_signups  (admin self-registration, held until email is verified —
--                       intentionally has NO workspace_id: the workspace is
--                       created only after OTP verification succeeds)
-- ----------------------------------------------------------------------------
CREATE TABLE pending_signups (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_token   VARCHAR(128) NOT NULL,
    workspace_name  VARCHAR(150) NOT NULL,
    full_name       VARCHAR(120) NOT NULL,
    email           VARCHAR(255) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    email_verified  TINYINT(1) NOT NULL DEFAULT 0,
    expires_at      DATETIME NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pending_email (email),
    INDEX idx_pending_session (session_token),
    INDEX idx_pending_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 17. email_verifications  (OTP records for signup and invitation flows —
--                           also table-free of workspace_id for the same reason)
-- ----------------------------------------------------------------------------
CREATE TABLE email_verifications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL,
    otp_hash        VARCHAR(255) NOT NULL,
    purpose         ENUM('admin_signup','user_invitation') NOT NULL,
    reference_token VARCHAR(255) NULL,
    attempts        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at      DATETIME NOT NULL,
    verified_at     DATETIME NULL,
    last_sent_at    DATETIME NOT NULL,
    send_count      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ev_email_purpose (email, purpose),
    INDEX idx_ev_reference (reference_token),
    INDEX idx_ev_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 18. password_resets  (forgot-password flow — token-based, one per user)
-- ----------------------------------------------------------------------------
CREATE TABLE password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token       VARCHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_token (token),
    KEY idx_user (user_id),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Seed data — default workspace + enough records to log in and exercise
-- every role immediately. Default password for every seed user is:
--   ChangeMe123!
-- bcrypt hash generated with password_hash('ChangeMe123!', PASSWORD_BCRYPT)
-- ============================================================================

-- 1) Default workspace (id fixed at 1; owner_id is backfilled once the
--    admin user below exists).
INSERT INTO workspaces (id, name, slug, status) VALUES
  (1, 'Default Workspace', 'default', 'active');

-- 2) Departments (workspace 1)
INSERT INTO departments (workspace_id, name, description, status) VALUES
  (1, 'HV Lab',           'High-voltage testing',                          'active'),
  (1, 'Insulation Lab',   'Insulation and dielectric testing',             'active'),
  (1, 'Mechanical Lab',   'Mechanical and enclosure testing',              'active'),
  (1, 'Environmental Lab','Temperature, humidity & UV exposure testing',   'active'),
  (1, 'EMC Lab',          'Electromagnetic compatibility testing',         'inactive');

-- 3) Users (workspace 1) — ids resolve to 1..8 in insertion order
INSERT INTO users (workspace_id, name, email, email_verified, password_hash, role, department_id, status, last_login_at) VALUES
  (1, 'System Administrator', 'admin@labauto.local',      1, '$2y$10$lrohAr6ykaNRuQwzwixJie.kTYHvdd3kXKaDe/uHaMDk/CtNnoBn.', 'administrator',    NULL, 'active', NOW() - INTERVAL 45 MINUTE),
  (1, 'R. Chowdhury',         'engineer@labauto.local',   1, '$2y$10$lrohAr6ykaNRuQwzwixJie.kTYHvdd3kXKaDe/uHaMDk/CtNnoBn.', 'testing_engineer',    1, 'active', NOW() - INTERVAL 75 MINUTE),
  (1, 'A. Fernandes',         'technician@labauto.local', 1, '$2y$10$lrohAr6ykaNRuQwzwixJie.kTYHvdd3kXKaDe/uHaMDk/CtNnoBn.', 'lab_technician',      1, 'active', NOW() - INTERVAL 16 HOUR),
  (1, 'J. Rao',               'qm@labauto.local',         1, '$2y$10$lrohAr6ykaNRuQwzwixJie.kTYHvdd3kXKaDe/uHaMDk/CtNnoBn.', 'quality_manager',     2, 'active', NOW() - INTERVAL 25 HOUR),
  (1, 'S. Verma',             'auditor@labauto.local',    1, '$2y$10$lrohAr6ykaNRuQwzwixJie.kTYHvdd3kXKaDe/uHaMDk/CtNnoBn.', 'auditor',          NULL, 'active', NOW() - INTERVAL 3 DAY),
  (1, 'P. Nakamura',          'p.nakamura@labauto.local', 1, '$2y$10$lrohAr6ykaNRuQwzwixJie.kTYHvdd3kXKaDe/uHaMDk/CtNnoBn.', 'lab_technician',      2, 'active', NOW() - INTERVAL 2 HOUR),
  (1, 'K. Okonkwo',           'k.okonkwo@labauto.local',  1, '$2y$10$lrohAr6ykaNRuQwzwixJie.kTYHvdd3kXKaDe/uHaMDk/CtNnoBn.', 'testing_engineer',    3, 'active', NOW() - INTERVAL 18 HOUR),
  (1, 'L. Garcia',            'l.garcia@labauto.local',   1, '$2y$10$lrohAr6ykaNRuQwzwixJie.kTYHvdd3kXKaDe/uHaMDk/CtNnoBn.', 'lab_technician',      3, 'inactive', NOW() - INTERVAL 18 DAY);

-- Backfill workspace owner now that the administrator (user id 1) exists.
UPDATE workspaces SET owner_id = 1 WHERE id = 1;

-- 4) Testing types (workspace 1) — ids resolve to 1..6 in insertion order
INSERT INTO testing_types (workspace_id, name, category, description, version, status, created_by) VALUES
  (1, 'Insulation Test',          'Electrical',    'Measures insulation resistance and dielectric strength of electrical equipment.', 1, 'active',   1),
  (1, 'Dielectric Strength Test', 'Electrical',    'High-voltage withstand test to verify insulation integrity under stress.',       1, 'active',   1),
  (1, 'Mechanical Endurance',     'Mechanical',    'Mechanical durability and enclosure integrity assessment.',                      1, 'active',   1),
  (1, 'IP Rating Assessment',     'Mechanical',    'Ingress protection rating verification per IEC 60529.',                          1, 'active',   1),
  (1, 'Thermal Cycling',          'Environmental', 'Thermal shock and cycling endurance assessment.',                                2, 'active',   1),
  (1, 'UV Resistance (Legacy)',   'Environmental', 'UV exposure resistance testing — superseded by Thermal Cycling.',               1, 'archived', 1);

-- 5) Testing type parameters — ids resolve to 1..11 in insertion order
INSERT INTO testing_type_parameters (testing_type_id, name, unit, min_value, nominal_value, max_value, required_instrument, sort_order) VALUES
  (1, 'Insulation Resistance', 'MΩ',  100.0000, NULL,     NULL,     'Megger MIT525',   1),
  (1, 'Dielectric Strength',   'kV',   20.0000,  22.5000,  25.0000, 'HiPot Tester',    2),
  (1, 'Ambient Temperature',   '°C',   18.0000,  23.0000,  28.0000, 'Thermometer',     3),
  (2, 'Withstand Voltage',     'kV',   28.0000,  30.0000,  32.0000, 'HiPot Tester',    1),
  (2, 'Leakage Current',       'mA',    0.0000,  NULL,      5.0000, 'Leakage Clamp',   2),
  (2, 'Test Duration',         's',    60.0000,  60.0000,  60.0000, 'Stopwatch',       3),
  (3, 'Impact Force',          'J',     5.0000,   7.5000,  10.0000, 'Impact Tester',   1),
  (3, 'Enclosure Rating',      'IP',   54.0000,  65.0000,  67.0000, 'IP Test Chamber', 2),
  (3, 'Vibration Frequency',   'Hz',   10.0000,  50.0000, 100.0000, 'Vibration Tester',3),
  (3, 'Temperature Range',     '°C', -20.0000,  23.0000,  55.0000, 'Thermal Chamber',  4),
  (3, 'Load Capacity',         'kg',    5.0000,  10.0000,  15.0000, 'Load Cell',       5);

-- 6) Products (workspace 1) — ids resolve to 1..12 in insertion order
INSERT INTO products (workspace_id, serial_number, spec_model, department_id, manufactured_at, status, attempt_count, registered_by, notes, created_at) VALUES
  (1, 'FZ-2026-0451', 'Transformer T-400kV',    1, '2026-07-20', 'in_testing',            1, 2, 'High-priority unit for client delivery Q3', '2026-08-05 10:30:00'),
  (1, 'FZ-2026-0452', 'Switchgear SG-12',       1, '2026-07-18', 'submitted_for_review',  1, 2, NULL, '2026-08-04 14:15:00'),
  (1, 'FZ-2026-0453', 'Cable Termination CT-72',2, '2026-07-15', 'pending_cpri_approval', 1, 2, 'All tests passed — awaiting CPRI sign-off', '2026-08-03 09:00:00'),
  (1, 'FZ-2026-0454', 'Bushing B-245',          1, '2026-07-10', 'approved',              1, 2, 'CPRI approved on Aug 2', '2026-08-01 11:20:00'),
  (1, 'FZ-2026-0455', 'Insulator INS-33',       2, '2026-08-01', 'registered',            0, 7, NULL, '2026-08-06 16:45:00'),
  (1, 'FZ-2026-0456', 'Enclosure ENC-IP65',     3, '2026-07-22', 'in_testing',            1, 7, NULL, '2026-08-02 08:30:00'),
  (1, 'FZ-2026-0457', 'Surge Arrester SA-10',   1, '2026-07-08', 'failed_pending_rework', 2, 2, 'Failed insulation test — rework initiated', '2026-07-29 13:10:00'),
  (1, 'FZ-2026-0458', 'Transformer T-220kV',    1, '2026-06-25', 'released',              1, 2, 'Released for shipment', '2026-07-25 09:45:00'),
  (1, 'FZ-2026-0459', 'Cable Joint CJ-66',      2, '2026-07-12', 'on_hold',               1, 7, 'Awaiting replacement part', '2026-07-22 10:00:00'),
  (1, 'FZ-2026-0460', 'Panel Board PB-LV',      3, '2026-08-03', 'assigned',              0, 7, NULL, '2026-08-07 07:00:00'),
  (1, 'FZ-2026-0461', 'CT Metering Unit',       1, '2026-07-28', 'in_testing',            1, 2, NULL, '2026-08-06 11:30:00'),
  (1, 'FZ-2026-0462', 'VCB Breaker 36kV',       1, '2026-08-05', 'registered',            0, 2, 'New registration', '2026-08-07 08:15:00');

-- 7) Testing records (workspace 1) — ids resolve to 1..10 in insertion order
INSERT INTO testing_records (workspace_id, product_id, testing_type_id, department_id, tester_id, assigned_by, status, attempt_number, due_date, started_at, submitted_at, reviewed_by, review_decision, reviewed_at, created_at) VALUES
  (1, 1, 1, 1, 3, 2, 'in_testing',           1, '2026-08-10', '2026-08-05 11:00:00', NULL, NULL, NULL, NULL, '2026-08-05 10:45:00'),
  (1, 1, 2, 1, 3, 2, 'assigned',             1, '2026-08-12', NULL, NULL, NULL, NULL, NULL, '2026-08-05 10:45:00'),
  (1, 2, 1, 1, 3, 2, 'submitted_for_review', 1, '2026-08-08', '2026-08-04 15:00:00', '2026-08-07 14:30:00', NULL, NULL, NULL, '2026-08-04 14:30:00'),
  (1, 3, 2, 2, 6, 2, 'passed',               1, '2026-08-06', '2026-08-03 10:00:00', '2026-08-05 16:00:00', 2, 'pass', '2026-08-06 09:00:00', '2026-08-03 09:15:00'),
  (1, 6, 3, 3, 7, 2, 'in_testing',           1, '2026-08-09', '2026-08-02 09:00:00', NULL, NULL, NULL, NULL, '2026-08-02 08:45:00'),
  (1, 6, 4, 3, 7, 2, 'assigned',             1, '2026-08-11', NULL, NULL, NULL, NULL, NULL, '2026-08-02 08:45:00'),
  (1, 7, 1, 1, 3, 2, 'failed',               2, '2026-08-01', '2026-07-30 09:30:00', '2026-07-31 15:00:00', 2, 'fail', '2026-08-01 10:00:00', '2026-07-30 09:00:00'),
  (1, 4, 2, 1, 3, 2, 'passed',               1, '2026-08-03', '2026-08-01 12:00:00', '2026-08-02 16:00:00', 2, 'pass', '2026-08-03 09:00:00', '2026-08-01 11:30:00'),
  (1, 10, 3, 3, 7, 2, 'assigned',            1, '2026-08-14', NULL, NULL, NULL, NULL, NULL, '2026-08-07 07:15:00'),
  (1, 11, 1, 1, 6, 2, 'in_testing',          1, '2026-08-13', '2026-08-06 12:00:00', NULL, NULL, NULL, NULL, '2026-08-06 11:45:00');

-- 8) Measurements for passed tests
INSERT INTO measurements (testing_record_id, parameter_id, recorded_value, unit, instrument, in_tolerance, recorded_by, recorded_at, locked_at) VALUES
  (4, 4, 30.5000, 'kV', 'HiPot Tester', 1, 6, '2026-08-05 14:30:00', '2026-08-06 09:00:00'),
  (4, 5, 2.1000, 'mA', 'Leakage Clamp', 1, 6, '2026-08-05 14:45:00', '2026-08-06 09:00:00'),
  (4, 6, 60.0000, 's', 'Stopwatch', 1, 6, '2026-08-05 15:00:00', '2026-08-06 09:00:00'),
  (8, 4, 31.0000, 'kV', 'HiPot Tester', 1, 3, '2026-08-02 14:00:00', '2026-08-03 09:00:00'),
  (8, 5, 1.8000, 'mA', 'Leakage Clamp', 1, 3, '2026-08-02 14:15:00', '2026-08-03 09:00:00'),
  (8, 6, 60.0000, 's', 'Stopwatch', 1, 3, '2026-08-02 14:30:00', '2026-08-03 09:00:00');

-- Measurements for failed test (out of tolerance)
INSERT INTO measurements (testing_record_id, parameter_id, recorded_value, unit, instrument, in_tolerance, recorded_by, recorded_at, locked_at) VALUES
  (7, 1, 45.0000, 'MΩ', 'Megger MIT525', 0, 3, '2026-07-31 14:00:00', '2026-08-01 10:00:00'),
  (7, 2, 18.5000, 'kV', 'HiPot Tester', 0, 3, '2026-07-31 14:20:00', '2026-08-01 10:00:00'),
  (7, 3, 24.0000, '°C', 'Thermometer', 1, 3, '2026-07-31 14:30:00', '2026-08-01 10:00:00');

-- 9) CPRI record
INSERT INTO cpri_records (product_id, decision, decided_by, reason, certificate_number, decided_at) VALUES
  (4, 'approved', 4, 'All testing parameters within specification. Product meets CPRI standards.', 'CPRI-2026-0454-A', '2026-08-02 15:00:00');

-- 10) Notifications (workspace 1)
INSERT INTO notifications (workspace_id, user_id, title, message, type, link, is_read, created_at) VALUES
  (1, 1, 'Test submitted for review', 'A. Fernandes submitted measurements for Switchgear SG-12', 'warning', '/lab-automation/testing/detail.php?id=3', 0, NOW() - INTERVAL 12 MINUTE),
  (1, 1, 'CPRI approval needed', 'Cable Termination CT-72 is pending CPRI approval', 'info', '/lab-automation/products/detail.php?id=3', 0, NOW() - INTERVAL 1 HOUR),
  (1, 1, 'Test failed — rework required', 'Surge Arrester SA-10 failed insulation resistance test', 'error', '/lab-automation/testing/detail.php?id=7', 0, NOW() - INTERVAL 26 HOUR),
  (1, 1, 'New product registered', 'VCB Breaker 36kV has been registered by K. Okonkwo', 'success', '/lab-automation/products/detail.php?id=12', 1, NOW() - INTERVAL 3 HOUR),
  (1, 1, 'Testing type updated', 'Insulation Resistance Test updated to version 3', 'info', '/lab-automation/admin/testing-types.php', 1, NOW() - INTERVAL 5 HOUR),
  (1, 2, 'New product assigned', 'Panel Board PB-LV has been assigned for testing', 'assignment', '/lab-automation/testing/detail.php?id=9', 0, NOW() - INTERVAL 45 MINUTE),
  (1, 3, 'Test assignment', 'You have been assigned to test Transformer T-400kV', 'assignment', '/lab-automation/testing/detail.php?id=1', 0, NOW() - INTERVAL 2 DAY),
  (1, 4, 'CPRI review requested', 'Cable Termination CT-72 requires your CPRI review', 'warning', '/lab-automation/products/detail.php?id=3', 0, NOW() - INTERVAL 1 DAY);

-- 11) Audit log (workspace 1)
INSERT INTO audit_log (workspace_id, actor_id, action, entity_type, entity_id, after_json, ip_address, created_at) VALUES
  (1, 2, 'product.register', 'product', 12, '{"serial_number":"FZ-2026-0462","spec_model":"VCB Breaker 36kV"}', '192.168.1.105', NOW() - INTERVAL 3 HOUR),
  (1, 2, 'testing.assign', 'testing_record', 9, '{"product":"Panel Board PB-LV","tester":"K. Okonkwo"}', '192.168.1.105', NOW() - INTERVAL 45 MINUTE),
  (1, 3, 'testing.submit', 'testing_record', 3, '{"product":"Switchgear SG-12","status":"submitted_for_review"}', '192.168.1.112', NOW() - INTERVAL 12 MINUTE),
  (1, 4, 'cpri.approve', 'cpri_record', 1, '{"product":"Bushing B-245","decision":"approved","certificate":"CPRI-2026-0454-A"}', '192.168.1.118', NOW() - INTERVAL 1 DAY),
  (1, 6, 'testing.start', 'testing_record', 10, '{"product":"CT Metering Unit","testing_type":"Insulation Resistance Test"}', '192.168.1.120', NOW() - INTERVAL 2 HOUR),
  (1, 3, 'testing.fail', 'testing_record', 7, '{"product":"Surge Arrester SA-10","decision":"fail"}', '192.168.1.112', NOW() - INTERVAL 26 HOUR),
  (1, 2, 'testing.pass', 'testing_record', 4, '{"product":"Cable Termination CT-72","decision":"pass"}', '192.168.1.105', NOW() - INTERVAL 28 HOUR),
  (1, 1, 'testing_type.update', 'testing_type', 1, '{"name":"Insulation Resistance Test","version":3}', '192.168.1.100', NOW() - INTERVAL 5 HOUR),
  (1, 1, 'user.login', 'user', 1, '{"email":"admin@labauto.local"}', '192.168.1.100', NOW() - INTERVAL 45 MINUTE);

-- 12) Settings (workspace 1) — application defaults
INSERT INTO settings (workspace_id, setting_key, setting_value, category) VALUES
  (1, 'session_timeout_minutes', '30',        'security'),
  (1, 'max_login_attempts',      '5',         'security'),
  (1, 'lockout_duration_minutes','15',        'security'),
  (1, 'password_min_length',     '8',         'security'),
  (1, 'password_require_upper',  '1',         'security'),
  (1, 'password_require_number', '1',         'security'),
  (1, 'password_require_special','1',         'security'),
  (1, 'app_name',                'Lab Automation System', 'general'),
  (1, 'company_name',            'FZ Engineering',        'general'),
  (1, 'records_per_page',        '25',        'general'),
  (1, 'date_format',             'Y-m-d',     'general'),
  (1, 'timezone',                'UTC',       'general');

-- pending_signups and email_verifications are intentionally left empty —
-- they are transient/queue tables populated at runtime by the signup and
-- invitation flows, not part of initial seed data.
