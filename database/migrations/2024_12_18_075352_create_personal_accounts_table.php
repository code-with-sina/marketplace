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
        Schema::create('personal_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('personalId');
            $table->string('personalType');
            $table->string('bankId')->nullable();
            $table->string('bankName')->nullable();
            $table->string('cbnCode')->nullable();
            $table->string('nipCode')->nullable();
            $table->string('accountName')->nullable();
            $table->string('accountNumber')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->string('frozen')->nullable();
            $table->string('currency')->nullable();
            $table->string('availableBalance')->nullable();
            $table->string('pendingBalance')->nullable();
            $table->string('ledgerBalance')->nullable();
            $table->string('virtualNubans_id')->nullable();
            $table->string('virtualNubans_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_accounts');
    }
};
