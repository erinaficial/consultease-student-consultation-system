# ConsultEase

**A web-based student consultation booking system.** Faculty publish their available times, students book them with a topic, and faculty approve, decline or complete each request, with in-system notifications at every step.

**Live demo:** https://consultease-ph.site.je/

Built as the individual final project for **Web Systems Technology**.

## Features
- Registration, login and logout with hashed passwords, sessions and role-based access (Student, Faculty, Administrator)
- Faculty availability slots (add, edit, delete) limited to school hours
- Student search by faculty, department and date, with booking and cancellation
- One booking per slot, protected by a database transaction and a unique constraint
- Faculty approve, decline or complete requests with remarks
- In-system notifications (bell with auto-refresh) and a notifications page
- Administrator overview with a Chart.js chart, user management (create, edit, delete) and filterable reports with CSV download
- Dark / light mode saved in localStorage and a responsive layout
- [Keep only if in your code: onsite or online meeting details on slots, shown to the student after approval]

## Technologies
HTML5, CSS3, Bootstrap 5, JavaScript ES6+ (ES modules, Fetch API, async/await), Chart.js, PHP 8 (PDO, sessions), MySQL / MariaDB, Git and GitHub. Hosted on InfinityFree.

## Requirements
PHP 8 and MySQL or MariaDB (XAMPP provides both).

## Installation (local, XAMPP)
1. Install XAMPP and start **Apache** and **MySQL**.
2. Copy this folder to `C:\xampp\htdocs\consultease`.
3. In `includes/`, copy `config.example.php` to `config.php` and check the database values (the defaults match XAMPP). Set `'debug' => true` while developing and `false` on a live site. Never commit `config.php`.
4. Create the database (next section).
5. Open `http://localhost/consultease/public/`.

## Database setup
1. Open `http://localhost/phpmyadmin`, click **Import**, and choose `database/database.sql` (creates the `consultease` database and its tables).
2. For the demo accounts, run once: `cd C:\xampp\htdocs\consultease\database` then `C:\xampp\php\php.exe seed.php`.
3. On a hosting server, import `database/consultease_deploy.sql` into your existing database instead (it includes the demo accounts and has no `CREATE DATABASE` line).
4. Do not import a fresh dump over a database that already contains data.

## Demo accounts (test data only)
| Role | Email | Password |
|---|---|---|
| Administrator | admin@consultease.test | Admin123! |
| Faculty | prof@consultease.test | Faculty123! |
| Student | student1@test.com | Student123! |

## API endpoints (used with the Fetch API)
| Endpoint | Purpose |
|---|---|
| `api/slots_search.php` | Search open slots by faculty, department and date |
| `api/notifications.php` | Read and mark the signed-in user's notifications |
| `api/admin_stats.php` | Consultation counts for the administrator chart |

## Project structure
