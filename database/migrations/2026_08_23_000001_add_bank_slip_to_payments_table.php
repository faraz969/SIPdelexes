<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'bank_slip_path')) {
                $table->string('bank_slip_path')->nullable()->after('payment_details');
            }
        });

        // Widen method column so Paystack and bank transfer slips both store cleanly.
        DB::statement("ALTER TABLE payments MODIFY payment_method VARCHAR(50) NOT NULL DEFAULT 'card'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'bank_slip_path')) {
                $table->dropColumn('bank_slip_path');
            }
        });

        DB::statement("ALTER TABLE payments MODIFY payment_method ENUM('card', 'momo', 'bank', 'cash', 'other') NOT NULL DEFAULT 'card'");
    }
};
