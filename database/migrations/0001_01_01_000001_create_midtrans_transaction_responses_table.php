<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('midtrans_transaction_responses', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->string('transaction_type')->nullable();
            $table->string('transaction_time');
            $table->string('transaction_status');
            $table->string('transaction_id');
            $table->string('status_message')->nullable();
            $table->string('status_code', 10)->nullable();
            $table->string('signature_key')->nullable();
            $table->string('settlement_time')->nullable();
            $table->string('payment_type');
            $table->string('merchant_id')->nullable();
            $table->string('masked_card')->nullable();
            $table->decimal('gross_amount', 12, 2);
            $table->string('fraud_status')->nullable();
            $table->string('eci', 10)->nullable();
            $table->string('currency', 10)->default('IDR');
            $table->string('channel_response_message')->nullable();
            $table->string('channel_response_code', 10)->nullable();
            $table->string('card_type')->nullable();
            $table->string('bank')->nullable();
            $table->string('approval_code')->nullable();
            $table->string('merchant_cross_reference_id')->nullable();
            $table->string('issuer')->nullable();
            $table->string('acquirer')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('midtrans_transactions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('midtrans_transaction_responses');
    }
};
