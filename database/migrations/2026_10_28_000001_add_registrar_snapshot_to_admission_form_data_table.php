<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegistrarSnapshotToAdmissionFormDataTable extends Migration
{
    public function up()
    {
        Schema::table('admission_form_data', function (Blueprint $table) {
            $table->string('registrar_name')->nullable()->after('lectures_begin');
            $table->string('registrar_signature')->nullable()->after('registrar_name')
                ->comment('Copied signature path frozen at letter issue time');
        });
    }

    public function down()
    {
        Schema::table('admission_form_data', function (Blueprint $table) {
            $table->dropColumn(['registrar_name', 'registrar_signature']);
        });
    }
}
