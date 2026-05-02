<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('midtrans_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->string('item_id')->nullable();
            $table->integer('price')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('name')->nullable();
            $table->string('brand')->nullable();
            $table->string('category')->nullable();
            $table->string('merchant_name')->nullable();
            $table->integer('tenor')->nullable();
            $table->string('code_plan')->nullable();
            $table->string('mid')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('midtrans_transactions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('midtrans_transaction_items');
    }
};
