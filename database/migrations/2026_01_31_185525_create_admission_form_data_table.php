<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmissionFormDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admission_form_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('total_fees', 10, 2)->nullable();
            $table->decimal('minimum_fee_percentage', 5, 2)->nullable()->comment('Minimum fee percentage required');
            $table->decimal('balance_percentage', 5, 2)->nullable()->comment('Balance percentage');
            $table->date('paid_fees_by_date')->nullable()->comment('Paid fees by date');
            $table->date('registration_begins')->nullable();
            $table->date('orientation_new_students')->nullable()->comment('Orientation for new students');
            $table->date('faculty_orientation')->nullable();
            $table->date('lectures_begin')->nullable();
            $table->string('generated_file_path')->nullable()->comment('Path to generated admission form file');
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
        Schema::dropIfExists('admission_form_data');
    }
}
