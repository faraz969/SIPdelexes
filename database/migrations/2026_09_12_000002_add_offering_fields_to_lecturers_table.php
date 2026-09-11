<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lecturers', function (Blueprint $table) {
            $table->foreignId('semester_course_offering_id')
                ->nullable()
                ->after('session_id')
                ->constrained('semester_course_offerings')
                ->nullOnDelete();
            $table->string('academic_year')->nullable()->after('semester_course_offering_id');
            $table->string('semester')->nullable()->after('academic_year');
        });
    }

    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_course_offering_id');
            $table->dropColumn(['academic_year', 'semester']);
        });
    }
};
