-- ============================================================
--  Library Management System  -  database structure + sample data
--  Import: phpMyAdmin (XAMPP) -> Import -> choose this file -> Go
--  or CLI: mysql -u root < library_system.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS library_system
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_system;

DROP TABLE IF EXISTS issues;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS books;

-- Catalogue of books -----------------------------------------
CREATE TABLE books (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(150) NOT NULL,
    author           VARCHAR(100) NOT NULL,
    isbn             VARCHAR(13)  NOT NULL UNIQUE,
    category         VARCHAR(50)  NOT NULL,
    total_copies     INT UNSIGNED NOT NULL,
    available_copies INT UNSIGNED NOT NULL,
    added_on         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_copies CHECK (available_copies <= total_copies)
) ENGINE=InnoDB;

-- Registered library members (students) ----------------------
CREATE TABLE students (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(120) NOT NULL UNIQUE,
    enrollment_no VARCHAR(20)  NOT NULL UNIQUE,
    phone         CHAR(10)     NOT NULL,
    registered_on DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Book issue / return records --------------------------------
CREATE TABLE issues (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id     INT UNSIGNED NOT NULL,
    student_id  INT UNSIGNED NOT NULL,
    issue_date  DATE NOT NULL,
    due_date    DATE NOT NULL,
    return_date DATE NULL,
    fine_amount INT UNSIGNED NOT NULL DEFAULT 0,
    status      ENUM('Issued','Returned') NOT NULL DEFAULT 'Issued',
    CONSTRAINT fk_issue_book    FOREIGN KEY (book_id)    REFERENCES books(id)    ON DELETE RESTRICT,
    CONSTRAINT fk_issue_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Sample data ------------------------------------------------
INSERT INTO books (title, author, isbn, category, total_copies, available_copies) VALUES
('Let Us C',                          'Yashavant Kanetkar',   '9788183331630', 'Programming',       5, 4),
('Data Structures Using C',           'Reema Thareja',        '9780198099307', 'Computer Science',  4, 4),
('Database System Concepts',          'Abraham Silberschatz', '9780078022159', 'Databases',         3, 2),
('Learning PHP, MySQL and JavaScript','Robin Nixon',          '9781491978917', 'Web Technologies',  3, 3),
('HTML and CSS: Design and Build Websites','Jon Duckett',     '9781118008188', 'Web Technologies',  2, 2),
('Operating System Concepts',         'Abraham Silberschatz', '9781119800361', 'Computer Science',  3, 3),
('Wings of Fire',                     'A.P.J. Abdul Kalam',   '9788173711466', 'Biography',         6, 6),
('The Alchemist',                     'Paulo Coelho',         '9780061122415', 'Fiction',           4, 4),
('Business Communication',            'Meenakshi Raman',      '9780198065272', 'Management',        3, 3),
('Introduction to Algorithms',        'Thomas H. Cormen',     '9780262046305', 'Computer Science',  2, 2);

INSERT INTO students (full_name, email, enrollment_no, phone) VALUES
('Ananya Rao',    'ananya.rao@example.com',   'SU2024BCA101', '9876501234'),
('Rohan Mehta',   'rohan.mehta@example.com',  'SU2024BCA102', '9812312345'),
('Kavya Nair',    'kavya.nair@example.com',   'SU2024BCA103', '9823456789'),
('Imran Qureshi', 'imran.q@example.com',      'SU2024BCA104', '9834567890');

-- One overdue loan and one running loan (dates relative to today)
INSERT INTO issues (book_id, student_id, issue_date, due_date, status) VALUES
(1, 2, DATE_SUB(CURDATE(), INTERVAL 21 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY),  'Issued'),
(3, 3, DATE_SUB(CURDATE(), INTERVAL 4 DAY),  DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Issued');

-- One completed loan with a fine
INSERT INTO issues (book_id, student_id, issue_date, due_date, return_date, fine_amount, status) VALUES
(7, 1, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 16 DAY), DATE_SUB(CURDATE(), INTERVAL 13 DAY), 15, 'Returned');
