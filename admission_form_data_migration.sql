-- Migration: Create admission_form_data table
-- Created: 2026-01-31

CREATE TABLE `admission_form_data` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `application_id` BIGINT UNSIGNED NULL,
  `total_fees` DECIMAL(10, 2) NULL,
  `minimum_fee_percentage` DECIMAL(5, 2) NULL COMMENT 'Minimum fee percentage required',
  `balance_percentage` DECIMAL(5, 2) NULL COMMENT 'Balance percentage',
  `paid_fees_by_date` DATE NULL COMMENT 'Paid fees by date',
  `registration_begins` DATE NULL,
  `orientation_new_students` DATE NULL COMMENT 'Orientation for new students',
  `faculty_orientation` DATE NULL,
  `lectures_begin` DATE NULL,
  `generated_file_path` VARCHAR(255) NULL COMMENT 'Path to generated admission form file',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admission_form_data_student_id_unique` (`student_id`),
  KEY `admission_form_data_application_id_foreign` (`application_id`),
  CONSTRAINT `admission_form_data_student_id_foreign` 
    FOREIGN KEY (`student_id`) 
    REFERENCES `students` (`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `admission_form_data_application_id_foreign` 
    FOREIGN KEY (`application_id`) 
    REFERENCES `applications` (`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

