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
        Schema::table('p_to_p_s', function (Blueprint $table) {
            $table->text('proof_of_payment')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('p_to_p_s', function (Blueprint $table) {
            $table->string('proof_of_payment')->nullable()->change();
        });
    }
};
