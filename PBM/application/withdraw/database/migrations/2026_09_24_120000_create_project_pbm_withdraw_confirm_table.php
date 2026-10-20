<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared confirmation ledger in the project schema so the project app can list
 * "confirmed for withdraw but not yet saved" via its own API.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->projectSchemaExists()) {
            return;
        }

        if (Schema::hasTable('project.pbm_withdraw_confirm')) {
            return;
        }

        DB::statement("
            CREATE TABLE IF NOT EXISTS `project`.`pbm_withdraw_confirm` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `token` VARCHAR(64) NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `project_id` VARCHAR(100) NULL,
                `project_code` VARCHAR(100) NULL,
                `project_name` VARCHAR(255) NULL,
                `activity_id` VARCHAR(100) NULL,
                `activity_name` VARCHAR(255) NULL,
                `applicant_staff_id` VARCHAR(100) NULL,
                `item_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `project_amount` DECIMAL(15,2) NULL,
                `payload_json` LONGTEXT NOT NULL,
                `withdraw_id` INT UNSIGNED NULL,
                `resume_url` VARCHAR(500) NULL,
                `expires_at` TIMESTAMP NULL DEFAULT NULL,
                `consumed_at` TIMESTAMP NULL DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `pbm_withdraw_confirm_token_unique` (`token`),
                KEY `pbm_withdraw_confirm_status_index` (`status`),
                KEY `pbm_withdraw_confirm_project_code_index` (`project_code`),
                KEY `pbm_withdraw_confirm_applicant_index` (`applicant_staff_id`),
                KEY `pbm_withdraw_confirm_expires_index` (`expires_at`),
                KEY `pbm_withdraw_confirm_created_index` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        if (! $this->projectSchemaExists()) {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS `project`.`pbm_withdraw_confirm`');
    }

    private function projectSchemaExists(): bool
    {
        try {
            $row = DB::selectOne('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', ['project']);

            return $row !== null;
        } catch (\Throwable) {
            return false;
        }
    }
};
