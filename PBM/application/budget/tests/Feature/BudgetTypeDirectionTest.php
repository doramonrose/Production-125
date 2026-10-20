<?php

namespace Tests\Feature;

use App\Http\Controllers\Budget\BudgetSettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BudgetTypeDirectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('budget_set_type', function ($table) {
            $table->increments('budget_type_id');
            $table->unsignedInteger('budget_type_code');
            $table->string('budget_type_name');
            $table->string('budget_type_detail')->default('');
            $table->unsignedTinyInteger('budget_type_status')->default(1);
            $table->dateTime('budget_type_date_create')->nullable();
            $table->dateTime('budget_type_date_modify')->nullable();
        });

        (require database_path('migrations/2026_09_17_000001_add_direction_to_budget_set_type_table.php'))->up();
    }

    public function test_it_stores_the_selected_budget_type_direction(): void
    {
        $response = app(BudgetSettingController::class)->apiSave(
            Request::create('/', 'POST', ['name' => 'ตัดลดงบประมาณ', 'detail' => '', 'direction' => '-']),
            'type'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('-', DB::table('budget_set_type')->value('budget_type_direction'));

        $updateResponse = app(BudgetSettingController::class)->apiSave(
            Request::create('/', 'POST', [
                'id' => 1,
                'name' => 'เพิ่มงบประมาณ',
                'detail' => '',
                'direction' => '+',
            ]),
            'type'
        );

        $this->assertSame(200, $updateResponse->getStatusCode());
        $this->assertSame('+', DB::table('budget_set_type')->value('budget_type_direction'));
    }

    public function test_it_rejects_an_invalid_budget_type_direction(): void
    {
        $response = app(BudgetSettingController::class)->apiSave(
            Request::create('/', 'POST', ['name' => 'ข้อมูลไม่ถูกต้อง', 'detail' => '', 'direction' => '*']),
            'type'
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(0, DB::table('budget_set_type')->count());
    }
}