<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_salary') && Schema::hasColumn('withdraw_salary', 'withdraw_salary_request_type')) {
            DB::table('withdraw_salary')
                ->where('withdraw_salary_request_type', 'salary')
                ->update(['withdraw_salary_request_type' => 'salary_civil_servant']);
        }

        if (! Schema::hasTable('withdraw_set_memo_template')) {
            return;
        }

        $now = now();
        $legacySalary = DB::table('withdraw_set_memo_template')
            ->where('withdraw_set_memo_template_kind', 'salary')
            ->where('withdraw_set_memo_template_sub_type', 'salary')
            ->first();

        $this->ensureMemoTemplate('salary', 'salary_civil_servant', [
            'name' => 'เงินเดือนข้าราชการ',
            'subject' => 'ขอเบิกเงินเดือนข้าราชการ ประจำเดือน.............................................',
            'to' => 'อธิการบดี มหาวิทยาลัยเชียงใหม่',
            'paragraph1' => 'ด้วยคณะเภสัชศาสตร์ มหาวิทยาลัยเชียงใหม่ ขอเบิกเงินเดือนข้าราชการ ประจำเดือน.............................................ดังนี้',
            'paragraph2' => 'ในการนี้.........................................................................................................จึงมีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย ดังนี้.-',
            'paragraph3' => 'จึงเรียนมาเพื่อโปรดพิจารณาดำเนินการในส่วนที่เกี่ยวข้องต่อไป',
            'block_order' => ['paragraph1', 'items', 'paragraph2', 'paragraph3'],
            'approval_step_codes' => ['finance_manager', 'faculty_secretary', 'vice_dean', 'dean'],
        ], $legacySalary, $now);

        $this->ensureMemoTemplate('salary', 'salary_university_staff', [
            'name' => 'เงินเดือนพนักงานมหาวิทยาลัย',
            'subject' => 'ขอเบิกเงินเดือนพนักงานมหาวิทยาลัย ประจำเดือน.............................................',
            'to' => 'อธิการบดี มหาวิทยาลัยเชียงใหม่',
            'paragraph1' => 'ด้วยคณะเภสัชศาสตร์ มหาวิทยาลัยเชียงใหม่ ขอเบิกเงินเดือนพนักงานมหาวิทยาลัย ประจำเดือน.............................................ดังนี้',
            'paragraph2' => 'ในการนี้.........................................................................................................จึงมีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย ดังนี้.-',
            'paragraph3' => 'จึงเรียนมาเพื่อโปรดพิจารณาดำเนินการในส่วนที่เกี่ยวข้องต่อไป',
            'block_order' => ['paragraph1', 'items', 'paragraph2', 'paragraph3'],
            'approval_step_codes' => ['finance_manager', 'faculty_secretary', 'vice_dean', 'dean'],
        ], $legacySalary, $now);
    }

    public function down(): void
    {
        if (Schema::hasTable('withdraw_salary') && Schema::hasColumn('withdraw_salary', 'withdraw_salary_request_type')) {
            DB::table('withdraw_salary')
                ->whereIn('withdraw_salary_request_type', ['salary_civil_servant', 'salary_university_staff'])
                ->update(['withdraw_salary_request_type' => 'salary']);
        }

        if (! Schema::hasTable('withdraw_set_memo_template')) {
            return;
        }

        DB::table('withdraw_set_memo_template')
            ->where('withdraw_set_memo_template_kind', 'salary')
            ->whereIn('withdraw_set_memo_template_sub_type', ['salary_civil_servant', 'salary_university_staff'])
            ->delete();
    }

    private function ensureMemoTemplate(
        string $kind,
        string $subType,
        array $defaults,
        ?object $legacyRow,
        $now
    ): void {
        $exists = DB::table('withdraw_set_memo_template')
            ->where('withdraw_set_memo_template_kind', $kind)
            ->where('withdraw_set_memo_template_sub_type', $subType)
            ->exists();
        if ($exists) {
            return;
        }

        $blockOrder = $defaults['block_order'];
        $approvalStepCodes = $defaults['approval_step_codes'];

        DB::table('withdraw_set_memo_template')->insert([
            'withdraw_set_memo_template_kind' => $kind,
            'withdraw_set_memo_template_sub_type' => $subType,
            'withdraw_set_memo_template_name' => (string) ($defaults['name'] ?? ''),
            'withdraw_set_memo_template_subject' => (string) ($defaults['subject'] ?? ($legacyRow->withdraw_set_memo_template_subject ?? '')),
            'withdraw_set_memo_template_to' => (string) ($defaults['to'] ?? ($legacyRow->withdraw_set_memo_template_to ?? '')),
            'withdraw_set_memo_template_paragraph1' => (string) ($defaults['paragraph1'] ?? ($legacyRow->withdraw_set_memo_template_paragraph1 ?? '')),
            'withdraw_set_memo_template_paragraph2' => (string) ($defaults['paragraph2'] ?? ($legacyRow->withdraw_set_memo_template_paragraph2 ?? '')),
            'withdraw_set_memo_template_paragraph3' => (string) ($defaults['paragraph3'] ?? ($legacyRow->withdraw_set_memo_template_paragraph3 ?? '')),
            'withdraw_set_memo_template_block_order' => json_encode($blockOrder, JSON_UNESCAPED_UNICODE),
            'withdraw_set_memo_template_approval_step_codes' => json_encode($approvalStepCodes, JSON_UNESCAPED_UNICODE),
            'withdraw_set_memo_template_process_actors' => json_encode([], JSON_UNESCAPED_UNICODE),
            'withdraw_set_memo_template_status' => 1,
            'withdraw_set_memo_template_date_create' => $now,
            'withdraw_set_memo_template_date_modify' => $now,
        ]);
    }
};
