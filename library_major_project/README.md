# Library Management System Using PHP and MySQL

**Web Technologies – Major Project 1: Design and Develop a Database-Driven Web Application**

## 1. Aim
To design and develop a responsive web application that allows library staff to manage books and students, issue and return books, and calculate fines for late returns.

## 2. Requirements checklist (from the project brief)

| Brief requirement | Implemented in |
|---|---|
| User-friendly interface using HTML5 and CSS3 | `assets/css/style.css`, `includes/header.php`, `includes/footer.php`, every page (semantic HTML5, responsive layout) |
| JavaScript for basic form validation and interactivity | `assets/js/validate.js`: field validation, live error messages, due-date preview, confirm dialogs, auto-hiding messages |
| PHP for server-side programming and form processing | All `.php` pages; validation rules and helpers in `includes/init.php` |
| MySQL for database connectivity and CRUD operations | `includes/init.php` (mysqli), `database/library_system.sql` |
| Search, update and delete wherever applicable | See the module table below |
| Proper testing, debugging and documentation | Section 7 (test cases), this file, `docs/PROJECT_PROPOSAL.md` |

## 3. Modules

| Module | Create | Read / Search | Update | Delete |
|---|---|---|---|---|
| **Home** (`index.php`) | – | Library name, summary counts, latest activity, rules | – | – |
| **Books** (`books.php`, `book_form.php`) | Add book | Search by title / author / ISBN, filter by category | Edit details and copies | Delete (blocked if the book has issue records) |
| **Students** (`students.php`, `student_form.php`) | Register student | Search by name / enrollment no. / email | Edit details | Delete (blocked if the student has issue records) |
| **Issue Book** (`issue.php`) | Issue a book to a student | – | Stock reduces automatically | – |
| **Issue Records** (`issues.php`) | – | Search + filter (issued / overdue / returned) | Return book: status, return date, fine, stock | Delete returned records |

**Rules enforced:** max 3 books per student, loan period 1–30 days (default 14), fine Rs. 5 per day after the due date, a student cannot hold two copies of the same book, a book with no available copies cannot be issued.

## 4. Database design (`database/library_system.sql`)

**books**: `id` (PK), `title`, `author`, `isbn` (unique), `category`, `total_copies`, `available_copies`, `added_on`

**students**: `id` (PK), `full_name`, `email` (unique), `enrollment_no` (unique), `phone`, `registered_on`

**issues**: `id` (PK), `book_id` (FK → books), `student_id` (FK → students), `issue_date`, `due_date`, `return_date`, `fine_amount`, `status` (`Issued` / `Returned`)
<img width="1209" height="547" alt="image (2)" src="https://github.com/user-attachments/assets/7fed0d53-0fb7-4947-b5ab-290eef450cad" />

Relationships: one student → many issues; one book → many issues. Foreign keys use `ON DELETE RESTRICT` so history is never orphaned.

## 5. Folder structure

```
library_major_project/
├── index.php            Home page
├── books.php            View / search / delete books
├── book_form.php        Add / edit a book
├── students.php         View / search / delete students
├── student_form.php     Register / edit a student
├── issue.php            Issue a book
├── issues.php           Issue records, returns, fines
├── includes/
│   ├── init.php         DB connection, helpers, validation, CSRF
│   ├── header.php       Shared header + navigation
│   └── footer.php       Shared footer
├── assets/
│   ├── css/style.css
│   └── js/validate.js
├── database/library_system.sql
└── docs/PROJECT_PROPOSAL.md
```

## 6. How to run (VS Code + XAMPP)

1. Open **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Copy the `library_major_project` folder into `C:\xampp\htdocs\`.
3. Open `http://localhost/phpmyadmin` → **Import** → choose `database/library_system.sql` → **Go**.
4. Open `http://localhost/library_major_project/` in your browser.
5. If your MySQL root user has a password, change `DB_PASS` in `includes/init.php`.

## 7. Test cases 

| # | Feature | Test input | Expected output | Result |
|---|---|---|---|---|
| 1 | Add book | All fields valid | Book saved, success message, appears in list | |
| 2 | Add book – bad ISBN | `12345` | Red error under field; form not submitted | |
| 3 | Add book – duplicate ISBN | ISBN `9788183331630` | "A book with this ISBN already exists." | |
| 4 | Add book – empty title | Leave blank | "This field is required" | |
| 5 | Edit book copies below issued count | Book with 1 issued, set copies = 0/… | Error: cannot be less than issued copies | |
| 6 | Search books | `php` | Only matching books shown | |
| 7 | Filter books by category | Web Technologies | Only that category shown | |
| 8 | Delete book without history | Click Delete → OK | Book removed | |
| 9 | Delete book with history | Book id 1 | Blocked, message shown | |
| 10 | Register student – bad phone | `12345` | Validation error | |
| 11 | Register student – duplicate email | Existing email | Duplicate error | |
| 12 | Search students | `SU2024BCA103` | Matching student shown | |
| 13 | Issue book – valid | Student + available book | Record created, copies reduce by 1 | |
| 14 | Issue 4th book to one student | Student already holding 3 | Maximum limit error | |
| 15 | Issue same book twice to a student | Same pair | "already has a copy" error | |
| 16 | Return on time | Return a running loan | Status Returned, copies +1, no fine | |
| 17 | Return late | Return the overdue loan | Fine = days late × Rs. 5 | |
| 18 | Filter overdue | Status = Overdue | Only overdue loans shown | |
| 19 | Delete returned record | Click Delete → OK | Record removed | |
| 20 | JavaScript off / bypass | Submit invalid data via browser tools | PHP validation still rejects it | |

## 8. Security and good practice
- Prepared statements for every query (prevents SQL injection).
- Output escaped with `htmlspecialchars` (prevents XSS).
- CSRF token on every POST form.
- Validation on both client (JavaScript) and server (PHP).
- Database transactions with row locking when issuing and returning books.

- 
- **Possible future work:** librarian login, email reminders for due dates, book reservations, reports export.
<img width="1209" height="662" alt="image (1)" src="https://github.com/user-attachments/assets/69267615-6a31-4547-8ecb-7c681396936f" />
