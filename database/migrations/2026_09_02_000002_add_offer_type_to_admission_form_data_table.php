<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_form_data', function (Blueprint $table) {
            $table->string('offer_type', 20)->default('regular')->after('application_id');
            $table->string('conditional_subject')->nullable()->after('offer_type');
        });
    }

    public function down(): void
    {
        Schema::table('admission_form_data', function (Blueprint $table) {
            $table->dropColumn(['offer_type', 'conditional_subject']);
        });
    }
};
