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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('session');
            $table->string('acceptance');
            $table->string('sender');
            $table->string('receiver');
            $table->string('admin');
            $table->text('content')->nullable();
            $table->string('image')->nullable();
            $table->enum('contentType', ['text', 'file']);
            $table->datetime('timestamp');
            $table->enum('status', ['seen', 'sent'])->default('sent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
