<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegistrationRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('registration_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name');
            $table->decimal('minimum_payment_percentage', 5, 2)->default(70.00);
            $table->decimal('late_registration_fee', 10, 2)->default(0);
            $table->integer('late_registration_days')->default(14)->comment('Days after registration period to apply late fee');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('registration_rules');
    }
}

