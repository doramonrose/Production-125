<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('withdraw_general') && Schema::hasColumn('withdraw_general', 'withdraw_general_status')) {
            DB::table('withdraw_general')
                ->where('withdraw_general_status', 'processing')
                ->update([
                    'withdraw_general_status' => 'pending_primary_review',
                    'withdraw_general_date_modify' => now(),
                ]);
        }

        if (Schema::hasTable('withdraw_general_status')) {
            $existing = DB::table('withdraw_general_status')
                ->where('withdraw_general_status_code', 'processing')
                ->first();

            if ($existing) {
                DB::table('withdraw_general_status')
                    ->where('withdraw_general_status_id', (int) ($existing->withdraw_general_status_id ?? 0))
                    ->update([
                        'withdraw_general_status_code' => 'pending_primary_review',
                        'withdraw_general_status_name' => 'รอตรวจสอบ/อนุมัติ',
                        'withdraw_general_status_date_modify' => now(),
                    ]);
            } else {
                DB::table('withdraw_general_status')->updateOrInsert(
                    ['withdraw_general_status_code' => 'pending_primary_review'],
                    [
                        'withdraw_general_status_name' => 'รอตรวจสอบ/อนุมัติ',
                        'withdraw_general_status_order' => 20,
                        'withdraw_general_status_active' => 1,
                        'withdraw_general_status_date_create' => now(),
                        'withdraw_general_status_date_modify' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('withdraw_general') && Schema::hasColumn('withdraw_general', 'withdraw_general_status')) {
            DB::table('withdraw_general')
                ->where('withdraw_general_status', 'pending_primary_review')
                ->update([
                    'withdraw_general_status' => 'processing',
                    'withdraw_general_date_modify' => now(),
                ]);
        }

        if (Schema::hasTable('withdraw_general_status')) {
            $existing = DB::table('withdraw_general_status')
                ->where('withdraw_general_status_code', 'pending_primary_review')
                ->first();

            if ($existing) {
                DB::table('withdraw_general_status')
                    ->where('withdraw_general_status_id', (int) ($existing->withdraw_general_status_id ?? 0))
                    ->update([
                        'withdraw_general_status_code' => 'processing',
                        'withdraw_general_status_name' => 'รอตรวจสอบ/อนุมัติ',
                        'withdraw_general_status_date_modify' => now(),
                    ]);
            }
        }
    }
};

