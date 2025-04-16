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
        Schema::create('fee_extracts', function (Blueprint $table) {
            $table->id();
            $table->string('offer_owner');
            $table->string('product');
            $table->string('type');
            $table->string('type_id');
            $table->string('reg_key');
            $table->string('total_amount');
            $table->string('valued_amount');
            $table->string('valued_fee');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_extracts');
    }
};
