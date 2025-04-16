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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('trade_registry');
            $table->decimal('amount', 14, 2);
            $table->string('owner');
            $table->string('recipient');
            $table->string('wallet_name');
            $table->string('wallet_id');
            $table->string('item_for');
            $table->string('session');
            $table->string('acceptance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
