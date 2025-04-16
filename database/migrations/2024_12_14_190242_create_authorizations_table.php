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
        Schema::create('authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('priviledge', ['blocked', 'activated'])->default('blocked');
            $table->enum('email', ['verified', 'unverified'])->default('unverified');
            $table->enum('type', ['none', 'freelance', 'shopper-migrant', 'both'])->default('none');
            $table->enum('wallet_status', ['has_wallet', 'no_wallet'])->default('no_wallet');
            $table->enum('kyc', ['unapproved', 'approved'])->default('unapproved');
            $table->enum('internal_kyc', ['unapproved', 'approved'])->default('unapproved');
            $table->enum('profile', ['unchecked', 'has_profile', 'checked', 'no_profile'])->default('unchecked');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authorizations');
    }
};
