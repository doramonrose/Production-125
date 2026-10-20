<?php

namespace Tests\Feature;

use App\Http\Controllers\Budget\BudgetSettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BudgetExpenseWithdrawAccountBindingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('budget_set_expenses', function ($table) {
            $table->increments('budget_set_expenses_id');
            $table->unsignedInteger('budget_pay_code');
            $table->unsignedInteger('budget_set_expenses_code');
            $table->string('budget_set_expenses_name');
            $table->string('budget_set_expenses_detail')->default('');
            $table->unsignedTinyInteger('budget_set_expenses_status')->default(1);
            $table->dateTime('budget_set_expenses_date_create')->nullable();
            $table->dateTime('budget_set_expenses_date_modify')->nullable();
        });
        Schema::create('budget_set_expenses_withdraw_accounts', function ($table) {
            $table->unsignedInteger('budget_set_expenses_code');
            $table->unsignedInteger('withdraw_set_expenses_code');
            $table->string('withdraw_set_expenses_name')->default('');
            $table->dateTime('date_create')->nullable();
        });
    }

    public function test_it_rejects_a_withdraw_account_already_bound_to_another_expense(): void
    {
        DB::table('budget_set_expenses')->insert([
            'budget_pay_code' => 1,
            'budget_set_expenses_code' => 1,
            'budget_set_expenses_name' => 'รายจ่ายเดิม',
        ]);
        DB::table('budget_set_expenses_withdraw_accounts')->insert([
            'budget_set_expenses_code' => 1,
            'withdraw_set_expenses_code' => 501,
            'withdraw_set_expenses_name' => 'ค่าวัสดุ',
        ]);

        $response = app(BudgetSettingController::class)->apiSave(
            Request::create('/', 'POST', [
                'pay_code' => 1,
                'name' => 'รายจ่ายใหม่',
                'withdraw_expenses_codes' => [501],
            ]),
            'expenses'
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('ค่าวัสดุ', $response->getData(true)['message']);
        $this->assertSame(1, DB::table('budget_set_expenses')->count());
    }

    public function test_it_allows_an_expense_to_keep_its_own_withdraw_account_binding(): void
    {
        DB::table('budget_set_expenses')->insert([
            'budget_set_expenses_id' => 1,
            'budget_pay_code' => 1,
            'budget_set_expenses_code' => 1,
            'budget_set_expenses_name' => 'รายจ่ายเดิม',
        ]);
        DB::table('budget_set_expenses_withdraw_accounts')->insert([
            'budget_set_expenses_code' => 1,
            'withdraw_set_expenses_code' => 501,
            'withdraw_set_expenses_name' => 'ค่าวัสดุ',
        ]);

        $response = app(BudgetSettingController::class)->apiSave(
            Request::create('/', 'POST', [
                'id' => 1,
                'pay_code' => 1,
                'name' => 'รายจ่ายเดิม (แก้ไข)',
                'withdraw_expenses_codes' => [501],
            ]),
            'expenses'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, DB::table('budget_set_expenses_withdraw_accounts')->count());
    }
}