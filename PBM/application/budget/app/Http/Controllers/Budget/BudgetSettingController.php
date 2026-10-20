<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BudgetSettingController extends Controller
{
    public function yearIndex() { return $this->renderIndex('ตั้งค่าปีงบประมาณ', 'year'); }
    public function planIndex() { return $this->renderIndex('ตั้งค่าแผน', 'plan'); }
    public function eventIndex() { return $this->renderIndex('ตั้งค่างาน/กิจกรรม', 'event'); }
    public function activityIndex()
    {
        return redirect()->route('budget.settings.event.index');
    }
    public function fundIndex() { return $this->renderIndex('ตั้งค่ากองทุน', 'fund'); }
    public function costIndex() { return $this->renderIndex('ตั้งค่าต้นทุน', 'cost'); }
    public function sourceIndex() { return $this->renderIndex('ตั้งค่าแหล่งงบ', 'source'); }
    public function typeIndex() { return $this->renderIndex('ตั้งค่าประเภทงบ', 'type'); }
    public function categoryBudgetIndex() { return $this->renderIndex('ตั้งค่าหมวดงบ', 'category-budget'); }
    public function categoryPayIndex() { return $this->renderIndex('ตั้งค่าหมวดรายจ่าย', 'category-pay'); }
    public function payListIndex() { return $this->renderIndex('ตั้งค่ารายการจ่าย', 'pay-list'); }
    public function expensesIndex() { return $this->renderIndex('ตั้งค่ารายจ่ายย่อย', 'expenses'); }

    private function renderIndex(string $title, string $module)
    {
        return view('budget.settings.index', [
            'title' => $title,
            'module' => $module,
            'rows' => $this->fetchModuleData($module),
            'optionsData' => $this->fetchOptionsData(),
        ]);
    }

    public function apiList(string $module): JsonResponse
    {
        $data = $this->fetchModuleData($module);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function apiOptions(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->fetchOptionsData(),
        ]);
    }

    private function fetchModuleData(string $module)
    {
        return match ($module) {
            'year' => $this->mapWithCanDelete(
                DB::table('budget_set_year')->orderByDesc('budget_year_code')->get(),
                'year'
            ),
            'plan' => $this->mapWithCanDelete(
                DB::table('budget_set_plan')->orderByDesc('budget_plan_code')->get(),
                'plan'
            ),
            'event' => $this->fetchEventModuleRows(),
            'activity' => $this->mapWithCanDelete(
                DB::table('budget_set_activity as a')
                    ->leftJoin('budget_set_event as e', 'a.budget_event_code', '=', 'e.budget_event_code')
                    ->leftJoin('budget_set_plan as p', 'e.budget_plan_code', '=', 'p.budget_plan_code')
                    ->select('a.*', 'e.budget_event_name', 'p.budget_plan_name')
                    ->orderBy('p.budget_plan_name')
                    ->orderBy('e.budget_event_name')
                    ->orderBy('a.budget_activity_name')
                    ->get(),
                'activity'
            ),
            'fund' => $this->mapWithCanDelete(
                DB::table('budget_set_fund')->orderByDesc('budget_fund_code')->get(),
                'fund'
            ),
            'cost' => $this->mapWithCanDelete(
                DB::table('budget_set_cost')->orderByDesc('budget_cost_code')->get(),
                'cost'
            ),
            'source' => $this->mapWithCanDelete(
                DB::table('budget_set_source')->orderByDesc('budget_source_code')->get(),
                'source'
            ),
            'type' => $this->mapWithCanDelete(
                DB::table('budget_set_type')->orderByDesc('budget_type_code')->get(),
                'type'
            ),
            'category-budget' => $this->mapWithCanDelete(
                DB::table('budget_set_category_budget')->orderByDesc('budget_category_budget_code')->get(),
                'category-budget'
            ),
            'category-pay' => $this->mapWithCanDelete(
                DB::table('budget_set_category_pay')->orderByDesc('budget_category_pay_code')->get(),
                'category-pay'
            ),
            'pay-list' => $this->mapWithCanDelete(
                DB::table('budget_set_pay_list as pl')
                    ->leftJoin('budget_set_category_pay as cp', 'pl.budget_category_pay_code', '=', 'cp.budget_category_pay_code')
                    ->select('pl.*', 'cp.budget_category_pay_name')
                    ->orderBy('cp.budget_category_pay_name')
                    ->orderBy('pl.budget_pay_name')
                    ->get(),
                'pay-list'
            ),
            'expenses' => $this->mapWithCanDelete(
                $this->attachWithdrawExpenseBindings(
                    DB::table('budget_set_expenses as se')
                        ->leftJoin('budget_set_pay_list as pl', 'se.budget_pay_code', '=', 'pl.budget_pay_code')
                        ->select('se.*', 'pl.budget_pay_name')
                        ->orderBy('pl.budget_pay_name')
                        ->orderBy('se.budget_set_expenses_name')
                        ->get()
                ),
                'expenses'
            ),
            default => collect(),
        };
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function mapWithCanDelete(Collection $rows, string $module): Collection
    {
        return $rows->map(function ($row) use ($module) {
            $r = (array) json_decode(json_encode($row), true);
            $r['can_delete'] = ! $this->settingRowIsReferenced($module, $row);

            return (object) $r;
        });
    }

    private function settingRowIsReferenced(string $module, object $row): bool
    {
        return match ($module) {
            'year' => DB::table('budget_manage')->where(function ($q) use ($row) {
                $q->where('budget_manage_year', (string) $row->budget_year_code)
                    ->orWhere('budget_manage_year', (string) $row->budget_year_name);
            })->exists(),
            'plan' => DB::table('budget_manage')->where('budget_plan_code', $row->budget_plan_code)->exists()
                || DB::table('budget_set_event')->where('budget_plan_code', $row->budget_plan_code)->exists(),
            'fund' => DB::table('budget_manage')->where('budget_fund_code', $row->budget_fund_code)->exists(),
            'cost' => DB::table('budget_manage')->where('budget_cost_code', $row->budget_cost_code)->exists(),
            'source' => DB::table('budget_manage')->where('budget_source_code', $row->budget_source_code)->exists()
                || DB::table('budget_manage_list')->where('budget_source_code', $row->budget_source_code)->exists(),
            'type' => DB::table('budget_manage')->where('budget_type_code', $row->budget_type_code)->exists()
                || DB::table('budget_manage_list')->where('budget_type_code', $row->budget_type_code)->exists(),
            'category-budget' => DB::table('budget_manage')->where('budget_category_budget_code', $row->budget_category_budget_code)->exists(),
            'category-pay' => DB::table('budget_manage')->where('budget_category_pay_code', $row->budget_category_pay_code)->exists()
                || DB::table('budget_manage_list')->where('budget_category_pay_code', $row->budget_category_pay_code)->exists()
                || DB::table('budget_set_pay_list')->where('budget_category_pay_code', $row->budget_category_pay_code)->exists(),
            'pay-list' => DB::table('budget_manage_expenses')->where('budget_pay_code', $row->budget_pay_code)->exists()
                || DB::table('budget_set_expenses')->where('budget_pay_code', $row->budget_pay_code)->exists(),
            'expenses' => DB::table('budget_manage_expenses_sub')->where('budget_expenses_sub_code', $row->budget_set_expenses_code)->exists(),
            'activity' => DB::table('budget_manage')->where('budget_activity_code', $row->budget_activity_code)->exists(),
            default => false,
        };
    }

    private function eventCannotDelete(object $event): bool
    {
        if (DB::table('budget_manage')->where('budget_event_code', $event->budget_event_code)->exists()) {
            return true;
        }

        return DB::table('budget_set_activity')->where('budget_event_code', $event->budget_event_code)->exists();
    }

    /**
     * งาน + กิจกรรมในตารางเดียว (เทียบกับ budget_event.php เดิม)
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchEventModuleRows(): array
    {
        $activitiesByEvent = DB::table('budget_set_activity')
            ->orderBy('budget_activity_name')
            ->get()
            ->groupBy('budget_event_code');

        $eventsByPlan = DB::table('budget_set_event as e')
            ->leftJoin('budget_set_plan as p', 'e.budget_plan_code', '=', 'p.budget_plan_code')
            ->select('e.*', 'p.budget_plan_name')
            ->orderBy('p.budget_plan_name')
            ->orderBy('e.budget_event_name')
            ->get()
            ->groupBy('budget_plan_code');

        $plans = DB::table('budget_set_plan')->orderBy('budget_plan_name')->get();

        $manageEventCodes = DB::table('budget_manage')
            ->whereNotNull('budget_event_code')
            ->where('budget_event_code', '!=', 0)
            ->pluck('budget_event_code')
            ->unique()
            ->flip()
            ->all();

        $manageActivityCodes = DB::table('budget_manage')
            ->whereNotNull('budget_activity_code')
            ->where('budget_activity_code', '!=', 0)
            ->pluck('budget_activity_code')
            ->unique()
            ->flip()
            ->all();

        $rows = [];

        foreach ($plans as $plan) {
            $pc = $plan->budget_plan_code;
            $planEvents = $eventsByPlan->get($pc)
                ?? $eventsByPlan->get((string) $pc)
                ?? $eventsByPlan->get((int) $pc)
                ?? collect();
            if ($planEvents->isEmpty()) {
                continue;
            }

            $rows[] = [
                '_row_type' => 'plan_header',
                'budget_plan_code' => (int) $plan->budget_plan_code,
                'budget_plan_name' => $plan->budget_plan_name,
            ];

            foreach ($planEvents as $event) {
                $eventCode = (int) $event->budget_event_code;
                $evActivities = $activitiesByEvent->get($eventCode)
                    ?? $activitiesByEvent->get((string) $eventCode)
                    ?? collect();
                $hasManageEvent = isset($manageEventCodes[$eventCode]);
                $canDeleteEvent = ! $hasManageEvent && $evActivities->isEmpty();

                $eventRow = (array) json_decode(json_encode($event), true);
                $eventRow['_row_type'] = 'event';
                $eventRow['budget_plan_name'] = $event->budget_plan_name;
                $eventRow['can_delete'] = $canDeleteEvent;
                $rows[] = $eventRow;

                foreach ($evActivities as $act) {
                    $actCode = (int) $act->budget_activity_code;
                    $hasManageAct = isset($manageActivityCodes[$actCode]);
                    $actRow = (array) json_decode(json_encode($act), true);
                    $actRow['_row_type'] = 'activity';
                    $actRow['budget_event_name'] = $event->budget_event_name;
                    $actRow['budget_plan_code'] = (int) $plan->budget_plan_code;
                    $actRow['budget_plan_name'] = $plan->budget_plan_name;
                    $actRow['can_delete'] = ! $hasManageAct;
                    $rows[] = $actRow;
                }
            }
        }

        return $rows;
    }

    private function fetchOptionsData(): array
    {
        return [
            'plans' => DB::table('budget_set_plan')->where('budget_plan_status', 1)->orderBy('budget_plan_name')->get(),
            'events' => DB::table('budget_set_event')->where('budget_event_status', 1)->orderBy('budget_event_name')->get(),
            'categoryPays' => DB::table('budget_set_category_pay')->where('budget_category_pay_status', 1)->orderBy('budget_category_pay_name')->get(),
            'payLists' => DB::table('budget_set_pay_list')->where('budget_pay_status', 1)->orderBy('budget_pay_name')->get(),
        ];
    }

    public function apiSave(Request $request, string $module): JsonResponse
    {
        return match ($module) {
            'year' => $this->saveSimple($request, 'budget_set_year', 'budget_year', 'budget_year_name', 'budget_year_detail', 'budget_year_status', 'budget_year_code'),
            'plan' => $this->saveSimple($request, 'budget_set_plan', 'budget_plan', 'budget_plan_name', 'budget_plan_detail', 'budget_plan_status', 'budget_plan_code'),
            'fund' => $this->saveSimple($request, 'budget_set_fund', 'budget_fund', 'budget_fund_name', 'budget_fund_detail', 'budget_fund_status', 'budget_fund_code'),
            'cost' => $this->saveSimple($request, 'budget_set_cost', 'budget_cost', 'budget_cost_name', 'budget_cost_detail', 'budget_cost_status', 'budget_cost_code'),
            'source' => $this->saveSimple($request, 'budget_set_source', 'budget_source', 'budget_source_name', 'budget_source_detail', 'budget_source_status', 'budget_source_code'),
            'type' => $this->saveBudgetType($request),
            'category-budget' => $this->saveSimple($request, 'budget_set_category_budget', 'budget_category_budget', 'budget_category_budget_name', 'budget_category_budget_detail', 'budget_category_budget_status', 'budget_category_budget_code'),
            'category-pay' => $this->saveSimple($request, 'budget_set_category_pay', 'budget_category_pay', 'budget_category_pay_name', 'budget_category_pay_detail', 'budget_category_pay_status', 'budget_category_pay_code'),
            'pay-list' => $this->savePayList($request),
            'expenses' => $this->saveSetExpenses($request),
            'event' => $this->saveEvent($request),
            'activity' => $this->saveActivity($request),
            default => response()->json(['status' => 'error', 'message' => 'ไม่พบโมดูลที่รองรับ'], 422),
        };
    }

    public function apiDelete(Request $request, string $module): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        $refRow = null;
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรหัสรายการ'], 422);
        }

        if ($module === 'event') {
            $event = DB::table('budget_set_event')->where('budget_event_id', $id)->first();
            if (! $event) {
                return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูลงาน'], 422);
            }
            if ($this->eventCannotDelete($event)) {
                return response()->json(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากมีการใช้งานในงบประมาณหรือมีกิจกรรมภายใต้งานนี้'], 422);
            }
        } elseif ($module === 'activity') {
            $act = DB::table('budget_set_activity')->where('budget_activity_id', $id)->first();
            if (! $act) {
                return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูลกิจกรรม'], 422);
            }
            if ($this->settingRowIsReferenced('activity', $act)) {
                return response()->json(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากมีการใช้งานในงบประมาณ'], 422);
            }
        } else {
            $refRow = match ($module) {
                'year' => DB::table('budget_set_year')->where('budget_year_id', $id)->first(),
                'plan' => DB::table('budget_set_plan')->where('budget_plan_id', $id)->first(),
                'fund' => DB::table('budget_set_fund')->where('budget_fund_id', $id)->first(),
                'cost' => DB::table('budget_set_cost')->where('budget_cost_id', $id)->first(),
                'source' => DB::table('budget_set_source')->where('budget_source_id', $id)->first(),
                'type' => DB::table('budget_set_type')->where('budget_type_id', $id)->first(),
                'category-budget' => DB::table('budget_set_category_budget')->where('budget_category_budget_id', $id)->first(),
                'category-pay' => DB::table('budget_set_category_pay')->where('budget_category_pay_id', $id)->first(),
                'pay-list' => DB::table('budget_set_pay_list')->where('budget_pay_id', $id)->first(),
                'expenses' => DB::table('budget_set_expenses')->where('budget_set_expenses_id', $id)->first(),
                default => null,
            };

            if ($refRow !== null && $this->settingRowIsReferenced($module, $refRow)) {
                return response()->json(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากมีการใช้งานในระบบ'], 422);
            }

            if ($refRow === null && in_array($module, ['year', 'plan', 'fund', 'cost', 'source', 'type', 'category-budget', 'category-pay', 'pay-list', 'expenses'], true)) {
                return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูล'], 422);
            }
        }

        $table = match ($module) {
            'year' => ['budget_set_year', 'budget_year_id'],
            'plan' => ['budget_set_plan', 'budget_plan_id'],
            'event' => ['budget_set_event', 'budget_event_id'],
            'activity' => ['budget_set_activity', 'budget_activity_id'],
            'fund' => ['budget_set_fund', 'budget_fund_id'],
            'cost' => ['budget_set_cost', 'budget_cost_id'],
            'source' => ['budget_set_source', 'budget_source_id'],
            'type' => ['budget_set_type', 'budget_type_id'],
            'category-budget' => ['budget_set_category_budget', 'budget_category_budget_id'],
            'category-pay' => ['budget_set_category_pay', 'budget_category_pay_id'],
            'pay-list' => ['budget_set_pay_list', 'budget_pay_id'],
            'expenses' => ['budget_set_expenses', 'budget_set_expenses_id'],
            default => null,
        };

        if (! $table) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบโมดูลที่รองรับ'], 422);
        }

        DB::table($table[0])->where($table[1], $id)->delete();
        if ($module === 'expenses' && isset($refRow->budget_set_expenses_code)) {
            $this->syncExpenseWithdrawAccounts((int) $refRow->budget_set_expenses_code, collect());
        }
        return response()->json(['status' => 'success', 'message' => 'ลบข้อมูลสำเร็จ']);
    }

    public function apiToggle(Request $request, string $module): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        $status = (int) $request->input('status', 0);

        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'], 422);
        }

        $map = match ($module) {
            'year' => ['budget_set_year', 'budget_year_id', 'budget_year_status', 'budget_year_date_modify'],
            'plan' => ['budget_set_plan', 'budget_plan_id', 'budget_plan_status', 'budget_plan_date_modify'],
            'event' => ['budget_set_event', 'budget_event_id', 'budget_event_status', 'budget_event_date_modify'],
            'activity' => ['budget_set_activity', 'budget_activity_id', 'budget_activity_status', 'budget_activity_date_modify'],
            'fund' => ['budget_set_fund', 'budget_fund_id', 'budget_fund_status', 'budget_fund_date_modify'],
            'cost' => ['budget_set_cost', 'budget_cost_id', 'budget_cost_status', 'budget_cost_date_modify'],
            'source' => ['budget_set_source', 'budget_source_id', 'budget_source_status', 'budget_source_date_modify'],
            'type' => ['budget_set_type', 'budget_type_id', 'budget_type_status', 'budget_type_date_modify'],
            'category-budget' => ['budget_set_category_budget', 'budget_category_budget_id', 'budget_category_budget_status', 'budget_category_budget_date_modify'],
            'category-pay' => ['budget_set_category_pay', 'budget_category_pay_id', 'budget_category_pay_status', 'budget_category_pay_date_modify'],
            'pay-list' => ['budget_set_pay_list', 'budget_pay_id', 'budget_pay_status', 'budget_pay_date_modify'],
            'expenses' => ['budget_set_expenses', 'budget_set_expenses_id', 'budget_set_expenses_status', 'budget_set_expenses_date_modify'],
            default => null,
        };

        if (! $map) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบโมดูลที่รองรับ'], 422);
        }

        DB::table($map[0])->where($map[1], $id)->update($this->applyStaffModifyAudit($map[0], [
            $map[2] => $status,
            $map[3] => date('Y-m-d H:i:s'),
        ]));

        return response()->json(['status' => 'success']);
    }

    private function saveBudgetType(Request $request): JsonResponse
    {
        $direction = (string) $request->input('direction', '');
        if (! in_array($direction, ['+', '-'], true)) {
            return response()->json(['status' => 'error', 'message' => 'กรุณาเลือกการเพิ่มหรือลดงบ'], 422);
        }

        return $this->saveSimple(
            $request,
            'budget_set_type',
            'budget_type',
            'budget_type_name',
            'budget_type_detail',
            'budget_type_status',
            'budget_type_code',
            ['budget_type_direction' => $direction]
        );
    }

    private function saveSimple(Request $request, string $table, string $prefix, string $nameCol, string $detailCol, string $statusCol, string $codeCol, array $additionalData = []): JsonResponse
    {
        $idCol = $prefix.'_id';
        $name = trim((string) $request->input('name', ''));
        $detail = trim((string) $request->input('detail', ''));
        $id = (int) $request->input('id', 0);

        if ($name === '') {
            return response()->json(['status' => 'error', 'message' => 'กรุณากรอกชื่อข้อมูล'], 422);
        }

        $dup = DB::table($table)->where($nameCol, $name);
        if ($id > 0) {
            $dup->where($idCol, '!=', $id);
        }
        if ($dup->exists()) {
            return response()->json(['status' => 'error', 'message' => 'ชื่อข้อมูลนี้มีอยู่แล้ว'], 422);
        }

        if ($id > 0) {
            DB::table($table)->where($idCol, $id)->update($this->applyStaffModifyAudit($table, array_merge([
                $nameCol => $name,
                $detailCol => $detail,
                $prefix.'_date_modify' => date('Y-m-d H:i:s'),
            ], $additionalData)));
        } else {
            $maxCode = (int) (DB::table($table)->max($codeCol) ?? 0);
            DB::table($table)->insert($this->applyStaffCreateAudit($table, array_merge([
                $codeCol => $maxCode + 1,
                $nameCol => $name,
                $detailCol => $detail,
                $statusCol => 1,
                $prefix.'_date_create' => date('Y-m-d H:i:s'),
            ], $additionalData)));
        }

        return response()->json(['status' => 'success']);
    }

    private function savePayList(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        $categoryPayCode = (int) $request->input('category_pay_code', 0);
        $name = trim((string) $request->input('name', ''));
        $detail = trim((string) $request->input('detail', ''));

        if ($categoryPayCode <= 0 || $name === '') {
            return response()->json(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบ'], 422);
        }

        if ($id > 0) {
            DB::table('budget_set_pay_list')->where('budget_pay_id', $id)->update($this->applyStaffModifyAudit('budget_set_pay_list', [
                'budget_category_pay_code' => $categoryPayCode,
                'budget_pay_name' => $name,
                'budget_pay_detail' => $detail,
                'budget_pay_date_modify' => date('Y-m-d H:i:s'),
            ]));
        } else {
            $maxCode = (int) (DB::table('budget_set_pay_list')->max('budget_pay_code') ?? 0);
            DB::table('budget_set_pay_list')->insert($this->applyStaffCreateAudit('budget_set_pay_list', [
                'budget_category_pay_code' => $categoryPayCode,
                'budget_pay_code' => $maxCode + 1,
                'budget_pay_name' => $name,
                'budget_pay_detail' => $detail,
                'budget_pay_status' => 1,
                'budget_pay_date_create' => date('Y-m-d H:i:s'),
            ]));
        }

        return response()->json(['status' => 'success']);
    }

    private function saveSetExpenses(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        $payCode = (int) $request->input('pay_code', 0);
        $name = trim((string) $request->input('name', ''));
        $detail = trim((string) $request->input('detail', ''));
        $withdrawExpenseCodes = collect($request->input('withdraw_expenses_codes', []))
            ->map(fn ($x) => (int) $x)
            ->filter(fn ($x) => $x > 0)
            ->unique()
            ->values();
        $withdrawExpenseItems = collect($request->input('withdraw_expenses_items', []))
            ->map(function ($item) {
                $code = (int) data_get($item, 'code', 0);
                $name = trim((string) data_get($item, 'name', ''));
                if ($code <= 0) {
                    return null;
                }

                return [
                    'code' => $code,
                    'name' => $name,
                ];
            })
            ->filter()
            ->keyBy('code');

        if ($payCode <= 0 || $name === '') {
            return response()->json(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบ'], 422);
        }

        DB::beginTransaction();
        try {
            if ($id > 0) {
                $row = DB::table('budget_set_expenses')
                    ->where('budget_set_expenses_id', $id)
                    ->first();
                if (! $row) {
                    DB::rollBack();

                    return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูลรายจ่ายย่อยที่ต้องการแก้ไข'], 422);
                }
                $expenseCode = (int) ($row->budget_set_expenses_code ?? 0);
                if ($expenseCode <= 0) {
                    DB::rollBack();

                    return response()->json(['status' => 'error', 'message' => 'ข้อมูลรายจ่ายย่อยไม่ถูกต้อง'], 422);
                }

                $conflictingAccounts = $this->withdrawAccountsBoundToOtherExpenses($expenseCode, $withdrawExpenseCodes);
                if ($conflictingAccounts->isNotEmpty()) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 'error',
                        'message' => 'ผังบัญชี '.implode(', ', $conflictingAccounts->all()).' ถูกใช้งานกับรายจ่ายย่อยอื่นแล้ว',
                    ], 422);
                }

                DB::table('budget_set_expenses')->where('budget_set_expenses_id', $id)->update($this->applyStaffModifyAudit('budget_set_expenses', [
                    'budget_pay_code' => $payCode,
                    'budget_set_expenses_name' => $name,
                    'budget_set_expenses_detail' => $detail,
                    'budget_set_expenses_date_modify' => date('Y-m-d H:i:s'),
                ]));

                $this->syncExpenseWithdrawAccounts($expenseCode, $withdrawExpenseCodes, $withdrawExpenseItems);
            } else {
                $maxCode = (int) (DB::table('budget_set_expenses')->max('budget_set_expenses_code') ?? 0);
                $newExpenseCode = $maxCode + 1;

                $conflictingAccounts = $this->withdrawAccountsBoundToOtherExpenses($newExpenseCode, $withdrawExpenseCodes);
                if ($conflictingAccounts->isNotEmpty()) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 'error',
                        'message' => 'ผังบัญชี '.implode(', ', $conflictingAccounts->all()).' ถูกใช้งานกับรายจ่ายย่อยอื่นแล้ว',
                    ], 422);
                }

                DB::table('budget_set_expenses')->insert($this->applyStaffCreateAudit('budget_set_expenses', [
                    'budget_pay_code' => $payCode,
                    'budget_set_expenses_code' => $newExpenseCode,
                    'budget_set_expenses_name' => $name,
                    'budget_set_expenses_detail' => $detail,
                    'budget_set_expenses_status' => 1,
                    'budget_set_expenses_date_create' => date('Y-m-d H:i:s'),
                ]));

                $this->syncExpenseWithdrawAccounts($newExpenseCode, $withdrawExpenseCodes, $withdrawExpenseItems);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['status' => 'error', 'message' => 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง'], 500);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * @param  Collection<int, int>  $withdrawExpenseCodes
     * @return Collection<int, string>
     */
    private function withdrawAccountsBoundToOtherExpenses(int $budgetSetExpensesCode, Collection $withdrawExpenseCodes): Collection
    {
        if (! Schema::hasTable('budget_set_expenses_withdraw_accounts') || $withdrawExpenseCodes->isEmpty()) {
            return collect();
        }

        return DB::table('budget_set_expenses_withdraw_accounts')
            ->whereIn('withdraw_set_expenses_code', $withdrawExpenseCodes->all())
            ->where('budget_set_expenses_code', '!=', $budgetSetExpensesCode)
            ->orderBy('withdraw_set_expenses_name')
            ->get(['withdraw_set_expenses_code', 'withdraw_set_expenses_name'])
            ->map(function ($binding) {
                $name = trim((string) ($binding->withdraw_set_expenses_name ?? ''));

                return $name !== '' ? $name : 'รหัสผังบัญชี '.((int) $binding->withdraw_set_expenses_code);
            })
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function attachWithdrawExpenseBindings(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $expenseCodes = $rows->pluck('budget_set_expenses_code')
            ->map(fn ($x) => (int) $x)
            ->filter(fn ($x) => $x > 0)
            ->unique()
            ->values();

        $bindByExpenseCode = collect();
        if (Schema::hasTable('budget_set_expenses_withdraw_accounts') && $expenseCodes->isNotEmpty()) {
            $bindRows = DB::table('budget_set_expenses_withdraw_accounts')
                ->whereIn('budget_set_expenses_code', $expenseCodes->all())
                ->orderBy('withdraw_set_expenses_name')
                ->get();
            $bindByExpenseCode = $bindRows->groupBy('budget_set_expenses_code');
        }

        return $rows->map(function ($row) use ($bindByExpenseCode) {
            $r = (array) json_decode(json_encode($row), true);
            $expenseCode = (int) ($r['budget_set_expenses_code'] ?? 0);
            $binds = collect($bindByExpenseCode->get($expenseCode, []));

            $codes = $binds
                ->map(fn ($x) => (int) ($x->withdraw_set_expenses_code ?? 0))
                ->map(fn ($x) => (int) $x)
                ->filter(fn ($x) => $x > 0)
                ->values()
                ->all();

            $r['withdraw_expenses_codes'] = $codes;
            $r['withdraw_expenses_names'] = $binds
                ->map(function ($bind) {
                    $name = trim((string) ($bind->withdraw_set_expenses_name ?? ''));
                    if ($name !== '') {
                        return $name;
                    }

                    $code = (int) ($bind->withdraw_set_expenses_code ?? 0);

                    return $code > 0 ? 'รหัสผังบัญชี '.$code : '';
                })
                ->filter(fn ($x) => $x !== '')
                ->values()
                ->all();

            return (object) $r;
        });
    }

    /**
     * @param  Collection<int, int>  $withdrawExpenseCodes
     * @param  Collection<int, array{code:int,name:string}>|null  $withdrawExpenseItems
     */
    private function syncExpenseWithdrawAccounts(int $budgetSetExpensesCode, Collection $withdrawExpenseCodes, ?Collection $withdrawExpenseItems = null): void
    {
        if (! Schema::hasTable('budget_set_expenses_withdraw_accounts')) {
            return;
        }

        DB::table('budget_set_expenses_withdraw_accounts')
            ->where('budget_set_expenses_code', $budgetSetExpensesCode)
            ->delete();

        if ($withdrawExpenseCodes->isEmpty()) {
            return;
        }

        $nameByCode = collect();
        if (Schema::hasTable('withdraw_set_expenses')) {
            $nameByCode = DB::table('withdraw_set_expenses')
                ->whereIn('withdraw_set_expenses_code', $withdrawExpenseCodes->all())
                ->pluck('withdraw_set_expenses_name', 'withdraw_set_expenses_code');
        }
        $requestNameByCode = ($withdrawExpenseItems ?? collect())
            ->mapWithKeys(fn (array $x) => [(int) ($x['code'] ?? 0) => trim((string) ($x['name'] ?? ''))]);

        $now = date('Y-m-d H:i:s');
        $rows = $withdrawExpenseCodes->map(function (int $code) use ($budgetSetExpensesCode, $nameByCode, $requestNameByCode, $now) {
            $name = trim((string) ($nameByCode[$code] ?? ''));
            if ($name === '') {
                $name = trim((string) ($requestNameByCode[$code] ?? ''));
            }
            return [
                'budget_set_expenses_code' => $budgetSetExpensesCode,
                'withdraw_set_expenses_code' => $code,
                'withdraw_set_expenses_name' => $name,
                'date_create' => $now,
            ];
        })->values()->all();

        if (! empty($rows)) {
            DB::table('budget_set_expenses_withdraw_accounts')->insert($rows);
        }
    }

    private function saveEvent(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        $planCode = (int) $request->input('plan_code', 0);
        $name = trim((string) $request->input('name', ''));
        $detail = trim((string) $request->input('detail', ''));
        $existingEventCode = (int) $request->input('existing_event_code', 0);
        $eventNameNew = trim((string) $request->input('event_name_new', ''));
        $activityName = trim((string) $request->input('activity_name', ''));

        // แก้ไขงานที่มีอยู่
        if ($id > 0) {
            if ($planCode <= 0 || $name === '') {
                return response()->json(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบ'], 422);
            }

            DB::table('budget_set_event')->where('budget_event_id', $id)->update($this->applyStaffModifyAudit('budget_set_event', [
                'budget_plan_code' => $planCode,
                'budget_event_name' => $name,
                'budget_event_detail' => $detail,
                'budget_event_date_modify' => date('Y-m-d H:i:s'),
            ]));

            return response()->json(['status' => 'success']);
        }

        // เพิ่มใหม่ (เทียบ update_budget_set_event.php: addBudgetEvent)
        if ($planCode <= 0) {
            return response()->json(['status' => 'error', 'message' => 'กรุณาเลือกแผนงานงบประมาณ'], 422);
        }

        $eventNameForNew = $eventNameNew !== '' ? $eventNameNew : $name;
        $eventCodeToUse = $existingEventCode;

        if ($existingEventCode === 0) {
            if ($eventNameForNew === '') {
                return response()->json(['status' => 'error', 'message' => 'กรุณากรอกชื่องาน'], 422);
            }

            $dup = DB::table('budget_set_event')->where('budget_event_name', $eventNameForNew);
            if ($dup->exists()) {
                return response()->json(['status' => 'error', 'message' => 'ชื่องานนี้มีอยู่แล้ว กรุณาใช้ชื่ออื่น'], 422);
            }

            $maxCode = (int) (DB::table('budget_set_event')->max('budget_event_code') ?? 0);
            $newEventCode = $maxCode + 1;
            DB::table('budget_set_event')->insert($this->applyStaffCreateAudit('budget_set_event', [
                'budget_plan_code' => $planCode,
                'budget_event_code' => $newEventCode,
                'budget_event_name' => $eventNameForNew,
                'budget_event_detail' => $detail,
                'budget_event_status' => 1,
                'budget_event_date_create' => date('Y-m-d H:i:s'),
            ]));
            $eventCodeToUse = $newEventCode;
        } else {
            $exists = DB::table('budget_set_event')
                ->where('budget_plan_code', $planCode)
                ->where('budget_event_code', $existingEventCode)
                ->exists();
            if (! $exists) {
                return response()->json(['status' => 'error', 'message' => 'ไม่พบงานที่เลือกในแผนนี้'], 422);
            }
            $eventCodeToUse = $existingEventCode;
        }

        if ($activityName !== '') {
            $maxAct = (int) (DB::table('budget_set_activity')->max('budget_activity_code') ?? 0);
            DB::table('budget_set_activity')->insert($this->applyStaffCreateAudit('budget_set_activity', [
                'budget_event_code' => $eventCodeToUse,
                'budget_activity_code' => $maxAct + 1,
                'budget_activity_name' => $activityName,
                'budget_activity_detail' => $detail,
                'budget_activity_status' => 1,
                'budget_activity_date_create' => date('Y-m-d H:i:s'),
            ]));
        }

        return response()->json(['status' => 'success']);
    }

    private function saveActivity(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        $eventCode = (int) $request->input('event_code', 0);
        $name = trim((string) $request->input('name', ''));
        $detail = trim((string) $request->input('detail', ''));

        if ($eventCode <= 0 || $name === '') {
            return response()->json(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบ'], 422);
        }

        if ($id > 0) {
            DB::table('budget_set_activity')->where('budget_activity_id', $id)->update($this->applyStaffModifyAudit('budget_set_activity', [
                'budget_event_code' => $eventCode,
                'budget_activity_name' => $name,
                'budget_activity_detail' => $detail,
                'budget_activity_date_modify' => date('Y-m-d H:i:s'),
            ]));
        } else {
            $maxCode = (int) (DB::table('budget_set_activity')->max('budget_activity_code') ?? 0);
            DB::table('budget_set_activity')->insert($this->applyStaffCreateAudit('budget_set_activity', [
                'budget_event_code' => $eventCode,
                'budget_activity_code' => $maxCode + 1,
                'budget_activity_name' => $name,
                'budget_activity_detail' => $detail,
                'budget_activity_status' => 1,
                'budget_activity_date_create' => date('Y-m-d H:i:s'),
            ]));
        }

        return response()->json(['status' => 'success']);
    }
}
