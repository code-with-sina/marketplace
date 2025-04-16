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
        Schema::create('trade_requests', function (Blueprint $table) {
            $table->id();
            $table->string('trade_registry');
            $table->decimal('trade_rate', 14, 2);
            $table->string('wallet_name');
            $table->string('wallet_id');
            $table->enum('item_for', ['buy', 'sell']);
            $table->string('item_id');
            $table->decimal('amount', 14, 2);
            $table->decimal('amount_to_receive', 14, 2)->nullable();
            $table->string('owner');
            $table->string('recipient');
            $table->enum('status', ['active', 'cancelled', 'accepted', 'rejected']);
            $table->enum('notify_time', ['start', 'end']);
            $table->enum('fund_attached', ['yes', 'no'])->default('no');
            $table->string('fund_reg')->nullable();
            $table->dateTime('start');
            $table->dateTime('end');
            $table->integer('duration');
            $table->enum('charges_for', ['buyer', 'seller']);
            $table->string('ratefy_fee');
            $table->string('percentage');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_requests');
    }
};
