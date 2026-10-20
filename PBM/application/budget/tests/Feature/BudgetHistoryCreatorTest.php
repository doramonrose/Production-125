<?php

namespace Tests\Feature;

use App\Http\Controllers\Budget\BudgetManageController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BudgetHistoryCreatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('budget_manage', function ($table) {
            $table->increments('budget_manage_id');
            $table->string('budget_manage_staff_create')->nullable();
        });
        Schema::create('budget_manage_list', function ($table) {
            $table->increments('budget_manage_list_id');
            $table->unsignedInteger('budget_manage_id');
            $table->unsignedInteger('budget_type_code')->nullable();
            $table->unsignedInteger('budget_source_code')->nullable();
            $table->unsignedInteger('budget_category_pay_code')->nullable();
        });
        Schema::create('budget_set_type', function ($table) {
            $table->unsignedInteger('budget_type_code')->primary();
            $table->string('budget_type_name')->nullable();
        });
        Schema::create('budget_set_source', function ($table) {
            $table->unsignedInteger('budget_source_code')->primary();
            $table->string('budget_source_name')->nullable();
        });
        Schema::create('budget_set_category_pay', function ($table) {
            $table->unsignedInteger('budget_category_pay_code')->primary();
            $table->string('budget_category_pay_name')->nullable();
        });
        Schema::create('users', function ($table) {
            $table->string('user_id')->primary();
            $table->string('staff_code')->nullable();
            $table->string('name_th')->nullable();
            $table->string('name')->nullable();
            $table->string('name_en')->nullable();
        });
    }

    public function test_it_includes_the_initial_budget_creator_in_history_data(): void
    {
        DB::table('users')->insert([
            'user_id' => '1001',
            'staff_code' => '1001',
            'name_th' => 'ผู้บันทึก ทดสอบ',
        ]);
        DB::table('budget_manage')->insert([
            'budget_manage_id' => 1,
            'budget_manage_staff_create' => '1001',
        ]);

        $response = app(BudgetManageController::class)->apiHistory('1');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'ผู้บันทึก ทดสอบ',
            $response->getData(true)['data']['main']['budget_manage_staff_create_name']
        );
    }
}