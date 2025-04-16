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
        Schema::create('virtual_nubans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escrow_account_id')->nullable()->constrained('escrow_accounts')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('personal_account_id')->nullable()->constrained('personal_accounts')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('nubanId');
            $table->string('nubanType');
            $table->string('bankId')->nullable();
            $table->string('bankName')->nullable();
            $table->string('nipCode')->nullable();
            $table->string('accountName')->nullable();
            $table->string('accountNumber')->nullable();
            $table->string('currency')->nullable();
            $table->string('permanent')->nullable();
            $table->string('isDefault')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_nubans');
    }
};
