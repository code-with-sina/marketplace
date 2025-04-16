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
        Schema::create('freelances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('options',['remote_worker', 'freelancer', 'online_seller', 'others'])->default('freelancer');
            $table->string('service_offer');
            $table->string('portfolio')->nullable();
            $table->string('work_history')->nullable();
            $table->enum('experience', ['starter', 'intermediate', 'expert'])->default('starter');
            $table->enum('purpose', ['get_naira'])->default('get_naira');
            $table->enum('status', ['unapproved', 'review', 'approved'])->default('review');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelances');
    }
};
