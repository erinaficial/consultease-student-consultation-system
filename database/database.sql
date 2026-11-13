-- ConsultEase database schema (updated with faculty_id and slot_label on consultations).
-- Use this file for a FRESH install only. For an existing database, apply the numbered migration scripts in order.
CREATE DATABASE IF NOT EXISTS consultease CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE consultease;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','faculty','admin') NOT NULL DEFAULT 'student',
  department VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE availability_slots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  faculty_id INT NOT NULL,
  slot_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  meeting_mode ENUM('onsite','online') NULL,
  meeting_details TEXT NULL,
  status ENUM('open','booked','cancelled') NOT NULL DEFAULT 'open',
  FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE consultations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slot_id INT NULL UNIQUE,   -- one consultation per slot; set to NULL when cancelled/declined so the slot can be rebooked
  student_id INT NOT NULL,
  faculty_id INT NULL,
  slot_label VARCHAR(100) NULL,
  topic VARCHAR(255) NOT NULL,
  status ENUM('pending','approved','declined','completed','cancelled') NOT NULL DEFAULT 'pending',
  faculty_remarks TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (slot_id) REFERENCES availability_slots(id) ON DELETE SET NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message VARCHAR(255) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
