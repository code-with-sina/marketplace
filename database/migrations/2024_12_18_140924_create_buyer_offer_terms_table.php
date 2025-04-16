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
        Schema::create('buyer_offer_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_offer_id')->constrained('buyer_offers')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('title');
            $table->text('condition');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyer_offer_terms');
    }
};
