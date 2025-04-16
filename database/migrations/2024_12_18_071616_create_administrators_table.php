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
        Schema::create('administrators', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('adminId');
            $table->string('adminType');
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
        Schema::dropIfExists('administrators');
    }
};
