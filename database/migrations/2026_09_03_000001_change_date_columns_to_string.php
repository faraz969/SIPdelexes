<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('admission_form_defaults', function (Blueprint $table) {
            $table->string('paid_fees_by_date')->nullable()->change();
            $table->string('registration_begins')->nullable()->change();
            $table->string('orientation_new_students')->nullable()->change();
            $table->string('faculty_orientation')->nullable()->change();
            $table->string('lectures_begin')->nullable()->change();
        });

        Schema::table('admission_form_data', function (Blueprint $table) {
            $table->string('paid_fees_by_date')->nullable()->change();
            $table->string('registration_begins')->nullable()->change();
            $table->string('orientation_new_students')->nullable()->change();
            $table->string('faculty_orientation')->nullable()->change();
            $table->string('lectures_begin')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('admission_form_defaults', function (Blueprint $table) {
            $table->date('paid_fees_by_date')->nullable()->change();
            $table->date('registration_begins')->nullable()->change();
            $table->date('orientation_new_students')->nullable()->change();
            $table->date('faculty_orientation')->nullable()->change();
            $table->date('lectures_begin')->nullable()->change();
        });

        Schema::table('admission_form_data', function (Blueprint $table) {
            $table->date('paid_fees_by_date')->nullable()->change();
            $table->date('registration_begins')->nullable()->change();
            $table->date('orientation_new_students')->nullable()->change();
            $table->date('faculty_orientation')->nullable()->change();
            $table->date('lectures_begin')->nullable()->change();
        });
    }
};
