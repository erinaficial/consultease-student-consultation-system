# ConsultEase

**ConsultEase: A Web-Based Student Consultation Booking System**

> **Project status:** Proposal / initial repository. The application has not been implemented or deployed yet.

## About

Students often need to find professors in person or message them to arrange consultations. This can cause double bookings, missed appointments, and a lack of records about consultations.

ConsultEase is planned as a secure, responsive web application where students can book consultation slots with faculty. Faculty will manage availability and consultation requests, while administrators will manage accounts and view reports.

## Target users

- **Students** will search for faculty, book an available consultation slot, and follow appointment status.
- **Faculty** will manage availability, approve or decline bookings, complete consultations, and record remarks.
- **Administrators** will manage user accounts and view consultation reports.

## Planned features

- Registration, login, and logout with hashed passwords, sessions, and role-based access for students, faculty, and administrators.
- Faculty availability slot creation, editing, and deletion.
- Faculty search and student booking with a consultation topic. Each slot will be bookable only once.
- Faculty actions to approve, decline, or complete consultations, with remarks.
- In-system notifications for consultation status changes.
- A dashboard with Chart.js consultation statistics by month and status.
- Live search and filters using the Fetch API.
- Dark mode preference saved with Web Storage.

## Proposed technology

- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript ES6+
- **Backend:** PHP with PDO, sessions, and `password_hash`
- **Database:** MySQL or MariaDB
- **Charts:** Chart.js

## Planned database

- **`users`** — name, email, password hash, role, and department.
- **`availability_slots`** — faculty owner, date, start/end time, and open/booked/cancelled status.
- **`consultations`** — booked slot, student, topic, pending/approved/declined/completed/cancelled status, faculty remarks, and creation time. A unique constraint on `slot_id` is planned to prevent booking one slot more than once.
- **`notifications`** — recipient, message, read status, and creation time.

## Scope and limitations

ConsultEase is intended to schedule and track consultations; consultations will not take place inside the system. Video calls and email notifications are not planned.

## Project deliverables

The project is expected to include the deployed web application, source repository, documentation, diagrams, presentation slides, and a demo video. These deliverables are not available yet.

## Local development

The application and its setup instructions have not been implemented yet. Setup guidance will be added when the development environment and application are ready.
