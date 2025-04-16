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
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_request_id')->constrained('trade_requests')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('product')->nullable();
            $table->string('offer')->nullable();
            $table->string('owner')->nullable();
            $table->string('uuid')->nullable();
            $table->string('fee')->nullable();
            $table->string('total')->nullable();
            $table->string('failed')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
