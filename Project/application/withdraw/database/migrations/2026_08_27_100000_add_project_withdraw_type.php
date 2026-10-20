<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (Schema::hasTable('withdraw_type')) {
            $existing = DB::table('withdraw_type')->where('withdraw_type_code', 'project')->first();
            $payload = [
                'withdraw_type_name' => 'เบิกจ่ายเงินโครงการ',
                'withdraw_type_order' => 15,
                'withdraw_type_active' => 1,
                'withdraw_type_date_modify' => $now,
            ];
            if ($existing) {
                DB::table('withdraw_type')
                    ->where('withdraw_type_id', (int) $existing->withdraw_type_id)
                    ->update($payload);
            } else {
                DB::table('withdraw_type')->insert(array_merge($payload, [
                    'withdraw_type_code' => 'project',
                    'withdraw_type_date_create' => $now,
                ]));
            }
        }

        if (Schema::hasTable('withdraw')) {
            Schema::table('withdraw', function (Blueprint $table) {
                if (! Schema::hasColumn('withdraw', 'withdraw_project_id')) {
                    $table->string('withdraw_project_id', 80)->nullable()->after('withdraw_budget_manage_id');
                }
                if (! Schema::hasColumn('withdraw', 'withdraw_project_code')) {
                    $table->string('withdraw_project_code', 80)->nullable();
                }
                if (! Schema::hasColumn('withdraw', 'withdraw_project_activity_id')) {
                    $table->string('withdraw_project_activity_id', 80)->nullable();
                }
                if (! Schema::hasColumn('withdraw', 'withdraw_project_activity_name')) {
                    $table->string('withdraw_project_activity_name', 255)->nullable();
                }
                if (! Schema::hasColumn('withdraw', 'withdraw_project_handoff_token')) {
                    $table->string('withdraw_project_handoff_token', 64)->nullable();
                }
            });
        }

        if (Schema::hasTable('withdraw_set_memo_template')) {
            $exists = DB::table('withdraw_set_memo_template')
                ->where('withdraw_set_memo_template_kind', 'project')
                ->where('withdraw_set_memo_template_sub_type', 'default')
                ->exists();
            if (! $exists) {
                $source = DB::table('withdraw_set_memo_template')
                    ->where('withdraw_set_memo_template_kind', 'general')
                    ->where('withdraw_set_memo_template_sub_type', 'default')
                    ->first();
                $insert = [
                    'withdraw_set_memo_template_kind' => 'project',
                    'withdraw_set_memo_template_sub_type' => 'default',
                    'withdraw_set_memo_template_name' => 'เบิกจ่ายเงินโครงการ',
                    'withdraw_set_memo_template_subject' => $source?->withdraw_set_memo_template_subject ?? 'ขออนุมัติเบิกจ่าย........',
                    'withdraw_set_memo_template_to' => $source?->withdraw_set_memo_template_to ?? 'คณบดีคณะเภสัชศาสตร์',
                    'withdraw_set_memo_template_paragraph1' => $source?->withdraw_set_memo_template_paragraph1 ?? 'ตามที่...............................................................................................',
                    'withdraw_set_memo_template_paragraph2' => $source?->withdraw_set_memo_template_paragraph2 ?? 'ในการนี้.........................................................................................................จึงมีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย ดังนี้.-',
                    'withdraw_set_memo_template_paragraph3' => $source?->withdraw_set_memo_template_paragraph3 ?? 'จึงเรียนมาเพื่อโปรดพิจารณา',
                    'withdraw_set_memo_template_block_order' => $source?->withdraw_set_memo_template_block_order ?? json_encode(['paragraph1', 'paragraph2', 'items', 'paragraph3'], JSON_UNESCAPED_UNICODE),
                    'withdraw_set_memo_template_approval_step_codes' => $source?->withdraw_set_memo_template_approval_step_codes ?? json_encode([
                        'reviewer_head',
                        'finance_officer',
                        'finance_manager',
                        'faculty_secretary',
                        'vice_dean',
                        'dean',
                    ], JSON_UNESCAPED_UNICODE),
                    'withdraw_set_memo_template_process_actors' => $source?->withdraw_set_memo_template_process_actors ?? json_encode([], JSON_UNESCAPED_UNICODE),
                    'withdraw_set_memo_template_status' => 1,
                    'withdraw_set_memo_template_date_create' => $now,
                    'withdraw_set_memo_template_date_modify' => $now,
                ];
                if (Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_process_actors_enabled')) {
                    $insert['withdraw_set_memo_template_process_actors_enabled'] = (int) ($source?->withdraw_set_memo_template_process_actors_enabled ?? 0);
                }
                DB::table('withdraw_set_memo_template')->insert($insert);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('withdraw_type')) {
            DB::table('withdraw_type')->where('withdraw_type_code', 'project')->delete();
        }
        if (Schema::hasTable('withdraw_set_memo_template')) {
            DB::table('withdraw_set_memo_template')
                ->where('withdraw_set_memo_template_kind', 'project')
                ->where('withdraw_set_memo_template_sub_type', 'default')
                ->delete();
        }
        if (Schema::hasTable('withdraw')) {
            $columns = [];
            foreach ([
                'withdraw_project_id',
                'withdraw_project_code',
                'withdraw_project_activity_id',
                'withdraw_project_activity_name',
                'withdraw_project_handoff_token',
            ] as $column) {
                if (Schema::hasColumn('withdraw', $column)) {
                    $columns[] = $column;
                }
            }
            if ($columns !== []) {
                Schema::table('withdraw', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }
};
