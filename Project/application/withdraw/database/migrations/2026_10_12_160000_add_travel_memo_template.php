<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_set_memo_template')) {
            return;
        }

        $existing = DB::table('withdraw_set_memo_template')
            ->where('withdraw_set_memo_template_kind', 'travel')
            ->where('withdraw_set_memo_template_sub_type', 'default')
            ->first();
        if ($existing) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'withdraw_set_memo_template_kind' => 'travel',
            'withdraw_set_memo_template_sub_type' => 'default',
            'withdraw_set_memo_template_name' => 'เบิกจ่ายในการเดินทางไปปฏิบัติงาน',
            'withdraw_set_memo_template_subject' => 'ขออนุมัติเบิกค่าใช้จ่ายในการเดินทางไปปฏิบัติงาน',
            'withdraw_set_memo_template_to' => 'คณบดีคณะเภสัชศาสตร์',
            'withdraw_set_memo_template_paragraph1' => 'ด้วยงานการเงินได้ตรวจสอบรายงานการเดินทางไปปฏิบัติงานที่ลงนามเรียบร้อยแล้ว มีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย ดังนี้',
            'withdraw_set_memo_template_paragraph2' => 'จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ โดยเบิกจ่ายตามรายการข้างต้น',
            'withdraw_set_memo_template_paragraph3' => 'จึงเรียนมาเพื่อโปรดพิจารณาดำเนินการในส่วนที่เกี่ยวข้องต่อไป',
            'withdraw_set_memo_template_block_order' => json_encode(
                ['paragraph1', 'items', 'paragraph2', 'paragraph3', 'paragraph4'],
                JSON_UNESCAPED_UNICODE
            ),
            'withdraw_set_memo_template_approval_step_codes' => json_encode(
                ['finance_manager', 'faculty_secretary', 'vice_dean', 'dean'],
                JSON_UNESCAPED_UNICODE
            ),
            'withdraw_set_memo_template_process_actors' => json_encode([], JSON_UNESCAPED_UNICODE),
            'withdraw_set_memo_template_status' => 1,
            'withdraw_set_memo_template_date_create' => $now,
            'withdraw_set_memo_template_date_modify' => $now,
        ];

        if (Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_paragraph4')) {
            $payload['withdraw_set_memo_template_paragraph4'] = '';
        }
        if (Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_paragraph_enabled')) {
            $payload['withdraw_set_memo_template_paragraph_enabled'] = json_encode([
                'paragraph1' => true,
                'paragraph2' => true,
                'paragraph3' => true,
                'paragraph4' => false,
            ], JSON_UNESCAPED_UNICODE);
        }
        if (Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_process_actors_enabled')) {
            $payload['withdraw_set_memo_template_process_actors_enabled'] = 1;
        }

        DB::table('withdraw_set_memo_template')->insert($payload);
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdraw_set_memo_template')) {
            return;
        }

        DB::table('withdraw_set_memo_template')
            ->where('withdraw_set_memo_template_kind', 'travel')
            ->where('withdraw_set_memo_template_sub_type', 'default')
            ->delete();
    }
};
