<?php

namespace Tests\Feature;

use App\Support\WithdrawDisbursementTotals;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class WithdrawDisbursementTypeTest extends TestCase
{
    public function test_detail_queries_preserve_travel_subtype_for_pending_and_approved_documents(): void
    {
        config(['database.connections.withdraw' => config('database.connections.sqlite')]);
        DB::purge('withdraw');
        Schema::connection('withdraw')->create('withdraw', function ($table) {
            $table->integer('withdraw_id');
            $table->string('withdraw_type');
            $table->string('withdraw_sub_type');
            $table->string('withdraw_status');
        });
        Schema::connection('withdraw')->create('withdraw_item', function ($table) {
            $table->integer('withdraw_id');
            $table->decimal('amount', 12, 2);
        });

        $service = new WithdrawDisbursementTotals;
        $select = new ReflectionMethod($service, 'selectItemDetailColumns');
        $append = new ReflectionMethod($service, 'appendItemDetails');
        foreach (['pending_approval', 'approved'] as $status) {
            foreach (['travel_leave', 'salary_civil_servant'] as $subType) {
                DB::connection('withdraw')->table('withdraw')->delete();
                DB::connection('withdraw')->table('withdraw_item')->delete();
                DB::connection('withdraw')->table('withdraw')->insert([
                    'withdraw_id' => 1, 'withdraw_type' => 'salary',
                    'withdraw_sub_type' => $subType, 'withdraw_status' => $status,
                ]);
                DB::connection('withdraw')->table('withdraw_item')->insert(['withdraw_id' => 1, 'amount' => 1250]);
                $query = DB::connection('withdraw')->table('withdraw_item as i')
                    ->join('withdraw as p', 'p.withdraw_id', '=', 'i.withdraw_id');
                $result = $select->invoke($service, $query, 'withdraw', 'withdraw_item',
                    'withdraw_id', 'withdraw_status', 'amount', 'subject', 'date_create',
                    'finance_date', 'item_name', 'item_category', 'general', 'เบิกทั่วไป')->get();
                $rows = [];
                $append->invokeArgs($service, [&$rows, $result]);
                $this->assertSame($subType === 'travel_leave'
                    ? 'เบิกจ่ายในการเดินทางไปปฏิบัติงาน' : 'เบิกเงินเดือน', $rows[0]['source_label']);
                $this->assertSame('salary', $rows[0]['source']);
                $this->assertSame($status, $rows[0]['status']);
                $this->assertSame(1250.0, $rows[0]['amount']);
            }
        }
    }
}
