<?php

namespace App\Http\Controllers\Withdraw;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawManageController extends Controller
{
    public function index()
    {
        return view('budget.manage.index', [
            'years' => DB::table('budget_set_year')->where('budget_year_status', 1)->orderBy('budget_year_name')->get(),
            'sources' => DB::table('budget_set_source')->where('budget_source_status', 1)->orderBy('budget_source_name')->get(),
            'plans' => DB::table('budget_set_plan')->where('budget_plan_status', 1)->orderBy('budget_plan_name')->get(),
            'events' => DB::table('budget_set_event')->where('budget_event_status', 1)->orderBy('budget_event_name')->get(),
            'activities' => DB::table('budget_set_activity')->where('budget_activity_status', 1)->orderBy('budget_activity_name')->get(),
            'funds' => DB::table('budget_set_fund')->where('budget_fund_status', 1)->orderBy('budget_fund_name')->get(),
            'categoryBudgets' => DB::table('budget_set_category_budget')->where('budget_category_budget_status', 1)->orderBy('budget_category_budget_name')->get(),
            'categoryPays' => DB::table('budget_set_category_pay')->where('budget_category_pay_status', 1)->orderBy('budget_category_pay_name')->get(),
            'types' => DB::table('budget_set_type')->where('budget_type_status', 1)->orderBy('budget_type_name')->get(),
            'rows' => $this->fetchManageRows(),
        ]);
    }

    public function show(string $budgetManageId)
    {
        $budget = DB::table('budget_manage as bm')
            ->leftJoin('budget_set_source as bs', 'bm.budget_source_code', '=', 'bs.budget_source_code')
            ->leftJoin('budget_set_plan as bp', 'bm.budget_plan_code', '=', 'bp.budget_plan_code')
            ->leftJoin('budget_set_event as be', 'bm.budget_event_code', '=', 'be.budget_event_code')
            ->leftJoin('budget_set_activity as ba', 'bm.budget_activity_code', '=', 'ba.budget_activity_code')
            ->leftJoin('budget_set_fund as bf', 'bm.budget_fund_code', '=', 'bf.budget_fund_code')
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
                'bcb.budget_category_budget_name',
                'bcp.budget_category_pay_name',
                'btyp.budget_type_name as budget_manage_type_name',
            ])
            ->where('bm.budget_manage_id', $budgetManageId)
            ->first();

        abort_unless($budget, 404);

        $categoryPayCode = (int) ($budget->budget_category_pay_code ?? 0);

        $payListsQuery = DB::table('budget_set_pay_list')
            ->where('budget_pay_status', 1)
            ->orderBy('budget_pay_name');

        if ($categoryPayCode > 0) {
            $payListsQuery->where('budget_category_pay_code', $categoryPayCode);
        }

        return view('budget.manage.show', [
            'budget' => $budget,
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

    private function manageListBaseQuery()
    {
        return DB::table('budget_manage as bm')
            ->leftJoin('budget_set_source as bs', 'bm.budget_source_code', '=', 'bs.budget_source_code')
            ->leftJoin('budget_set_plan as bp', 'bm.budget_plan_code', '=', 'bp.budget_plan_code')
            ->leftJoin('budget_set_event as be', 'bm.budget_event_code', '=', 'be.budget_event_code')
            ->leftJoin('budget_set_activity as ba', 'bm.budget_activity_code', '=', 'ba.budget_activity_code')
            ->leftJoin('budget_set_fund as bf', 'bm.budget_fund_code', '=', 'bf.budget_fund_code')
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
                'bm.budget_category_budget_code',
                'bm.budget_category_pay_code',
                'bm.budget_type_code',
                'bs.budget_source_name',
                'bp.budget_plan_name',
                'be.budget_event_name',
                'ba.budget_activity_name',
                'bf.budget_fund_name',
                'bcb.budget_category_budget_name',
                'bcp.budget_category_pay_name',
                'bm.budget_category_pay_detail',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatManageListItem(object $row): array
    {
        $sourcePath = $row->budget_source_name.'ปี '.$row->budget_manage_year
            .'/'.$row->budget_plan_name
            .'/'.$row->budget_event_name
            .($row->budget_activity_name ? '-'.$row->budget_activity_name : '')
            .'/'.$row->budget_fund_name
            .'/'.$row->budget_category_budget_name
            .'/'.$row->budget_category_pay_name
            .($row->budget_category_pay_detail ? '-'.$row->budget_category_pay_detail : '');

        $amount = (float) $row->budget_manage_amount;
        $balance = (float) $row->budget_manage_balance;

        $planUsed = (float) DB::table('budget_manage_expenses_sub')
            ->where('budget_manage_id', $row->budget_manage_id)
            ->sum('budget_expenses_sub_total');

        $withdrawAmount = (float) DB::table('budget_manage_list')
            ->where('budget_manage_id', $row->budget_manage_id)
            ->where('budget_manage_list_type', 'withdraw')
            ->sum('budget_manage_list_amount');

        $withdrawWait = (float) DB::table('budget_manage_list')
            ->where('budget_manage_id', $row->budget_manage_id)
            ->where('budget_manage_list_type', 'pending')
            ->sum('budget_manage_list_amount');

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
            'date' => $row->budget_manage_date,
            'year' => $row->budget_manage_year,
            'source_code' => $row->budget_source_code,
            'plan_code' => $row->budget_plan_code,
            'event_code' => $row->budget_event_code,
            'activity_code' => $row->budget_activity_code,
            'fund_code' => $row->budget_fund_code,
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

        return $row ? $this->formatManageListItem($row) : null;
    }

    private function fetchManageRows()
    {
        return $this->manageListBaseQuery()
            ->orderByDesc('bm.budget_manage_id')
            ->get()
            ->map(fn ($row) => $this->formatManageListItem($row));
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

    public function apiHistory(string $budgetManageId): JsonResponse
    {
        $main = DB::table('budget_manage')
            ->where('budget_manage_id', $budgetManageId)
            ->first();

        $list = DB::table('budget_manage_list as bml')
            ->leftJoin('budget_set_type as bt', 'bml.budget_type_code', '=', 'bt.budget_type_code')
            ->leftJoin('budget_set_source as bs', 'bml.budget_source_code', '=', 'bs.budget_source_code')
            ->leftJoin('budget_set_category_pay as bcp', 'bml.budget_category_pay_code', '=', 'bcp.budget_category_pay_code')
            ->select([
                'bml.*',
                'bt.budget_type_name',
                'bs.budget_source_name',
                'bcp.budget_category_pay_name',
            ])
            ->where('bml.budget_manage_id', $budgetManageId)
            ->orderByDesc('bml.budget_manage_list_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'main' => $main,
                'items' => $list,
            ],
        ]);
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
        $categoryBudgetCode = (int) $request->input('budgetManageCategoryBudget', 0);
        $categoryPayCode = (int) $request->input('budgetManageCategoryPay', 0);
        $categoryPayDetail = trim((string) $request->input('budgetManageCategoryPayDetail', ''));
        $typeCode = (int) $request->input('budgetManageType', 0);
        $amount = (float) $request->input('budgetManageAmount', 0);
        $date = trim((string) $request->input('budgetManageDate', ''));
        $detail = trim((string) $request->input('budgetManageDetail', ''));
        if ($year === '' || ($year === '0' && $yearText === '')) return response()->json(['status' => 'error', 'message' => 'กรุณาเลือกปีงบประมาณ'], 422);
        if ($code === '') return response()->json(['status' => 'error', 'message' => 'กรุณากรอกรหัสงบประมาณ'], 422);
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
            'budget_category_budget_code' => $categoryBudgetCode,
            'budget_category_pay_code' => $categoryPayCode,
            'budget_category_pay_detail' => $categoryPayDetail,
            'budget_type_code' => $typeCode,
            'budget_manage_amount' => $amount,
            'budget_manage_balance' => $amount,
            'budget_manage_date' => $date,
            'budget_manage_detail' => $detail,
        ];

        if ($id > 0) {
            $payload['budget_manage_date_modify'] = date('Y-m-d H:i:s');
            DB::table('budget_manage')
                ->where('budget_manage_id', $id)
                ->update($this->applyStaffModifyAudit('budget_manage', $payload));
            $manageId = $id;
        } else {
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
        $date = trim((string) $request->input('budgetManageDate', ''));
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

            $currentBalance = (float) $manage->budget_manage_balance;

            if ($id > 0) {
                $old = DB::table('budget_manage_list')->where('budget_manage_list_id', $id)->first();
                $oldAmount = (float) ($old->budget_manage_list_amount ?? 0);
                $newBalance = ($currentBalance - $oldAmount) + $amount;

                DB::table('budget_manage_list')->where('budget_manage_list_id', $id)->update($this->applyStaffModifyAudit('budget_manage_list', [
                    'budget_manage_id' => $manageId,
                    'budget_manage_list_date' => $date,
                    'budget_type_code' => $type,
                    'budget_source_code' => $source,
                    'budget_category_pay_code' => $catPay,
                    'budget_manage_list_amount' => $amount,
                    'budget_manage_list_balance' => $newBalance,
                    'budget_manage_list_detail' => $detail,
                    'budget_manage_list_date_modify' => date('Y-m-d H:i:s'),
                ]));

                DB::table('budget_manage')->where('budget_manage_id', $manageId)->update($this->applyStaffModifyAudit('budget_manage', [
                    'budget_manage_balance' => $newBalance,
                    'budget_manage_date_modify' => date('Y-m-d H:i:s'),
                ]));
            } else {
                $newBalance = $currentBalance + $amount;
                DB::table('budget_manage_list')->insert($this->applyStaffCreateAudit('budget_manage_list', [
                    'budget_manage_id' => $manageId,
                    'budget_manage_list_type' => 'add',
                    'budget_source_code' => $source,
                    'budget_category_pay_code' => $catPay,
                    'budget_type_code' => $type,
                    'budget_manage_list_amount' => $amount,
                    'budget_manage_list_balance' => $newBalance,
                    'budget_manage_list_date' => $date,
                    'budget_manage_list_detail' => $detail,
                    'budget_manage_list_status' => 0,
                    'budget_manage_list_date_create' => date('Y-m-d H:i:s'),
                ]));

                DB::table('budget_manage')->where('budget_manage_id', $manageId)->update($this->applyStaffModifyAudit('budget_manage', [
                    'budget_manage_balance' => $newBalance,
                ]));
            }
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

        DB::transaction(function () use ($id) {
            $item = DB::table('budget_manage_list')->where('budget_manage_list_id', $id)->first();
            if (! $item) {
                return;
            }

            DB::table('budget_manage')->where('budget_manage_id', $item->budget_manage_id)->update($this->applyStaffModifyAudit('budget_manage', [
                'budget_manage_balance' => DB::raw('budget_manage_balance - '.((float) $item->budget_manage_list_amount)),
            ]));
            DB::table('budget_manage_list')->where('budget_manage_list_id', $id)->delete();
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
}
