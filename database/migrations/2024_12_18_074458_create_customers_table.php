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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_status_id')->constrained('customer_statuses')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('customerId');
            $table->string('customerType');
            $table->boolean('soleProprietor')->default(false);
            $table->string('firstName');
            $table->string('lastName');
            $table->string('email');
            $table->string('phoneNumber');
            $table->string('address');
            $table->string('country');
            $table->string('state');
            $table->string('city');
            $table->string('postalCode');
            $table->string('gender')->nullable();
            $table->string('dateOfBirth')->nullable();
            $table->string('bvn')->nullable();
            $table->string('selfieImage')->nullable();
            $table->string('expiryDate')->nullable();
            $table->string('idType')->nullable();
            $table->string('idNumber')->nullable();
            $table->string('status');
            $table->string('registered');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
