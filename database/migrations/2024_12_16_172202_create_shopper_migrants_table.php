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
        Schema::create('shopper_migrants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('option', ['visa_student', 'general_migrant', 'shopper', 'remote_employer', 'others']);
            $table->enum('experience',['start', 'experience']);
            $table->enum('purpose', ['get_foreign_currency'])->default('get_foreign_currency');
            $table->enum('status', ['unapproved', 'review', 'approved'])->default('review');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopper_migrants');
    }
};
