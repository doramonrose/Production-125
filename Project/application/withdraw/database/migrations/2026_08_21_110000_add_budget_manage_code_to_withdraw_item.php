<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_item')) {
            return;
        }

        if (! Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_code')) {
            Schema::table('withdraw_item', function (Blueprint $table) {
                $table->string('withdraw_item_budget_manage_code')->nullable()->after('withdraw_item_budget_manage_id');
                $table->index('withdraw_item_budget_manage_code', 'wdi_budget_manage_code_idx');
            });
        }

        $this->backfillCodes();
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_item') || ! Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_code')) {
            return;
        }

        Schema::table('withdraw_item', function (Blueprint $table) {
            $table->dropIndex('wdi_budget_manage_code_idx');
            $table->dropColumn('withdraw_item_budget_manage_code');
        });
    }

    private function backfillCodes(): void
    {
        if (! Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_id')
            || ! Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_code')) {
            return;
        }

        $ids = DB::table('withdraw_item')
            ->where('withdraw_item_budget_manage_id', '>', 0)
            ->where(function ($query) {
                $query->whereNull('withdraw_item_budget_manage_code')
                    ->orWhere('withdraw_item_budget_manage_code', '');
            })
            ->distinct()
            ->pluck('withdraw_item_budget_manage_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
        if ($ids === []) {
            return;
        }

        $codeById = [];
        if (Schema::connection('budget')->hasTable('budget_manage')
            && Schema::connection('budget')->hasColumn('budget_manage', 'budget_manage_code')) {
            $rows = DB::connection('budget')
                ->table('budget_manage')
                ->whereIn('budget_manage_id', $ids)
                ->get(['budget_manage_id', 'budget_manage_code']);
            foreach ($rows as $row) {
                $id = (int) ($row->budget_manage_id ?? 0);
                $code = trim((string) ($row->budget_manage_code ?? ''));
                if ($id > 0 && $code !== '') {
                    $codeById[$id] = $code;
                }
            }
        }

        foreach ($codeById as $budgetManageId => $code) {
            DB::table('withdraw_item')
                ->where('withdraw_item_budget_manage_id', (int) $budgetManageId)
                ->where(function ($query) {
                    $query->whereNull('withdraw_item_budget_manage_code')
                        ->orWhere('withdraw_item_budget_manage_code', '');
                })
                ->update(['withdraw_item_budget_manage_code' => $code]);
        }
    }
};
