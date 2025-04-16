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
        Schema::create('buyer_offer_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_offer_id')->constrained('buyer_offers')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('requirement_id')->constrained('requirements')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyer_offer_requirements');
    }
};
