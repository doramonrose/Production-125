<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Services\ProjectPbmService;
use App\Support\WithdrawDisbursementTotals;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BudgetManageController extends Controller
{
    public function index()
    {
        $years = DB::table('budget_set_year')->where('budget_year_status', 1)->orderBy('budget_year_name')->get();
        $yearNames = $years
            ->pluck('budget_year_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->values()
            ->all();
        $currentYear = $this->currentFiscalYearLabel();
        if (! in_array($currentYear, $yearNames, true)) {
            rsort($yearNames, SORT_STRING);
            $currentYear = $yearNames[0] ?? $currentYear;
        }

        return view('budget.manage.index', [
            'years' => $years,
            'currentYear' => $currentYear,
            'sources' => DB::table('budget_set_source')->where('budget_source_status', 1)->orderBy('budget_source_name')->get(),
            'plans' => DB::table('budget_set_plan')->where('budget_plan_status', 1)->orderBy('budget_plan_name')->get(),
            'events' => DB::table('budget_set_event')->where('budget_event_status', 1)->orderBy('budget_event_name')->get(),
            'activities' => DB::table('budget_set_activity')->where('budget_activity_status', 1)->orderBy('budget_activity_name')->get(),
            'funds' => DB::table('budget_set_fund')->where('budget_fund_status', 1)->orderBy('budget_fund_name')->get(),
            'costs' => DB::table('budget_set_cost')->where('budget_cost_status', 1)->orderBy('budget_cost_name')->get(),
            'categoryBudgets' => DB::table('budget_set_category_budget')->where('budget_category_budget_status', 1)->orderBy('budget_category_budget_name')->get(),
            'categoryPays' => DB::table('budget_set_category_pay')->where('budget_category_pay_status', 1)->orderBy('budget_category_pay_name')->get(),
            'types' => DB::table('budget_set_type')->where('budget_type_status', 1)->orderBy('budget_type_name')->get(),
            'rows' => $this->fetchManageRows(),
        ]);
    }

    /**
     * Thai fiscal year starts 1 October and is named by the Buddhist year it ends in.
     */
    private function currentFiscalYearLabel(): string
    {
        $today = now();
        $fiscalYear = (int) $today->format('Y') + 543;
        if ((int) $today->format('n') >= 10) {
            $fiscalYear++;
        }

        return (string) $fiscalYear;
    }

    public function show(string $budgetManageId)
    {
        $budget = DB::table('budget_manage as bm')
            ->leftJoin('budget_set_source as bs', 'bm.budget_source_code', '=', 'bs.budget_source_code')
            ->leftJoin('budget_set_plan as bp', 'bm.budget_plan_code', '=', 'bp.budget_plan_code')
            ->leftJoin('budget_set_event as be', 'bm.budget_event_code', '=', 'be.budget_event_code')
            ->leftJoin('budget_set_activity as ba', 'bm.budget_activity_code', '=', 'ba.budget_activity_code')
            ->leftJoin('budget_set_fund as bf', 'bm.budget_fund_code', '=', 'bf.budget_fund_code')
            ->leftJoin('budget_set_cost as bcst', 'bm.budget_cost_code', '=', 'bcst.budget_cost_code')
            ->leftJoin('budget_set_category_budget as bcb', 'bm.budget_category_budget_code', '=', 'bcb.budget_category_budget_code')
            ->leftJoin('budget_set_category_pay as bcp', 'bm.budget_category_pay_code', '=', 'bcp.budget_category_pay_code')
            ->leftJoin('budget_set_type as btyp', 'bm.budget_type_code', '=', 'btyp.budget_type_code')
            ->select([
                'bm.*',
                'bs.budget_source_name',
                'bp.budget_plan_name',
                'be.budget_event_name',
                'ba.budget_activity_name',
                'bf.budget_fund_name',
                'bcst.budget_cost_name',
                'bcb.budget_category_budget_name',
                'bcp.budget_category_pay_name',
                'btyp.budget_type_name as budget_manage_type_name',
            ])
            ->where('bm.budget_manage_id', $budgetManageId)
            ->first();

        abort_unless($budget, 404);

        $budget->budget_manage_staff_create_name = $this->createdByName(
            $budget,
            'budget_manage',
            ['budget_manage_staff_create', 'staff_create']
        );

        $withdrawTotals = app(WithdrawDisbursementTotals::class)
            ->forManage((int) $budget->budget_manage_id, (string) ($budget->budget_manage_code ?? ''));

        $disbursementHistory = app(WithdrawDisbursementTotals::class)->itemsForManage(
            (int) $budget->budget_manage_id,
            (string) ($budget->budget_manage_code ?? ''),
            'all'
        );

        $categoryPayCode = (int) ($budget->budget_category_pay_code ?? 0);

        $payListsQuery = DB::table('budget_set_pay_list')
            ->where('budget_pay_status', 1)
            ->orderBy('budget_pay_name');

        if ($categoryPayCode > 0) {
            $payListsQuery->where('budget_category_pay_code', $categoryPayCode);
        }

        return view('budget.manage.show', [
            'budget' => $budget,
            'withdrawApproved' => $withdrawTotals['approved'],
            'withdrawWait' => $withdrawTotals['pending'],
            'disbursementHistory' => $disbursementHistory,
            'sources' => DB::table('budget_set_source')->where('budget_source_status', 1)->orderBy('budget_source_name')->get(),
            'categoryPays' => DB::table('budget_set_category_pay')->where('budget_category_pay_status', 1)->orderBy('budget_category_pay_name')->get(),
            'types' => DB::table('budget_set_type')->where('budget_type_status', 1)->orderBy('budget_type_name')->get(),
            'payLists' => $payListsQuery->get(),
            'setExpenses' => DB::table('budget_set_expenses')->where('budget_set_expenses_status', 1)->orderBy('budget_set_expenses_name')->get(),
        ]);
    }

    public function apiList(): JsonResponse
    {
        $rows = $this->fetchManageRows();

        return response()->json([
            'status' => 'success',
            'data' => $rows,
        ]);
    }

    public function apiDisbursements(Request $request, string $budgetManageId): JsonResponse
    {
        $kind = $request->query('kind') === 'pending' ? 'pending' : 'approved';
        $row = DB::table('budget_manage')
            ->where('budget_manage_id', $budgetManageId)
            ->first(['budget_manage_id', 'budget_manage_code']);

        abort_unless($row, 404);

        $payload = app(WithdrawDisbursementTotals::class)->itemsForManage(
            (int) $row->budget_manage_id,
            (string) ($row->budget_manage_code ?? ''),
            $kind
        );

        return response()->json([
            'status' => 'success',
            'data' => $payload,
        ]);
    }

    public function apiPayListOptions(): JsonResponse
    {
        $rows = DB::table('budget_set_pay_list as p')
            ->leftJoin('budget_set_category_pay as c', 'p.budget_category_pay_code', '=', 'c.budget_category_pay_code')
            ->where('p.budget_pay_status', 1)
            ->orderBy('p.budget_pay_name')
            ->select([
                'p.budget_pay_code',
                'p.budget_pay_name',
                'p.budget_pay_status',
                'p.budget_category_pay_code',
                'c.budget_category_pay_name',
            ])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $rows,
        ]);
    }

    private function manageListBaseQuery()
    {
        return DB::table('budget_manage as bm')
            ->leftJoin('budget_set_source as bs', 'bm.budget_source_code', '=', 'bs.budget_source_code')
            ->leftJoin('budget_set_plan as bp', 'bm.budget_plan_code', '=', 'bp.budget_plan_code')
            ->leftJoin('budget_set_event as be', 'bm.budget_event_code', '=', 'be.budget_event_code')
            ->leftJoin('budget_set_activity as ba', 'bm.budget_activity_code', '=', 'ba.budget_activity_code')
            ->leftJoin('budget_set_fund as bf', 'bm.budget_fund_code', '=', 'bf.budget_fund_code')
            ->leftJoin('budget_set_cost as bcst', 'bm.budget_cost_code', '=', 'bcst.budget_cost_code')
            ->leftJoin('budget_set_category_budget as bcb', 'bm.budget_category_budget_code', '=', 'bcb.budget_category_budget_code')
            ->leftJoin('budget_set_category_pay as bcp', 'bm.budget_category_pay_code', '=', 'bcp.budget_category_pay_code')
            ->select([
                'bm.budget_manage_id',
                'bm.budget_manage_code',
                'bm.budget_manage_year',
                'bm.budget_manage_amount',
                'bm.budget_manage_balance',
                'bm.budget_manage_date',
                'bm.budget_manage_detail',
                'bm.budget_source_code',
                'bm.budget_plan_code',
                'bm.budget_event_code',
                'bm.budget_activity_code',
                'bm.budget_fund_code',
                'bm.budget_cost_code',
                'bm.budget_category_budget_code',
                'bm.budget_category_pay_code',
                'bm.budget_type_code',
                'bs.budget_source_name',
                'bp.budget_plan_name',
                'be.budget_event_name',
                'ba.budget_activity_name',
                'bf.budget_fund_name',
                'bcst.budget_cost_name',
                'bcb.budget_category_budget_name',
                'bcp.budget_category_pay_name',
                'bm.budget_category_pay_detail',
            ]);
    }

    /**
     * @param  array<int|string, float>  $planUsedById
     * @param  array{approved_by_code: array<string, float>, pending_by_code: array<string, float>, approved_by_id: array<int, float>, pending_by_id: array<int, float>}  $withdrawGrouped
     * @return array<string, mixed>
     */
    private function formatManageListItem(object $row, array $planUsedById = [], array $withdrawGrouped = []): array
    {
        $sourcePath = $row->budget_source_name.'ปี '.$row->budget_manage_year
            .'/'.$row->budget_plan_name
            .'/'.$row->budget_event_name
            .($row->budget_activity_name ? '-'.$row->budget_activity_name : '')
            .'/'.$row->budget_fund_name
            .($row->budget_cost_name ? '/'.$row->budget_cost_name : '')
            .'/'.$row->budget_category_budget_name
            .'/'.$row->budget_category_pay_name
            .($row->budget_category_pay_detail ? '-'.$row->budget_category_pay_detail : '');

        $amount = (float) $row->budget_manage_amount;
        $balance = (float) $row->budget_manage_balance;
        $manageId = (int) $row->budget_manage_id;

        $planUsed = (float) ($planUsedById[$manageId] ?? 0);
        $code = trim((string) ($row->budget_manage_code ?? ''));
        $withdrawAmount = $code !== ''
            ? (float) ($withdrawGrouped['approved_by_code'][$code] ?? $withdrawGrouped['approved_by_id'][$manageId] ?? 0)
            : (float) ($withdrawGrouped['approved_by_id'][$manageId] ?? 0);
        $withdrawWait = $code !== ''
            ? (float) ($withdrawGrouped['pending_by_code'][$code] ?? $withdrawGrouped['pending_by_id'][$manageId] ?? 0)
            : (float) ($withdrawGrouped['pending_by_id'][$manageId] ?? 0);

        return [
            'id' => $row->budget_manage_id,
            'code' => $row->budget_manage_code,
            'source_path' => $sourcePath,
            'amount' => $amount,
            'dw' => $balance - $amount,
            'balance' => $balance,
            'plan_used' => $planUsed,
            'plan_balance' => $balance - $planUsed,
            'withdraw_amount' => $withdrawAmount,
            'withdraw_balance' => $balance - $withdrawAmount,
            'withdraw_wait' => $withdrawWait,
            'date' => $this->normalizeStoredDate((string) ($row->budget_manage_date ?? '')),
            'year' => $row->budget_manage_year,
            'source_code' => $row->budget_source_code,
            'plan_code' => $row->budget_plan_code,
            'event_code' => $row->budget_event_code,
            'activity_code' => $row->budget_activity_code,
            'fund_code' => $row->budget_fund_code,
            'cost_code' => $row->budget_cost_code,
            'category_budget_code' => $row->budget_category_budget_code,
            'category_pay_code' => $row->budget_category_pay_code,
            'category_pay_detail' => $row->budget_category_pay_detail,
            'type_code' => $row->budget_type_code,
            'detail' => $row->budget_manage_detail,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchManageRowById(int $id): ?array
    {
        $row = $this->manageListBaseQuery()
            ->where('bm.budget_manage_id', $id)
            ->first();

        if (! $row) {
            return null;
        }

        [$planUsedById, $withdrawGrouped] = $this->loadManageListTotals(collect([$row]));

        return $this->formatManageListItem($row, $planUsedById, $withdrawGrouped);
    }

    private function fetchManageRows()
    {
        $rows = $this->manageListBaseQuery()
            ->orderByDesc('bm.budget_manage_id')
            ->get();

        [$planUsedById, $withdrawGrouped] = $this->loadManageListTotals($rows);

        return $rows->map(fn ($row) => $this->formatManageListItem($row, $planUsedById, $withdrawGrouped));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array{0: array<int|string, float>, 1: array{approved_by_code: array<string, float>, pending_by_code: array<string, float>, approved_by_id: array<int, float>, pending_by_id: array<int, float>}}
     */
    private function loadManageListTotals($rows): array
    {
        $ids = $rows
            ->pluck('budget_manage_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $planUsedById = [];
        if ($ids !== []) {
            $planUsedById = DB::table('budget_manage_expenses_sub')
                ->whereIn('budget_manage_id', $ids)
                ->groupBy('budget_manage_id')
                ->selectRaw('budget_manage_id, SUM(budget_expenses_sub_total) as total')
                ->pluck('total', 'budget_manage_id')
                ->map(fn ($total) => (float) $total)
                ->all();
        }

        $pbmPlanByCode = app(ProjectPbmService::class)->planTotalsIndexedByBudgetCode();
        if ($pbmPlanByCode !== []) {
            foreach ($rows as $row) {
                $manageId = (int) ($row->budget_manage_id ?? 0);
                $code = trim((string) ($row->budget_manage_code ?? ''));
                if ($manageId <= 0 || $code === '') {
                    continue;
                }
                $pbmTotal = (float) ($pbmPlanByCode[$code] ?? 0);
                if ($pbmTotal <= 0) {
                    continue;
                }
                $planUsedById[$manageId] = round(((float) ($planUsedById[$manageId] ?? 0)) + $pbmTotal, 2);
            }
        }

        $withdrawGrouped = app(WithdrawDisbursementTotals::class)->forManageRows($rows);

        return [$planUsedById, $withdrawGrouped];
    }

    public function apiExpenses(string $budgetManageId): JsonResponse
    {
        $rows = DB::table('budget_manage_expenses as bme')
            ->leftJoin('budget_set_pay_list as bpl', 'bme.budget_pay_code', '=', 'bpl.budget_pay_code')
            ->select([
                'bme.*',
                'bpl.budget_pay_name',
            ])
            ->where('bme.budget_manage_id', $budgetManageId)
            ->orderBy('bme.budget_expenses_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $rows,
        ]);
    }

    public function apiPbmProjects(string $budgetManageId): JsonResponse
    {
        $row = DB::table('budget_manage')
            ->where('budget_manage_id', $budgetManageId)
            ->first(['budget_manage_id', 'budget_manage_code']);

        abort_unless($row, 404);

        $plan = app(ProjectPbmService::class)->spendingPlanByBudgetCode(
            (string) ($row->budget_manage_code ?? '')
        );

        return response()->json([
            'status' => 'success',
            'data' => $plan,
        ]);
    }

    public function apiHistory(string $budgetManageId): JsonResponse
    {
        $main = DB::table('budget_manage')
            ->where('budget_manage_id', $budgetManageId)
            ->first();

        if ($main) {
            $main->budget_manage_staff_create_name = $this->createdByName(
                $main,
                'budget_manage',
                ['budget_manage_staff_create', 'staff_create']
            );
        }

        $staffCreateColumn = null;
        if (Schema::hasColumn('budget_manage_list', 'budget_manage_list_staff_create')) {
            $staffCreateColumn = 'bml.budget_manage_list_staff_create';
        } elseif (Schema::hasColumn('budget_manage_list', 'staff_create')) {
            $staffCreateColumn = 'bml.staff_create';
        }

        $staffNameExpr = "NULL AS budget_manage_list_staff_create_name";
        if ($staffCreateColumn !== null) {
            $staffNameExpr = "COALESCE(NULLIF(u.name_th, ''), NULLIF(u.name, ''), NULLIF(u.name_en, ''), {$staffCreateColumn}) AS budget_manage_list_staff_create_name";
        }

        $listQuery = DB::table('budget_manage_list as bml')
            ->leftJoin('budget_set_type as bt', 'bml.budget_type_code', '=', 'bt.budget_type_code')
            ->leftJoin('budget_set_source as bs', 'bml.budget_source_code', '=', 'bs.budget_source_code')
            ->leftJoin('budget_set_category_pay as bcp', 'bml.budget_category_pay_code', '=', 'bcp.budget_category_pay_code')
            ->when($staffCreateColumn !== null, function ($query) use ($staffCreateColumn) {
                $query->leftJoin('users as u', function ($join) use ($staffCreateColumn) {
                    $join->on('u.staff_code', '=', DB::raw($staffCreateColumn))
                        ->orOn('u.user_id', '=', DB::raw($staffCreateColumn));
                });
            })
            ->select([
                'bml.*',
                'bt.budget_type_name',
                'bs.budget_source_name',
                'bcp.budget_category_pay_name',
                DB::raw($staffNameExpr),
            ])
            ->where('bml.budget_manage_id', $budgetManageId)
            ->orderByDesc('bml.budget_manage_list_id');

        $list = $listQuery->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'main' => $main,
                'items' => $list,
            ],
        ]);
    }

    /**
     * Resolve the creator name while supporting legacy audit-column variants.
     *
     * @param  array<int, string>  $candidateColumns
     */
    private function createdByName(object $row, string $table, array $candidateColumns): ?string
    {
        $staffColumn = collect($candidateColumns)
            ->first(fn (string $column) => Schema::hasColumn($table, $column));

        if (! $staffColumn) {
            return null;
        }

        $staffId = trim((string) ($row->{$staffColumn} ?? ''));
        if ($staffId === '') {
            return null;
        }

        $user = DB::table('users')
            ->where('staff_code', $staffId)
            ->orWhere('user_id', $staffId)
            ->first(['name_th', 'name', 'name_en']);

        return $user
            ? (collect([$user->name_th, $user->name, $user->name_en])
                ->map(fn ($name) => trim((string) $name))
                ->first(fn (string $name) => $name !== '') ?: $staffId)
            : $staffId;
    }

    public function apiExpenseSubs(string $budgetExpensesId): JsonResponse
    {
        $rows = DB::table('budget_manage_expenses_sub')
            ->where('budget_expenses_id', $budgetExpensesId)
            ->orderBy('budget_expenses_sub_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $rows,
        ]);
    }

    public function apiSaveManage(Request $request): JsonResponse
    {
        $id = (int) $request->input('budgetManageId', 0);
        $year = trim((string) $request->input('budgetManageYear', ''));
        $yearText = trim((string) $request->input('budgetManageYearText', ''));
        $code = trim((string) $request->input('budgetManageCode', ''));
        $sourceCode = (int) $request->input('budgetManageSource', 0);
        $planCode = (int) $request->input('budgetManagePlan', 0);
        $eventRaw = trim((string) $request->input('budgetManageEvent', ''));
        [$eventCode, $activityCode] = array_pad(explode('||', $eventRaw), 2, '');
        $fundCode = (int) $request->input('budgetManageFund', 0);
        $costCode = (int) $request->input('budgetManageCost', 0);
        $categoryBudgetCode = (int) $request->input('budgetManageCategoryBudget', 0);
        $categoryPayCode = (int) $request->input('budgetManageCategoryPay', 0);
        $categoryPayDetail = trim((string) $request->input('budgetManageCategoryPayDetail', ''));
        $typeCode = (int) $request->input('budgetManageType', 0);
        $amount = (float) $request->input('budgetManageAmount', 0);
        $date = $this->normalizeStoredDate(trim((string) $request->input('budgetManageDate', '')));
        $detail = trim((string) $request->input('budgetManageDetail', ''));
        if ($year === '' || ($year === '0' && $yearText === '')) return response()->json(['status' => 'error', 'message' => 'กรุณาเลือกปีงบประมาณ'], 422);
        if ($code === '') return response()->json(['status' => 'error', 'message' => 'กรุณากรอกรหัสงบประมาณ'], 422);

        $duplicateCode = DB::table('budget_manage')->where('budget_manage_code', $code);
        if ($id > 0) {
            $duplicateCode->where('budget_manage_id', '!=', $id);
        }
        if ($duplicateCode->exists()) {
            return response()->json(['status' => 'error', 'message' => 'รหัสงบประมาณนี้มีอยู่แล้ว'], 422);
        }

        if ($sourceCode <= 0 || $planCode <= 0 || (int) $eventCode <= 0 || $fundCode <= 0 || $categoryBudgetCode <= 0 || $categoryPayCode <= 0 || $typeCode <= 0) {
            return response()->json(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'], 422);
        }

        $payload = [
            'budget_manage_year' => $year === '0' ? $yearText : $year,
            'budget_manage_code' => $code,
            'budget_source_code' => $sourceCode,
            'budget_plan_code' => $planCode,
            'budget_event_code' => (int) $eventCode,
            'budget_activity_code' => (int) ($activityCode ?: 0),
            'budget_fund_code' => $fundCode,
            'budget_cost_code' => $costCode,
            'budget_category_budget_code' => $categoryBudgetCode,
            'budget_category_pay_code' => $categoryPayCode,
            'budget_category_pay_detail' => $categoryPayDetail,
            'budget_type_code' => $typeCode,
            'budget_manage_amount' => $amount,
            'budget_manage_date' => $date,
            'budget_manage_detail' => $detail,
        ];

        if ($id > 0) {
            // Do not reset balance to amount — keep adjustments from budget_manage_list.
            $payload['budget_manage_date_modify'] = date('Y-m-d H:i:s');
            DB::table('budget_manage')
                ->where('budget_manage_id', $id)
                ->update($this->applyStaffModifyAudit('budget_manage', $payload));
            $this->recalculateManageBalance($id);
            $manageId = $id;
        } else {
            $payload['budget_manage_balance'] = $amount;
            $payload['budget_manage_status'] = 0;
            $payload['budget_manage_date_create'] = date('Y-m-d H:i:s');
            $manageId = (int) DB::table('budget_manage')
                ->insertGetId($this->applyStaffCreateAudit('budget_manage', $payload));
        }

        $row = $this->fetchManageRowById($manageId);
        if (! $row) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรายการหลังบันทึก'], 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $row,
        ]);
    }

    public function apiDeleteManage(Request $request): JsonResponse
    {
        $id = (int) $request->input('budgetManageId', 0);
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรหัสงบประมาณ'], 422);
        }

        DB::transaction(function () use ($id) {
            DB::table('budget_manage')->where('budget_manage_id', $id)->delete();
        });

        return response()->json(['status' => 'success']);
    }

    public function apiSaveManageList(Request $request): JsonResponse
    {
        $id = (int) $request->input('budgetManageListId', 0);
        $manageId = (int) $request->input('budgetManageId', 0);
        $date = $this->normalizeStoredDate(trim((string) $request->input('budgetManageDate', '')));
        $type = (int) $request->input('budgetManageType', 0);
        $source = (int) $request->input('budgetManageSource', 0);
        $catPay = (int) $request->input('budgetManageCategoryPay', 0);
        $amount = (float) $request->input('budgetManageAmount', 0);
        $detail = trim((string) $request->input('budgetManageDetail', ''));

        if ($manageId <= 0 || $date === '' || $type <= 0 || $source <= 0 || $catPay <= 0 || $amount <= 0) {
            return response()->json(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'], 422);
        }

        DB::transaction(function () use ($id, $manageId, $date, $type, $source, $catPay, $amount, $detail) {
            $manage = DB::table('budget_manage')->where('budget_manage_id', $manageId)->first();
            if (! $manage) {
                abort(422, 'ไม่พบรายการบริหารงบประมาณ');
            }

            if ($id > 0) {
                DB::table('budget_manage_list')->where('budget_manage_list_id', $id)->update($this->applyStaffModifyAudit('budget_manage_list', [
                    'budget_manage_id' => $manageId,
                    'budget_manage_list_date' => $date,
                    'budget_type_code' => $type,
                    'budget_source_code' => $source,
                    'budget_category_pay_code' => $catPay,
                    'budget_manage_list_amount' => $amount,
                    'budget_manage_list_detail' => $detail,
                    'budget_manage_list_date_modify' => date('Y-m-d H:i:s'),
                ]));
            } else {
                DB::table('budget_manage_list')->insert($this->applyStaffCreateAudit('budget_manage_list', [
                    'budget_manage_id' => $manageId,
                    'budget_manage_list_type' => 'add',
                    'budget_source_code' => $source,
                    'budget_category_pay_code' => $catPay,
                    'budget_type_code' => $type,
                    'budget_manage_list_amount' => $amount,
                    'budget_manage_list_balance' => 0,
                    'budget_manage_list_date' => $date,
                    'budget_manage_list_detail' => $detail,
                    'budget_manage_list_status' => 0,
                    'budget_manage_list_date_create' => date('Y-m-d H:i:s'),
                ]));
            }

            $this->recalculateManageBalance($manageId);
        });

        $updatedBalance = DB::table('budget_manage')
            ->where('budget_manage_id', $manageId)
            ->value('budget_manage_balance');

        return response()->json([
            'status' => 'success',
            'data' => [
                'budgetManageBalance' => (float) ($updatedBalance ?? 0),
            ],
        ]);
    }

    public function apiDeleteManageList(Request $request): JsonResponse
    {
        $id = (int) $request->input('budgetManageListId', 0);
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรหัสรายการเพิ่มงบ'], 422);
        }

        $manageId = (int) DB::table('budget_manage_list')->where('budget_manage_list_id', $id)->value('budget_manage_id');

        DB::transaction(function () use ($id, $manageId) {
            $item = DB::table('budget_manage_list')->where('budget_manage_list_id', $id)->first();
            if (! $item) {
                return;
            }

            DB::table('budget_manage_list')->where('budget_manage_list_id', $id)->delete();

            if ($manageId > 0) {
                $this->recalculateManageBalance($manageId);
            }
        });

        $updatedBalance = $manageId > 0
            ? DB::table('budget_manage')->where('budget_manage_id', $manageId)->value('budget_manage_balance')
            : null;

        return response()->json([
            'status' => 'success',
            'data' => [
                'budgetManageBalance' => (float) ($updatedBalance ?? 0),
            ],
        ]);
    }

    public function apiSaveExpense(Request $request): JsonResponse
    {
        $id = (int) $request->input('budgetExpensesPlanId', 0);
        $manageId = (int) $request->input('budgetManageId', 0);
        $payCode = (int) $request->input('budgetPayListCode', 0);
        $detail = trim((string) $request->input('budgetExpensesPlanDetail', ''));
        $amount = (float) $request->input('budgetExpensesPlanAmount', 0);

        if ($payCode <= 0 || $amount <= 0 || $manageId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'], 422);
        }

        $manage = DB::table('budget_manage')->where('budget_manage_id', $manageId)->first();
        if (! $manage) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบงบประมาณ'], 422);
        }

        $payRow = DB::table('budget_set_pay_list')->where('budget_pay_code', $payCode)->first();
        if (! $payRow) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรายการจ่ายที่เลือก'], 422);
        }
        $expectedCat = (int) ($manage->budget_category_pay_code ?? 0);
        if ($expectedCat > 0 && (int) ($payRow->budget_category_pay_code ?? 0) !== $expectedCat) {
            return response()->json(['status' => 'error', 'message' => 'รายการจ่ายไม่ตรงกับหมวดรายจ่ายของงบประมาณนี้'], 422);
        }

        if ($id > 0) {
            DB::table('budget_manage_expenses')->where('budget_expenses_id', $id)->update($this->applyStaffModifyAudit('budget_manage_expenses', [
                'budget_pay_code' => $payCode,
                'budget_expenses_amount' => $amount,
                'budget_expenses_balance' => $amount,
                'budget_expenses_detail' => $detail,
                'budget_expenses_status' => 0,
                'budget_expenses_date_modify' => date('Y-m-d H:i:s'),
            ]));
        } else {
            $existing = DB::table('budget_manage_expenses')
                ->where('budget_manage_id', $manageId)
                ->where('budget_pay_code', $payCode)
                ->first();

            if ($existing) {
                $nextAmount = (float) ($existing->budget_expenses_amount ?? 0) + $amount;
                DB::table('budget_manage_expenses')
                    ->where('budget_expenses_id', $existing->budget_expenses_id)
                    ->update($this->applyStaffModifyAudit('budget_manage_expenses', [
                        'budget_expenses_amount' => $nextAmount,
                        'budget_expenses_balance' => $nextAmount,
                        'budget_expenses_detail' => trim((string) ($existing->budget_expenses_detail ?: $detail)),
                        'budget_expenses_status' => 0,
                        'budget_expenses_date_modify' => date('Y-m-d H:i:s'),
                    ]));
            } else {
                DB::table('budget_manage_expenses')->insert($this->applyStaffCreateAudit('budget_manage_expenses', [
                    'budget_manage_id' => $manageId,
                    'budget_pay_code' => $payCode,
                    'budget_expenses_amount' => $amount,
                    'budget_expenses_balance' => $amount,
                    'budget_expenses_detail' => $detail,
                    'budget_expenses_status' => 0,
                    'budget_expenses_date_create' => date('Y-m-d H:i:s'),
                ]));
            }
        }

        return response()->json(['status' => 'success']);
    }

    public function apiDeleteExpense(Request $request): JsonResponse
    {
        $id = (int) $request->input('budgetManageExpensesId', 0);
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรหัสแผนรายการจ่าย'], 422);
        }

        DB::table('budget_manage_expenses')->where('budget_expenses_id', $id)->delete();
        DB::table('budget_manage_expenses_sub')->where('budget_expenses_id', $id)->delete();

        return response()->json(['status' => 'success']);
    }

    public function apiSaveExpenseSub(Request $request): JsonResponse
    {
        $id = (int) $request->input('budgetManageExpensesSubId', 0);
        $manageId = (int) $request->input('budgetManageId', 0);
        $expenseId = (int) $request->input('budgetManageExpensesId', 0);
        $selectedExpense = trim((string) $request->input('budgetManageExpensesSubName', ''));

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = (float) $request->input('budgetManageExpensesSubMonth'.$m, 0);
        }
        $total = array_sum($months);

        if ($selectedExpense === '' || $manageId <= 0 || $expenseId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'], 422);
        }

        $setExpense = DB::table('budget_set_expenses')
            ->where('budget_set_expenses_code', $selectedExpense)
            ->orWhere('budget_set_expenses_name', $selectedExpense)
            ->first();

        if (! $setExpense) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรายจ่ายที่เลือก'], 422);
        }

        $payload = [
            'budget_manage_id' => $manageId,
            'budget_expenses_id' => $expenseId,
            'budget_expenses_sub_code' => $setExpense->budget_set_expenses_code,
            'budget_expenses_sub_name' => $setExpense->budget_set_expenses_name,
            'budget_expenses_sub_total' => $total,
        ];

        foreach ($months as $m => $val) {
            $payload['budget_expenses_sub_month_'.$m] = $val;
        }

        if ($id > 0) {
            $payload['budget_expenses_sub_date_modify'] = date('Y-m-d H:i:s');
            DB::table('budget_manage_expenses_sub')
                ->where('budget_expenses_sub_id', $id)
                ->update($this->applyStaffModifyAudit('budget_manage_expenses_sub', $payload));
        } else {
            // ถ้าเพิ่มรายการใหม่แต่มีรายจ่ายเดิมอยู่แล้ว ให้บวกรายเดือนเข้าแถวเดิมแทนการเพิ่มแถวใหม่
            $existing = DB::table('budget_manage_expenses_sub')
                ->where('budget_manage_id', $manageId)
                ->where('budget_expenses_id', $expenseId)
                ->where('budget_expenses_sub_code', $setExpense->budget_set_expenses_code)
                ->first();

            if ($existing) {
                $mergedPayload = [];
                $mergedTotal = 0.0;
                for ($m = 1; $m <= 12; $m++) {
                    $oldVal = (float) ($existing->{'budget_expenses_sub_month_'.$m} ?? 0);
                    $newVal = (float) ($months[$m] ?? 0);
                    $merged = $oldVal + $newVal;
                    $mergedPayload['budget_expenses_sub_month_'.$m] = $merged;
                    $mergedTotal += $merged;
                }
                $mergedPayload['budget_expenses_sub_total'] = $mergedTotal;
                $mergedPayload['budget_expenses_sub_date_modify'] = date('Y-m-d H:i:s');

                DB::table('budget_manage_expenses_sub')
                    ->where('budget_expenses_sub_id', $existing->budget_expenses_sub_id)
                    ->update($this->applyStaffModifyAudit('budget_manage_expenses_sub', $mergedPayload));
            } else {
                $payload['budget_expenses_sub_status'] = 0;
                $payload['budget_expenses_sub_date_create'] = date('Y-m-d H:i:s');
                DB::table('budget_manage_expenses_sub')->insert($this->applyStaffCreateAudit('budget_manage_expenses_sub', $payload));
            }
        }

        return response()->json(['status' => 'success']);
    }

    public function apiDeleteExpenseSub(Request $request): JsonResponse
    {
        $id = (int) $request->input('budgetManageExpensesSubId', 0);
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรหัสรายการย่อย'], 422);
        }

        DB::table('budget_manage_expenses_sub')->where('budget_expenses_sub_id', $id)->delete();
        return response()->json(['status' => 'success']);
    }

    public function apiFinalizePlan(Request $request): JsonResponse
    {
        $id = (int) $request->input('budgetManageId', 0);
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรหัสแผนการใช้งบ'], 422);
        }

        DB::transaction(function () use ($id) {
            DB::table('budget_manage')
                ->where('budget_manage_id', $id)
                ->update($this->applyStaffModifyAudit('budget_manage', ['budget_manage_status' => 1]));
            DB::table('budget_manage_expenses')
                ->where('budget_manage_id', $id)
                ->update($this->applyStaffModifyAudit('budget_manage_expenses', ['budget_expenses_status' => 1]));
            DB::table('budget_manage_expenses_sub')
                ->where('budget_manage_id', $id)
                ->update($this->applyStaffModifyAudit('budget_manage_expenses_sub', ['budget_expenses_sub_status' => 1]));
        });

        return response()->json(['status' => 'success']);
    }

    /**
     * Recalculate current balance from original amount + signed history adjustments.
     * Also refreshes each list row's running balance.
     */
    private function recalculateManageBalance(int $manageId): float
    {
        $manage = DB::table('budget_manage')->where('budget_manage_id', $manageId)->first();
        if (! $manage) {
            return 0.0;
        }

        $balance = (float) ($manage->budget_manage_amount ?? 0);

        $lists = DB::table('budget_manage_list as bml')
            ->leftJoin('budget_set_type as bt', 'bml.budget_type_code', '=', 'bt.budget_type_code')
            ->where('bml.budget_manage_id', $manageId)
            ->orderBy('bml.budget_manage_list_id')
            ->get([
                'bml.budget_manage_list_id',
                'bml.budget_manage_list_amount',
                'bt.budget_type_direction',
            ]);

        foreach ($lists as $list) {
            $adj = abs((float) ($list->budget_manage_list_amount ?? 0));
            if (($list->budget_type_direction ?? '+') === '-') {
                $balance -= $adj;
            } else {
                $balance += $adj;
            }

            DB::table('budget_manage_list')
                ->where('budget_manage_list_id', $list->budget_manage_list_id)
                ->update(['budget_manage_list_balance' => $balance]);
        }

        DB::table('budget_manage')
            ->where('budget_manage_id', $manageId)
            ->update($this->applyStaffModifyAudit('budget_manage', [
                'budget_manage_balance' => $balance,
                'budget_manage_date_modify' => date('Y-m-d H:i:s'),
            ]));

        return $balance;
    }
}
