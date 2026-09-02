<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_form_data', function (Blueprint $table) {
            $table->timestamp('offer_accepted_at')->nullable()->after('conditional_subject');
        });
    }

    public function down(): void
    {
        Schema::table('admission_form_data', function (Blueprint $table) {
            $table->dropColumn('offer_accepted_at');
        });
    }
};
