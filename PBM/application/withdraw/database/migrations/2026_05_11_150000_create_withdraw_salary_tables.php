<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableMap = [
            'withdraw_salary' => 'withdraw_general',
            'withdraw_salary_item' => 'withdraw_general_item',
            'withdraw_salary_document' => 'withdraw_general_document',
            'withdraw_salary_file' => 'withdraw_general_file',
            'withdraw_salary_approval_step' => 'withdraw_general_approval_step',
            'withdraw_salary_action_logs' => 'withdraw_general_action_logs',
        ];

        foreach ($tableMap as $target => $source) {
            if (Schema::hasTable($target) || ! Schema::hasTable($source)) {
                continue;
            }

            DB::statement(sprintf('CREATE TABLE `%s` LIKE `%s`', $target, $source));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_salary_action_logs');
        Schema::dropIfExists('withdraw_salary_approval_step');
        Schema::dropIfExists('withdraw_salary_file');
        Schema::dropIfExists('withdraw_salary_document');
        Schema::dropIfExists('withdraw_salary_item');
        Schema::dropIfExists('withdraw_salary');
    }
};
