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
        Schema::create('mini_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('bvn')->nullable();
            $table->string('selfieImage')->nullable();
            $table->string('idType')->nullable();
            $table->string('idNumber')->nullable();
            $table->string('dateOfBirth')->nullable();
            $table->string('gender')->nullable();
            $table->enum('status', ['approved', 'pending', 'none','under review', 'denied' ])->default('none')->nullable();
            $table->string('badge')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mini_profiles');
    }
};
