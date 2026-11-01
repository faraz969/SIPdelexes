<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegistrarTitleToAdmissionFormTables extends Migration
{
    public function up()
    {
        Schema::table('admission_form_defaults', function (Blueprint $table) {
            $table->string('registrar_title')->nullable()->after('registrar_name');
        });

        Schema::table('admission_form_data', function (Blueprint $table) {
            $table->string('registrar_title')->nullable()->after('registrar_name');
        });
    }

    public function down()
    {
        Schema::table('admission_form_defaults', function (Blueprint $table) {
            $table->dropColumn('registrar_title');
        });

        Schema::table('admission_form_data', function (Blueprint $table) {
            $table->dropColumn('registrar_title');
        });
    }
}
