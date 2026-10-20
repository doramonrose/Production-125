<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Support\WithdrawMemoTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WithdrawSettingController extends Controller
{
    /**
     * ขั้นตอนที่กำหนดจากฟอร์มเบิกจ่ายโดยตรง ไม่ต้องแสดงในเมนูตั้งค่า
     *
     * @var array<int, string>
     */
    private array $excludedApproverStepCodes = [
        'reviewer_head',
        'additional_reviewer',
        'finance_officer',
    ];

    /**
     * @var array<int, string>
     */
    private array $supportedModules = ['expenses', 'documents', 'book-numbers', 'approvers', 'finance-reviewers'];

    public function settingIndex(string $module)
    {
        if (! in_array($module, $this->supportedModules, true)) {
            abort(404);
        }

        return view('setting.index', [
            'title' => $module === 'documents'
                ? 'เอกสารประกอบการเบิกจ่าย'
                : ($module === 'book-numbers'
                    ? 'เลขที่หนังสือ'
                    : ($module === 'approvers'
                        ? 'ผู้อนุมัติ'
                        : ($module === 'finance-reviewers' ? 'ผู้ตรวจสอบ (การเงิน)' : 'ผังบัญชี (รายจ่าย)'))),
            'module' => $module,
            'rows' => match ($module) {
                'documents' => $this->fetchDocumentItemsData(),
                'book-numbers' => $this->fetchBookNumbersData(),
                'approvers' => $this->fetchApproversData(),
                'finance-reviewers' => $this->fetchFinanceReviewersData(),
                default => $this->fetchExpensesData(),
            },
            'settingsApiBase' => url('/withdraw/setting/api'),
        ]);
    }

    public function apiList(string $module): JsonResponse
    {
        if (! in_array($module, $this->supportedModules, true)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบโมดูลที่รองรับ'], 422);
        }

        return response()->json([
            'status' => 'success',
            'data' => match ($module) {
                'documents' => $this->fetchDocumentItemsData(),
                'book-numbers' => $this->fetchBookNumbersData(),
                'approvers' => $this->fetchApproversData(),
                'finance-reviewers' => $this->fetchFinanceReviewersData(),
                default => $this->fetchExpensesData(),
            },
        ]);
    }

    public function apiOptions(): JsonResponse
    {
        $paySource = $this->resolvePayListSource();
        $payLists = collect();

        if ($paySource !== null) {
            $connection = trim((string) ($paySource['connection'] ?? config('database.default')));
            $query = DB::connection($connection)->table($paySource['table'].' as p')
                ->where('p.'.$paySource['status_column'], 1)
                ->orderBy('p.'.$paySource['name_column'])
                ->select([
                    'p.'.$paySource['code_column'].' as budget_pay_code',
                    'p.'.$paySource['name_column'].' as budget_pay_name',
                ]);

            if (($paySource['category_table'] ?? null) && ($paySource['category_column'] ?? null)) {
                $query->leftJoin($paySource['category_table'].' as c', 'p.'.$paySource['category_column'], '=', 'c.'.$paySource['category_code_column'])
                    ->orderBy('c.'.$paySource['category_name_column'])
                    ->addSelect('c.'.$paySource['category_name_column'].' as budget_category_pay_name');
            }

            $payLists = $query->get()
                ->map(function ($row) {
                    $category = trim((string) ($row->budget_category_pay_name ?? ''));
                    $name = trim((string) ($row->budget_pay_name ?? ''));

                    return [
                        'code' => (int) $row->budget_pay_code,
                        'name' => $name,
                        'category' => $category,
                        'label' => $category !== '' ? $category.' / '.$name : $name,
                    ];
                });
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'pay_lists' => $payLists,
                'document_types' => $this->fetchDocumentTypeOptions(),
                'staff_lists' => $this->fetchStaffListOptions(),
            ],
        ]);
    }

    public function apiSave(Request $request, string $module): JsonResponse
    {
        if (! in_array($module, $this->supportedModules, true)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบโมดูลที่รองรับ'], 422);
        }

        if ($module === 'documents') {
            return $this->saveDocumentItem($request);
        }
        if ($module === 'book-numbers') {
            return $this->saveBookNumber($request);
        }
        if ($module === 'approvers') {
            return $this->saveApprover($request);
        }
        if ($module === 'finance-reviewers') {
            return $this->saveFinanceReviewer($request);
        }

        $id = (int) $request->input('id', 0);
        $paySource = $this->resolvePayListSource();
        $payCode = (int) $request->input('pay_code', 0);
        $name = trim((string) $request->input('name', ''));
        $detail = trim((string) $request->input('detail', ''));

        if ($payCode <= 0) {
            return response()->json(['status' => 'error', 'message' => 'กรุณาเลือกรายการจ่ายจากระบบงบประมาณ'], 422);
        }

        if ($paySource !== null) {
            $connection = trim((string) ($paySource['connection'] ?? config('database.default')));
            $payRow = DB::connection($connection)->table($paySource['table'])
                ->where($paySource['code_column'], $payCode)
                ->where($paySource['status_column'], 1)
                ->first();
            if (! $payRow) {
                return response()->json(['status' => 'error', 'message' => 'ไม่พบรายการจ่ายที่เลือก หรือรายการถูกปิดใช้งาน'], 422);
            }
        }

        if ($name === '') {
            return response()->json(['status' => 'error', 'message' => 'กรุณากรอกชื่อผังบัญชี'], 422);
        }

        $dup = DB::table('withdraw_set_expenses')->where('withdraw_set_expenses_name', $name);
        if ($id > 0) {
            $dup->where('withdraw_set_expenses_id', '!=', $id);
        }
        if ($dup->exists()) {
            return response()->json(['status' => 'error', 'message' => 'ชื่อผังบัญชีนี้ซ้ำในฐานข้อมูล กรุณาแก้ไขชื่อใหม่'], 422);
        }

        if ($id > 0) {
            DB::table('withdraw_set_expenses')
                ->where('withdraw_set_expenses_id', $id)
                ->update($this->applyStaffModifyAudit('withdraw_set_expenses', [
                    'withdraw_pay_code' => $payCode,
                    'withdraw_set_expenses_name' => $name,
                    'withdraw_set_expenses_detail' => $detail,
                    'withdraw_set_expenses_date_modify' => date('Y-m-d H:i:s'),
                ]));
        } else {
            $maxCode = (int) (DB::table('withdraw_set_expenses')->max('withdraw_set_expenses_code') ?? 0);
            DB::table('withdraw_set_expenses')->insert($this->applyStaffCreateAudit('withdraw_set_expenses', [
                'withdraw_pay_code' => $payCode,
                'withdraw_set_expenses_code' => $maxCode + 1,
                'withdraw_set_expenses_name' => $name,
                'withdraw_set_expenses_detail' => $detail,
                'withdraw_set_expenses_status' => 1,
                'withdraw_set_expenses_date_create' => date('Y-m-d H:i:s'),
            ]));
        }

        return response()->json(['status' => 'success']);
    }

    public function apiDelete(Request $request, string $module): JsonResponse
    {
        if (! in_array($module, $this->supportedModules, true)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบโมดูลที่รองรับ'], 422);
        }

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรหัสรายการ'], 422);
        }

        if ($module === 'documents') {
            DB::table('withdraw_set_document_item')
                ->where('withdraw_set_document_item_id', $id)
                ->delete();
        } elseif ($module === 'book-numbers') {
            DB::table('withdraw_set_book_number')
                ->where('withdraw_set_book_number_id', $id)
                ->delete();
        } elseif ($module === 'approvers') {
            DB::table('withdraw_set_approver')
                ->where('withdraw_set_approver_id', $id)
                ->delete();
        } elseif ($module === 'finance-reviewers') {
            DB::table('withdraw_set_finance_reviewer')
                ->where('withdraw_set_finance_reviewer_id', $id)
                ->delete();
        } else {
            $expenseRow = DB::table('withdraw_set_expenses')
                ->where('withdraw_set_expenses_id', $id)
                ->first();
            if ($expenseRow && $this->expenseRowIsReferenced($expenseRow)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ไม่สามารถลบได้ เนื่องจากผังบัญชีนี้ถูกใช้งานในรายการเบิกจ่ายแล้ว',
                ], 422);
            }

            DB::table('withdraw_set_expenses')
                ->where('withdraw_set_expenses_id', $id)
                ->delete();
        }

        return response()->json(['status' => 'success', 'message' => 'ลบข้อมูลสำเร็จ']);
    }

    public function apiToggle(Request $request, string $module): JsonResponse
    {
        if (! in_array($module, $this->supportedModules, true)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบโมดูลที่รองรับ'], 422);
        }

        $id = (int) $request->input('id', 0);
        $status = (int) $request->input('status', 0);
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'], 422);
        }

        if ($module === 'documents') {
            DB::table('withdraw_set_document_item')
                ->where('withdraw_set_document_item_id', $id)
                ->update($this->applyStaffModifyAudit('withdraw_set_document_item', [
                    'withdraw_set_document_item_status' => $status,
                    'withdraw_set_document_item_date_modify' => date('Y-m-d H:i:s'),
                ]));
        } elseif ($module === 'book-numbers') {
            DB::table('withdraw_set_book_number')
                ->where('withdraw_set_book_number_id', $id)
                ->update($this->applyStaffModifyAudit('withdraw_set_book_number', [
                    'withdraw_set_book_number_status' => $status,
                    'withdraw_set_book_number_date_modify' => date('Y-m-d H:i:s'),
                ]));
        } elseif ($module === 'approvers') {
            DB::table('withdraw_set_approver')
                ->where('withdraw_set_approver_id', $id)
                ->update($this->applyStaffModifyAudit('withdraw_set_approver', [
                    'withdraw_set_approver_status' => $status,
                    'withdraw_set_approver_date_modify' => date('Y-m-d H:i:s'),
                ]));
        } elseif ($module === 'finance-reviewers') {
            DB::table('withdraw_set_finance_reviewer')
                ->where('withdraw_set_finance_reviewer_id', $id)
                ->update($this->applyStaffModifyAudit('withdraw_set_finance_reviewer', [
                    'withdraw_set_finance_reviewer_status' => $status,
                    'withdraw_set_finance_reviewer_date_modify' => date('Y-m-d H:i:s'),
                ]));
        } else {
            DB::table('withdraw_set_expenses')
                ->where('withdraw_set_expenses_id', $id)
                ->update($this->applyStaffModifyAudit('withdraw_set_expenses', [
                    'withdraw_set_expenses_status' => $status,
                    'withdraw_set_expenses_date_modify' => date('Y-m-d H:i:s'),
                ]));
        }

        return response()->json(['status' => 'success']);
    }

    public function apiToggleApproverNotification(Request $request): JsonResponse
    {
        if (! Schema::hasTable('withdraw_set_approver') || ! Schema::hasColumn('withdraw_set_approver', 'withdraw_set_approver_notification_enabled')) {
            return response()->json(['status' => 'error', 'message' => 'ระบบยังไม่ได้อัปเดตฐานข้อมูลสำหรับการแจ้งเตือนผู้อนุมัติ'], 422);
        }

        $id = (int) $request->input('id', 0);
        $enabled = (int) $request->input('enabled', 1) === 1 ? 1 : 0;
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'], 422);
        }

        DB::table('withdraw_set_approver')
            ->where('withdraw_set_approver_id', $id)
            ->update($this->applyStaffModifyAudit('withdraw_set_approver', [
                'withdraw_set_approver_notification_enabled' => $enabled,
                'withdraw_set_approver_date_modify' => date('Y-m-d H:i:s'),
            ]));

        return response()->json(['status' => 'success']);
    }

    private function saveDocumentItem(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        $typeCode = (int) $request->input('type_code', 0);
        $newTypeName = trim((string) $request->input('new_type_name', ''));
        $name = trim((string) $request->input('name', ''));
        $detail = trim((string) $request->input('detail', ''));

        if ($typeCode <= 0 && $newTypeName === '') {
            return response()->json(['status' => 'error', 'message' => 'กรุณาเลือกหรือเพิ่มประเภทไฟล์'], 422);
        }
        if ($name === '') {
            return response()->json(['status' => 'error', 'message' => 'กรุณากรอกรายการเอกสาร'], 422);
        }

        if ($typeCode <= 0 && $newTypeName !== '') {
            $existingType = DB::table('withdraw_set_document_type')
                ->where('withdraw_set_document_type_name', $newTypeName)
                ->first();
            if ($existingType) {
                $typeCode = (int) ($existingType->withdraw_set_document_type_code ?? 0);
            } else {
                $maxTypeCode = (int) (DB::table('withdraw_set_document_type')->max('withdraw_set_document_type_code') ?? 0);
                $typeCode = $maxTypeCode + 1;
                DB::table('withdraw_set_document_type')
                    ->insert($this->applyStaffCreateAudit('withdraw_set_document_type', [
                        'withdraw_set_document_type_code' => $typeCode,
                        'withdraw_set_document_type_name' => $newTypeName,
                        'withdraw_set_document_type_status' => 1,
                        'withdraw_set_document_type_date_create' => date('Y-m-d H:i:s'),
                    ]));
            }
        }

        $typeRow = DB::table('withdraw_set_document_type')
            ->where('withdraw_set_document_type_code', $typeCode)
            ->where('withdraw_set_document_type_status', 1)
            ->first();
        if (! $typeRow) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบประเภทไฟล์ที่เลือก หรือประเภทไฟล์ถูกปิดใช้งาน'], 422);
        }

        $dup = DB::table('withdraw_set_document_item')
            ->where('withdraw_set_document_type_code', $typeCode)
            ->where('withdraw_set_document_item_name', $name);
        if ($id > 0) {
            $dup->where('withdraw_set_document_item_id', '!=', $id);
        }
        if ($dup->exists()) {
            return response()->json(['status' => 'error', 'message' => 'รายการเอกสารนี้ซ้ำในประเภทไฟล์เดียวกัน'], 422);
        }

        if ($id > 0) {
            DB::table('withdraw_set_document_item')
                ->where('withdraw_set_document_item_id', $id)
                ->update($this->applyStaffModifyAudit('withdraw_set_document_item', [
                    'withdraw_set_document_type_code' => $typeCode,
                    'withdraw_set_document_item_name' => $name,
                    'withdraw_set_document_item_detail' => $detail,
                    'withdraw_set_document_item_date_modify' => date('Y-m-d H:i:s'),
                ]));
        } else {
            $maxCode = (int) (DB::table('withdraw_set_document_item')->max('withdraw_set_document_item_code') ?? 0);
            DB::table('withdraw_set_document_item')
                ->insert($this->applyStaffCreateAudit('withdraw_set_document_item', [
                    'withdraw_set_document_type_code' => $typeCode,
                    'withdraw_set_document_item_code' => $maxCode + 1,
                    'withdraw_set_document_item_name' => $name,
                    'withdraw_set_document_item_detail' => $detail,
                    'withdraw_set_document_item_status' => 1,
                    'withdraw_set_document_item_date_create' => date('Y-m-d H:i:s'),
                ]));
        }

        return response()->json(['status' => 'success']);
    }

    private function fetchExpensesData()
    {
        $paySource = $this->resolvePayListSource();
        $rows = DB::table('withdraw_set_expenses as wse')
            ->orderBy('wse.withdraw_set_expenses_name')
            ->select([
                'wse.*',
                DB::raw('NULL as budget_pay_name'),
            ])
            ->get();

        $payNameByCode = [];
        if ($paySource !== null) {
            $connection = trim((string) ($paySource['connection'] ?? config('database.default')));
            $payCodes = collect($rows)
                ->pluck('withdraw_pay_code')
                ->map(fn ($code) => (int) $code)
                ->filter(fn ($code) => $code > 0)
                ->unique()
                ->values();

            if ($payCodes->isNotEmpty()) {
                $payNameByCode = DB::connection($connection)
                    ->table($paySource['table'])
                    ->whereIn($paySource['code_column'], $payCodes->all())
                    ->pluck($paySource['name_column'], $paySource['code_column'])
                    ->map(fn ($name) => trim((string) $name))
                    ->all();
            }
        }

        return $rows->map(function ($row) use ($payNameByCode) {
            $row->budget_pay_name = (string) ($payNameByCode[(int) ($row->withdraw_pay_code ?? 0)] ?? '');
            $row->can_delete = ! $this->expenseRowIsReferenced($row);

            return $row;
        });
    }

    private function fetchDocumentItemsData()
    {
        if (! Schema::hasTable('withdraw_set_document_item') || ! Schema::hasTable('withdraw_set_document_type')) {
            return collect();
        }

        return DB::table('withdraw_set_document_item as d')
            ->leftJoin('withdraw_set_document_type as t', 'd.withdraw_set_document_type_code', '=', 't.withdraw_set_document_type_code')
            ->orderBy('t.withdraw_set_document_type_name')
            ->orderBy('d.withdraw_set_document_item_name')
            ->select([
                'd.*',
                DB::raw('t.withdraw_set_document_type_name as withdraw_set_document_type_name'),
            ])
            ->get()
            ->map(function ($row) {
                $row->can_delete = true;
                return $row;
            });
    }

    private function fetchDocumentTypeOptions()
    {
        if (! Schema::hasTable('withdraw_set_document_type')) {
            return collect();
        }

        return DB::table('withdraw_set_document_type')
            ->where('withdraw_set_document_type_status', 1)
            ->orderBy('withdraw_set_document_type_name')
            ->get([
                'withdraw_set_document_type_code as code',
                'withdraw_set_document_type_name as name',
                'withdraw_set_document_type_detail as detail',
            ])->map(function ($row) {
                $name = trim((string) ($row->name ?? ''));
                return [
                    'code' => (int) ($row->code ?? 0),
                    'name' => $name,
                    'label' => $name,
                    'detail' => trim((string) ($row->detail ?? '')),
                ];
            });
    }

    private function fetchBookNumbersData()
    {
        if (! Schema::hasTable('withdraw_set_book_number')) {
            return collect();
        }

        return DB::table('withdraw_set_book_number')
            ->orderBy('withdraw_set_book_number_type')
            ->orderBy('withdraw_set_book_number_value')
            ->get()
            ->map(function ($row) {
                $row->can_delete = true;
                return $row;
            });
    }

    private function fetchApproversData()
    {
        if (! Schema::hasTable('withdraw_set_approver')) {
            return collect();
        }

        $rows = DB::table('withdraw_set_approver')
            ->whereNotIn('withdraw_set_approver_step_code', $this->excludedApproverStepCodes)
            ->orderBy('withdraw_set_approver_step_order')
            ->orderBy('withdraw_set_approver_id')
            ->get();

        $staffIds = collect($rows)
            ->pluck('withdraw_set_approver_staff_id')
            ->map(fn ($staffId) => trim((string) $staffId))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $staffNameMap = $this->resolveStaffNamesByIds($staffIds);

        return collect($rows)->map(function ($row) use ($staffNameMap) {
            $staffId = trim((string) ($row->withdraw_set_approver_staff_id ?? ''));
            $staffName = trim((string) ($staffNameMap[$staffId] ?? ''));
            $row->withdraw_set_approver_staff_label = $staffId !== ''
                ? ($staffName !== '' ? $staffName.' ('.$staffId.')' : $staffId)
                : '-';
            $row->withdraw_set_approver_staff_detail = $staffId !== ''
                ? ('ลำดับ '.$row->withdraw_set_approver_step_order.' : '.$row->withdraw_set_approver_staff_label)
                : 'ยังไม่กำหนดผู้อนุมัติ';
            $row->can_delete = true;
            return $row;
        });
    }

    private function fetchFinanceReviewersData()
    {
        if (! Schema::hasTable('withdraw_set_finance_reviewer')) {
            return collect();
        }

        $rows = DB::table('withdraw_set_finance_reviewer')
            ->orderBy('withdraw_set_finance_reviewer_name')
            ->orderBy('withdraw_set_finance_reviewer_id')
            ->get();

        return collect($rows)->map(function ($row) {
            $label = trim((string) ($row->withdraw_set_finance_reviewer_name ?? ''));
            $detail = trim((string) ($row->withdraw_set_finance_reviewer_detail ?? ''));
            $staffId = trim((string) ($row->withdraw_set_finance_reviewer_staff_id ?? ''));
            if ($label === '' && $staffId !== '') {
                $label = $staffId;
            }
            $row->withdraw_set_finance_reviewer_name = $label;
            $row->withdraw_set_finance_reviewer_detail = $detail;
            $row->can_delete = true;
            return $row;
        });
    }

    private function saveBookNumber(Request $request): JsonResponse
    {
        if (! Schema::hasTable('withdraw_set_book_number')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางตั้งค่าเลขที่หนังสือ'], 422);
        }

        $id = (int) $request->input('id', 0);
        $type = trim((string) $request->input('type', ''));
        $bookNumber = trim((string) $request->input('book_number', ''));

        if ($type === '') {
            return response()->json(['status' => 'error', 'message' => 'กรุณากรอกประเภทการเบิกจ่าย'], 422);
        }
        if ($bookNumber === '') {
            return response()->json(['status' => 'error', 'message' => 'กรุณากรอกเลขที่หนังสือ'], 422);
        }

        $dup = DB::table('withdraw_set_book_number')
            ->where('withdraw_set_book_number_type', $type)
            ->where('withdraw_set_book_number_value', $bookNumber);
        if ($id > 0) {
            $dup->where('withdraw_set_book_number_id', '!=', $id);
        }
        if ($dup->exists()) {
            return response()->json(['status' => 'error', 'message' => 'ข้อมูลเลขที่หนังสือนี้ซ้ำแล้ว'], 422);
        }

        if ($id > 0) {
            DB::table('withdraw_set_book_number')
                ->where('withdraw_set_book_number_id', $id)
                ->update($this->applyStaffModifyAudit('withdraw_set_book_number', [
                    'withdraw_set_book_number_type' => $type,
                    'withdraw_set_book_number_value' => $bookNumber,
                    'withdraw_set_book_number_date_modify' => date('Y-m-d H:i:s'),
                ]));
        } else {
            $maxCode = (int) (DB::table('withdraw_set_book_number')->max('withdraw_set_book_number_code') ?? 0);
            DB::table('withdraw_set_book_number')
                ->insert($this->applyStaffCreateAudit('withdraw_set_book_number', [
                    'withdraw_set_book_number_code' => $maxCode + 1,
                    'withdraw_set_book_number_type' => $type,
                    'withdraw_set_book_number_value' => $bookNumber,
                    'withdraw_set_book_number_status' => 1,
                    'withdraw_set_book_number_date_create' => date('Y-m-d H:i:s'),
                ]));
        }

        return response()->json(['status' => 'success']);
    }

    private function saveApprover(Request $request): JsonResponse
    {
        if (! Schema::hasTable('withdraw_set_approver')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางตั้งค่าผู้อนุมัติ'], 422);
        }

        $id = (int) $request->input('id', 0);
        $name = trim((string) $request->input('name', ''));
        $staffId = trim((string) $request->input('staff_id', ''));
        $stepOrder = (int) $request->input('step_order', 0);

        if ($name === '') {
            return response()->json(['status' => 'error', 'message' => 'กรุณากรอกชื่อขั้นตอนอนุมัติ'], 422);
        }
        if ($staffId === '') {
            return response()->json(['status' => 'error', 'message' => 'กรุณาเลือกผู้อนุมัติจาก StaffRxList'], 422);
        }
        if ($stepOrder <= 0) {
            return response()->json(['status' => 'error', 'message' => 'กรุณาระบุลำดับอนุมัติให้มากกว่า 0'], 422);
        }

        $hasNotifyColumn = Schema::hasColumn('withdraw_set_approver', 'withdraw_set_approver_notification_enabled');
        $notifyEnabled = (int) $request->input('notify_enabled', 1) === 1 ? 1 : 0;

        $code = trim((string) $request->input('step_code', ''));
        if ($code !== '' && in_array($code, $this->excludedApproverStepCodes, true)) {
            return response()->json(['status' => 'error', 'message' => 'ขั้นตอนนี้กำหนดจากฟอร์มเบิกจ่ายแล้ว ไม่สามารถตั้งค่าที่เมนูนี้ได้'], 422);
        }
        if ($id > 0) {
            $existing = DB::table('withdraw_set_approver')
                ->where('withdraw_set_approver_id', $id)
                ->first();
            if (! $existing) {
                return response()->json(['status' => 'error', 'message' => 'ไม่พบรายการผู้อนุมัติที่ต้องการแก้ไข'], 404);
            }
            if (in_array((string) ($existing->withdraw_set_approver_step_code ?? ''), $this->excludedApproverStepCodes, true)) {
                return response()->json(['status' => 'error', 'message' => 'ขั้นตอนนี้กำหนดจากฟอร์มเบิกจ่ายแล้ว ไม่สามารถแก้ไขที่เมนูนี้ได้'], 422);
            }
            if ($code === '') {
                $code = trim((string) ($existing->withdraw_set_approver_step_code ?? ''));
            }
        }

        if ($code === '') {
            $code = $this->inferApproverStepCodeFromName($name);
        }
        if ($code === '') {
            $code = 'custom_'.time().'_'.mt_rand(100, 999);
        }

        if ($id > 0) {
            $updatePayload = [
                'withdraw_set_approver_step_code' => $code,
                'withdraw_set_approver_step_name' => $name,
                'withdraw_set_approver_step_order' => $stepOrder,
                'withdraw_set_approver_staff_id' => $staffId,
                'withdraw_set_approver_date_modify' => date('Y-m-d H:i:s'),
            ];
            if ($hasNotifyColumn) {
                $updatePayload['withdraw_set_approver_notification_enabled'] = $notifyEnabled;
            }
            DB::table('withdraw_set_approver')
                ->where('withdraw_set_approver_id', $id)
                ->update($this->applyStaffModifyAudit('withdraw_set_approver', $updatePayload));
        } else {
            $insertPayload = [
                'withdraw_set_approver_step_code' => $code,
                'withdraw_set_approver_step_name' => $name,
                'withdraw_set_approver_step_order' => $stepOrder,
                'withdraw_set_approver_staff_id' => $staffId,
                'withdraw_set_approver_status' => 1,
                'withdraw_set_approver_date_create' => date('Y-m-d H:i:s'),
            ];
            if ($hasNotifyColumn) {
                $insertPayload['withdraw_set_approver_notification_enabled'] = $notifyEnabled;
            }
            DB::table('withdraw_set_approver')
                ->insert($this->applyStaffCreateAudit('withdraw_set_approver', $insertPayload));
        }

        $this->syncPendingApprovalStepAssignees($code, $stepOrder, $staffId, (string) ($request->session()->get('staff_id') ?? ''));

        return response()->json(['status' => 'success']);
    }

    private function saveFinanceReviewer(Request $request): JsonResponse
    {
        if (! Schema::hasTable('withdraw_set_finance_reviewer')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางตั้งค่าผู้ตรวจสอบการเงิน'], 422);
        }

        $id = (int) $request->input('id', 0);
        $staffId = trim((string) $request->input('staff_id', ''));
        if ($staffId === '') {
            $staffId = trim((string) $request->input('pay_code', ''));
        }
        if ($staffId === '') {
            $staffId = trim((string) $request->input('source_code', ''));
        }
        if ($staffId === '') {
            return response()->json(['status' => 'error', 'message' => 'กรุณาเลือกผู้ตรวจสอบงานการเงินจาก StaffRxList'], 422);
        }
        $detail = trim((string) $request->input('detail', ''));

        $staffNameMap = $this->resolveStaffNamesByIds([$staffId]);
        $staffLabel = trim((string) ($staffNameMap[$staffId] ?? ''));
        if ($staffLabel === '') {
            $staffLabel = $staffId;
        }

        $duplicateQuery = DB::table('withdraw_set_finance_reviewer')
            ->where('withdraw_set_finance_reviewer_staff_id', $staffId);
        if ($id > 0) {
            $duplicateQuery->where('withdraw_set_finance_reviewer_id', '!=', $id);
        }
        if ($duplicateQuery->exists()) {
            return response()->json(['status' => 'error', 'message' => 'ผู้ตรวจสอบงานการเงินคนนี้ถูกเพิ่มไว้แล้ว'], 422);
        }

        $payload = [
            'withdraw_set_finance_reviewer_staff_id' => $staffId,
            'withdraw_set_finance_reviewer_name' => $staffLabel,
            'withdraw_set_finance_reviewer_detail' => $detail !== '' ? $detail : null,
            'withdraw_set_finance_reviewer_date_modify' => date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            DB::table('withdraw_set_finance_reviewer')
                ->where('withdraw_set_finance_reviewer_id', $id)
                ->update($this->applyStaffModifyAudit('withdraw_set_finance_reviewer', $payload));
        } else {
            $maxCode = (int) (DB::table('withdraw_set_finance_reviewer')->max('withdraw_set_finance_reviewer_code') ?? 0);
            DB::table('withdraw_set_finance_reviewer')
                ->insert($this->applyStaffCreateAudit('withdraw_set_finance_reviewer', array_merge($payload, [
                    'withdraw_set_finance_reviewer_code' => $maxCode + 1,
                    'withdraw_set_finance_reviewer_status' => 1,
                    'withdraw_set_finance_reviewer_date_create' => date('Y-m-d H:i:s'),
                ])));
        }

        return response()->json(['status' => 'success']);
    }

    private function expenseRowIsReferenced(object $row): bool
    {
        $expenseId = (int) ($row->withdraw_set_expenses_id ?? 0);
        if ($expenseId > 0
            && Schema::hasTable('withdraw_item')
            && Schema::hasColumn('withdraw_item', 'withdraw_item_expense_chart_id')
            && DB::table('withdraw_item')->where('withdraw_item_expense_chart_id', $expenseId)->exists()
        ) {
            return true;
        }

        $code = (int) ($row->withdraw_set_expenses_code ?? 0);
        $name = trim((string) ($row->withdraw_set_expenses_name ?? ''));

        $refs = [
            ['table' => 'budget_manage_expenses_sub', 'code_column' => 'budget_expenses_sub_code', 'name_column' => 'budget_expenses_sub_name'],
            ['table' => 'withdraw_manage_expenses_sub', 'code_column' => 'withdraw_expenses_sub_code', 'name_column' => 'withdraw_expenses_sub_name'],
        ];

        foreach ($refs as $ref) {
            $table = $ref['table'];
            if (! Schema::hasTable($table)) {
                continue;
            }

            if ($code > 0 && Schema::hasColumn($table, $ref['code_column'])) {
                if (DB::table($table)->where($ref['code_column'], $code)->exists()) {
                    return true;
                }
            }

            if ($name !== '' && Schema::hasColumn($table, $ref['name_column'])) {
                if (DB::table($table)->where($ref['name_column'], $name)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string, string>|null
     */
    private function resolvePayListSource(): ?array
    {
        try {
            if (Schema::connection('budget')->hasTable('budget_set_pay_list')) {
                return [
                    'connection' => 'budget',
                    'table' => 'budget_set_pay_list',
                    'code_column' => 'budget_pay_code',
                    'name_column' => 'budget_pay_name',
                    'status_column' => 'budget_pay_status',
                    'category_table' => Schema::connection('budget')->hasTable('budget_set_category_pay') ? 'budget_set_category_pay' : '',
                    'category_column' => 'budget_category_pay_code',
                    'category_code_column' => 'budget_category_pay_code',
                    'category_name_column' => 'budget_category_pay_name',
                ];
            }
        } catch (\Throwable $e) {
            // Fallback to local table lookup when budget DB is unavailable.
        }

        if (Schema::hasTable('withdraw_set_pay_list')) {
            return [
                'connection' => config('database.default'),
                'table' => 'withdraw_set_pay_list',
                'code_column' => 'withdraw_pay_code',
                'name_column' => 'withdraw_pay_name',
                'status_column' => 'withdraw_pay_status',
                'category_table' => Schema::hasTable('withdraw_set_category_pay') ? 'withdraw_set_category_pay' : '',
                'category_column' => 'withdraw_category_pay_code',
                'category_code_column' => 'withdraw_category_pay_code',
                'category_name_column' => 'withdraw_category_pay_name',
            ];
        }

        return null;
    }

    private function fetchStaffListOptions()
    {
        $tokenRefreshUrl = trim((string) config('services.staff_rx_api.token_refresh_url', ''));
        $listStaffUrl = trim((string) config('services.staff_rx_api.list_staff_url', ''));
        if ($tokenRefreshUrl === '' || $listStaffUrl === '') {
            return collect();
        }

        try {
            $refresh = DB::connection()->getPdo(); // warm DB connection
            unset($refresh);
            $tokenResponse = \Illuminate\Support\Facades\Http::acceptJson()->timeout(10)->get($tokenRefreshUrl);
            if (! $tokenResponse->successful()) {
                return collect();
            }
            $bearerHeaderToken = trim((string) $tokenResponse->json('token', ''));
            $rawBearerToken = trim((string) $tokenResponse->json('raw_token', ''));
            if ($bearerHeaderToken === '' && $rawBearerToken === '') {
                return collect();
            }

            $http = \Illuminate\Support\Facades\Http::acceptJson()->timeout(20);
            if ($rawBearerToken !== '') {
                $http = $http->withToken($rawBearerToken);
            } elseif (str_starts_with($bearerHeaderToken, 'Bearer ')) {
                $http = $http->withHeaders(['Authorization' => $bearerHeaderToken]);
            } else {
                $http = $http->withToken($bearerHeaderToken);
            }

            $response = $http->get($listStaffUrl);
            if (! $response->successful()) {
                return collect();
            }
            $rows = $response->json('data', []);
            if (! is_array($rows)) {
                return collect();
            }

            return collect($rows)->map(function ($row) {
                if (! is_array($row)) return null;
                $id = trim((string) ($row['id'] ?? $row['staff_id'] ?? $row['code'] ?? ''));
                if ($id === '') return null;
                $name = \App\Support\StaffApiName::composeDisplayName($row);
                if ($name === '') {
                    $name = trim((string) ($row['name'] ?? $row['full_name'] ?? ''));
                }
                $position = trim((string) ($row['name_position'] ?? $row['position'] ?? ''));
                $department = trim((string) ($row['dep_name'] ?? $row['department_name'] ?? ''));
                $job = trim((string) ($row['job_name'] ?? ''));
                $detail = trim(collect([$position, collect([$department, $job])->filter()->implode(' / ')])->filter()->implode(' / '));
                return [
                    'code' => $id,
                    'label' => $detail !== '' ? $name.' - '.$detail : $name,
                ];
            })->filter()->values();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * @param array<int, string> $staffIds
     * @return array<string, string>
     */
    private function resolveStaffNamesByIds(array $staffIds): array
    {
        $staffIds = collect($staffIds)
            ->map(fn ($staffId) => trim((string) $staffId))
            ->filter()
            ->unique()
            ->values();

        if ($staffIds->isEmpty()) {
            return [];
        }

        $options = $this->fetchStaffListOptions();
        if (! $options || $options->isEmpty()) {
            return [];
        }

        $map = [];
        foreach ($options as $option) {
            if (! is_array($option)) {
                continue;
            }
            $code = trim((string) ($option['code'] ?? ''));
            if ($code === '' || ! $staffIds->contains($code)) {
                continue;
            }
            $label = trim((string) ($option['label'] ?? ''));
            if ($label !== '') {
                $map[$code] = $label;
            }
        }

        return $map;
    }

    private function syncPendingApprovalStepAssignees(string $stepCode, int $stepOrder, string $staffId, string $modifiedBy = ''): void
    {
        $normalizedStepCode = trim($stepCode);
        $normalizedStepOrder = (int) $stepOrder;
        $normalizedStaffId = trim($staffId);
        if (($normalizedStepCode === '' && $normalizedStepOrder <= 0) || $normalizedStaffId === '') {
            return;
        }
        if (! Schema::hasTable('withdraw_approval_step') || ! Schema::hasTable('withdraw')) {
            return;
        }

        // อัปเดต step ที่ยังไม่อนุมัติ (pending/ว่าง) โดยไม่ผูกเฉพาะสถานะรายการหลัก
        // เพื่อรองรับข้อมูลเดิมที่สถานะหลักอาจไม่อยู่ในชุดเดิม แต่ step ยังรออนุมัติจริง
        $query = DB::table('withdraw_approval_step')
            ->where(function ($statusQuery) {
                $statusQuery
                    ->where('withdraw_approval_status', 'pending')
                    ->orWhereNull('withdraw_approval_status')
                    ->orWhere('withdraw_approval_status', '');
            });

        $query->where(function ($stepQuery) use ($normalizedStepCode, $normalizedStepOrder) {
            if ($normalizedStepCode !== '') {
                $stepQuery->where('withdraw_approval_step_code', $normalizedStepCode);
            }
            if ($normalizedStepOrder > 0) {
                if ($normalizedStepCode !== '') {
                    $stepQuery->orWhere('withdraw_approval_step_order', $normalizedStepOrder);
                } else {
                    $stepQuery->where('withdraw_approval_step_order', $normalizedStepOrder);
                }
            }
        });

        $query->update([
                'withdraw_approval_staff_id' => $normalizedStaffId,
                'withdraw_approval_date_modify' => now(),
                'staff_modify' => $modifiedBy,
            ]);
    }

    private function inferApproverStepCodeFromName(string $stepName): string
    {
        $name = trim($stepName);
        if ($name === '') {
            return '';
        }

        return match ($name) {
            'หัวหน้างานการเงิน' => 'finance_manager',
            'เลขาคณะ' => 'faculty_secretary',
            'รองคณบดี' => 'vice_dean',
            'คณบดี' => 'dean',
            default => '',
        };
    }

    public function memoTemplatesIndex()
    {
        return view('setting.memo-templates', [
            'title' => 'เทมเพลต (บันทึกข้อความ)',
            'catalog' => WithdrawMemoTemplate::catalog(),
            'blockLabels' => WithdrawMemoTemplate::BLOCK_LABELS,
            'approvalStepOptions' => WithdrawMemoTemplate::approvalStepOptions(),
            'settingsApiBase' => url('/withdraw/setting/api'),
        ]);
    }

    public function apiMemoTemplatesCatalog(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'catalog' => WithdrawMemoTemplate::catalog(),
                'approval_step_options' => WithdrawMemoTemplate::approvalStepOptions(),
                'block_labels' => WithdrawMemoTemplate::BLOCK_LABELS,
            ],
        ]);
    }

    public function apiMemoTemplateResolve(Request $request): JsonResponse
    {
        $kind = WithdrawMemoTemplate::normalizeKind((string) $request->query('kind', 'general'));
        $subType = WithdrawMemoTemplate::normalizeSubType($kind, (string) $request->query('sub_type', ''));

        $template = WithdrawMemoTemplate::resolve($kind, $subType);

        return response()->json([
            'status' => 'success',
            'data' => $template,
        ]);
    }

    public function apiMemoTemplateSave(Request $request): JsonResponse
    {
        if (! Schema::hasTable('withdraw_set_memo_template')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางตั้งค่าเทมเพลตบันทึกข้อความ'], 422);
        }

        $kind = WithdrawMemoTemplate::normalizeKind((string) $request->input('kind', ''));
        $subType = WithdrawMemoTemplate::normalizeSubType($kind, (string) $request->input('sub_type', ''));
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            $subLabels = WithdrawMemoTemplate::SUB_TYPE_LABELS[$kind] ?? [];
            $name = trim((string) ($subLabels[$subType] ?? 'เทมเพลต'));
        }

        $blockOrder = WithdrawMemoTemplate::normalizeBlockOrder($request->input('block_order', []));
        $approvalStepCodes = WithdrawMemoTemplate::normalizeApprovalStepCodes($request->input('approval_step_codes', []));
        $processActorsEnabled = (int) $request->input('process_actors_enabled', 0) === 1;
        $processActors = $processActorsEnabled
            ? WithdrawMemoTemplate::normalizeProcessActors($request->input('process_actors', []))
            : [];

        $payload = [
            'withdraw_set_memo_template_name' => $name,
            'withdraw_set_memo_template_subject' => trim((string) $request->input('subject', '')),
            'withdraw_set_memo_template_to' => trim((string) $request->input('to', '')),
            'withdraw_set_memo_template_paragraph1' => trim((string) $request->input('paragraph1', '')),
            'withdraw_set_memo_template_paragraph2' => trim((string) $request->input('paragraph2', '')),
            'withdraw_set_memo_template_paragraph3' => trim((string) $request->input('paragraph3', '')),
            'withdraw_set_memo_template_block_order' => json_encode($blockOrder, JSON_UNESCAPED_UNICODE),
            'withdraw_set_memo_template_approval_step_codes' => json_encode($approvalStepCodes, JSON_UNESCAPED_UNICODE),
            'withdraw_set_memo_template_process_actors' => json_encode($processActors, JSON_UNESCAPED_UNICODE),
            'withdraw_set_memo_template_date_modify' => date('Y-m-d H:i:s'),
        ];

        if (Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_paragraph4')) {
            $payload['withdraw_set_memo_template_paragraph4'] = trim((string) $request->input('paragraph4', ''));
        }

        if (Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_paragraph_enabled')) {
            $payload['withdraw_set_memo_template_paragraph_enabled'] = json_encode(
                WithdrawMemoTemplate::normalizeParagraphEnabled($request->input('paragraph_enabled', [])),
                JSON_UNESCAPED_UNICODE
            );
        }

        if (Schema::hasColumn('withdraw_set_memo_template', 'withdraw_set_memo_template_process_actors_enabled')) {
            $payload['withdraw_set_memo_template_process_actors_enabled'] = $processActorsEnabled ? 1 : 0;
        }

        $existing = DB::table('withdraw_set_memo_template')
            ->where('withdraw_set_memo_template_kind', $kind)
            ->where('withdraw_set_memo_template_sub_type', $subType)
            ->first();

        if ($existing) {
            DB::table('withdraw_set_memo_template')
                ->where('withdraw_set_memo_template_id', (int) $existing->withdraw_set_memo_template_id)
                ->update($this->applyStaffModifyAudit('withdraw_set_memo_template', $payload));
        } else {
            DB::table('withdraw_set_memo_template')->insert($this->applyStaffCreateAudit('withdraw_set_memo_template', array_merge($payload, [
                'withdraw_set_memo_template_kind' => $kind,
                'withdraw_set_memo_template_sub_type' => $subType,
                'withdraw_set_memo_template_status' => 1,
                'withdraw_set_memo_template_date_create' => date('Y-m-d H:i:s'),
            ])));
        }

        return response()->json([
            'status' => 'success',
            'data' => WithdrawMemoTemplate::resolve($kind, $subType),
        ]);
    }
}
