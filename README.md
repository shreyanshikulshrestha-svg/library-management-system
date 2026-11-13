# library-management-system
Library Management System using PHP and MySQL

A database-driven web application for managing a college library, built using **PHP, MySQL, HTML5, CSS3 and JavaScript**.

**Subject:** Web Technologies – Major Project 1
**Developed by:** Shreyanshi kulshrestha and Sanjhi Sharma | **roll No:** 2401040129 and 2401040151

## About the Project
The system lets library staff manage books and students, issue and return books, and calculate fines for late returns. Book stock updates automatically on every issue and return.

## Features
- Add, edit, search and delete books
- Register, edit, search and delete students
- Issue books (maximum 3 per student)
- Return books with automatic fine calculation (Rs. 5 per day after the due date)
- Search and filter issue records (issued, overdue, returned)
- Form validation using JavaScript and PHP
- Responsive design for desktop and mobile

## Technologies Used
| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript |
| Backend | PHP |
| Database | MySQL |
| Server | WampServer |

## How to Run
1. Start **WampServer** and wait for the green icon.
2. Copy the `library_major_project` folder into the `www` folder of WAMP.
3. Open `http://localhost/phpmyadmin`, click **Import**, and select `library_major_project/database/library_system.sql`.
4. Open `http://localhost/library_major_project/` in your browser.

Default database settings: user `root`, empty password, database `library_system`.

## Project Structure
```
library_major_project/
├── index.php Home page
├── books.php Book list, search, delete
├── book_form.php Add / edit book
├── students.php Student list, search, delete
├── student_form.php Register / edit student
├── issue.php Issue a book
├── issues.php Issue records and returns
├── includes/ Database connection, header, footer
├── assets/ CSS and JavaScript
├── database/ SQL file
└── database/docs/ Project proposal
```

## Database Tables
- **books**: title, author, ISBN, category, copies
- **students**: name, email, enrollment number, phone
- **issues**: book, student, issue date, due date, return date, fine, status

