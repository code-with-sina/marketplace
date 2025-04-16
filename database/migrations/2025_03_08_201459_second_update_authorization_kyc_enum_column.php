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
        DB::statement("ALTER TABLE `authorizations` ADD COLUMN `kyc_new` ENUM('pending', 'processing', 'approved', 'rejected', 'error') NOT NULL DEFAULT 'pending'");

        // Step 2: Migrate existing data to the new column
        DB::statement("
            UPDATE `authorizations` SET `kyc_new` = 
                CASE 
                    WHEN `kyc` IN ('unapproved', 'review') THEN 'pending'
                    WHEN `kyc` = 'approved' THEN 'approved'
                    ELSE 'error'
                END
        ");

        // Step 3: Drop the old `kyc` column
        DB::statement("ALTER TABLE `authorizations` DROP COLUMN `kyc`");

        // Step 4: Rename `kyc_new` to `kyc`
        DB::statement("ALTER TABLE `authorizations` CHANGE COLUMN `kyc_new` `kyc` ENUM('pending', 'processing', 'approved', 'rejected', 'error') NOT NULL");
    }


    /**
     * Reverse the migrations.
     */


    public function down(): void
    {
        // Step 1: Add back the old ENUM column
        DB::statement("ALTER TABLE `authorizations` ADD COLUMN `kyc_old` ENUM('unapproved', 'approved', 'review', 'pending') NOT NULL DEFAULT 'unapproved'");

        // Step 2: Migrate existing data back to the old column
        DB::statement("
            UPDATE `authorizations` SET `kyc_old` = 
                CASE 
                    WHEN `kyc` = 'pending' THEN 'unapproved'
                    WHEN `kyc` = 'approved' THEN 'approved'
                    WHEN `kyc` = 'processing' THEN 'review'
                    ELSE 'unapproved'
                END
        ");

        // Step 3: Drop the new `kyc` column
        DB::statement("ALTER TABLE `authorizations` DROP COLUMN `kyc`");

        // Step 4: Rename `kyc_old` back to `kyc`
        DB::statement("ALTER TABLE `authorizations` CHANGE COLUMN `kyc_old` `kyc` ENUM('unapproved', 'approved', 'review', 'pending') NOT NULL");
    }
};
