<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deferments', function (Blueprint $table) {
            $table->enum('hod_status', ['pending', 'approved', 'rejected'])->default('pending')->after('status');
            $table->text('hod_comments')->nullable()->after('hod_status');
            $table->foreignId('hod_reviewed_by')->nullable()->after('hod_comments')->constrained('users')->nullOnDelete();
            $table->timestamp('hod_reviewed_at')->nullable()->after('hod_reviewed_by');
            $table->enum('registrar_status', ['pending', 'approved', 'rejected'])->default('pending')->after('hod_reviewed_at');
        });

        DB::table('deferments')->where('status', 'approved')->update([
            'hod_status' => 'approved',
            'registrar_status' => 'approved',
        ]);

        DB::table('deferments')->where('status', 'rejected')->update([
            'hod_status' => 'approved',
            'registrar_status' => 'rejected',
        ]);

        DB::table('deferments')->where('status', 'reactivated')->update([
            'hod_status' => 'approved',
            'registrar_status' => 'approved',
        ]);
    }

    public function down(): void
    {
        Schema::table('deferments', function (Blueprint $table) {
            $table->dropForeign(['hod_reviewed_by']);
            $table->dropColumn([
                'hod_status',
                'hod_comments',
                'hod_reviewed_by',
                'hod_reviewed_at',
                'registrar_status',
            ]);
        });
    }
};
