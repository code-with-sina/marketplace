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
        Schema::create('seller_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('guide');
            $table->integer('duration');
            $table->double('min_amount', 10, 2);
            $table->double('max_amount', 10, 2);
            $table->double('percentage', 4, 2)->nullable();
            $table->double('ratefyfee', 4, 2)->default(-2);
            $table->double('fixed_rate', 8,2)->nullable();
            $table->foreignId('ewallet_id')->constrained('ewallets')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('payment_option_id')->constrained('payment_options')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('status', ['active', 'paused', 'deleted'])->default('active');
            $table->enum('approval', ['review', 'pending', 'approved'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_offers');
    }
};
