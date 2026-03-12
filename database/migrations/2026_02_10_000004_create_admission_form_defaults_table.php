<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admission_form_defaults', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_fees', 10, 2)->nullable();
            $table->decimal('minimum_fee_percentage', 5, 2)->nullable();
            $table->decimal('balance_percentage', 5, 2)->nullable();
            $table->date('paid_fees_by_date')->nullable();
            $table->date('registration_begins')->nullable();
            $table->date('orientation_new_students')->nullable();
            $table->date('faculty_orientation')->nullable();
            $table->date('lectures_begin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_form_defaults');
    }
};

