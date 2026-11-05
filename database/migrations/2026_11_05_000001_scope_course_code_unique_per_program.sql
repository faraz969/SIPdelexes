-- Allow the same course_code on different programs.
-- Uniqueness is now per program: (program_id, course_code).

ALTER TABLE `courses` DROP INDEX `courses_course_code_unique`;
ALTER TABLE `courses` ADD UNIQUE `courses_program_id_course_code_unique` (`program_id`, `course_code`);
