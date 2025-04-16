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
        Schema::create('escrow_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escrow_account_id')->nullable()->constrained('escrow_accounts')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('uuid');
            $table->double('availableBalance');
            $table->double('ledgerBalance');
            $table->double('hold');
            $table->double('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escrow_balances');
    }
};
