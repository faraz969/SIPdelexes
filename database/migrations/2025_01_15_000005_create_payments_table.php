<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_reference')->unique();
            $table->string('erp_payment_id')->nullable()->comment('Payment ID from ERP');
            $table->enum('payment_method', ['card', 'momo', 'bank', 'cash', 'other'])->default('card');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('transaction_id')->nullable();
            $table->json('payment_details')->nullable()->comment('Payment gateway response');
            $table->enum('erp_status', ['pending', 'synced', 'failed'])->default('pending');
            $table->timestamp('erp_synced_at')->nullable();
            $table->text('erp_response')->nullable();
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
        Schema::dropIfExists('payments');
    }
}

