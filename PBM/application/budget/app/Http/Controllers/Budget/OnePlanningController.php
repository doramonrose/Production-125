<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OnePlanningController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['fiscal_year_th' => 'nullable|integer|between:2500,2700']);
        $currentYear = $this->currentFiscalYear();
        $year = (int) ($validated['fiscal_year_th'] ?? $currentYear);
        $yearOptions = range($currentYear + 1, max(2500, $currentYear - 8));
        if (! in_array($year, $yearOptions, true)) {
            $yearOptions[] = $year;
            rsort($yearOptions);
        }
        $rows = [];
        $error = null;
        $settings = config('services.oneplanning');

        if (empty($settings['auth_code']) || empty($settings['pass_key'])) {
            $error = 'ยังไม่ได้ตั้งค่าข้อมูลเชื่อมต่อ One Planning กรุณาติดต่อผู้ดูแลระบบ';
        } else {
            try {
                $response = Http::asForm()->acceptJson()->connectTimeout(10)->timeout(30)
                    ->withOptions(['allow_redirects' => false])
                    ->post($settings['url'], [
                        'auth_code' => $settings['auth_code'],
                        'pass_key' => $settings['pass_key'],
                        'ref_code' => $settings['ref_code'],
                        'fiscal_year_th' => $year,
                    ]);
                $payload = $response->json();
                if (! $response->successful() || ! is_array($payload)) {
                    $error = 'ไม่สามารถอ่านข้อมูลจาก One Planning ได้ กรุณาลองใหม่อีกครั้ง';
                } elseif (data_get($payload, 'error', data_get($payload, 'status.error', 0)) != 0) {
                    $error = (int) data_get($payload, 'error', data_get($payload, 'status.error')) === 3
                        ? 'One Planning ปฏิเสธการยืนยันตัวตน กรุณาให้ผู้ดูแลระบบตรวจสอบข้อมูลเชื่อมต่อ (error authentication failed)'
                        : 'One Planning แจ้งข้อผิดพลาด กรุณาให้ผู้ดูแลระบบตรวจสอบการเชื่อมต่อ';
                } else {
                    $data = array_is_list($payload) ? $payload : ($payload['data'] ?? $payload['budget'] ?? $payload['budgets'] ?? null);
                    if (! is_array($data) || ! array_is_list($data) || count(array_filter($data, 'is_array')) !== count($data)) {
                        $error = 'รูปแบบข้อมูลจาก One Planning ยังไม่รองรับ กรุณาติดต่อผู้ดูแลระบบ';
                    } else {
                        $rows = $this->presentRows($data);
                    }
                }
            } catch (ConnectionException $exception) {
                $error = 'เชื่อมต่อ One Planning ไม่สำเร็จหรือใช้เวลานานเกินไป กรุณาลองใหม่อีกครั้ง';
            }
        }

        $typeOptions = $this->uniqueTexts($rows, 'budget_type_name');
        $planOptions = $this->uniqueTexts($rows, 'budget_3d_plan_name');
        $fundOptions = $this->uniqueTexts($rows, 'budget_fund_name');
        $categoryOptions = $this->uniqueTexts($rows, 'budget_expend_group_name');
        $roundOptions = $this->uniqueTexts($rows, 'budget_round_group_name');

        return view('budget.one-planning', compact(
            'year',
            'currentYear',
            'yearOptions',
            'rows',
            'typeOptions',
            'planOptions',
            'fundOptions',
            'categoryOptions',
            'roundOptions',
            'error'
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $data
     * @return list<array<string, mixed>>
     */
    private function presentRows(array $data): array
    {
        $groups = [];
        foreach ($data as $index => $row) {
            $code = $this->cellText($row, 'code');
            $groups[$code !== '' ? $code : '__row_'.$index][] = $row;
        }

        $presented = [];
        foreach ($groups as $key => $items) {
            $presented[] = $this->presentGroup($items, (string) $key);
        }

        return $presented;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function presentGroup(array $items, string $id): array
    {
        $first = $items[0];
        $code = $this->cellText($first, 'code');
        $sourcePath = implode('/', array_values(array_filter([
            $this->cellText($first, 'budget_fund_name'),
            $this->cellText($first, 'budget_3d_plan_name'),
            $this->cellText($first, 'budget_3d_job_name'),
            $this->cellText($first, 'budget_3d_activity_name'),
            $this->cellText($first, 'budget_expend_group_name'),
        ], fn (string $value) => $value !== '')));
        $amount = array_sum(array_map(fn (array $row): float => (float) ($row['budget_val'] ?? 0), $items));
        $balance = array_sum(array_map(fn (array $row): float => (float) ($row['budget_balance'] ?? 0), $items));
        $lines = array_map(function (array $row): array {
            $lineAmount = (float) ($row['budget_val'] ?? 0);
            $lineBalance = (float) ($row['budget_balance'] ?? 0);

            return [
                'budget_item_name' => $this->cellText($row, 'budget_item_name'),
                'budget_round_group_name' => $this->cellText($row, 'budget_round_group_name'),
                'item_amount' => $this->cellText($row, 'item_amount'),
                'item_unit' => $this->cellText($row, 'item_unit'),
                'item_ppu' => $this->cellText($row, 'item_ppu'),
                'reason_request' => $this->cellText($row, 'reason_request'),
                'budget_val' => $lineAmount,
                'budget_balance' => $lineBalance,
                'delta' => $lineBalance - $lineAmount,
            ];
        }, $items);
        $reasons = array_values(array_unique(array_filter(array_column($lines, 'reason_request'))));
        $roundGroups = array_values(array_unique(array_filter(array_column($lines, 'budget_round_group_name'))));

        return [
            'id' => $id,
            'code' => $code,
            'name' => $this->cellText($first, 'name'),
            'source_path' => $sourcePath !== '' ? $sourcePath : $this->cellText($first, 'name'),
            'pj_project_name' => $this->cellText($first, 'pj_project_name'),
            'budget_fund_name' => $this->cellText($first, 'budget_fund_name'),
            'budget_type_name' => $this->cellText($first, 'budget_type_name'),
            'budget_3d_plan_name' => $this->cellText($first, 'budget_3d_plan_name'),
            'budget_expend_group_name' => $this->cellText($first, 'budget_expend_group_name'),
            'budget_department_name' => $this->cellText($first, 'budget_department_name'),
            'budget_round_group_name' => implode(', ', $roundGroups),
            'reason_request' => count($reasons) === 1 ? $reasons[0] : '',
            'budget_val' => $amount,
            'budget_balance' => $balance,
            'delta' => $balance - $amount,
            'item_count' => count($lines),
            'items' => $lines,
            'facts' => $this->groupFacts($first),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<array{label: string, value: string}>
     */
    private function groupFacts(array $row): array
    {
        $labels = [
            'name' => 'ชื่อของงบประมาณ',
            'fiscal_year_th' => 'ปีงบประมาณที่ได้รับงบประมาณ (พ.ศ.)',
            'budget_type_name' => 'ชื่อของประเภทงบประมาณ',
            'budget_fund_name' => 'ชื่อของกองทุน',
            'budget_department_name' => 'ชื่อของส่วนงานตามโครงสร้างงบประมาณ',
            'cmu_department_name' => 'ชื่อของส่วนงานตามโครงสร้างมหาวิทยาลัย',
            'pj_project_name' => 'ชื่อของโครงการ/งาน',
            'budget_3d_plan_name' => 'ชื่อของแผนงานมหาวิทยาลัย',
            'budget_3d_job_name' => 'ชื่อของงานมหาวิทยาลัย',
            'budget_3d_activity_name' => 'ชื่อของกิจกรรมมหาวิทยาลัย',
            'budget_3d_account_name' => 'ชื่อของแผนงานที่ใช้ประกอบงบประมาณ',
            'budget_expend_group_name' => 'ชื่อของหมวดรายจ่าย',
            'budget_bb_plan_name' => 'ชื่อของแผนงานสำนักงานประมาณ',
            'budget_bb_output_name' => 'ชื่อของผลผลิตสำนักงานประมาณ',
            'budget_bb_activity_name' => 'ชื่อของกิจกรรมสำนักงานประมาณ',
            'cmu_strategy_group_name' => 'ชื่อของยุทธศาสตร์หลักมหาวิทยาลัย',
            'budget_cmu_strategy_name' => 'ชื่อของยุทธศาสตร์ย่อยมหาวิทยาลัย',
            'budget_round_group_name' => 'ชื่อของรอบงบประมาณ',
            'budget_round_name' => 'ชื่อของรอบงบประมาณในแต่ละปี',
        ];
        $facts = [];
        foreach ($labels as $key => $label) {
            $value = $this->cellText($row, $key);
            if ($value !== '') {
                $facts[] = ['label' => $label, 'value' => $value];
            }
        }

        return $facts;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<string>
     */
    private function uniqueTexts(array $rows, string $key): array
    {
        $values = array_values(array_unique(array_filter(array_map(
            fn (array $row): string => trim((string) ($row[$key] ?? '')),
            $rows
        ), fn (string $value) => $value !== '')));
        sort($values, SORT_STRING);

        return $values;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function cellText(array $row, string $key): string
    {
        $value = $row[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * Thai fiscal year starts 1 October and is named by the Buddhist year it ends in.
     */
    private function currentFiscalYear(): int
    {
        $today = now();
        $fiscalYear = (int) $today->format('Y') + 543;
        if ((int) $today->format('n') >= 10) {
            $fiscalYear++;
        }

        return $fiscalYear;
    }
}
