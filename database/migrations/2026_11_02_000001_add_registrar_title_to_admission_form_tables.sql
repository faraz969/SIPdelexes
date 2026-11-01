-- Make registrar title configurable on admission form defaults,
-- and freeze it per issued letter on admission_form_data.

ALTER TABLE `admission_form_defaults`
  ADD COLUMN `registrar_title` VARCHAR(255) NULL AFTER `registrar_name`;

ALTER TABLE `admission_form_data`
  ADD COLUMN `registrar_title` VARCHAR(255) NULL AFTER `registrar_name`;
