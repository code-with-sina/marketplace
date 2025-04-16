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
        DB::statement("ALTER TABLE `customer_statuses` MODIFY COLUMN `status` ENUM('unverified', 'semi-verified', 'fully-verified', 'review', 'pending') NOT NULL");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `customer_statuses` MODIFY COLUMN `status` ENUM('unverified', 'semi-verified', 'fully-verified') NOT NULL");

    }
};
