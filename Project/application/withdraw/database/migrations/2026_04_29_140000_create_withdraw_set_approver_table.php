<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_set_approver')) {
            return;
        }

        Schema::create('withdraw_set_approver', function (Blueprint $table) {
            $table->id('withdraw_set_approver_id');
            $table->string('withdraw_set_approver_step_code', 60)->nullable();
            $table->string('withdraw_set_approver_step_name', 150);
            $table->integer('withdraw_set_approver_step_order')->default(0);
            $table->string('withdraw_set_approver_staff_id')->nullable();
            $table->integer('withdraw_set_approver_status')->default(1);
            $table->dateTime('withdraw_set_approver_date_create')->nullable();
            $table->dateTime('withdraw_set_approver_date_modify')->nullable();
            $table->string('staff_create')->nullable();
            $table->string('staff_modify')->nullable();
            $table->index('withdraw_set_approver_step_order', 'withdraw_set_approver_order_idx');
        });

        $defaultRows = [
            ['code' => 'finance_manager', 'name' => 'หัวหน้างานการเงิน', 'order' => 50],
            ['code' => 'faculty_secretary', 'name' => 'เลขาคณะ', 'order' => 60],
            ['code' => 'vice_dean', 'name' => 'รองคณบดี', 'order' => 70],
            ['code' => 'dean', 'name' => 'คณบดี', 'order' => 80],
        ];

        $now = now();
        foreach ($defaultRows as $row) {
            DB::table('withdraw_set_approver')->insert([
                'withdraw_set_approver_step_code' => (string) $row['code'],
                'withdraw_set_approver_step_name' => (string) $row['name'],
                'withdraw_set_approver_step_order' => (int) $row['order'],
                'withdraw_set_approver_staff_id' => null,
                'withdraw_set_approver_status' => 1,
                'withdraw_set_approver_date_create' => $now,
                'withdraw_set_approver_date_modify' => $now,
                'staff_create' => null,
                'staff_modify' => null,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_set_approver');
    }
};

