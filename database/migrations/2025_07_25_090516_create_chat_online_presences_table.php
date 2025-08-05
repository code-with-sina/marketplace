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
        Schema::create('chat_online_presences', function (Blueprint $table) {
            $table->id();
            $table->string("owner_uuid");
            $table->string("recipient_uuid");
            $table->string("session_id")->unique();
            $table->timestamp("owner_last_seen")->nullable();
            $table->timestamp("recipient_last_seen")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_online_presences');
    }
};
