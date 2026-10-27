-- Snapshot registrar signature/name on each issued admission letter.
-- Run this if you apply schema changes manually in phpMyAdmin.

ALTER TABLE `admission_form_data`
  ADD COLUMN `registrar_name` VARCHAR(255) NULL AFTER `lectures_begin`,
  ADD COLUMN `registrar_signature` VARCHAR(255) NULL AFTER `registrar_name`
    COMMENT 'Copied signature path frozen at letter issue time';
