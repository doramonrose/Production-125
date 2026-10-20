<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw') && ! Schema::hasColumn('withdraw', 'withdraw_paragraph_4')) {
            Schema::table('withdraw', function (Blueprint $table) {
                $table->text('withdraw_paragraph_4')->nullable()->after('withdraw_paragraph_3');
            });
        }

        if (
            Schema::hasTable('withdraw_set_memo_template')
            && ! Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_paragraph4')
        ) {
            Schema::table('withdraw_set_memo_template', function (Blueprint $table) {
                $table->text('withdraw_set_memo_template_paragraph4')->nullable()->after('withdraw_set_memo_template_paragraph3');
            });
        }

        if (! Schema::hasTable('withdraw_set_memo_template')) {
            return;
        }

        $rows = DB::table('withdraw_set_memo_template')->get([
            'withdraw_set_memo_template_id',
            'withdraw_set_memo_template_block_order',
        ]);

        foreach ($rows as $row) {
            $order = json_decode((string) ($row->withdraw_set_memo_template_block_order ?? ''), true);
            if (! is_array($order)) {
                $order = [];
            }
            if (! in_array('paragraph4', $order, true)) {
                $order[] = 'paragraph4';
            }
            DB::table('withdraw_set_memo_template')
                ->where('withdraw_set_memo_template_id', (int) $row->withdraw_set_memo_template_id)
                ->update([
                    'withdraw_set_memo_template_block_order' => json_encode(array_values($order), JSON_UNESCAPED_UNICODE),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('withdraw') && Schema::hasColumn('withdraw', 'withdraw_paragraph_4')) {
            Schema::table('withdraw', function (Blueprint $table) {
                $table->dropColumn('withdraw_paragraph_4');
            });
        }

        if (
            Schema::hasTable('withdraw_set_memo_template')
            && Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_paragraph4')
        ) {
            Schema::table('withdraw_set_memo_template', function (Blueprint $table) {
                $table->dropColumn('withdraw_set_memo_template_paragraph4');
            });
        }
    }
};
