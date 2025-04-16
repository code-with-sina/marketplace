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
        DB::statement("ALTER TABLE `authorizations` MODIFY COLUMN `kyc` ENUM('unapproved', 'approved', 'review', 'pending') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `authorizations` MODIFY COLUMN `kyc` ENUM('unapproved', 'approved') NOT NULL");
    }
};
