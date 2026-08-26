<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddLevelToStudentsSemesterOfferingsAndExamPins extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'level')) {
                $table->string('level', 10)->default('100')->after('academic_year');
            }
        });

        DB::table('students')->where(function ($q) {
            $q->whereNull('level')->orWhere('level', '');
        })->update(['level' => '100']);

        Schema::table('semester_course_offerings', function (Blueprint $table) {
            if (!Schema::hasColumn('semester_course_offerings', 'level')) {
                $table->string('level', 10)->default('100')->after('semester');
            }
        });

        DB::table('semester_course_offerings')->where(function ($q) {
            $q->whereNull('level')->orWhere('level', '');
        })->update(['level' => '100']);

        // MySQL: program_id FK depends on the unique index — drop FK first
        $this->runSqlQuietly('ALTER TABLE `semester_course_offerings` DROP FOREIGN KEY `semester_course_offerings_program_id_foreign`');
        $this->runSqlQuietly('ALTER TABLE `semester_course_offerings` DROP INDEX `sco_program_year_semester_unique`');

        $indexes = collect(DB::select('SHOW INDEX FROM semester_course_offerings'))
            ->pluck('Key_name')
            ->unique()
            ->all();

        if (!in_array('sco_program_year_semester_level_unique', $indexes, true)) {
            DB::statement(
                'ALTER TABLE `semester_course_offerings`
                 ADD UNIQUE KEY `sco_program_year_semester_level_unique` (`program_id`, `academic_year`, `semester`, `level`)'
            );
        }

        // Ensure program_id still has an index for the FK, then restore FK
        $indexes = collect(DB::select('SHOW INDEX FROM semester_course_offerings'))
            ->pluck('Key_name')
            ->unique()
            ->all();

        if (!$this->foreignKeyExists('semester_course_offerings', 'semester_course_offerings_program_id_foreign')) {
            DB::statement(
                'ALTER TABLE `semester_course_offerings`
                 ADD CONSTRAINT `semester_course_offerings_program_id_foreign`
                 FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE'
            );
        }

        Schema::table('exam_pins', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_pins', 'level')) {
                $table->string('level', 10)->default('100')->after('academic_year');
            }
        });

        DB::table('exam_pins')->where(function ($q) {
            $q->whereNull('level')->orWhere('level', '');
        })->update(['level' => '100']);
    }

    public function down()
    {
        $this->runSqlQuietly('ALTER TABLE `semester_course_offerings` DROP FOREIGN KEY `semester_course_offerings_program_id_foreign`');
        $this->runSqlQuietly('ALTER TABLE `semester_course_offerings` DROP INDEX `sco_program_year_semester_level_unique`');

        DB::statement(
            'ALTER TABLE `semester_course_offerings`
             ADD UNIQUE KEY `sco_program_year_semester_unique` (`program_id`, `academic_year`, `semester`)'
        );
        DB::statement(
            'ALTER TABLE `semester_course_offerings`
             ADD CONSTRAINT `semester_course_offerings_program_id_foreign`
             FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE'
        );

        Schema::table('semester_course_offerings', function (Blueprint $table) {
            if (Schema::hasColumn('semester_course_offerings', 'level')) {
                $table->dropColumn('level');
            }
        });

        Schema::table('exam_pins', function (Blueprint $table) {
            if (Schema::hasColumn('exam_pins', 'level')) {
                $table->dropColumn('level');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'level')) {
                $table->dropColumn('level');
            }
        });
    }

    private function runSqlQuietly(string $sql): void
    {
        try {
            DB::statement($sql);
        } catch (\Throwable $e) {
            // Constraint/index may already be absent
        }
    }

    private function foreignKeyExists(string $table, string $foreignName): bool
    {
        $dbName = DB::getDatabaseName();
        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$dbName, $table, $foreignName, 'FOREIGN KEY']
        );

        return (bool) $exists;
    }
}
