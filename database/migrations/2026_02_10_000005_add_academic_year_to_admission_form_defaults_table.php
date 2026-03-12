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
        Schema::table('admission_form_defaults', function (Blueprint $table) {
            $table->string('academic_year', 50)->nullable()->after('id');
            $table->index('academic_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_form_defaults', function (Blueprint $table) {
            $table->dropIndex(['academic_year']);
            $table->dropColumn('academic_year');
        });
    }
};

