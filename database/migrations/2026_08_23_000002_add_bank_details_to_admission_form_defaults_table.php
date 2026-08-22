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
            $table->string('bank_name')->nullable()->after('registrar_signature');
            $table->string('bank_branch')->nullable()->after('bank_name');
            $table->string('bank_account_no')->nullable()->after('bank_branch');
            $table->string('payment_reference')->nullable()->after('bank_account_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_form_defaults', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_branch', 'bank_account_no', 'payment_reference']);
        });
    }
};
