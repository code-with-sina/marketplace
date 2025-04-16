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
        Schema::create('net_auth_tokens', function (Blueprint $table) {
            $table->id();            
            $table->string('token', 255)->unique();
            $table->string('user_id');
            $table->string('username');
            $table->string('by_subdomain');
            $table->boolean('is_revoked')->default(false);
            $table->dateTime('revoked_at')->nullable();
            $table->string('revoked_by', 255)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('net_auth_tokens');
    }
};
