CREATE DATABASE IF NOT EXISTS `student hub`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `student hub`;

CREATE TABLE IF NOT EXISTS students (
    student_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_code VARCHAR(30) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    phone VARCHAR(30) NULL,
    date_of_birth DATE NULL,
    gender ENUM('Female', 'Male', 'Other', 'Prefer not to say') NULL,
    program VARCHAR(120) NULL,
    academic_year VARCHAR(20) NULL,
    address VARCHAR(255) NULL,
    bio VARCHAR(500) NULL,
    enrollment_date DATE NULL,
    gpa DECIMAL(4,2) NULL,
    account_status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id),
    UNIQUE KEY uq_students_student_code (student_code),
    UNIQUE KEY uq_students_email (email),
    CONSTRAINT chk_students_gpa CHECK (gpa IS NULL OR (gpa >= 0 AND gpa <= 4.00))
) ENGINE=InnoDB;

ALTER TABLE students
    ADD COLUMN IF NOT EXISTS role ENUM('student', 'admin') NOT NULL DEFAULT 'student' AFTER password_hash;

CREATE TABLE IF NOT EXISTS attendance_records (
    attendance_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    class_date DATE NOT NULL,
    subject VARCHAR(120) NOT NULL,
    attendance_status ENUM('present', 'absent', 'excused') NOT NULL,
    remarks VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (attendance_id),
    UNIQUE KEY uq_attendance_student_class (student_id, class_date, subject),
    KEY idx_attendance_class_date (class_date),
    CONSTRAINT fk_attendance_student
        FOREIGN KEY (student_id) REFERENCES students (student_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assignments (
    assignment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(180) NOT NULL,
    subject VARCHAR(120) NOT NULL,
    description TEXT NULL,
    due_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (assignment_id),
    KEY idx_assignments_due_at (due_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assignment_submissions (
    submission_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    assignment_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    submitted_at DATETIME NULL,
    submission_url VARCHAR(2048) NULL,
    status ENUM('pending', 'submitted', 'overdue', 'graded') NOT NULL DEFAULT 'pending',
    score DECIMAL(6,2) NULL,
    feedback TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (submission_id),
    UNIQUE KEY uq_submission_assignment_student (assignment_id, student_id),
    CONSTRAINT fk_submissions_assignment
        FOREIGN KEY (assignment_id) REFERENCES assignments (assignment_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_submissions_student
        FOREIGN KEY (student_id) REFERENCES students (student_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
    event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    starts_at DATETIME NOT NULL,
    location VARCHAR(180) NULL,
    capacity INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (event_id),
    KEY idx_events_starts_at (starts_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_registrations (
    event_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    registration_status ENUM('registered', 'cancelled', 'attended') NOT NULL DEFAULT 'registered',
    PRIMARY KEY (event_id, student_id),
    CONSTRAINT fk_event_registrations_event
        FOREIGN KEY (event_id) REFERENCES events (event_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_event_registrations_student
        FOREIGN KEY (student_id) REFERENCES students (student_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notices (
    notice_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    published_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    PRIMARY KEY (notice_id),
    KEY idx_notices_published_at (published_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feedback (
    feedback_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NULL,
    subject VARCHAR(180) NOT NULL,
    category VARCHAR(80) NOT NULL DEFAULT 'General',
    rating TINYINT UNSIGNED NULL,
    submitter_name VARCHAR(100) NULL,
    submitter_email VARCHAR(254) NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'reviewed', 'resolved') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (feedback_id),
    KEY idx_feedback_status_created (status, created_at),
    CONSTRAINT fk_feedback_student
        FOREIGN KEY (student_id) REFERENCES students (student_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

ALTER TABLE feedback
    ADD COLUMN IF NOT EXISTS category VARCHAR(80) NOT NULL DEFAULT 'General' AFTER subject,
    ADD COLUMN IF NOT EXISTS rating TINYINT UNSIGNED NULL AFTER category,
    ADD COLUMN IF NOT EXISTS submitter_name VARCHAR(100) NULL AFTER rating,
    ADD COLUMN IF NOT EXISTS submitter_email VARCHAR(254) NULL AFTER submitter_name;

CREATE TABLE IF NOT EXISTS contact_messages (
    contact_message_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'reviewed', 'resolved') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (contact_message_id),
    KEY idx_contact_messages_status_created (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS faq_entries (
    faq_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (faq_id),
    UNIQUE KEY uq_faq_entries_question (question)
) ENGINE=InnoDB;

INSERT IGNORE INTO faq_entries (question, answer, sort_order) VALUES
    ('How do I register?', 'Visit the Register page, fill in your personal details, and create your account.', 1),
    ('Where can I find event updates?', 'The Events page lists upcoming workshops, celebrations, and student events.', 2),
    ('How can I send feedback?', 'Use the Feedback page to submit ideas or concerns to the admin team.', 3),
    ('Can I access notices from one place?', 'Yes, the Dashboard brings key notices and updates together in one view.', 4),
    ('Do I need to log in to view events?', 'No, all public event information is available without requiring a login.', 5),
    ('What if I forget my password?', 'Contact the Student Hub support team to recover access to your account.', 6);

INSERT INTO events (title, description, starts_at, location, capacity)
SELECT 'Academic Skills Workshop', 'Build study strategies with practical guidance from academic counselors.', DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY), 'Seminar Hall', 80
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Academic Skills Workshop');

INSERT INTO events (title, description, starts_at, location, capacity)
SELECT 'Campus Sports Day', 'Join friendly team competitions and campus-wide sports activities.', DATE_ADD(CURRENT_DATE, INTERVAL 14 DAY), 'Sports Ground', 120
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Campus Sports Day');

INSERT INTO events (title, description, starts_at, location, capacity)
SELECT 'Student Cultural Festival', 'Celebrate student music, dance, and creative performances.', DATE_ADD(CURRENT_DATE, INTERVAL 21 DAY), 'Open Stage', 200
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Student Cultural Festival');

INSERT INTO notices (title, body, expires_at)
SELECT 'Welcome to Student Hub', 'Check the dashboard regularly for academic updates, upcoming events, and student support information.', NULL
WHERE NOT EXISTS (SELECT 1 FROM notices WHERE title = 'Welcome to Student Hub');

INSERT IGNORE INTO students (student_code, full_name, email, password_hash, role, phone, date_of_birth, gender, program, academic_year, enrollment_date, account_status)
VALUES
    ('SHADMIN01', 'Student Hub Admin', 'admin@studenthub.edu', '$2y$12$YKmaXPw.Z7H41R8IeOZcUuP/wXDI5RtU/gbBPhvBkFH8mE2k/n5q6', 'admin', '+919876543210', '1990-05-12', 'Female', 'Administration', 'Staff', '2024-08-01', 'active'),
    ('SHSTU0001', 'Ram Kumar', 'ramkumar@studenthub.edu', '$2y$12$AAKaxxDOIisUQj7JrpAH9uezsPkX4BqHudvqZN.MSvUMQR0Dat1zy', 'student', '+919123456789', '2004-02-18', 'Male', 'BSc Computer Science', '2nd Year', '2024-08-01', 'active')
;

UPDATE students
SET role = 'admin'
WHERE student_code = 'SHADMIN01' AND email = 'admin@studenthub.edu';
