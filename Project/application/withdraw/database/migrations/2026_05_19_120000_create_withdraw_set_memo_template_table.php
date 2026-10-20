<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_set_memo_template')) {
            return;
        }

        Schema::create('withdraw_set_memo_template', function (Blueprint $table) {
            $table->id('withdraw_set_memo_template_id');
            $table->string('withdraw_set_memo_template_kind', 20);
            $table->string('withdraw_set_memo_template_sub_type', 50)->default('');
            $table->unique(
                ['withdraw_set_memo_template_kind', 'withdraw_set_memo_template_sub_type'],
                'wsmt_kind_subtype_uq'
            );
            $table->string('withdraw_set_memo_template_name', 150);
            $table->text('withdraw_set_memo_template_subject')->nullable();
            $table->text('withdraw_set_memo_template_to')->nullable();
            $table->text('withdraw_set_memo_template_paragraph1')->nullable();
            $table->text('withdraw_set_memo_template_paragraph2')->nullable();
            $table->text('withdraw_set_memo_template_paragraph3')->nullable();
            $table->json('withdraw_set_memo_template_block_order')->nullable();
            $table->json('withdraw_set_memo_template_approval_step_codes')->nullable();
            $table->json('withdraw_set_memo_template_process_actors')->nullable();
            $table->boolean('withdraw_set_memo_template_status')->default(true);
            $table->dateTime('withdraw_set_memo_template_date_create')->nullable();
            $table->dateTime('withdraw_set_memo_template_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
        });

        $now = now();
        $defaults = [
            [
                'kind' => 'general',
                'sub_type' => 'default',
                'name' => 'เบิกจ่ายทั่วไป',
                'subject' => 'ขออนุมัติเบิกจ่าย........',
                'to' => 'คณบดีคณะเภสัชศาสตร์',
                'paragraph1' => 'ตามที่...............................................................................................',
                'paragraph2' => 'ในการนี้.........................................................................................................จึงมีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย ดังนี้.-',
                'paragraph3' => 'จึงเรียนมาเพื่อโปรดพิจารณา',
                'block_order' => ['paragraph1', 'paragraph2', 'items', 'paragraph3'],
                'approval_step_codes' => ['finance_officer', 'finance_manager', 'faculty_secretary', 'vice_dean', 'dean'],
            ],
            [
                'kind' => 'salary',
                'sub_type' => 'salary',
                'name' => 'เงินเดือน',
                'subject' => 'ขอเบิกเงินประจำตำแหน่ง ค่าตอบแทนรายเดือนเท่ากับเงินประจำตำแหน่ง สำหรับข้าราชการ',
                'to' => 'อธิการบดี มหาวิทยาลัยเชียงใหม่',
                'paragraph1' => 'ด้วยคณะเภสัชศาสตร์ มหาวิทยาลัยเชียงใหม่ ขอเบิกเงินประจำตำแหน่งค่าตอบแทนรายเดือนเท่ากับเงินประจำตำแหน่ง............................................ดังนี้',
                'paragraph2' => 'ในการนี้.........................................................................................................จึงมีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย ดังนี้.-',
                'paragraph3' => 'จึงเรียนมาเพื่อโปรดพิจารณาดำเนินการในส่วนที่เกี่ยวข้องต่อไป',
                'block_order' => ['paragraph1', 'items', 'paragraph2', 'paragraph3'],
                'approval_step_codes' => ['finance_manager', 'faculty_secretary', 'vice_dean', 'dean'],
            ],
            [
                'kind' => 'salary',
                'sub_type' => 'position_allowance',
                'name' => 'เงินประจำตำแหน่ง',
                'subject' => 'ขอเบิกเงินประจำตำแหน่ง ค่าตอบแทนรายเดือนเท่ากับเงินประจำตำแหน่ง สำหรับข้าราชการ',
                'to' => 'อธิการบดี มหาวิทยาลัยเชียงใหม่',
                'paragraph1' => 'ด้วยคณะเภสัชศาสตร์ มหาวิทยาลัยเชียงใหม่ ขอเบิกเงินประจำตำแหน่งค่าตอบแทนรายเดือนเท่ากับเงินประจำตำแหน่ง............................................ดังนี้',
                'paragraph2' => 'ในการนี้.........................................................................................................จึงมีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย ดังนี้.-',
                'paragraph3' => 'จึงเรียนมาเพื่อโปรดพิจารณาดำเนินการในส่วนที่เกี่ยวข้องต่อไป',
                'block_order' => ['paragraph1', 'items', 'paragraph2', 'paragraph3'],
                'approval_step_codes' => ['finance_manager', 'faculty_secretary', 'vice_dean', 'dean'],
            ],
            [
                'kind' => 'salary',
                'sub_type' => 'pts',
                'name' => 'เงิน พ.ต.ส.',
                'subject' => 'ขอเบิกเงิน พ.ต.ส.',
                'to' => 'อธิการบดี มหาวิทยาลัยเชียงใหม่',
                'paragraph1' => 'ด้วยคณะเภสัชศาสตร์ มหาวิทยาลัยเชียงใหม่ ขอเบิกเงิน พ.ต.ส. ............................................ดังนี้',
                'paragraph2' => 'ในการนี้.........................................................................................................จึงมีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย ดังนี้.-',
                'paragraph3' => 'จึงเรียนมาเพื่อโปรดพิจารณาดำเนินการในส่วนที่เกี่ยวข้องต่อไป',
                'block_order' => ['paragraph1', 'items', 'paragraph2', 'paragraph3'],
                'approval_step_codes' => ['finance_manager', 'faculty_secretary', 'vice_dean', 'dean'],
            ],
        ];

        foreach ($defaults as $row) {
            DB::table('withdraw_set_memo_template')->insert([
                'withdraw_set_memo_template_kind' => $row['kind'],
                'withdraw_set_memo_template_sub_type' => $row['sub_type'],
                'withdraw_set_memo_template_name' => $row['name'],
                'withdraw_set_memo_template_subject' => $row['subject'],
                'withdraw_set_memo_template_to' => $row['to'],
                'withdraw_set_memo_template_paragraph1' => $row['paragraph1'],
                'withdraw_set_memo_template_paragraph2' => $row['paragraph2'],
                'withdraw_set_memo_template_paragraph3' => $row['paragraph3'],
                'withdraw_set_memo_template_block_order' => json_encode($row['block_order'], JSON_UNESCAPED_UNICODE),
                'withdraw_set_memo_template_approval_step_codes' => json_encode($row['approval_step_codes'], JSON_UNESCAPED_UNICODE),
                'withdraw_set_memo_template_process_actors' => json_encode([], JSON_UNESCAPED_UNICODE),
                'withdraw_set_memo_template_status' => 1,
                'withdraw_set_memo_template_date_create' => $now,
                'withdraw_set_memo_template_date_modify' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_set_memo_template');
    }
};
