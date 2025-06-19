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
        Schema::create('kyc_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string("house_number")->nullable();
            $table->string("street");
            $table->string("city");
            $table->string("state");
            $table->string("country");
            $table->string("zip_code");
            $table->string('bvn')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string("date_of_birth")->nullable();
            $table->string("phone_number1")->nullanble();
            $table->string("phone_number2")->nullable();
            $table->enum("gender", ["Female", "Male"])->nullable();
            $table->string("image")->nullable();
            $table->string("selfie_verification_value")->nullable();
            $table->enum("selfie_verification_status", ["true", "false"])->nullable();
            $table->string('selfie_image_initiated')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_details');
    }
};
