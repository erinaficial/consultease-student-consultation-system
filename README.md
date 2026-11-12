# ConsultEase

**A web-based student consultation booking system.** Faculty publish their available times, students book them with a topic, and faculty approve, decline or complete each request, with notifications at every step.

**Live demo:** https://consultease-ph.site.je/

## Features
- Registration, login and logout with hashed passwords, sessions and role-based access (Student, Faculty, Administrator)
- Faculty availability slots (add, edit, delete) limited to school hours
- Student search by faculty, department and date, with booking and cancellation
- One booking per slot, protected by a database transaction and a unique constraint
- Faculty approve, decline or complete requests with remarks
- In-system notifications (bell with auto-refresh) and a notifications page
- Administrator overview with a Chart.js chart, user management (create, edit, delete) and filterable reports with CSV download
- Dark / light mode saved in localStorage, three color palettes, responsive layout with a sidebar

## Technologies
HTML5, CSS3, Bootstrap 5, JavaScript ES6+ (ES modules, Fetch API, async/await), Chart.js, PHP 8 (PDO, sessions), MySQL / MariaDB, Git and GitHub.

## Installation (local, XAMPP)
1. Install XAMPP and start **Apache** and **MySQL**.
2. Copy this folder to `C:\xampp\htdocs\consultease`.
3. In `includes/`, copy `config.example.php` to `config.php` and check the database values (the defaults match XAMPP).
4. Create the database (next section).
5. Open `http://localhost/consultease/public/`.

## Database setup
1. Open `http://localhost/phpmyadmin`.
2. Click **Import** and choose `database/database.sql` (creates the `consultease` database and its four tables).
3. To get the demo accounts, run once from a command prompt:
   `cd C:\xampp\htdocs\consultease\database` then `C:\xampp\php\php.exe seed.php`.
   On a hosting server, import `database/consultease_deploy.sql` into your existing database instead (it already includes the demo accounts and has no `CREATE DATABASE` line).

## Demo accounts (test data only)
| Role | Email | Password |
|---|---|---|
| Administrator | admin@consultease.test | Admin123! |
| Faculty | prof@consultease.test | Faculty123! |
| Student | register a new account on the Register page | |

## Project structure
```
public/     pages, css/, js/, images/
api/        JSON endpoints used with the Fetch API
includes/   auth, csrf, database connection, shared header and footer
database/   database.sql, consultease_deploy.sql, seed.php
```

## Screenshots
_Add screenshots of the home page, the student booking page, the faculty requests page and the admin overview._

## Developer
Erin Leigh A. Aficial: design, development, testing, deployment and documentation.
