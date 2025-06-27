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
         Schema::create('chat_histories', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // título do chat (ex: "Como surgiu o universo?")
            $table->text('joke')->nullable(); // piada usada
            $table->json('fields')->nullable(); // os campos selecionados (em JSON)
            $table->text('extra')->nullable(); // detalhes extras
            $table->longText('prompt')->nullable(); // prompt gerado
            $table->longText('final_prompt')->nullable(); // prompt final
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_histories');
    }
};
