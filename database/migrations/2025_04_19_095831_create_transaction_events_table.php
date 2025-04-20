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
        Schema::create('transaction_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('type', ['BuyerRequest', 'BuyerApproval', 'PeerPaymentFee', 'Disbursement']);
            $table->string('reference')->nullable();
            $table->enum('status', ['initiated', 'successful', 'failed'])->default('initiated');
            $table->string('message')->nullable();
            $table->json('payload')->nullable();
            $table->string('event_time')->nullable();
            $table->string('event_id')->nullable();
            $table->string('event_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_events');
    }
};
