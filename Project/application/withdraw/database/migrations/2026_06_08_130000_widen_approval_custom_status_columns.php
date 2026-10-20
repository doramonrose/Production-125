<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_salary_approval_step')) {
            DB::statement('ALTER TABLE `withdraw_salary_approval_step` MODIFY `withdraw_salary_approval_custom_status` TEXT NULL');
        }

        if (Schema::hasTable('withdraw_general_approval_step')) {
            DB::statement('ALTER TABLE `withdraw_general_approval_step` MODIFY `withdraw_general_approval_custom_status` TEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('withdraw_salary_approval_step')) {
            DB::statement('ALTER TABLE `withdraw_salary_approval_step` MODIFY `withdraw_salary_approval_custom_status` VARCHAR(255) NULL');
        }

        if (Schema::hasTable('withdraw_general_approval_step')) {
            DB::statement('ALTER TABLE `withdraw_general_approval_step` MODIFY `withdraw_general_approval_custom_status` VARCHAR(255) NULL');
        }
    }
};
