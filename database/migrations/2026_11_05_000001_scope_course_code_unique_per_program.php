<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ScopeCourseCodeUniquePerProgram extends Migration
{
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['course_code']);
            $table->unique(['program_id', 'course_code'], 'courses_program_id_course_code_unique');
        });
    }

    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique('courses_program_id_course_code_unique');
            $table->unique('course_code');
        });
    }
}
