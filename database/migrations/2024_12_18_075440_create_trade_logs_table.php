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
        Schema::create('trade_logs', function (Blueprint $table) {
            $table->id();
            $table->string('reg');
            $table->string('acceptance_id')->nullable();
            $table->string('item_for');
            $table->string('wallet_name');
            $table->string('buyer_uuid')->nullable();
            $table->string('seller_uuid')->nullable();
            $table->string('trade_request_ref')->nullable();
            $table->string('transfer_id')->nullable();
            $table->string('type')->nullable();
            $table->string('reason')->nullable();
            $table->string('reference')->nullable();
            $table->string('amount');
            $table->string('failureReason')->nullable();
            $table->string('currency')->nullable();
            $table->string('status');
            $table->enum('state', ['withhold', 'release']);
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->string('destination_id')->nullable();
            $table->string('from_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_logs');
    }
};
