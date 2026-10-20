<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_set_approver')) {
            return;
        }

        DB::table('withdraw_set_approver')
            ->whereIn('withdraw_set_approver_step_code', [
                'reviewer_head',
                'additional_reviewer',
                'finance_officer',
            ])
            ->delete();
    }

    public function down(): void
    {
        // no-op: intentionally not restoring deleted rows
    }
};

