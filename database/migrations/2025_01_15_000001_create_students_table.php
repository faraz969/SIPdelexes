<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->nullable()->constrained()->onDelete('set null');
            $table->string('student_id')->unique()->comment('Unique Index Number');
            $table->foreignId('program_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->string('academic_year');
            $table->enum('academic_status', ['active', 'deferred', 'graduated', 'withdrawn'])->default('active');
            $table->date('admission_date');
            $table->date('deferred_at')->nullable();
            $table->date('reactivated_at')->nullable();
            $table->json('biodata')->nullable()->comment('Student biodata synced from application');
            $table->boolean('sip_account_created')->default(false);
            $table->timestamp('sip_account_created_at')->nullable();
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
        Schema::dropIfExists('students');
    }
}

