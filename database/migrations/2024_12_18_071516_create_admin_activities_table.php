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
        Schema::create('admin_activities', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->string('email')->nullable();
            $table->string('fullname')->nullable();
            $table->text('activity_performed')->nullable();
            $table->string('amount')->nullable();
            $table->json('buyer')->nullable();
            $table->json('seller')->nullable();
            $table->string('reg')->nullable();
            $table->string('trnx_ref')->nullable();
            $table->text('session_acceptance_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_activities');
    }
};
