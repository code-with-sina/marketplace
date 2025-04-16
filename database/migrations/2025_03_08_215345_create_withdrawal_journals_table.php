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
        Schema::create('withdrawal_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum("account_type", ["Withdrawal", "Failure"]);
            $table->text("narration");
            $table->string("trust_id");
            $table->decimal("amount", 14, 2);
            $table->string("reference");
            $table->string("trnx_ref")->nullable();
            $table->string("reason_for_failure")->nullable();
            $table->enum("status", ["success", "failed", "pending"])->default("pending");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_journals');
    }
};
