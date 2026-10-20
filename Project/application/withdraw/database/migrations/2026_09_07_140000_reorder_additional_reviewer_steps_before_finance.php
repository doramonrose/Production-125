<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ย้ายลำดับผู้ตรวจสอบ/อนุมัติ เพิ่มเติม ให้อยู่หลังหัวหน้างาน (10)
     * และก่อนงานการเงิน (40) สำหรับรายการที่สร้างไว้ก่อนหน้า
     */
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_approval_step')) {
            return;
        }

        $withdrawIds = DB::table('withdraw_approval_step')
            ->where('withdraw_approval_step_code', 'additional_reviewer')
            ->distinct()
            ->pluck('withdraw_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        foreach ($withdrawIds as $withdrawId) {
            $additionalRows = DB::table('withdraw_approval_step')
                ->where('withdraw_id', $withdrawId)
                ->where('withdraw_approval_step_code', 'additional_reviewer')
                ->orderBy('withdraw_approval_step_order')
                ->orderBy('withdraw_approval_step_id')
                ->get([
                    'withdraw_approval_step_id',
                    'withdraw_approval_step_order',
                ]);

            if ($additionalRows->isEmpty()) {
                continue;
            }

            $financeOrder = (int) (DB::table('withdraw_approval_step')
                ->where('withdraw_id', $withdrawId)
                ->where('withdraw_approval_step_code', 'finance_officer')
                ->value('withdraw_approval_step_order') ?? 40);

            $needsReorder = $additionalRows->contains(
                fn ($row) => (int) ($row->withdraw_approval_step_order ?? 0) >= $financeOrder
                    || (int) ($row->withdraw_approval_step_order ?? 0) > 39
            );

            if (! $needsReorder) {
                continue;
            }

            $actorOrder = 1;
            foreach ($additionalRows as $row) {
                $stepId = (int) ($row->withdraw_approval_step_id ?? 0);
                if ($stepId <= 0) {
                    continue;
                }

                DB::table('withdraw_approval_step')
                    ->where('withdraw_approval_step_id', $stepId)
                    ->update([
                        'withdraw_approval_step_name' => 'ผู้ตรวจสอบ/อนุมัติ เพิ่มเติม',
                        'withdraw_approval_step_order' => 20 + $actorOrder,
                        'withdraw_approval_date_modify' => now(),
                    ]);
                $actorOrder += 1;
            }
        }
    }

    public function down(): void
    {
        // ไม่ย้อนลำดับเดิม เพราะไม่เก็บค่า order เก่าไว้
    }
};
