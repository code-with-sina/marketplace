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
        Schema::create('temp_trade_data', function (Blueprint $table) {
            $table->id();
            $table->string('trade_registry')->nullable();
            $table->decimal('trade_rate', 14, 2)->nullable();
            $table->string('wallet_name')->nullable();
            $table->string('wallet_id')->nullable();
            $table->enum('item_for', ['buy', 'sell']);
            $table->string('item_id')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->decimal('amount_to_receive', 14, 2)->nullable();
            $table->string('owner')->nullable();
            $table->string('recipient')->nullable();
            $table->enum('status', ['active', 'cancelled', 'accepted', 'rejected']);
            $table->enum('notify_time', ['start', 'end'])->nullable();
            $table->enum('fund_attached', ['yes', 'no'])->default('no');
            $table->string('fund_reg')->nullable();
            $table->dateTime('start')->nullable();
            $table->dateTime('end')->nullable();
            $table->integer('duration')->nullable();
            $table->enum('charges_for', ['buyer', 'seller']);
            $table->string('ratefy_fee')->nullable();
            $table->string('percentage')->nullable();
            $table->enum('debit', ['success', 'failed', 'initiated']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_trade_data');
    }
};
