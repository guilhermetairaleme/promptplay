<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      Schema::create('hotmart_orders', function (Blueprint $table) {
            $table->id();
            $table->string('event');
            $table->string('transaction_id')->unique();
            $table->string('buyer_email')->nullable();
            $table->string('buyer_name')->nullable();
            $table->string('product_name')->nullable();
            $table->string('status')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('payment_type')->nullable();
            $table->decimal('commission_total', 10, 2)->nullable();
            $table->json('data_json'); // Armazena todo o payload da chave "data"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotmart_orders');
    }
};
