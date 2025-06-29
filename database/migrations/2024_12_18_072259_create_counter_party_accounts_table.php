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
        Schema::create('counter_party_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id');
            $table->string('counterPartyId');
            $table->string('counterPartyType');
            $table->string('bankId');
            $table->string('bankName');
            $table->string('bankNipCode');
            $table->string('accountName');
            $table->string('accountNumber');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counter_party_accounts');
    }
};
