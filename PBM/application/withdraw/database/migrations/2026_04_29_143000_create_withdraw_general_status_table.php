<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdraw_general_status')) {
            Schema::create('withdraw_general_status', function (Blueprint $table) {
                $table->id('withdraw_general_status_id');
                $table->string('withdraw_general_status_code', 60)->unique();
                $table->string('withdraw_general_status_name', 150);
                $table->integer('withdraw_general_status_order')->default(0);
                $table->integer('withdraw_general_status_active')->default(1);
                $table->dateTime('withdraw_general_status_date_create')->nullable();
                $table->dateTime('withdraw_general_status_date_modify')->nullable();
                $table->string('staff_create')->nullable();
                $table->string('staff_modify')->nullable();
            });
        }

        $seedRows = [
            ['code' => 'saved', 'name' => 'ร่าง', 'order' => 10],
            ['code' => 'pending_primary_review', 'name' => 'รอตรวจสอบ/อนุมัติ', 'order' => 20],
            ['code' => 'pending_additional_review', 'name' => 'รอตรวจสอบ/อนุมัติ', 'order' => 30],
            ['code' => 'pending_finance_review', 'name' => 'รอการเงินตรวจสอบเอกสาร', 'order' => 40],
            ['code' => 'pending_approval', 'name' => 'รออนุมัติ', 'order' => 50],
            ['code' => 'approved', 'name' => 'อนุมัติ', 'order' => 60],
        ];

        foreach ($seedRows as $row) {
            $existing = DB::table('withdraw_general_status')
                ->where('withdraw_general_status_code', (string) $row['code'])
                ->first();

            $payload = [
                'withdraw_general_status_name' => (string) $row['name'],
                'withdraw_general_status_order' => (int) $row['order'],
                'withdraw_general_status_active' => 1,
                'withdraw_general_status_date_modify' => now(),
            ];

            if ($existing) {
                DB::table('withdraw_general_status')
                    ->where('withdraw_general_status_id', (int) ($existing->withdraw_general_status_id ?? 0))
                    ->update($payload);
            } else {
                DB::table('withdraw_general_status')->insert(array_merge($payload, [
                    'withdraw_general_status_code' => (string) $row['code'],
                    'withdraw_general_status_date_create' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_general_status');
    }
};

