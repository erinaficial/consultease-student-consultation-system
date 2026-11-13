-- Run this ONCE in phpMyAdmin (SQL tab) on your existing consultease database.
-- It lets a consultation remember its faculty member and a readable date/time label,
-- even after a cancelled or declined booking releases the slot.
USE consultease;

ALTER TABLE consultations
  ADD COLUMN faculty_id INT NULL AFTER student_id,
  ADD COLUMN slot_label VARCHAR(100) NULL AFTER faculty_id,
  ADD FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE SET NULL;
