-- Run once in phpMyAdmin after selecting the intended database.
ALTER TABLE `availability_slots`
  ADD COLUMN `meeting_mode` ENUM('onsite','online') NULL,
  ADD COLUMN `meeting_details` TEXT NULL;
