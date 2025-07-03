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
       Schema::create('items', function (Blueprint $table) {
            $table->id(); // ID local do Laravel
            $table->bigInteger('external_id')->unique(); // ID fornecido no JSON
            $table->string('format');
            $table->integer('warranty_period')->default(0);
            $table->string('status');
            $table->boolean('is_subscription')->default(false);
            $table->string('name');
            $table->timestamp('created_at_external')->nullable();
            $table->uuid('ucode');
            $table->timestamps(); // created_at e updated_at do Laravel
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
