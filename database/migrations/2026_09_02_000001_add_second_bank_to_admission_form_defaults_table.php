<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_form_defaults', function (Blueprint $table) {
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_name_2')->nullable()->after('payment_reference');
            $table->string('bank_account_name_2')->nullable()->after('bank_name_2');
            $table->string('bank_branch_2')->nullable()->after('bank_account_name_2');
            $table->string('bank_account_no_2')->nullable()->after('bank_branch_2');
            $table->string('payment_reference_2')->nullable()->after('bank_account_no_2');
        });
    }

    public function down(): void
    {
        Schema::table('admission_form_defaults', function (Blueprint $table) {
            $table->dropColumn([
                'bank_account_name',
                'bank_name_2',
                'bank_account_name_2',
                'bank_branch_2',
                'bank_account_no_2',
                'payment_reference_2',
            ]);
        });
    }
};
