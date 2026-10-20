<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_general')) {
            return;
        }

        if (! Schema::hasTable('withdraw_general_approval_step')) {
            Schema::create('withdraw_general_approval_step', function (Blueprint $table) {
                $table->id('withdraw_general_approval_step_id');
                $table->unsignedBigInteger('withdraw_general_id');
                $table->string('withdraw_general_approval_step_code', 50);
                $table->string('withdraw_general_approval_step_name', 120);
                $table->integer('withdraw_general_approval_step_order')->default(0);
                $table->string('withdraw_general_approval_staff_id')->nullable();
                $table->string('withdraw_general_approval_custom_status')->nullable();
                $table->string('withdraw_general_approval_status', 30)->default('pending');
                $table->text('withdraw_general_approval_comment')->nullable();
                $table->dateTime('withdraw_general_approval_approved_at')->nullable();
                $table->dateTime('withdraw_general_approval_notified_at')->nullable();
                $table->dateTime('withdraw_general_approval_date_create')->nullable();
                $table->dateTime('withdraw_general_approval_date_modify')->nullable();
                $table->string('staff_create')->nullable();
                $table->string('staff_modify')->nullable();

                $table->index('withdraw_general_id', 'wgap_general_idx');
                $table->index(['withdraw_general_id', 'withdraw_general_approval_step_order'], 'wgap_general_order_idx');
                $table->index(['withdraw_general_approval_step_code', 'withdraw_general_approval_staff_id'], 'wgap_code_staff_idx');
            });
        }

        // Backfill legacy reviewer/actor data into approval steps.
        $generalRows = DB::table('withdraw_general')->get([
            'withdraw_general_id',
            'withdraw_general_reviewer_staff_id',
            'staff_create',
            'staff_modify',
            'withdraw_general_date_create',
            'withdraw_general_date_modify',
        ]);

        foreach ($generalRows as $generalRow) {
            $generalId = (int) ($generalRow->withdraw_general_id ?? 0);
            if ($generalId <= 0) {
                continue;
            }

            $nowCreate = $generalRow->withdraw_general_date_create ?? now();
            $nowModify = $generalRow->withdraw_general_date_modify ?? now();
            $staffCreate = (string) ($generalRow->staff_create ?? '');
            $staffModify = (string) ($generalRow->staff_modify ?? '');

            DB::table('withdraw_general_approval_step')
                ->where('withdraw_general_id', $generalId)
                ->delete();

            $stepRows = [];

            $reviewerStaffId = trim((string) ($generalRow->withdraw_general_reviewer_staff_id ?? ''));
            $stepRows[] = [
                'withdraw_general_id' => $generalId,
                'withdraw_general_approval_step_code' => 'reviewer_head',
                'withdraw_general_approval_step_name' => 'ผู้ตรวจสอบ/หัวหน้างาน',
                'withdraw_general_approval_step_order' => 10,
                'withdraw_general_approval_staff_id' => $reviewerStaffId !== '' ? $reviewerStaffId : null,
                'withdraw_general_approval_custom_status' => null,
                'withdraw_general_approval_status' => 'pending',
                'withdraw_general_approval_comment' => null,
                'withdraw_general_approval_approved_at' => null,
                'withdraw_general_approval_notified_at' => null,
                'withdraw_general_approval_date_create' => $nowCreate,
                'withdraw_general_approval_date_modify' => $nowModify,
                'staff_create' => $staffCreate,
                'staff_modify' => $staffModify,
            ];

            if (Schema::hasTable('withdraw_general_actor')) {
                $actorRows = DB::table('withdraw_general_actor')
                    ->where('withdraw_general_id', $generalId)
                    ->orderBy('withdraw_general_actor_order')
                    ->orderBy('withdraw_general_actor_id')
                    ->get([
                        'withdraw_general_actor_staff_id',
                        'withdraw_general_actor_status',
                        'withdraw_general_actor_order',
                    ]);

                foreach ($actorRows as $actorRow) {
                    $actorStaffId = trim((string) ($actorRow->withdraw_general_actor_staff_id ?? ''));
                    if ($actorStaffId === '') {
                        continue;
                    }
                    $actorOrder = (int) ($actorRow->withdraw_general_actor_order ?? 0);
                    $actorStatus = trim((string) ($actorRow->withdraw_general_actor_status ?? ''));
                    $stepRows[] = [
                        'withdraw_general_id' => $generalId,
                        'withdraw_general_approval_step_code' => 'additional_reviewer',
                        'withdraw_general_approval_step_name' => 'ผู้ตรวจสอบเพิ่มเติม',
                        'withdraw_general_approval_step_order' => 20 + max($actorOrder, 1),
                        'withdraw_general_approval_staff_id' => $actorStaffId,
                        'withdraw_general_approval_custom_status' => $actorStatus !== '' ? $actorStatus : null,
                        'withdraw_general_approval_status' => 'pending',
                        'withdraw_general_approval_comment' => null,
                        'withdraw_general_approval_approved_at' => null,
                        'withdraw_general_approval_notified_at' => null,
                        'withdraw_general_approval_date_create' => $nowCreate,
                        'withdraw_general_approval_date_modify' => $nowModify,
                        'staff_create' => $staffCreate,
                        'staff_modify' => $staffModify,
                    ];
                }
            }

            $fixedSteps = [
                ['code' => 'finance_officer', 'name' => 'งานการเงิน', 'order' => 40],
                ['code' => 'finance_manager', 'name' => 'หัวหน้างานการเงิน', 'order' => 50],
                ['code' => 'faculty_secretary', 'name' => 'เลขาคณะ', 'order' => 60],
                ['code' => 'vice_dean', 'name' => 'รองคณบดี', 'order' => 70],
                ['code' => 'dean', 'name' => 'คณบดี', 'order' => 80],
            ];

            foreach ($fixedSteps as $fixedStep) {
                $stepRows[] = [
                    'withdraw_general_id' => $generalId,
                    'withdraw_general_approval_step_code' => $fixedStep['code'],
                    'withdraw_general_approval_step_name' => $fixedStep['name'],
                    'withdraw_general_approval_step_order' => (int) $fixedStep['order'],
                    'withdraw_general_approval_staff_id' => null,
                    'withdraw_general_approval_custom_status' => null,
                    'withdraw_general_approval_status' => 'pending',
                    'withdraw_general_approval_comment' => null,
                    'withdraw_general_approval_approved_at' => null,
                    'withdraw_general_approval_notified_at' => null,
                    'withdraw_general_approval_date_create' => $nowCreate,
                    'withdraw_general_approval_date_modify' => $nowModify,
                    'staff_create' => $staffCreate,
                    'staff_modify' => $staffModify,
                ];
            }

            if (count($stepRows) > 0) {
                DB::table('withdraw_general_approval_step')->insert($stepRows);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_general_approval_step');
    }
};

