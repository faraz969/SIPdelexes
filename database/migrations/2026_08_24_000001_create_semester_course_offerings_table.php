<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSemesterCourseOfferingsTable extends Migration
{
    public function up()
    {
        Schema::create('semester_course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->string('academic_year');
            $table->string('semester');
            $table->json('course_ids')->comment('Ordered list of course IDs included in this registration package');
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'academic_year', 'semester'], 'sco_program_year_semester_unique');
        });

        Schema::table('course_registrations', function (Blueprint $table) {
            $table->foreignId('semester_course_offering_id')
                ->nullable()
                ->after('student_id')
                ->constrained('semester_course_offerings')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('course_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_course_offering_id');
        });

        Schema::dropIfExists('semester_course_offerings');
    }
}
