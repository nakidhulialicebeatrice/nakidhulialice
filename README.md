# School ERP Mid-Term Prototype

## Project
A lightweight School ERP prototype demonstrating two connected business functions:
1. Student Management
2. Fees and Financial Management

The student and finance modules share one MySQL database. A fee charge or payment references the student through a foreign key. Student balances are calculated in the `student_balances` view rather than manually copied into each student record.

## Requirements
- XAMPP with Apache, PHP 8+ and MySQL/MariaDB
- A web browser

## Run locally
1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin (`http://localhost/phpmyadmin`).
3. Import `schema.sql`.
4. Copy this project folder into XAMPP's `htdocs` directory, for example `htdocs/school_erp_midterm/`.
5. If your MySQL credentials differ, update `$dbHost`, `$dbName`, `$dbUser`, and `$dbPass` at the top of `index.php`.
6. Open `http://localhost/school_erp_midterm/`.

## Demo sequence
1. Register a student with a unique student number.
2. Set a fee charge for that student, e.g. UGX 300,000 for Term 1, 2026.
3. Record a payment, e.g. UGX 100,000.
4. Confirm the balance becomes UGX 200,000.
5. Search for the student and edit the class.
6. Try entering the same student number or receipt number again; the database should reject duplicates.
7. Try deleting a student who has a fee charge/payment; the foreign key protects financial history.

## Architecture and data flow
Browser form -> PHP validation -> PDO prepared statement -> MySQL database -> SQL view -> dashboard.

`students` is the master record. `fee_charges` and `payments` reference `students.id`. The view sums charges and payments by student and calculates balance as total due minus total paid.

## Scope and limitations
This is a coursework prototype, not a production-ready ERP. It has no user login/role management, payment-provider integration, audit log, automated backup, or formal accounting ledger. Deletion of financial records is available for demonstration; a real ERP should generally reverse/void transactions and retain an audit trail. Review and test all code before submission.
