CREATE DATABASE IF NOT EXISTS school_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_erp;

CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_no VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    class_name VARCHAR(30) NOT NULL,
    guardian_phone VARCHAR(30) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receipt_no VARCHAR(40) NOT NULL UNIQUE,
    student_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_date DATE NOT NULL,
    method ENUM('Cash','MTN Mobile Money','Airtel Money','Bank') NOT NULL DEFAULT 'Cash',
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_payment_amount CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fee_charges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    term_name VARCHAR(40) NOT NULL,
    academic_year SMALLINT UNSIGNED NOT NULL,
    amount_due DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_term_year (student_id, term_name, academic_year),
    CONSTRAINT fk_charges_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_charge_amount CHECK (amount_due >= 0)
) ENGINE=InnoDB;

CREATE OR REPLACE VIEW student_balances AS
SELECT s.id, s.student_no, s.full_name, s.class_name,
       COALESCE(c.total_due, 0) AS total_due,
       COALESCE(p.total_paid, 0) AS total_paid,
       COALESCE(c.total_due, 0) - COALESCE(p.total_paid, 0) AS balance
FROM students s
LEFT JOIN (
    SELECT student_id, SUM(amount_due) AS total_due
    FROM fee_charges GROUP BY student_id
) c ON c.student_id = s.id
LEFT JOIN (
    SELECT student_id, SUM(amount) AS total_paid
    FROM payments GROUP BY student_id
) p ON p.student_id = s.id;
