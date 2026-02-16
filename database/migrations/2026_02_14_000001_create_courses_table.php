<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code')->unique();
            $table->string('course_title');
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->unsignedDecimal('credit_units', 5, 2)->default(0);
            $table->unsignedDecimal('total_credit_units', 5, 2)->nullable()->comment('If different from credit_units per semester');
            $table->string('assessment_split')->nullable()->comment('e.g. Class 30%, Exam 70%');
            $table->boolean('is_elective')->default(false)->comment('false = Core, true = Elective');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courses');
    }
}
