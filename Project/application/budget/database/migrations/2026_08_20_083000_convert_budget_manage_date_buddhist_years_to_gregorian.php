<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('budget_manage') || ! Schema::hasColumn('budget_manage', 'budget_manage_date')) {
            return;
        }

        DB::table('budget_manage')
            ->whereNotNull('budget_manage_date')
            ->whereRaw('YEAR(budget_manage_date) >= 2400')
            ->update([
                'budget_manage_date' => DB::raw('DATE_SUB(budget_manage_date, INTERVAL 543 YEAR)'),
            ]);
    }

    public function down(): void
    {
        // Data correction is not reversed: some rows already stored Gregorian years.
    }
};
