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
        // Step 1: Add the new ENUM column
        DB::statement("ALTER TABLE `customer_statuses` ADD COLUMN `status_new` ENUM('unverified', 'semi-verified', 'fully-verified', 'rejected', 'pending') NOT NULL DEFAULT 'unverified'");

        // Step 2: Migrate existing data to the new column
        DB::statement("
            UPDATE `customer_statuses` SET `status_new` = 
                CASE 
                    WHEN `status` = 'review' THEN 'pending'
                    ELSE `status`
                END
        ");

        // Step 3: Drop the old `status` column
        DB::statement("ALTER TABLE `customer_statuses` DROP COLUMN `status`");

        // Step 4: Rename `status_new` to `status`
        DB::statement("ALTER TABLE `customer_statuses` CHANGE COLUMN `status_new` `status` ENUM('unverified', 'semi-verified', 'fully-verified', 'rejected', 'pending') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Add back the old ENUM column
        DB::statement("ALTER TABLE `customer_statuses` ADD COLUMN `status_old` ENUM('unverified', 'semi-verified', 'fully-verified', 'review', 'pending') NOT NULL DEFAULT 'unverified'");

        // Step 2: Migrate data back to the old column
        DB::statement("
            UPDATE `customer_statuses` SET `status_old` = 
                CASE 
                    WHEN `status` = 'rejected' THEN 'review'
                    ELSE `status`
                END
        ");

        // Step 3: Drop the new `status` column
        DB::statement("ALTER TABLE `customer_statuses` DROP COLUMN `status`");

        // Step 4: Rename `status_old` back to `status`
        DB::statement("ALTER TABLE `customer_statuses` CHANGE COLUMN `status_old` `status` ENUM('unverified', 'semi-verified', 'fully-verified', 'review', 'pending') NOT NULL");
    }
};
