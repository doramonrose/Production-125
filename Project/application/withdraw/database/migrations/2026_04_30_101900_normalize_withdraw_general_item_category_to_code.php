<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_general_item') || ! Schema::hasTable('withdraw_set_pay_list')) {
            return;
        }

        $nameToCategoryCode = DB::table('withdraw_set_pay_list')
            ->whereNotNull('withdraw_pay_name')
            ->whereNotNull('withdraw_category_pay_code')
            ->get(['withdraw_pay_name', 'withdraw_category_pay_code'])
            ->reduce(function (array $carry, $row) {
                $name = trim((string) ($row->withdraw_pay_name ?? ''));
                $code = trim((string) ($row->withdraw_category_pay_code ?? ''));
                if ($name !== '' && $code !== '' && ctype_digit($code)) {
                    $carry[$name] = $code;
                }
                return $carry;
            }, []);

        if (count($nameToCategoryCode) === 0) {
            return;
        }

        DB::table('withdraw_general_item')
            ->select(['withdraw_general_item_id', 'withdraw_general_item_category'])
            ->orderBy('withdraw_general_item_id')
            ->chunkById(500, function ($rows) use ($nameToCategoryCode) {
                foreach ($rows as $row) {
                    $rawCategory = trim((string) ($row->withdraw_general_item_category ?? ''));
                    if ($rawCategory === '' || ctype_digit($rawCategory)) {
                        continue;
                    }
                    $mappedCode = (string) ($nameToCategoryCode[$rawCategory] ?? '');
                    if ($mappedCode === '') {
                        continue;
                    }
                    DB::table('withdraw_general_item')
                        ->where('withdraw_general_item_id', (int) $row->withdraw_general_item_id)
                        ->update(['withdraw_general_item_category' => $mappedCode]);
                }
            }, 'withdraw_general_item_id');
    }

    public function down(): void
    {
        // Irreversible data normalization.
    }
};
