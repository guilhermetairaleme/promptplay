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
       Schema::table('hotmart_orders', function (Blueprint $table) {
            $table->string('path')->nullable(); // você pode mudar o after() se quiser posicionar o campo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotmart_orders', function (Blueprint $table) {
            $table->dropColumn('path');
        });
    }
};
