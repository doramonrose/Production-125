<?php

namespace Tests\Feature;

use App\Http\Controllers\Budget\BudgetManageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BudgetManageCodeUniqueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('budget_manage', function ($table) {
            $table->increments('budget_manage_id');
            $table->string('budget_manage_code');
        });
    }

    public function test_it_rejects_a_budget_code_that_already_exists(): void
    {
        DB::table('budget_manage')->insert(['budget_manage_code' => '691E09961G00010']);

        $response = app(BudgetManageController::class)->apiSaveManage(
            Request::create('/', 'POST', [
                'budgetManageYear' => '2569',
                'budgetManageCode' => '691E09961G00010',
            ])
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('รหัสงบประมาณนี้มีอยู่แล้ว', $response->getData(true)['message']);
        $this->assertSame(1, DB::table('budget_manage')->count());
    }
}