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
        Schema::create('transactional_journals', function (Blueprint $table) {
            $table->id();
            $table->enum("account_type", ["Debit", "Payment", "Fee", "Reverse", "Withholding", "Disbursement", "Releasing"]);
            $table->text("narration");
            $table->string("source_account");
            $table->string("source_name");
            $table->enum("source_type", ["Credit", "Debit"]);
            $table->string("destination_account");
            $table->string("destination_name");
            $table->enum("destination_type", ["Credit", "Debit"]);
            $table->decimal("amount", 14, 2);
            $table->string("source_reference");
            $table->string("api_reference");
            $table->string("trnx_id")->nullable();
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
        Schema::dropIfExists('transactional_journals');
    }
};
