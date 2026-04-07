-- ============================================================
--  OJT Tracker — Database Schema + Seed Data
--  Run: mysql -u root -p < setup.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS ojt_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ojt_tracker;

-- ── Users ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(150)    NOT NULL,
    email           VARCHAR(200)    NOT NULL UNIQUE,
    password        VARCHAR(255)    NOT NULL,
    role            ENUM('admin','student') NOT NULL DEFAULT 'student',
    student_number  VARCHAR(50)     NULL,
    course          VARCHAR(150)    NULL,
    company         VARCHAR(200)    NULL,
    required_hours  SMALLINT UNSIGNED NOT NULL DEFAULT 600,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── OJT Logs ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ojt_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    NOT NULL,
    log_date        DATE            NOT NULL,
    time_in         TIME            NULL,
    time_out        TIME            NULL,
    hours_rendered  DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    remarks         TEXT            NULL,
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_note      TEXT            NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_date (user_id, log_date)
) ENGINE=InnoDB;

-- ── Seed Data ────────────────────────────────────────────────
-- All passwords are 'password' (bcrypt)
INSERT INTO users (full_name, email, password, role, student_number, course, company, required_hours) VALUES
('Admin User',    'admin@ojt.local',   '$2y$12$K7T1nqzj.ztS3RHN1.c7WOGXj6V9Ws/UKf6g4hLDqW9lxuJaVfEVi', 'admin',   NULL,       NULL,                        NULL,                   0),
('Maria Santos',  'maria@student.local','$2y$12$K7T1nqzj.ztS3RHN1.c7WOGXj6V9Ws/UKf6g4hLDqW9lxuJaVfEVi', 'student', '2021-0001','BS Information Technology', 'TechCorp Philippines',  600),
('Jose Reyes',    'jose@student.local', '$2y$12$K7T1nqzj.ztS3RHN1.c7WOGXj6V9Ws/UKf6g4hLDqW9lxuJaVfEVi', 'student', '2021-0002','BS Computer Science',       'Ayala Systems Inc.',    600);

-- Sample logs for Maria
INSERT INTO ojt_logs (user_id, log_date, time_in, time_out, hours_rendered, remarks, status) VALUES
(2, DATE_SUB(CURDATE(), INTERVAL 6 DAY), '08:00', '17:00', 9.00, 'Orientation and onboarding',         'approved'),
(2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '08:00', '17:00', 9.00, 'Backend API development',            'approved'),
(2, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '08:00', '17:00', 9.00, 'Frontend integration tasks',         'approved'),
(2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:00', '17:00', 9.00, 'Code review and debugging',          'approved'),
(2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:00', '17:00', 9.00, 'Sprint planning and documentation',  'approved'),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00', '17:00', 9.00, 'Unit testing and QA tasks',          'pending');

-- Sample logs for Jose
INSERT INTO ojt_logs (user_id, log_date, time_in, time_out, hours_rendered, remarks, status) VALUES
(3, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '08:00', '17:00', 9.00, 'Company orientation',                'approved'),
(3, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '08:00', '17:00', 9.00, 'Database design tasks',              'approved'),
(3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:00', '17:00', 9.00, 'Report generation module',           'rejected'),
(3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:00', '17:00', 9.00, 'Fixed rejected log and resubmitted', 'approved'),
(3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00', '17:00', 9.00, 'Mobile responsive fixes',            'pending');