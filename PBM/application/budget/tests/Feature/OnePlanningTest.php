<?php

namespace Tests\Feature;

use App\Http\Controllers\Budget\OnePlanningController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OnePlanningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.oneplanning.auth_code' => 'test-auth', 'services.oneplanning.pass_key' => 'test-secret']);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_fetches_budget_rows_with_server_side_form_credentials(): void
    {
        Carbon::setTestNow('2026-10-14');
        Http::fake(['*' => Http::response(['status' => ['error' => 0], 'data' => [['name' => 'Budget', 'budget_val' => 100]]])]);
        $view = (new OnePlanningController)->index(Request::create('/budget/one-planning'));
        $this->assertSame(2570, $view->getData()['year']);
        $this->assertSame(2570, $view->getData()['currentYear']);
        $this->assertContains(2570, $view->getData()['yearOptions']);
        $this->assertCount(1, $view->getData()['rows']);
        $this->assertNull($view->getData()['error']);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request['auth_code'] === 'test-auth'
            && $request['pass_key'] === 'test-secret'
            && $request['ref_code'] === config('services.oneplanning.ref_code')
            && (int) $request['fiscal_year_th'] === 2570
            && ! str_contains($request->url(), 'test-secret'));
    }

    public function test_uses_selected_year_and_previous_fiscal_year_before_october(): void
    {
        Carbon::setTestNow('2026-09-30');
        Http::fake(['*' => Http::response(['status' => ['error' => 0], 'data' => []])]);
        $controller = new OnePlanningController;
        $this->assertSame(2569, $controller->index(Request::create('/budget/one-planning'))->getData()['year']);
        $this->assertSame(2568, $controller->index(Request::create('/budget/one-planning', 'GET', ['fiscal_year_th' => 2568]))->getData()['year']);
    }

    public function test_presents_rows_like_the_budget_manage_list(): void
    {
        Http::fake(['*' => Http::response(['status' => ['error' => 0], 'data' => [[
            'code' => '7042',
            'name' => 'รายการทดสอบ',
            'budget_val' => '1000.0000',
            'budget_balance' => '250.0000',
            'budget_type_name' => 'เงินรายได้',
            'budget_fund_name' => 'กองทุนทั่วไป',
            'budget_3d_plan_name' => 'แผนงาน',
            'budget_expend_group_name' => 'ค่าครุภัณฑ์',
            'budget_round_group_name' => 'ต้นปี',
            'pj_project_name' => 'โครงการทดสอบ',
        ]]])]);

        $data = (new OnePlanningController)->index(Request::create('/budget/one-planning'))->getData();
        $row = $data['rows'][0];

        $this->assertSame('7042', $row['code']);
        $this->assertSame('รายการทดสอบ', $row['name']);
        $this->assertSame('กองทุนทั่วไป/แผนงาน/ค่าครุภัณฑ์', $row['source_path']);
        $this->assertSame('แผนงาน', $row['budget_3d_plan_name']);
        $this->assertSame('ต้นปี', $row['budget_round_group_name']);
        $this->assertSame('ต้นปี', $row['items'][0]['budget_round_group_name']);
        $this->assertSame(1000.0, $row['budget_val']);
        $this->assertSame(250.0, $row['budget_balance']);
        $this->assertSame(-750.0, $row['delta']);
        $this->assertSame(1, $row['item_count']);
        $this->assertSame(['เงินรายได้'], $data['typeOptions']);
        $this->assertSame(['แผนงาน'], $data['planOptions']);
        $this->assertSame(['กองทุนทั่วไป'], $data['fundOptions']);
        $this->assertSame(['ค่าครุภัณฑ์'], $data['categoryOptions']);
        $this->assertSame(['ต้นปี'], $data['roundOptions']);
    }

    public function test_groups_duplicate_budget_codes_into_one_main_row(): void
    {
        Http::fake(['*' => Http::response(['status' => ['error' => 0], 'data' => [
            [
                'code' => '7021',
                'name' => 'งานบริหารทั่วไป',
                'budget_val' => '100.0000',
                'budget_balance' => '40.0000',
                'budget_item_name' => 'ค่าตอบแทน',
                'budget_fund_name' => 'กองทุนทั่วไป',
                'reason_request' => 'ค่าตอบแทนประชุม',
            ],
            [
                'code' => '7021',
                'name' => 'งานบริหารทั่วไป',
                'budget_val' => '50.0000',
                'budget_balance' => '50.0000',
                'budget_item_name' => 'ค่าใช้สอย',
                'budget_fund_name' => 'กองทุนทั่วไป',
                'reason_request' => 'ค่าใช้สอยสำนักงาน',
            ],
        ]])]);

        $rows = (new OnePlanningController)->index(Request::create('/budget/one-planning'))->getData()['rows'];

        $this->assertCount(1, $rows);
        $this->assertSame('7021', $rows[0]['id']);
        $this->assertSame(150.0, $rows[0]['budget_val']);
        $this->assertSame(90.0, $rows[0]['budget_balance']);
        $this->assertSame(2, $rows[0]['item_count']);
        $this->assertSame(['ค่าตอบแทน', 'ค่าใช้สอย'], array_column($rows[0]['items'], 'budget_item_name'));
        $this->assertSame('', $rows[0]['reason_request']);
    }

    public function test_reports_authentication_failure_without_exposing_credentials(): void
    {
        Http::fake(['*' => Http::response(['status' => ['error' => 3, 'message' => 'test-secret']])]);
        $data = (new OnePlanningController)->index(Request::create('/budget/one-planning'))->getData();
        $this->assertSame([], $data['rows']);
        $this->assertStringContainsString('error authentication failed', $data['error']);
        $this->assertStringNotContainsString('test-secret', $data['error']);
    }

    public function test_handles_top_level_source_error_status(): void
    {
        Http::fake(['*' => Http::response(['error' => 3, 'message' => 'test-secret', 'data' => []])]);
        $data = (new OnePlanningController)->index(Request::create('/budget/one-planning'))->getData();

        $this->assertSame([], $data['rows']);
        $this->assertStringContainsString('error authentication failed', $data['error']);
        $this->assertStringNotContainsString('test-secret', $data['error']);
    }

    public function test_handles_connection_failure(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $data = (new OnePlanningController)->index(Request::create('/budget/one-planning'))->getData();
        $this->assertNotNull($data['error']);
    }

    public function test_rejects_unknown_response_and_accepts_empty_list(): void
    {
        Http::fake(['*' => Http::sequence()->push(['unexpected' => true])->push(['data' => []])]);
        $controller = new OnePlanningController;
        $this->assertNotNull($controller->index(Request::create('/'))->getData()['error']);
        $this->assertNull($controller->index(Request::create('/'))->getData()['error']);
    }
}
