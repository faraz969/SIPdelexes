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
            $table->string('registrar_name')->nullable()->after('lectures_begin');
            $table->string('registrar_signature')->nullable()->after('registrar_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_form_defaults', function (Blueprint $table) {
            $table->dropColumn(['registrar_name', 'registrar_signature']);
        });
    }
};
