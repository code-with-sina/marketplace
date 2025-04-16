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
        Schema::create('p_to_p_s', function (Blueprint $table) {
            $table->id();
            $table->string('acceptance_id');
            $table->string('session_id');
            $table->enum('session_status', ['open', 'closed'])->default('open');
            $table->string('item_id');
            $table->string('item_name');
            $table->string('item_for');
            $table->decimal('amount', 14, 2);
            $table->decimal('amount_to_receive', 14, 2);
            $table->string('duration');
            $table->enum('duration_status', ['started', 'paused', 'expired'])->default('started');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('payment_id')->nullable();
            $table->enum('payment_status', ['void', 'released', 'pending', 'paid'])->default('void');
            $table->string('proof_of_payment')->nullable();
            $table->enum('proof_of_payment_status', ['denied', 'accept', 'void'])->default('void');
            $table->enum('reportage', ['good', 'open_ticket', 'bad']);
            $table->enum('recipient', ['buyer', 'seller']);
            $table->enum('owner', ['buyer', 'seller']);
            $table->string('owner_id');
            $table->string('recipient_id');
            $table->enum('fund_attached', ['yes', 'no'])->default('no');
            $table->string('fund_reg')->nullable();
            $table->string('trade_registry');
            $table->decimal('trade_rate',14, 2);
            $table->enum('charges_for', ['buyer', 'seller']);
            $table->string('ratefy_fee');
            $table->string('percentage');
            $table->enum('status', ['pending', 'start', 'cancelled', 'completed', 'failed']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p_to_p_s');
    }
};
