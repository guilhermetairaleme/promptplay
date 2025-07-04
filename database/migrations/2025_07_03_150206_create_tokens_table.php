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
    Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->text('access_token');
            $table->string('token_type')->nullable();
            $table->integer('expires_in')->nullable();
            $table->string('scope')->nullable();
            $table->uuid('jti')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
