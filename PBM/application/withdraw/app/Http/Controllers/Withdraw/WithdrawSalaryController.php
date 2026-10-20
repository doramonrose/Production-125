<?php

namespace App\Http\Controllers\Withdraw;

use App\Mail\WithdrawProceedNotificationMail;
use App\Http\Controllers\Controller;
use App\Support\DocumentNumberClient;
use App\Support\EsignAttachedPdf;
use App\Support\OnePlanningClient;
use App\Support\WithdrawApprovalLink;
use App\Support\WithdrawMemoTemplate;
use App\Support\WithdrawSchema;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class WithdrawSalaryController extends Controller
{
    /** @var array<string, array{department_code:string,document_number:string,document_date:string,running_year:int,running_no:int}> */
    private array $withdrawSalaryDocumentMetaCache = [];

    public function index(Request $request)
    {
        $scope = trim((string) ($request->query('scope', $request->route('scope', ''))));
        $approvalScopes = ['reviewer-pending'];
        $isReviewerApprovalMode = in_array($scope, $approvalScopes, true);
        if ($isReviewerApprovalMode) {
            return redirect()->route('withdraw.approval.index', $request->query());
        }
        $resolvedScope = '';
        $currentStaffId = trim((string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? ''));
        $withdrawRows = $this->fetchGeneralRows($resolvedScope, $currentStaffId);

        $targetView = 'withdraw.salary.index';

        return view($targetView, [
            'title' => 'รายการเบิกจ่ายเงินเดือน/ค่าจ้าง',
            'withdrawRows' => $withdrawRows,
            'isReviewerApprovalMode' => $isReviewerApprovalMode,
            'isDocumentReviewMode' => false,
            'generalListApiUrl' => $isReviewerApprovalMode
                ? url('/withdraw/api/salary/list?scope='.rawurlencode($resolvedScope))
                : url('/withdraw/api/salary/list'),
        ]);
    }

    public function apiPayListOptions(): JsonResponse
    {
        $source = $this->resolvePayListSource();
        if ($source === null) {
            return response()->json([
                'status' => 'success',
                'data' => ['pay_lists' => []],
            ]);
        }

        $connection = trim((string) ($source['connection'] ?? config('database.default')));
        $query = DB::connection($connection)->table($source['table'].' as p')
            ->where('p.'.$source['status_column'], 1)
            ->orderBy('p.'.$source['name_column'])
            ->select([
                'p.'.$source['code_column'].' as budget_pay_code',
                'p.'.$source['name_column'].' as budget_pay_name',
            ]);

        if (($source['category_table'] ?? null) && ($source['category_column'] ?? null)) {
            $query->leftJoin($source['category_table'].' as c', 'p.'.$source['category_column'], '=', 'c.'.$source['category_code_column'])
                ->addSelect([
                    'p.'.$source['status_column'].' as budget_pay_status',
                    'p.'.$source['category_column'].' as budget_category_pay_code',
                    'c.'.$source['category_name_column'].' as budget_category_pay_name',
                ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'pay_lists' => $query->get(),
            ],
        ]);
    }

    public function apiDocumentOptions(): JsonResponse
    {
        if (! Schema::hasTable('withdraw_set_document_type') || ! Schema::hasTable('withdraw_set_document_item')) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'types' => [],
                    'items' => [],
                ],
            ]);
        }

        $types = DB::table('withdraw_set_document_type')
            ->where('withdraw_set_document_type_status', 1)
            ->orderBy('withdraw_set_document_type_name')
            ->get([
                'withdraw_set_document_type_code as code',
                'withdraw_set_document_type_name as name',
            ]);

        $items = DB::table('withdraw_set_document_item')
            ->where('withdraw_set_document_item_status', 1)
            ->orderBy('withdraw_set_document_type_code')
            ->orderBy('withdraw_set_document_item_name')
            ->get([
                'withdraw_set_document_item_id as id',
                'withdraw_set_document_type_code as type_code',
                'withdraw_set_document_item_name as name',
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'types' => $types,
                'items' => $items,
            ],
        ]);
    }

    public function apiExpenseChartOptions(): JsonResponse
    {
        if (! Schema::hasTable('withdraw_set_expenses')) {
            return response()->json([
                'status' => 'success',
                'data' => ['expense_charts' => []],
            ]);
        }

        $rows = DB::table('withdraw_set_expenses')
            ->where('withdraw_set_expenses_status', 1)
            ->orderBy('withdraw_set_expenses_name')
            ->get([
                'withdraw_set_expenses_id as id',
                'withdraw_pay_code as pay_code',
                'withdraw_set_expenses_code as expense_code',
                'withdraw_set_expenses_name as name',
                'withdraw_set_expenses_detail as detail',
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'expense_charts' => $rows,
            ],
        ]);
    }

    public function apiBudgetSourceOptions(): JsonResponse
    {
        try {
            if (! Schema::connection('budget')->hasTable('budget_manage')) {
                return response()->json([
                    'status' => 'success',
                    'data' => ['sources' => []],
                ]);
            }

            $rows = DB::connection('budget')
                ->table('budget_manage as bm')
                ->leftJoin('budget_set_source as bs', 'bm.budget_source_code', '=', 'bs.budget_source_code')
                ->leftJoin('budget_set_plan as bp', 'bm.budget_plan_code', '=', 'bp.budget_plan_code')
                ->leftJoin('budget_set_event as be', 'bm.budget_event_code', '=', 'be.budget_event_code')
                ->leftJoin('budget_set_activity as ba', 'bm.budget_activity_code', '=', 'ba.budget_activity_code')
                ->leftJoin('budget_set_fund as bf', 'bm.budget_fund_code', '=', 'bf.budget_fund_code')
                ->leftJoin('budget_set_category_budget as bcb', 'bm.budget_category_budget_code', '=', 'bcb.budget_category_budget_code')
                ->leftJoin('budget_set_category_pay as bcp', 'bm.budget_category_pay_code', '=', 'bcp.budget_category_pay_code')
                ->orderByDesc('bm.budget_manage_id')
                ->get([
                    'bm.budget_manage_id as id',
                    'bm.budget_manage_code as code',
                    'bm.budget_manage_amount as amount',
                    'bm.budget_manage_balance as balance',
                    'bm.budget_source_code as budget_source_code',
                    'bs.budget_source_name',
                    'bm.budget_manage_year',
                    'bp.budget_plan_name',
                    'be.budget_event_name',
                    'ba.budget_activity_name',
                    'bf.budget_fund_name',
                    'bcb.budget_category_budget_name',
                    'bcp.budget_category_pay_name',
                    'bm.budget_category_pay_detail',
                    'bm.budget_category_pay_code as category_pay_code',
                ]);

            $budgetManageIds = collect($rows)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
            $approvedAmountByManageId = [];
            if (count($budgetManageIds) > 0 && Schema::connection('budget')->hasTable('budget_manage_list')) {
                $approvedAmountByManageId = DB::connection('budget')
                    ->table('budget_manage_list')
                    ->whereIn('budget_manage_id', $budgetManageIds)
                    ->where('budget_manage_list_type', 'withdraw')
                    ->groupBy('budget_manage_id')
                    ->selectRaw('budget_manage_id, SUM(budget_manage_list_amount) as approved_amount')
                    ->pluck('approved_amount', 'budget_manage_id')
                    ->map(fn ($amount) => (float) ($amount ?? 0))
                    ->all();
            }
            $approvedAmountFromWithdrawByManageId = [];
            if (
                count($budgetManageIds) > 0
                && Schema::hasTable('withdraw')
                && Schema::hasTable('withdraw_item')
                && Schema::hasColumn('withdraw', 'withdraw_id')
                && Schema::hasColumn('withdraw', 'withdraw_status')
                && Schema::hasColumn('withdraw_item', 'withdraw_id')
                && Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_id')
                && Schema::hasColumn('withdraw_item', 'withdraw_item_amount')
            ) {
                $approvedAmountFromWithdrawByManageId = DB::table('withdraw_item as wgi')
                    ->join('withdraw as wg', 'wg.withdraw_id', '=', 'wgi.withdraw_id')
                    ->whereIn('wgi.withdraw_item_budget_manage_id', $budgetManageIds)
                    ->where('wg.withdraw_status', 'approved')
                    ->groupBy('wgi.withdraw_item_budget_manage_id')
                    ->selectRaw('wgi.withdraw_item_budget_manage_id as budget_manage_id, SUM(COALESCE(NULLIF(wgi.withdraw_item_finance_amount, 0), wgi.withdraw_item_amount)) as approved_amount')
                    ->pluck('approved_amount', 'budget_manage_id')
                    ->map(fn ($amount) => (float) ($amount ?? 0))
                    ->all();
            }

            $sources = collect($rows)->map(function ($row) use ($approvedAmountByManageId, $approvedAmountFromWithdrawByManageId) {
                $activityName = trim((string) ($row->budget_activity_name ?? ''));
                $payDetail = trim((string) ($row->budget_category_pay_detail ?? ''));
                $sourcePath = trim((string) ($row->budget_source_name ?? '')).'ปี '.trim((string) ($row->budget_manage_year ?? ''))
                    .'/'.trim((string) ($row->budget_plan_name ?? ''))
                    .'/'.trim((string) ($row->budget_event_name ?? ''))
                    .($activityName !== '' ? '-'.$activityName : '')
                    .'/'.trim((string) ($row->budget_fund_name ?? ''))
                    .'/'.trim((string) ($row->budget_category_budget_name ?? ''))
                    .'/'.trim((string) ($row->budget_category_pay_name ?? ''))
                    .($payDetail !== '' ? '-'.$payDetail : '');

                $budgetManageId = (int) ($row->id ?? 0);
                $approvedFromBudgetSystem = (float) ($approvedAmountByManageId[$budgetManageId] ?? 0);
                $approvedFromWithdraw = (float) ($approvedAmountFromWithdrawByManageId[$budgetManageId] ?? 0);

                return [
                    'id' => $budgetManageId,
                    'code' => trim((string) ($row->code ?? '')),
                    'year' => trim((string) ($row->budget_manage_year ?? '')),
                    'amount' => (float) ($row->amount ?? 0),
                    'balance' => (float) ($row->balance ?? 0),
                    // Use approved withdraw docs as the primary source for "อนุมัติแล้ว".
                    'approved_amount' => max($approvedFromWithdraw, $approvedFromBudgetSystem),
                    'source_path' => $sourcePath,
                    'budget_source_code' => trim((string) ($row->budget_source_code ?? '')),
                    'budget_source_name' => trim((string) ($row->budget_source_name ?? '')),
                    'category_pay_code' => trim((string) ($row->category_pay_code ?? '')),
                    'category_pay_name' => trim((string) ($row->budget_category_pay_name ?? '')),
                ];
            })->filter(function (array $row) {
                return (int) ($row['id'] ?? 0) > 0 && trim((string) ($row['source_path'] ?? '')) !== '';
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'sources' => $sources,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'success',
                'data' => ['sources' => []],
            ]);
        }
    }

    public function apiSaveDraft(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        $isRevisionUpdateInput = (int) $request->input('is_revision_update', 0) === 1;
        $subject = trim((string) $request->input('subject', ''));
        $departmentCode = trim((string) $request->input('department_code', ''));
        $documentNumber = trim((string) $request->input('document_number', ''));
        $documentDate = trim((string) $request->input('document_date', ''));
        $to = trim((string) $request->input('to', ''));
        $paragraph1 = trim((string) $request->input('paragraph1', ''));
        $paragraph2 = trim((string) $request->input('paragraph2', ''));
        $paragraph3 = trim((string) $request->input('paragraph3', ''));
        $paragraph4 = trim((string) $request->input('paragraph4', ''));
        $applicantStaffId = trim((string) $request->input('applicant_staff_id', ''));
        $salaryRequestType = $this->normalizeSalaryRequestType((string) $request->input('salary_request_type', 'salary_civil_servant'));
        $fiscalYear = $this->resolveFiscalYear($documentDate);
        $rawItems = json_decode((string) $request->input('items', '[]'), true);
        $documents = json_decode((string) $request->input('documents', '[]'), true);
        $processActors = json_decode((string) $request->input('process_actors', '[]'), true);
        if (! is_array($rawItems)) {
            $rawItems = [];
        }
        if (! is_array($documents)) {
            $documents = [];
        }
        if (! is_array($processActors)) {
            $processActors = [];
        }

        if ($subject === '') {
            return response()->json(['status' => 'error', 'message' => 'กรุณาระบุเรื่องก่อนบันทึกแบบร่าง'], 422);
        }
        if (! Schema::hasTable('withdraw')) {
            return response()->json(['status' => 'error', 'message' => 'ยังไม่พร้อมบันทึกแบบร่าง กรุณารัน migrate ก่อนใช้งาน'], 500);
        }
        if (! Schema::hasTable('withdraw_item') || ! Schema::hasTable('withdraw_req_document') || ! Schema::hasTable('withdraw_file') || ! Schema::hasTable('withdraw_approval_step')) {
            return response()->json(['status' => 'error', 'message' => 'ยังไม่พร้อมบันทึกข้อมูล กรุณารัน migrate โครงสร้างใหม่ก่อนใช้งาน'], 500);
        }

        $items = $this->normalizeSalaryDraftItems($rawItems);
        if (count($items) === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาเพิ่มรายการเบิกจ่ายอย่างน้อย 1 รายการ และกรอก งบประมาณ / หมวด / รายการ / จำนวนเงิน ให้ครบ',
            ], 422);
        }

        $budgetBreakdown = $this->buildBudgetBreakdownFromItems($items);
        if (count($budgetBreakdown) === 0) {
            $budgetBreakdown = $this->parseBudgetBreakdownInput($request);
        }
        $budgetManageId = (int) ($budgetBreakdown[0]['budget_manage_id'] ?? 0);
        if ($budgetManageId <= 0) {
            $budgetManageId = (int) ($items[0]['budget_manage_id'] ?? 0);
        }

        $totalAmount = collect($items)->sum(function (array $row) {
            return (float) ($row['amount'] ?? 0);
        });

        $payload = [
            'withdraw_type' => 'salary',
            'withdraw_sub_type' => $salaryRequestType,
            'withdraw_subject' => $subject,
            'withdraw_fiscal_year' => $fiscalYear,
            'withdraw_to' => $to,
            'withdraw_paragraph_1' => $paragraph1,
            'withdraw_paragraph_2' => $paragraph2,
            'withdraw_paragraph_3' => $paragraph3,
            'withdraw_applicant_staff_id' => $applicantStaffId,
            'withdraw_total_amount' => $totalAmount,
            'withdraw_date_modify' => now(),
            'staff_modify' => (string) ($request->session()->get('staff_id') ?? ''),
        ];
        if (Schema::hasColumn('withdraw', 'withdraw_paragraph_4')) {
            $payload['withdraw_paragraph_4'] = $paragraph4 !== '' ? $paragraph4 : null;
        }
        if (Schema::hasColumn('withdraw', 'withdraw_budget_manage_id')) {
            $payload['withdraw_budget_manage_id'] = $budgetManageId > 0 ? $budgetManageId : 0;
        }
        if (Schema::hasColumn('withdraw', 'withdraw_sub_type')) {
            $payload['withdraw_sub_type'] = $salaryRequestType;
        }

        $currentRecordStatus = 'saved';
        if ($id > 0) {
            $currentRecordStatus = trim((string) ($this->withdrawQuery()
                ->where('withdraw_id', $id)
                ->value('withdraw_status') ?? ''));
            if ($currentRecordStatus === '') {
                return response()->json(['status' => 'error', 'message' => 'ไม่พบรายการที่ต้องการแก้ไข'], 404);
            }
            $this->withdrawQuery()->where('withdraw_id', $id)->update($payload);
            $recordId = $id;
        } else {
            $recordId = (int) $this->withdrawQuery()->insertGetId(array_merge($payload, [
                'withdraw_status' => 'saved',
                'withdraw_date_create' => now(),
                'staff_create' => (string) ($request->session()->get('staff_id') ?? ''),
            ]));
            $currentRecordStatus = 'saved';
        }

        $this->upsertWithdrawDocumentMeta(
            $recordId,
            'salary',
            $departmentCode,
            $documentNumber,
            $documentDate
        );

        $sessionStaffId = (string) ($request->session()->get('staff_id') ?? '');
        $this->syncGeneralItems($recordId, $items, $sessionStaffId, $budgetManageId);
        $this->syncSalaryBudgets($recordId, $budgetBreakdown, $sessionStaffId);
        $activeDocumentKeys = $this->syncGeneralDocuments($recordId, is_array($documents) ? $documents : [], $sessionStaffId);
        $this->cleanupGeneralFilesByDocumentKeys($recordId, $activeDocumentKeys);
        $this->deleteGeneralFilesByIds($recordId, $request->input('remove_file_ids', []));

        $editableStatuses = ['saved', 'draft'];
        if (in_array(strtolower($currentRecordStatus), $editableStatuses, true)) {
        $this->syncApprovalSteps(
            $recordId,
            is_array($processActors) ? $processActors : [],
            (string) ($request->session()->get('staff_id') ?? '')
        );
        }

        $travelHandoffToken = trim((string) $request->input('travel_handoff_token', ''));
        if ($travelHandoffToken !== '' && $salaryRequestType === 'travel_leave') {
            \App\Support\TravelWithdrawHandoff::consume($travelHandoffToken, $recordId);
        }

        $uploadedDocuments = [];
        $uploadedRows = $request->file('withdraw_documents', []);
        if (is_array($uploadedRows)) {
            foreach ($uploadedRows as $typeCode => $files) {
                if (! is_array($files)) {
                    continue;
                }
                foreach ($files as $itemCode => $fileOrFiles) {
                    $fileList = is_array($fileOrFiles) ? $fileOrFiles : [$fileOrFiles];
                    foreach ($fileList as $file) {
                        if (! $file || ! $file->isValid()) {
                            continue;
                        }
                        $path = $file->store("withdraw/salary/{$recordId}", 'public');
                        $uploadedDocuments[] = [
                            'type' => (string) $typeCode,
                            'item' => (string) $itemCode,
                            'path' => $path,
                            'name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime' => (string) ($file->getClientMimeType() ?? ''),
                        ];
                    }
                }
            }
        }

        if (count($uploadedDocuments) > 0) {
            foreach ($uploadedDocuments as $uploaded) {
                $typeCode = trim((string) ($uploaded['type'] ?? ''));
                $itemCode = trim((string) ($uploaded['item'] ?? ''));
                $this->insertGeneralFile($recordId, [
                    'type' => $typeCode,
                    'item' => $itemCode,
                    'path' => (string) ($uploaded['path'] ?? ''),
                    'name' => (string) ($uploaded['name'] ?? ''),
                    'size' => (int) ($uploaded['size'] ?? 0),
                    'mime' => (string) ($uploaded['mime'] ?? ''),
                ], $sessionStaffId);
            }
        }

        $isRevisionUpdate = $recordId > 0
            && in_array(strtolower($currentRecordStatus), ['pending_revision'], true)
            && $isRevisionUpdateInput;
        if ($isRevisionUpdate) {
            $executiveRevisionStepCodes = [
                'reviewer_head',
                'additional_reviewer',
                'finance_manager',
                'faculty_secretary',
                'vice_dean',
                'dean',
            ];
            $revisionTargetStaffId = '';
            $revisionComment = '';
            $revisionTargetStatus = 'pending_approval';
            $revisionNotifyActionUrl = WithdrawApprovalLink::nextApprover($recordId, 'salary', 'finance_officer');
            $revisionNotifyMessage = 'ผู้ขอเบิกได้แก้ไขรายการเบิกจ่ายและส่งกลับให้ท่านตรวจสอบแล้ว - '.$subject.' ('.($documentNumber !== '' ? $documentNumber : (string) $recordId).')';
            $revisionLogLabel = 'ผู้ขอแก้ไขและส่งกลับให้การเงินตรวจสอบ';

            if (Schema::hasTable('withdraw_approval_step')) {
                $executiveRevisionStep = DB::table('withdraw_approval_step')
                    ->where('withdraw_id', $recordId)
                    ->whereIn('withdraw_approval_step_code', $executiveRevisionStepCodes)
                    ->where('withdraw_approval_status', 'rejected')
                    ->orderByDesc('withdraw_approval_step_id')
                    ->first([
                        'withdraw_approval_step_code',
                        'withdraw_approval_staff_id',
                        'withdraw_approval_comment',
                        'staff_modify',
                    ]);

                if ($executiveRevisionStep) {
                    $rejectedStepCode = trim((string) ($executiveRevisionStep->withdraw_approval_step_code ?? ''));
                    if ($rejectedStepCode === 'reviewer_head') {
                        $revisionTargetStatus = 'pending_primary_review';
                    } elseif ($rejectedStepCode === 'additional_reviewer') {
                        $revisionTargetStatus = 'pending_additional_review';
                    } else {
                        $revisionTargetStatus = 'pending_approval';
                    }
                    $revisionNotifyActionUrl = WithdrawApprovalLink::nextApprover(
                        $recordId,
                        'salary',
                        $rejectedStepCode
                    );
                    $revisionLogLabel = in_array($rejectedStepCode, ['reviewer_head', 'additional_reviewer'], true)
                        ? 'ผู้ขอแก้ไขและส่งกลับให้ผู้ตรวจสอบ'
                        : 'ผู้ขอแก้ไขและส่งกลับให้อนุมัติ';
                    $revisionTargetStaffId = trim((string) ($executiveRevisionStep->withdraw_approval_staff_id ?? ''));
                    if ($revisionTargetStaffId === '') {
                        $revisionTargetStaffId = trim((string) ($executiveRevisionStep->staff_modify ?? ''));
                    }
                    $revisionComment = trim((string) ($executiveRevisionStep->withdraw_approval_comment ?? ''));
                    $revisionNotifyMessage = 'ผู้ขอเบิกได้แก้ไขรายการเบิกจ่ายและส่งกลับให้ท่านพิจารณาแล้ว - '.$subject.' ('.($documentNumber !== '' ? $documentNumber : (string) $recordId).')';

                    DB::table('withdraw_approval_step')
                        ->where('withdraw_id', $recordId)
                        ->where('withdraw_approval_step_code', $rejectedStepCode)
                        ->when(
                            $rejectedStepCode === 'additional_reviewer' && $revisionTargetStaffId !== '',
                            static function ($query) use ($revisionTargetStaffId) {
                                $query->where('withdraw_approval_staff_id', $revisionTargetStaffId);
                            }
                        )
                        ->update([
                            'withdraw_approval_status' => 'pending',
                            'withdraw_approval_comment' => null,
                            'withdraw_approval_approved_at' => null,
                            'withdraw_approval_date_modify' => now(),
                            'staff_modify' => (string) ($request->session()->get('staff_id') ?? ''),
                        ]);
                } else {
                    $financeStep = DB::table('withdraw_approval_step')
                        ->where('withdraw_id', $recordId)
                        ->where('withdraw_approval_step_code', 'finance_officer')
                        ->where('withdraw_approval_status', 'rejected')
                        ->orderByDesc('withdraw_approval_step_id')
                        ->first([
                            'withdraw_approval_staff_id',
                            'withdraw_approval_comment',
                            'staff_modify',
                        ]);

                    DB::table('withdraw_approval_step')
                        ->where('withdraw_id', $recordId)
                        ->where('withdraw_approval_step_code', 'finance_officer')
                        ->update([
                            'withdraw_approval_status' => 'pending',
                            'withdraw_approval_comment' => null,
                            'withdraw_approval_approved_at' => null,
                            'withdraw_approval_date_modify' => now(),
                            'staff_modify' => (string) ($request->session()->get('staff_id') ?? ''),
                        ]);

                    $revisionTargetStaffId = trim((string) ($financeStep->withdraw_approval_staff_id ?? ''));
                    if ($revisionTargetStaffId === '') {
                        $revisionTargetStaffId = trim((string) ($financeStep->staff_modify ?? ''));
                    }
                    $revisionComment = trim((string) ($financeStep->withdraw_approval_comment ?? ''));
                }
            }

            $this->withdrawQuery()
                ->where('withdraw_id', $recordId)
                ->update([
                    'withdraw_status' => $revisionTargetStatus,
                    'withdraw_date_modify' => now(),
                    'staff_modify' => (string) ($request->session()->get('staff_id') ?? ''),
                ]);

            $financeStaffId = $revisionTargetStaffId;
            $displayCode = $documentNumber !== '' ? $documentNumber : (string) $recordId;
            $amountText = number_format((float) $totalAmount, 2);

            if ($financeStaffId !== '') {
                $createdBy = (string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? 'system');
                $notificationApiUrl = trim((string) config('services.notification_api.url', ''));
                if ($notificationApiUrl !== '') {
                    try {
                        Http::timeout(10)->post($notificationApiUrl, [
                            'staffId' => $financeStaffId,
                            'message' => $revisionNotifyMessage,
                            'systemName' => 'ระบบเบิกจ่าย',
                            'actionUrl' => $revisionNotifyActionUrl,
                            'createdBy' => $createdBy !== '' ? $createdBy : 'system',
                        ]);
                    } catch (\Throwable $e) {
                        // Best effort only.
                    }
                }

                $targetEmail = $this->resolveStaffEmailById($financeStaffId);
                if ($targetEmail !== '') {
                    $staffNameMap = $this->resolveStaffNamesByIds([$financeStaffId, $applicantStaffId]);
                    $recipientName = trim((string) ($staffNameMap[$financeStaffId] ?? $financeStaffId));
                    $applicantText = trim((string) ($staffNameMap[$applicantStaffId] ?? $applicantStaffId));
                    if ($applicantText === '') {
                        $applicantText = '-';
                    }

                    $mailTemplate = new WithdrawProceedNotificationMail([
                        'display_code' => $displayCode,
                        'subject_text' => $subject !== '' ? $subject : 'เรื่อง -',
                        'recipient_name' => $recipientName !== '' ? $recipientName : $financeStaffId,
                        'applicant_text' => $applicantText,
                        'amount_text' => $amountText,
                        'action_url' => $revisionNotifyActionUrl,
                        'mail_subject' => '[ระบบเบิกจ่าย] มีรายการแก้ไขส่งกลับมาตรวจสอบ '.$displayCode,
                        'header_title' => 'แจ้งเตือนรายการเบิกจ่ายเงินเดือน/ค่าจ้าง',
                        'header_description' => 'ผู้ขอเบิกได้แก้ไขรายการและส่งกลับให้ตรวจสอบ',
                        'status_badge' => 'รอการเงินตรวจสอบเอกสาร',
                        'intro_message' => 'มีรายการที่แก้ไขแล้ว รอการตรวจสอบจากท่าน',
                        'button_label' => 'เปิดรายการเพื่อตรวจสอบ',
                        'extra_note' => $revisionComment !== '' ? 'หมายเหตุการส่งกลับก่อนหน้า: '.$revisionComment : '',
                    ]);

                    $smtpHost = trim((string) env('MAIL_HOST', ''));
                    $smtpPort = (int) env('MAIL_PORT', 587);
                    $smtpUsername = trim((string) env('MAIL_USERNAME', ''));
                    $smtpPassword = (string) env('MAIL_PASSWORD', '');
                    $smtpFromAddress = trim((string) env('MAIL_FROM_ADDRESS', ''));
                    $smtpFromName = trim((string) env('MAIL_FROM_NAME', (string) config('app.name', 'PBM Withdraw')));

                    if ($smtpHost !== '' && $smtpFromAddress !== '') {
                        $mailer = new PHPMailer(true);
                        try {
                            $mailer->CharSet = 'UTF-8';
                            $mailer->Encoding = 'base64';
                            $mailer->isSMTP();
                            $mailer->Host = $smtpHost;
                            $mailer->SMTPAuth = true;
                            $mailer->Username = $smtpUsername;
                            $mailer->Password = $smtpPassword;
                            $mailer->Port = $smtpPort > 0 ? $smtpPort : 587;
                            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mailer->SMTPAutoTLS = true;
                            $mailer->SMTPOptions = [
                                'ssl' => [
                                    'verify_peer' => false,
                                    'verify_peer_name' => false,
                                    'allow_self_signed' => true,
                                ],
                            ];
                            $mailer->setFrom($smtpFromAddress, $smtpFromName !== '' ? $smtpFromName : 'PBM Withdraw');
                            $mailer->addAddress($targetEmail);
                            $mailer->Subject = $mailTemplate->subjectLine();
                            $mailer->Body = $mailTemplate->render();
                            $mailer->AltBody = $mailTemplate->plainMessage();
                            $mailer->isHTML(true);
                            $mailer->send();
                        } catch (PHPMailerException $e) {
                            // Best effort only.
                        }
                    }
                }
            }
        }

        $this->appendGeneralActionLog(
            $recordId,
            $isRevisionUpdate ? 'submit_revision_update' : 'save_draft',
            $isRevisionUpdate ? ($revisionLogLabel ?? 'ผู้ขอแก้ไขและส่งกลับให้การเงินตรวจสอบ') : 'บันทึกแบบร่าง',
            $currentRecordStatus,
            $isRevisionUpdate ? ($revisionTargetStatus ?? 'pending_approval') : 'saved',
            $sessionStaffId,
            ''
        );

        return response()->json([
            'status' => 'success',
            'message' => $isRevisionUpdate
                ? (($revisionTargetStatus ?? 'pending_approval') === 'pending_primary_review'
                    ? 'บันทึกการแก้ไขและส่งให้ผู้อนุมัติตรวจสอบแล้ว'
                    : 'บันทึกการแก้ไขและส่งให้การเงินตรวจสอบแล้ว')
                : 'บันทึกแบบร่างสำเร็จ',
            'data' => [
                'id' => $recordId,
                'is_revision_update' => $isRevisionUpdate,
            ],
        ]);
    }

    public function apiDetail(string $id): JsonResponse
    {
        if (! Schema::hasTable('withdraw')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางข้อมูล'], 500);
        }

        $row = $this->withdrawQuery()->where('withdraw_id', (int) $id)->first();
        if (! $row) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูล'], 404);
        }

        $generalId = (int) ($row->withdraw_id ?? 0);
        $processActors = $this->loadGeneralAdditionalReviewers($generalId);
        $approvalSteps = $this->filterSalaryApprovalRouteSteps($this->loadGeneralApprovalSteps($generalId));
        $financeManagerSigner = $this->resolveSalaryFinanceManagerSignerFromSteps($approvalSteps);
        $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
        $financeManagerStaffId = trim((string) ($financeManagerSigner['staff_id'] ?? ''));
        $staffIdsForSignatures = collect($processActors)
            ->pluck('staff_id')
            ->push($applicantStaffId)
            ->push($financeManagerStaffId)
            ->map(fn ($staffId) => trim((string) $staffId))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $signatureMap = $this->resolveStaffSignaturesByIds($staffIdsForSignatures);
        $signatureRawUrlMap = $this->resolveStaffSignatureRawUrlsByIds($staffIdsForSignatures);
        $staffNameMap = $this->resolveStaffNamesByIds(array_values(array_filter([$applicantStaffId, $financeManagerStaffId])));
        $staffPositionMap = $this->resolveStaffPositionsByIds(array_values(array_filter([$applicantStaffId, $financeManagerStaffId])));
        $revisionComment = '';
        if (Schema::hasTable('withdraw_approval_step')) {
            $revisionComment = trim((string) (DB::table('withdraw_approval_step')
                ->where('withdraw_id', $generalId)
                ->where('withdraw_approval_step_code', 'finance_officer')
                ->where('withdraw_approval_status', 'rejected')
                ->orderByDesc('withdraw_approval_step_id')
                ->value('withdraw_approval_comment') ?? ''));
        }
        $buildSignatureProxyUrl = static function (string $staffId): string {
            $normalizedStaffId = trim((string) $staffId);
            if ($normalizedStaffId === '') {
                return '';
            }

            return '/withdraw/api/general/signature-proxy?staff_id='.rawurlencode($normalizedStaffId);
        };

        $salaryBudgetBreakdown = $this->loadSalaryBudgets($generalId);

        $documentMeta = $this->resolveWithdrawGeneralDocumentMeta($row);
        $financeDocumentMeta = $this->resolveWithdrawDocumentMetaByType($generalId, 'salary_finance');
        $financeDocumentRaw = trim((string) ($financeDocumentMeta['document_number'] ?? ''));
        $financeRunningNo = (int) ($financeDocumentMeta['running_no'] ?? 0);
        $financeDocumentBase = $this->resolveFinanceDocumentNumberBaseForApi($financeDocumentRaw, $financeRunningNo);
        $financeDocumentDisplay = $financeDocumentBase;
        if ($financeDocumentBase !== '' && $financeRunningNo > 0) {
            $financeDocumentDisplay = $financeDocumentBase.$financeRunningNo;
        }

        $partitionedDocuments = EsignAttachedPdf::partitionFinanceEsignDocuments(
            $this->loadGeneralDocuments($generalId)
        );
        $partitionedFiles = EsignAttachedPdf::partitionFinanceEsignUploadedFiles(
            $this->loadGeneralUploadedFiles($generalId)
        );
        $departmentRef = $this->loadDepartmentApiReferenceMaps();
        $departmentCode = (string) ($documentMeta['department_code'] ?? '');
        $departmentDisplay = $this->resolveWithdrawGeneralDepartmentDisplay(
            $departmentCode,
            $departmentRef['name'],
            $departmentRef['meta'],
            [],
            false,
            true
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $generalId,
                'raw_status' => (string) ($row->withdraw_status ?? 'saved'),
                'status' => $this->mapWithdrawStatusLabel((string) ($row->withdraw_status ?? 'saved')),
                'budget_manage_id' => Schema::hasColumn('withdraw', 'withdraw_budget_manage_id')
                    ? (int) ($row->withdraw_budget_manage_id ?? 0)
                    : 0,
                'salary_request_type' => Schema::hasColumn('withdraw', 'withdraw_sub_type')
                    ? $this->normalizeSalaryRequestType((string) ($row->withdraw_sub_type ?? 'salary'))
                    : 'salary',
                'budget_breakdown' => $salaryBudgetBreakdown,
                'budget_manage_ids' => collect($salaryBudgetBreakdown)
                    ->pluck('budget_manage_id')
                    ->filter(fn ($budgetId) => (int) $budgetId > 0)
                    ->values()
                    ->all(),
                'revision_comment' => $revisionComment,
                'department_code' => $departmentCode,
                'department_display' => $departmentDisplay,
                'document_number' => (string) ($documentMeta['document_number'] ?? ''),
                'running_no' => (int) ($documentMeta['running_no'] ?? 0),
                'document_number_display' => $this->resolveWithdrawGeneralDisplayDocumentCode($row),
                'pts_document_number_display' => $this->salaryPtsUsesDualDocumentNumbers(
                    Schema::hasColumn('withdraw', 'withdraw_sub_type')
                        ? (string) ($row->withdraw_sub_type ?? 'salary')
                        : 'salary'
                )
                    ? $this->resolveSalaryPtsSecondDisplayDocumentCode($row)
                    : '',
                'document_date' => (string) ($documentMeta['document_date'] ?? ''),
                'subject' => (string) ($row->withdraw_subject ?? ''),
                'finance_document_number_base' => $financeDocumentBase,
                'finance_document_running_no' => $financeRunningNo,
                'finance_document_number_display' => $financeDocumentDisplay,
                // Finance document number is stored on withdraw_document (type general_finance)
                'finance_document_number' => $financeDocumentDisplay,
                'finance_document_date' => (string) ($row->withdraw_finance_document_date ?? ''),
                'finance_subject' => (string) ($row->withdraw_finance_subject ?? ''),
                'finance_to' => (string) ($row->withdraw_finance_to ?? ''),
                'finance_paragraph1' => (string) ($row->withdraw_finance_paragraph_1 ?? ''),
                'finance_paragraph2' => (string) ($row->withdraw_finance_paragraph_2 ?? ''),
                'finance_paragraph3' => (string) ($row->withdraw_finance_paragraph_3 ?? ''),
                'total_amount' => (float) ($row->withdraw_total_amount ?? 0),
                'to' => (string) ($row->withdraw_to ?? ''),
                'paragraph1' => (string) ($row->withdraw_paragraph_1 ?? ''),
                'paragraph2' => (string) ($row->withdraw_paragraph_2 ?? ''),
                'paragraph3' => (string) ($row->withdraw_paragraph_3 ?? ''),
                'paragraph4' => Schema::hasColumn('withdraw', 'withdraw_paragraph_4')
                    ? trim((string) ($row->withdraw_paragraph_4 ?? ''))
                    : '',
                'paragraph_enabled' => WithdrawMemoTemplate::paragraphEnabledForWithdraw($row),
                'block_order' => WithdrawMemoTemplate::blockOrderForWithdraw($row),
                'applicant_staff_id' => $applicantStaffId,
                'applicant_name' => (string) ($staffNameMap[$applicantStaffId] ?? ''),
                'traveler_names' => ($this->normalizeSalaryRequestType((string) ($row->withdraw_sub_type ?? '')) === 'travel_leave')
                    ? \App\Support\TravelWithdrawHandoff::travelerNamesForWithdraw($generalId)
                    : [],
                'travel_supporting_files' => ($this->normalizeSalaryRequestType((string) ($row->withdraw_sub_type ?? '')) === 'travel_leave')
                    ? \App\Support\TravelWithdrawHandoff::supportingFilesForWithdraw($generalId)
                    : [],
                'applicant_signature_base64' => (string) ($signatureMap[$applicantStaffId] ?? ''),
                'applicant_signature_proxy_url' => $buildSignatureProxyUrl($applicantStaffId),
                'applicant_signature_url' => (string) ($signatureMap[$applicantStaffId] ?? ''),
                'applicant_signature_status' => '',
                'applicant_signature_raw_url' => (string) ($signatureRawUrlMap[$applicantStaffId] ?? ''),
                'reviewer_staff_id' => $financeManagerStaffId,
                'reviewer_name' => (string) ($staffNameMap[$financeManagerStaffId] ?? ''),
                'reviewer_position' => trim((string) ($staffPositionMap[$financeManagerStaffId] ?? '')),
                'reviewer_signature_base64' => (string) ($signatureMap[$financeManagerStaffId] ?? ''),
                'reviewer_signature_proxy_url' => $buildSignatureProxyUrl($financeManagerStaffId),
                'reviewer_signature_url' => (string) ($signatureMap[$financeManagerStaffId] ?? ''),
                'reviewer_signature_status' => '',
                'reviewer_signature_raw_url' => (string) ($signatureRawUrlMap[$financeManagerStaffId] ?? ''),
                'finance_manager_step_status' => (string) ($financeManagerSigner['step_status'] ?? ''),
                'finance_manager_approved' => strtolower((string) ($financeManagerSigner['step_status'] ?? '')) === 'approved',
                'items' => $this->loadGeneralItems($generalId),
                'documents' => $partitionedDocuments['documents'],
                'uploaded_files' => $partitionedFiles['uploaded_files'],
                'finance_esign_files' => $partitionedFiles['finance_esign_files'],
                'approval_steps' => $approvalSteps,
                'approval_step_history' => $this->loadApprovalStepHistory($generalId),
                'action_logs' => $this->loadGeneralActionLogs($generalId),
                'process_actors' => collect($processActors)->map(function (array $actor) use ($signatureMap, $signatureRawUrlMap) {
                    $staffId = trim((string) ($actor['staff_id'] ?? ''));
                    $actor['signature_base64'] = (string) ($signatureMap[$staffId] ?? '');
                    $actor['signature_proxy_url'] = $staffId !== '' ? '/withdraw/api/general/signature-proxy?staff_id='.rawurlencode($staffId) : '';
                    $actor['signature_url'] = (string) ($signatureMap[$staffId] ?? '');
                    $actor['signature_status'] = '';
                    $actor['signature_raw_url'] = (string) ($signatureRawUrlMap[$staffId] ?? '');
                    return $actor;
                })->values()->all(),
            ],
        ]);
    }

    public function memoPdf(string $id, Request $request)
    {
        $fontStoragePath = storage_path('fonts');
        if (! File::exists($fontStoragePath)) {
            File::makeDirectory($fontStoragePath, 0775, true);
        }

        if (! Schema::hasTable('withdraw')) {
            abort(500, 'ไม่พบตารางข้อมูล');
        }

        $row = $this->withdrawQuery()->where('withdraw_id', (int) $id)->first();
        if (! $row) {
            abort(404, 'ไม่พบข้อมูล');
        }

        $isTravelMemo = Schema::hasColumn('withdraw', 'withdraw_sub_type')
            && $this->normalizeSalaryRequestType((string) ($row->withdraw_sub_type ?? '')) === 'travel_leave';
        if ($isTravelMemo && $request->routeIs('withdraw.salary.memo-pdf')) {
            return redirect()->route('withdraw.travel.memo-pdf', ['id' => (int) $id] + $request->query());
        }
        if (! $isTravelMemo && $request->routeIs('withdraw.travel.memo-pdf')) {
            return redirect()->route('withdraw.salary.memo-pdf', ['id' => (int) $id] + $request->query());
        }

        $generalId = (int) ($row->withdraw_id ?? 0);
        $processActors = $this->loadGeneralAdditionalReviewers($generalId);
        $approvalSteps = $this->filterSalaryApprovalRouteSteps($this->loadGeneralApprovalSteps($generalId));
        $financeManagerSigner = $this->resolveSalaryFinanceManagerSignerFromSteps($approvalSteps);
        $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
        $financeManagerStaffId = trim((string) ($financeManagerSigner['staff_id'] ?? ''));
        $actorStaffIds = collect($processActors)
            ->pluck('staff_id')
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->values()
            ->all();
        $deanSignerPreview = $this->resolveSalaryMemoDeanSigner($approvalSteps);
        $deanStaffIdPreview = trim((string) ($deanSignerPreview['staff_id'] ?? ''));
        $allStaffIdsForMemoRoles = array_values(array_unique(array_filter(array_merge(
            [$applicantStaffId, $deanStaffIdPreview, $financeManagerStaffId],
            $actorStaffIds
        ))));
        $salarySubType = Schema::hasColumn('withdraw', 'withdraw_sub_type')
            ? $this->normalizeSalaryRequestType((string) ($row->withdraw_sub_type ?? 'salary'))
            : 'salary_civil_servant';
        $staffNameMap = $this->resolveStaffNamesByIds($allStaffIdsForMemoRoles);
        $staffPositionMap = $this->resolveStaffPositionsByIds($allStaffIdsForMemoRoles);
        $deanSigner = $this->resolveSalaryMemoDeanSigner($approvalSteps, $staffNameMap);
        $deanStaffId = trim((string) ($deanSigner['staff_id'] ?? ''));
        $deanStepStatus = trim((string) ($deanSigner['status'] ?? ''));
        $deanStepName = trim((string) ($deanSigner['step_name'] ?? ''));
        $deanDisplayName = trim((string) ($deanSigner['name'] ?? ''));
        $deanAdminPositionMap = $deanStaffId !== ''
            ? $this->resolveStaffAdminPositionsByIds([$deanStaffId])
            : [];
        $deanDisplayPosition = trim((string) ($deanAdminPositionMap[$deanStaffId] ?? ''));
        if ($deanDisplayPosition === '') {
            $deanDisplayPosition = $deanStepName !== '' ? $deanStepName : 'คณบดีคณะเภสัชศาสตร์';
        }
        $deanApproved = (bool) ($deanSigner['approved'] ?? false);
        $deanApprovedAt = $deanApproved ? trim((string) ($deanSigner['approved_at'] ?? '')) : '';
        $travelHangingStaffIds = [];
        if ($salarySubType === 'travel_leave') {
            foreach (['finance_manager', 'faculty_secretary'] as $hangingCode) {
                foreach ($approvalSteps as $approvalStep) {
                    if (! is_array($approvalStep) || trim((string) ($approvalStep['step_code'] ?? '')) !== $hangingCode) {
                        continue;
                    }
                    if (strtolower(trim((string) ($approvalStep['status'] ?? ''))) !== 'approved') {
                        continue;
                    }
                    $hangingStaffId = trim((string) ($approvalStep['staff_id'] ?? ''));
                    if ($hangingStaffId !== '') {
                        $travelHangingStaffIds[] = $hangingStaffId;
                    }
                }
            }
        }
        $staffIdsForSignatures = collect($processActors)
            ->pluck('staff_id')
            ->push($applicantStaffId)
            ->push($deanStaffId)
            ->push($financeManagerStaffId)
            ->merge($travelHangingStaffIds)
            ->map(fn ($staffId) => trim((string) $staffId))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $signatureMap = $this->resolveStaffSignaturesByIds($staffIdsForSignatures);
        $signatureRawUrlMap = $this->resolveStaffSignatureRawUrlsByIds($staffIdsForSignatures);
        $travelHangingSigners = [];
        if ($salarySubType === 'travel_leave') {
            foreach (['finance_manager', 'faculty_secretary'] as $hangingCode) {
                foreach ($approvalSteps as $approvalStep) {
                    if (! is_array($approvalStep) || trim((string) ($approvalStep['step_code'] ?? '')) !== $hangingCode) {
                        continue;
                    }
                    if (strtolower(trim((string) ($approvalStep['status'] ?? ''))) !== 'approved') {
                        continue;
                    }
                    $hangingStaffId = trim((string) ($approvalStep['staff_id'] ?? ''));
                    $hangingSigUri = trim((string) ($signatureMap[$hangingStaffId] ?? ''));
                    if ($hangingStaffId === '' || $hangingSigUri === '' || str_starts_with($hangingSigUri, 'data:image/svg+xml')) {
                        continue;
                    }
                    $travelHangingSigners[] = ['sig_uri' => $hangingSigUri];
                }
            }
        }
        $revisionComment = '';
        if (Schema::hasTable('withdraw_approval_step')) {
            $revisionComment = trim((string) (DB::table('withdraw_approval_step')
                ->where('withdraw_id', $generalId)
                ->where('withdraw_approval_step_code', 'finance_officer')
                ->where('withdraw_approval_status', 'rejected')
                ->orderByDesc('withdraw_approval_step_id')
                ->value('withdraw_approval_comment') ?? ''));
        }
        $buildSignatureProxyUrl = static function (string $staffId): string {
            $normalizedStaffId = trim((string) $staffId);
            if ($normalizedStaffId === '') {
                return '';
            }

            return '/withdraw/api/general/signature-proxy?staff_id='.rawurlencode($normalizedStaffId);
        };

        $documentMeta = $this->resolveWithdrawGeneralDocumentMeta($row);

        $detail = [
            'id' => $generalId,
            'raw_status' => (string) ($row->withdraw_status ?? 'saved'),
            'status' => $this->mapWithdrawStatusLabel((string) ($row->withdraw_status ?? 'saved')),
            'revision_comment' => $revisionComment,
            'department_code' => (string) ($documentMeta['department_code'] ?? ''),
            'document_number' => (string) ($documentMeta['document_number'] ?? ''),
            'document_date' => (string) ($documentMeta['document_date'] ?? ''),
            'subject' => (string) ($row->withdraw_subject ?? ''),
            'total_amount' => (float) ($row->withdraw_total_amount ?? 0),
            'to' => (string) ($row->withdraw_to ?? ''),
            'paragraph1' => (string) ($row->withdraw_paragraph_1 ?? ''),
            'paragraph2' => (string) ($row->withdraw_paragraph_2 ?? ''),
            'paragraph3' => (string) ($row->withdraw_paragraph_3 ?? ''),
            'paragraph4' => Schema::hasColumn('withdraw', 'withdraw_paragraph_4')
                ? trim((string) ($row->withdraw_paragraph_4 ?? ''))
                : '',
            'paragraph_enabled' => WithdrawMemoTemplate::paragraphEnabledForWithdraw($row),
            'applicant_staff_id' => $applicantStaffId,
            'applicant_name' => (string) ($staffNameMap[$applicantStaffId] ?? ''),
            'traveler_names' => $salarySubType === 'travel_leave'
                ? \App\Support\TravelWithdrawHandoff::travelerNamesForWithdraw($generalId)
                : [],
            'applicant_position' => trim((string) ($staffPositionMap[$applicantStaffId] ?? '')),
            'applicant_signature_base64' => (string) ($signatureMap[$applicantStaffId] ?? ''),
            'applicant_signature_proxy_url' => $buildSignatureProxyUrl($applicantStaffId),
            'applicant_signature_url' => (string) ($signatureMap[$applicantStaffId] ?? ''),
            'applicant_signature_status' => '',
            'applicant_signature_raw_url' => (string) ($signatureRawUrlMap[$applicantStaffId] ?? ''),
            'travel_hanging_signers' => $travelHangingSigners,
            'reviewer_staff_id' => $financeManagerStaffId,
            'reviewer_name' => (string) ($staffNameMap[$financeManagerStaffId] ?? ''),
            'reviewer_position' => trim((string) ($staffPositionMap[$financeManagerStaffId] ?? '')),
            'reviewer_signature_base64' => (string) ($signatureMap[$financeManagerStaffId] ?? ''),
            'reviewer_signature_proxy_url' => $buildSignatureProxyUrl($financeManagerStaffId),
            'reviewer_signature_url' => (string) ($signatureMap[$financeManagerStaffId] ?? ''),
            'reviewer_signature_status' => '',
            'reviewer_signature_raw_url' => (string) ($signatureRawUrlMap[$financeManagerStaffId] ?? ''),
            'dean_staff_id' => $deanStaffId,
            'dean_name' => $deanDisplayName,
            'dean_position' => $deanDisplayPosition,
            'dean_signature_base64' => (string) ($signatureMap[$deanStaffId] ?? ''),
            'dean_signature_proxy_url' => $buildSignatureProxyUrl($deanStaffId),
            'dean_signature_url' => (string) ($signatureMap[$deanStaffId] ?? ''),
            'dean_signature_status' => '',
            'dean_signature_raw_url' => (string) ($signatureRawUrlMap[$deanStaffId] ?? ''),
            'dean_step_status' => $deanStepStatus,
            'dean_step_name' => $deanStepName,
            'dean_approved' => $deanApproved,
            'dean_approved_at' => $deanApprovedAt,
            'finance_manager_step_status' => (string) ($financeManagerSigner['step_status'] ?? ''),
            'finance_manager_approved' => strtolower((string) ($financeManagerSigner['step_status'] ?? '')) === 'approved',
            'items' => $this->loadGeneralItems($generalId),
            'documents' => $this->loadGeneralDocuments($generalId),
            'uploaded_files' => $this->loadGeneralUploadedFiles($generalId),
            'approval_steps' => $approvalSteps,
            'approval_step_history' => $this->loadApprovalStepHistory($generalId),
            'action_logs' => $this->loadGeneralActionLogs($generalId),
            'process_actors' => collect($processActors)->map(function (array $actor) use ($signatureMap, $signatureRawUrlMap, $staffNameMap) {
                $staffId = trim((string) ($actor['staff_id'] ?? ''));
                $actor['staff_name'] = (string) ($staffNameMap[$staffId] ?? '');
                $actor['signature_base64'] = (string) ($signatureMap[$staffId] ?? '');
                $actor['signature_proxy_url'] = $staffId !== '' ? '/withdraw/api/general/signature-proxy?staff_id='.rawurlencode($staffId) : '';
                $actor['signature_url'] = (string) ($signatureMap[$staffId] ?? '');
                $actor['signature_status'] = '';
                $actor['signature_raw_url'] = (string) ($signatureRawUrlMap[$staffId] ?? '');
                return $actor;
            })->values()->all(),
        ];

        $departmentRef = $this->loadDepartmentApiReferenceMaps();
        $detail['department_display'] = $this->resolveWithdrawGeneralDepartmentDisplay(
            (string) ($detail['department_code'] ?? ''),
            $departmentRef['name'],
            $departmentRef['meta'],
            [],
            true
        );
        $detail['department_phone'] = $this->resolveWithdrawGeneralDepartmentPhone(
            (string) ($detail['department_code'] ?? ''),
            $departmentRef['meta']
        );

        $detail['document_number_display'] = $this->resolveWithdrawGeneralDisplayDocumentCode($row);

        $filenameDocumentNo = trim((string) ($detail['document_number_display'] ?? $detail['document_number'] ?? ''));
        $filenameSubject = trim((string) ($detail['subject'] ?? ''));
        $filenameParts = array_values(array_filter([$filenameDocumentNo, $filenameSubject], static fn ($part) => $part !== ''));
        $filenameCode = count($filenameParts) > 0 ? implode(' - ', $filenameParts) : 'withdraw-general-'.$generalId;
        $safeFilenameCode = trim((string) preg_replace('/[\\\\\/:\*\?"<>\|\x00-\x1F]+/u', '-', $filenameCode), " .-\t\n\r\0\x0B");
        if ($safeFilenameCode === '') {
            $safeFilenameCode = 'withdraw-general-'.$generalId;
        }
        $itemsList = is_array($detail['items'] ?? null) ? $detail['items'] : [];
        $itemsTotalAmount = collect($itemsList)->sum(static function ($row) {
            return is_array($row) ? (float) ($row['amount'] ?? 0) : 0.0;
        });
        // Match app_mis/bills approach: use mPDF for Thai diacritics correctness.
        $memoKind = $salarySubType === 'travel_leave' ? 'travel' : 'salary';
        $memoSubType = $salarySubType === 'travel_leave' ? 'default' : $salarySubType;
        $memoTemplate = WithdrawMemoTemplate::resolve($memoKind, $memoSubType);
        $memoBlockOrder = is_array($memoTemplate['block_order'] ?? null) && $memoTemplate['block_order'] !== []
            ? $memoTemplate['block_order']
            : ['paragraph1', 'items', 'paragraph2', 'paragraph3', 'paragraph4'];
        $memoBlockOrder = WithdrawMemoTemplate::filterEnabledBlocks(
            $memoBlockOrder,
            WithdrawMemoTemplate::normalizeParagraphEnabled($memoTemplate['paragraph_enabled'] ?? null)
        );

        $memoParagraph2BudgetLines = $this->buildMemoParagraph2BudgetLines($this->loadSalaryBudgets($generalId));
        $memoCopies = $this->buildSalaryMemoPdfCopies($row, $salarySubType);
        $requestedCopy = (int) $request->query('copy', 0);
        if ($requestedCopy === 1 || $requestedCopy === 2) {
            $memoCopies = array_values(array_filter(
                $memoCopies,
                static fn (array $copy) => (int) ($copy['copy_index'] ?? 0) === $requestedCopy
            ));
            if ($memoCopies === []) {
                abort(404, 'ไม่พบบันทึกข้อความฉบับที่ร้องขอ');
            }
            $selectedDisplay = trim((string) ($memoCopies[0]['document_number_display'] ?? ''));
            if ($selectedDisplay !== '') {
                $filenameDocumentNo = $selectedDisplay;
                $filenameParts = array_values(array_filter([$filenameDocumentNo, $filenameSubject], static fn ($part) => $part !== ''));
                $filenameCode = count($filenameParts) > 0 ? implode(' - ', $filenameParts) : 'withdraw-general-'.$generalId;
                $safeFilenameCode = trim((string) preg_replace('/[\\\\\/:\*\?"<>\|\x00-\x1F]+/u', '-', $filenameCode), " .-\t\n\r\0\x0B");
                if ($safeFilenameCode === '') {
                    $safeFilenameCode = 'withdraw-general-'.$generalId;
                }
            }
        }
        $memoDate = $deanApprovedAt !== '' ? $deanApprovedAt : (string) ($detail['document_date'] ?? '');
        $html = view('withdraw.salary.pdf.memo', [
            'detail' => $detail,
            'memoBlockOrder' => $memoBlockOrder,
            'memoParagraph2BudgetLines' => $memoParagraph2BudgetLines,
            'memoCopies' => $memoCopies,
            'memoBodyView' => $this->resolveSalaryMemoPdfBodyView($salarySubType),
            'salaryRequestType' => $salarySubType,
            'salaryRequestTypeLabel' => $this->mapSalaryRequestTypeLabel($salarySubType),
            'thaiDate' => \App\Support\ThaiDigits::inString($this->formatThaiDateDetailModalStyle($memoDate)),
            'totalText' => $this->toThaiBahtText((float) ($detail['total_amount'] ?? 0)),
            'itemsTotalAmount' => $itemsTotalAmount,
            'itemsTotalText' => $this->toThaiBahtText($itemsTotalAmount),
            'additionalSignerCards' => $this->buildMemoAdditionalSignerCards($detail, $staffNameMap, $staffPositionMap),
        ])->render();

        $tempDir = storage_path('framework/cache/mpdf');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();

        $fontDir = array_merge($defaultConfig['fontDir'], [
            storage_path('fonts'),
        ]);

        $fontData = ($defaultFontConfig['fontdata'] ?? []) + [
            'thsarabunnew' => [
                'R' => 'THSarabunNew.ttf',
                'B' => 'THSarabunNew-Bold.ttf',
            ],
        ];

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDir,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 14,
            'margin_footer' => 2,
            'fontDir' => $fontDir,
            'fontdata' => $fontData,
            'default_font' => 'thsarabunnew',
            'setAutoBottomMargin' => false,
        ]);

        $mpdf->WriteHTML($html);
        $output = $mpdf->Output('', 'S');

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $safeFilenameCode . '.pdf"',
        ]);
    }

    public function apiFile(string $id, Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse|\Illuminate\Http\Response
    {
        if (! Schema::hasTable('withdraw')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางข้อมูล'], 500);
        }

        $row = $this->withdrawQuery()->where('withdraw_id', (int) $id)->first();
        if (! $row) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูล'], 404);
        }

        $fileId = (int) $request->query('file_id', 0);
        $type = trim((string) $request->query('type', ''));
        $item = trim((string) $request->query('item', ''));
        if ($fileId <= 0 && ($type === '' || $item === '')) {
            return response()->json(['status' => 'error', 'message' => 'ข้อมูลไฟล์ไม่ครบถ้วน'], 422);
        }

        if (! Schema::hasTable('withdraw_file')) {
            return response()->json(['status' => 'error', 'message' => 'ยังไม่พร้อมใช้งานไฟล์เอกสาร กรุณารัน migrate โครงสร้างใหม่ก่อนใช้งาน'], 500);
        }

        $fileQuery = DB::table('withdraw_file')
            ->where('withdraw_id', (int) $id);
        if ($fileId > 0) {
            $fileQuery->where('withdraw_file_id', $fileId);
        } else {
            $fileQuery
                ->where('withdraw_file_type_code', $type)
                ->where('withdraw_file_item_code', $item)
                ->orderByDesc('withdraw_file_id');
        }

        $matched = $fileQuery->first([
            'withdraw_file_path as path',
            'withdraw_file_name as name',
            'withdraw_file_type_code as type',
            'withdraw_file_mime as mime',
            'staff_create',
        ]);

        $path = trim((string) ($matched->path ?? ''));
        $name = trim((string) ($matched->name ?? 'document'));

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบไฟล์ที่ร้องขอ'], 404);
        }

        $absolutePath = Storage::disk('public')->path($path);
        $typeCode = trim((string) ($matched->type ?? $type));
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';
        $uploaderStaffId = trim((string) ($matched->staff_create ?? ''));
        $stampedPdf = $this->stampEsignAttachedPdfIfNeeded(
            $row,
            $absolutePath,
            $typeCode,
            (string) ($matched->mime ?? ''),
            $name,
            $uploaderStaffId
        );
        if (is_string($stampedPdf) && $stampedPdf !== '') {
            return response($stampedPdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition.'; filename="'.$name.'"',
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }

        return response()->file($absolutePath, [
            'Content-Disposition' => $disposition.'; filename="'.$name.'"',
        ]);
    }

    public function apiSignatureProxy(Request $request): JsonResponse|\Illuminate\Http\Response
    {
        $staffId = trim((string) $request->query('staff_id', ''));
        $rawUrl = $this->normalizeStaffSignatureUrl((string) $request->query('url', ''));
        [$bearerHeaderToken, $rawBearerToken] = $this->resolveStaffApiTokens();
        if ($staffId !== '' && ($bearerHeaderToken !== '' || $rawBearerToken !== '')) {
            try {
                $candidateIds = $this->resolveStaffIdentityCandidates($staffId, $bearerHeaderToken, $rawBearerToken);
                $resolvedRawUrl = $this->fetchStaffSignatureRawUrlByCandidateIds($candidateIds, $bearerHeaderToken, $rawBearerToken);
                if ($resolvedRawUrl !== '') {
                    $rawUrl = $this->normalizeStaffSignatureUrl($resolvedRawUrl);
                }
            } catch (\Throwable $e) {
                // Fall back to provided raw URL or placeholder below.
            }
        }

        if ($rawUrl === '') {
            return response($this->defaultSignaturePlaceholderSvg(), 200, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'public, max-age=300',
            ]);
        }

        $targetUrl = trim($rawUrl);
        if (! filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            return response($this->defaultSignaturePlaceholderSvg(), 200, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'public, max-age=300',
            ]);
        }

        try {
            $http = Http::timeout(20);
            if ($rawBearerToken !== '') {
                $http = $http->withToken($rawBearerToken);
            } elseif ($bearerHeaderToken !== '') {
                if (str_starts_with($bearerHeaderToken, 'Bearer ')) {
                    $http = $http->withHeaders(['Authorization' => $bearerHeaderToken]);
                } else {
                    $http = $http->withToken($bearerHeaderToken);
                }
            }

            $response = $http->get($targetUrl);
            if (! $response->successful()) {
                return response($this->defaultSignaturePlaceholderSvg(), 200, [
                    'Content-Type' => 'image/svg+xml',
                    'Cache-Control' => 'public, max-age=300',
                ]);
            }

            $contentType = strtolower(trim((string) $response->header('Content-Type', '')));
            if (! str_starts_with($contentType, 'image/')) {
                return response($this->defaultSignaturePlaceholderSvg(), 200, [
                    'Content-Type' => 'image/svg+xml',
                    'Cache-Control' => 'public, max-age=300',
                ]);
            }
            $contentType = explode(';', $contentType)[0] ?: 'image/png';

            return response($response->body(), 200, [
                'Content-Type' => $contentType,
                'Cache-Control' => 'public, max-age=300',
            ]);
        } catch (\Throwable $e) {
            return response($this->defaultSignaturePlaceholderSvg(), 200, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'public, max-age=300',
            ]);
        }
    }

    public function apiDelete(Request $request): JsonResponse
    {
        if (! Schema::hasTable('withdraw')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางข้อมูล'], 500);
        }

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'], 422);
        }
        $existingColumns = ['withdraw_status'];
        if (Schema::hasColumn('withdraw', 'withdraw_sub_type')) {
            $existingColumns[] = 'withdraw_sub_type';
        }
        $existing = $this->withdrawQuery()
            ->where('withdraw_id', $id)
            ->first($existingColumns);
        if (
            $existing
            && trim((string) ($existing->withdraw_sub_type ?? '')) === 'travel_leave'
            && (string) $request->session()->get('pbm.system_role', '') !== 'admin'
        ) {
            return response()->json(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลระบบเท่านั้นที่ลบรายการนี้ได้'], 403);
        }
        $currentStatus = $existing ? trim((string) ($existing->withdraw_status ?? '')) : '';
        $currentStaffId = trim((string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? ''));

        if (Schema::hasTable('withdraw_item')) {
            DB::table('withdraw_item')->where('withdraw_id', $id)->delete();
        }
        if (Schema::hasTable('withdraw_req_document')) {
            DB::table('withdraw_req_document')->where('withdraw_id', $id)->delete();
        }
        if (Schema::hasTable('withdraw_file')) {
            DB::table('withdraw_file')->where('withdraw_id', $id)->delete();
        }
        if (Schema::hasTable('withdraw_approval_step')) {
            DB::table('withdraw_approval_step')->where('withdraw_id', $id)->delete();
        }
        if (Schema::hasTable('withdraw_document')) {
            DB::table('withdraw_document')
                ->where('withdraw_id', $id)
                ->whereIn('withdraw_document_type', ['salary', 'salary_finance', 'salary_pts'])
                ->delete();
        }
        $this->withdrawQuery()->where('withdraw_id', $id)->delete();
        $this->appendGeneralActionLog(
            $id,
            'delete',
            'ลบรายการ',
            $currentStatus,
            'deleted',
            $currentStaffId
        );

        return response()->json(['status' => 'success']);
    }

    public function apiResendApprovalNotification(Request $request): JsonResponse
    {
        if (! Schema::hasTable('withdraw')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางข้อมูล'], 500);
        }

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'], 422);
        }

        $row = $this->withdrawQuery()->where('withdraw_id', $id)->first();
        if (! $row) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูล'], 404);
        }

        $currentStatus = trim((string) ($row->withdraw_status ?? ''));
        $allowedStatuses = ['pending_primary_review', 'processing', 'pending_additional_review', 'pending_approval'];
        if (! in_array($currentStatus, $allowedStatuses, true)) {
            return response()->json(['status' => 'error', 'message' => 'สถานะรายการนี้ยังไม่ถึงขั้นตอนผู้อนุมัติลำดับถัดไป'], 422);
        }

        $nextStep = $this->resolveNextPendingSalaryApprovalStep($id);
        if (! $nextStep) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบผู้อนุมัติลำดับถัดไปที่รอดำเนินการ'], 422);
        }

        $targetStepName = trim((string) ($nextStep->withdraw_approval_step_name ?? 'ผู้อนุมัติลำดับถัดไป'));
        if ($targetStepName === '') {
            $targetStepName = 'ผู้อนุมัติลำดับถัดไป';
        }

        $notifyResult = $this->notifyNextPendingSalaryApprovalStep(
            $id,
            $row,
            $request
        );
        $notifiedStaffIds = is_array($notifyResult['notification_debug']['notified_staff_ids'] ?? null)
            ? $notifyResult['notification_debug']['notified_staff_ids']
            : [];
        if (count($notifiedStaffIds) === 0) {
            $mailErrors = is_array($notifyResult['notification_debug']['mail_errors'] ?? null)
                ? $notifyResult['notification_debug']['mail_errors']
                : [];
            $firstError = trim((string) (($mailErrors[0]['error'] ?? '')));
            $message = $firstError !== ''
                ? 'ไม่สามารถส่งแจ้งเตือนได้: '.$firstError
                : 'ไม่สามารถส่งแจ้งเตือนได้ เนื่องจากผู้อนุมัติลำดับนี้ปิดการแจ้งเตือน';

            return response()->json(['status' => 'error', 'message' => $message], 422);
        }

        $currentStaffId = trim((string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? ''));
        $this->appendGeneralActionLog(
            $id,
            'resend_approval_notification',
            'ส่งแจ้งเตือนผู้อนุมัติลำดับถัดไปอีกครั้ง',
            $currentStatus,
            $currentStatus,
            $currentStaffId
        );

        return response()->json([
            'status' => 'success',
            'message' => 'ส่งแจ้งเตือนไปยัง'.$targetStepName.'เรียบร้อยแล้ว',
            'data' => [
                'next_step_name' => $targetStepName,
                'notified_at' => now()->format('Y-m-d H:i:s'),
                'notification_debug' => $notifyResult['notification_debug'],
                'mail_debug' => $notifyResult['mail_debug'],
            ],
        ]);
    }

    public function apiOnePlanningPreview(string $id): JsonResponse
    {
        if (! Schema::hasTable('withdraw')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางข้อมูล'], 500);
        }

        $withdrawId = (int) $id;
        $row = $this->withdrawQuery()->where('withdraw_id', $withdrawId)->first();
        if (! $row) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูล'], 404);
        }

        $refStatusName = $this->resolveOnePlanningRefStatusName($row);
        $comparison = $this->buildOnePlanningComparisonRows($row, $refStatusName);
        if (($comparison['rows'] ?? []) === [] && ($comparison['removed_rows'] ?? []) === []) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลงบประมาณที่สามารถส่งไป One Planning ได้',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'withdraw' => $this->summarizeSalaryRowForOnePlanning($row),
                'ref_status_name' => $refStatusName,
                'payloads' => $comparison['rows'],
                'removed_payloads' => $comparison['removed_rows'],
                'has_changes' => (bool) ($comparison['has_changes'] ?? false),
                'last_sent_at' => $comparison['last_sent_at'] ?? null,
                'payload_count' => count($comparison['rows']),
            ],
        ]);
    }

    public function apiOnePlanningSend(string $id, Request $request): JsonResponse
    {
        if (! Schema::hasTable('withdraw')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางข้อมูล'], 500);
        }

        $withdrawId = (int) $id;
        $row = $this->withdrawQuery()->where('withdraw_id', $withdrawId)->first();
        if (! $row) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูล'], 404);
        }

        $currentStaffId = trim((string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? ''));
        $refStatusName = $this->resolveOnePlanningRefStatusName($row);
        $sendResult = $this->dispatchOnePlanningBudgetUsed(
            $row,
            $refStatusName,
            'manual',
            $currentStaffId
        );

        if ((int) ($sendResult['failed_count'] ?? 0) > 0 && (int) ($sendResult['success_count'] ?? 0) === 0) {
            return response()->json([
                'status' => 'error',
                'message' => $this->summarizeOnePlanningSendFailureMessage($sendResult),
                'data' => $sendResult,
            ], 502);
        }

        if ((int) ($sendResult['failed_count'] ?? 0) > 0) {
            $failureDetail = $this->summarizeOnePlanningSendFailureMessage($sendResult);
            $failureDetail = preg_replace('/^ส่งข้อมูลไป One Planning ไม่สำเร็จ\s*—\s*/u', '', $failureDetail) ?: $failureDetail;

            return response()->json([
                'status' => 'success',
                'message' => 'ส่งข้อมูลไป One Planning บางรายการสำเร็จ — '.$failureDetail,
                'data' => $sendResult,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'ส่งข้อมูลไป One Planning เรียบร้อยแล้ว',
            'data' => $sendResult,
        ]);
    }

    /**
     * @return array{
     *   id:int,
     *   code:string,
     *   subject:string,
     *   status:string,
     *   raw_status:string,
     *   amount:float,
     *   amount_formatted:string,
     *   applicant:string,
     *   ref_id:string
     * }
     */
    private function summarizeSalaryRowForOnePlanning(object $row): array
    {
        $withdrawId = (int) ($row->withdraw_id ?? 0);
        $amount = (float) ($row->withdraw_total_amount ?? 0);
        $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
        $staffNameMap = $applicantStaffId !== ''
            ? $this->resolveStaffNamesByIds([$applicantStaffId])
            : [];

        return [
            'id' => $withdrawId,
            'code' => $this->resolveWithdrawGeneralDisplayDocumentCode($row),
            'subject' => trim((string) ($row->withdraw_subject ?? '')),
            'status' => $this->mapWithdrawStatusLabel((string) ($row->withdraw_status ?? '')),
            'raw_status' => trim((string) ($row->withdraw_status ?? '')),
            'amount' => $amount,
            'amount_formatted' => number_format($amount, 2),
            'applicant' => trim((string) ($staffNameMap[$applicantStaffId] ?? '')),
            'ref_id' => 'salary_'.$withdrawId.'_{n}',
            'ref_id_note' => 'แต่ละงบใช้ ref_id คนละค่า (ยาวไม่เกิน 20 ตัวอักษร) รูปแบบ salary_'.$withdrawId.'_[ลำดับงบ]',
        ];
    }

    /**
     * สร้าง ref_id สำหรับส่ง One Planning
     * - แต่ละงบในใบเดียวกันต้องไม่ซ้ำ
     * - One Planning รับ ref_id ได้สูงสุด 20 ตัวอักษร (ยาวกว่านั้นจะ Internal Error)
     * รูปแบบ: salary_{withdrawId}_{ลำดับงบ}
     */
    private function buildOnePlanningRefId(int $withdrawId, int $budgetIndex = 1, ?string $previousRefId = null): string
    {
        $budgetIndex = max(1, $budgetIndex);
        $expected = mb_substr('salary_'.$withdrawId.'_'.$budgetIndex, 0, 20);
        $previousRefId = trim((string) $previousRefId);

        // ใช้ซ้ำได้เฉพาะรูปแบบ salary_{id}_{n} ของใบนี้ (อัปเดตรอบถัดไปต้องใช้ ref_id เดิม)
        if (
            $previousRefId !== ''
            && strlen($previousRefId) <= 20
            && preg_match('/^salary_'.preg_quote((string) $withdrawId, '/').'_\d+$/', $previousRefId) === 1
        ) {
            return $previousRefId;
        }

        return $expected;
    }

    /**
     * รวมข้อความ error จากผลส่ง One Planning ให้แสดงกับผู้ใช้
     *
     * @param  array<string, mixed>  $sendResult
     */
    private function summarizeOnePlanningSendFailureMessage(array $sendResult): string
    {
        $messages = [];
        foreach (($sendResult['results'] ?? []) as $result) {
            if (! is_array($result) || ! empty($result['ok'])) {
                continue;
            }
            $budgetCode = trim((string) ($result['budget_code'] ?? ''));
            $message = trim((string) ($result['message'] ?? ''));
            if ($message === '') {
                continue;
            }
            $messages[] = ($budgetCode !== '' ? $budgetCode.': ' : '').$message;
        }

        $messages = array_values(array_unique($messages));
        if ($messages === []) {
            return 'ส่งข้อมูลไป One Planning ไม่สำเร็จ';
        }

        return 'ส่งข้อมูลไป One Planning ไม่สำเร็จ — '.implode(' | ', $messages);
    }

    private function resolveOnePlanningRefStatusName(object $row): string
    {
        $status = trim((string) ($row->withdraw_status ?? ''));

        return $status === 'approved'
            ? 'สิ้นสุดการดำเนินการแล้ว'
            : 'อยู่ระหว่างดำเนินการ';
    }

    /**
     * ตอนอนุมัติขั้นสุดท้าย: ใช้ payload จากรอบส่งล่าสุด (ถ้ามี) เพื่อคงงบหลายรายการ
     * เผื่อรายการถูกทับงบตอนงานการเงินผูกผังบัญชี
     *
     * @return list<array<string,mixed>>
     */
    private function buildOnePlanningPayloadsForFinalApproval(object $row): array
    {
        $withdrawId = (int) ($row->withdraw_id ?? 0);
        $refStatusName = 'สิ้นสุดการดำเนินการแล้ว';
        $fromItems = $this->buildOnePlanningBudgetUsedPayloads($row, $refStatusName);
        $lastByBudget = $this->loadLatestSuccessfulOnePlanningSends($withdrawId);

        if ($lastByBudget === []) {
            return $fromItems;
        }

        // ถ้าปัจจุบันเหลืองบน้อยกว่ารอบที่เคยส่งสำเร็จ ให้ใช้ snapshot รอบล่าสุด
        if (count($fromItems) < count($lastByBudget)) {
            $refCode = (string) config('services.one_planning.ref_code', 'BGAPMC');
            $departmentId = (string) config('services.one_planning.budget_departments_id', '11');

            return collect($lastByBudget)
                ->values()
                ->map(function (array $previous, int $idx) use ($refCode, $departmentId, $refStatusName, $withdrawId) {
                    $budgetCode = (string) ($previous['budget_code'] ?? '');

                    return [
                        'ref_code' => $refCode,
                        'ref_id' => $this->buildOnePlanningRefId(
                            $withdrawId,
                            $idx + 1,
                            (string) ($previous['ref_id'] ?? '')
                        ),
                        'ref_status_name' => $refStatusName,
                        'ref_edit_status' => 'Y',
                        'budget_code' => $budgetCode,
                        'budget_departments_id' => $departmentId,
                        'budget_value' => number_format((float) ($previous['budget_value'] ?? 0), 2, '.', ''),
                        'comment' => (string) ($previous['comment'] ?? ''),
                        'budget_manage_id' => 0,
                        'categories' => array_values(array_filter(array_map('trim', explode(',', (string) ($previous['comment'] ?? ''))))),
                    ];
                })
                ->filter(fn (array $payload) => trim((string) ($payload['budget_code'] ?? '')) !== '' && (float) ($payload['budget_value'] ?? 0) > 0)
                ->values()
                ->all();
        }

        return $fromItems;
    }

    /**
     * ส่ง One Planning ตามรายการงบในใบเบิก และบันทึกประวัติการส่ง
     *
     * @param  list<array<string,mixed>>|null  $payloads
     * @return array{
     *   success_count:int,
     *   failed_count:int,
     *   ref_status_name:string,
     *   trigger_source:string,
     *   results:list<array<string,mixed>>
     * }
     */
    private function dispatchOnePlanningBudgetUsed(
        object $row,
        string $refStatusName,
        string $triggerSource = 'manual',
        string $staffId = '',
        ?array $payloads = null
    ): array {
        $withdrawId = (int) ($row->withdraw_id ?? 0);
        $payloads = is_array($payloads)
            ? $payloads
            : $this->buildOnePlanningBudgetUsedPayloads($row, $refStatusName);
        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($payloads as $payload) {
            $payload['ref_status_name'] = $refStatusName;
            $apiResult = OnePlanningClient::addBudgetUsed($payload);
            $ok = (bool) ($apiResult['ok'] ?? false);
            if ($ok) {
                $successCount++;
            } else {
                $failedCount++;
            }

            $this->storeOnePlanningSendLog(
                $withdrawId,
                $payload,
                $triggerSource,
                $ok,
                (string) ($apiResult['message'] ?? ''),
                $apiResult['raw'] ?? null,
                $staffId
            );

            $results[] = [
                'budget_code' => (string) ($payload['budget_code'] ?? ''),
                'budget_value' => (string) ($payload['budget_value'] ?? ''),
                'comment' => (string) ($payload['comment'] ?? ''),
                'ref_id' => (string) ($payload['ref_id'] ?? ''),
                'ref_status_name' => (string) ($payload['ref_status_name'] ?? $refStatusName),
                'ok' => $ok,
                'message' => (string) ($apiResult['message'] ?? ''),
                'http_status' => (int) ($apiResult['http_status'] ?? 0),
                'error' => $apiResult['error'] ?? null,
            ];
        }

        if ($withdrawId > 0) {
            $currentStatus = trim((string) ($row->withdraw_status ?? ''));
            $this->appendGeneralActionLog(
                $withdrawId,
                'one_planning_send',
                'ส่งรายการไป One Planning ('.$refStatusName.') สำเร็จ '.$successCount.'/'.max(count($payloads), 1),
                $currentStatus,
                $currentStatus,
                $staffId,
                '',
                [
                    'trigger_source' => $triggerSource,
                    'ref_status_name' => $refStatusName,
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                ]
            );
        }

        return [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'ref_status_name' => $refStatusName,
            'trigger_source' => $triggerSource,
            'results' => $results,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  mixed  $responseRaw
     */
    private function storeOnePlanningSendLog(
        int $withdrawId,
        array $payload,
        string $triggerSource,
        bool $isSuccess,
        string $responseMessage = '',
        $responseRaw = null,
        string $staffId = ''
    ): void {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_one_planning_send')) {
            return;
        }

        $responseJson = null;
        if (is_array($responseRaw) || is_object($responseRaw)) {
            $responseJson = json_encode($responseRaw, JSON_UNESCAPED_UNICODE);
        } elseif (is_string($responseRaw) && trim($responseRaw) !== '') {
            $responseJson = $responseRaw;
        }

        DB::table('withdraw_one_planning_send')->insert([
            'withdraw_id' => $withdrawId,
            'ref_id' => (string) ($payload['ref_id'] ?? $this->buildOnePlanningRefId($withdrawId, 1)),
            'ref_status_name' => (string) ($payload['ref_status_name'] ?? ''),
            'budget_code' => (string) ($payload['budget_code'] ?? ''),
            'budget_value' => (float) ($payload['budget_value'] ?? 0),
            'comment' => (string) ($payload['comment'] ?? ''),
            'budget_departments_id' => (string) ($payload['budget_departments_id'] ?? ''),
            'trigger_source' => trim($triggerSource) !== '' ? trim($triggerSource) : 'manual',
            'is_success' => $isSuccess ? 1 : 0,
            'response_message' => $responseMessage !== '' ? mb_substr($responseMessage, 0, 255) : null,
            'response_json' => $responseJson,
            'staff_id' => trim($staffId) !== '' ? trim($staffId) : null,
            'sent_at' => now(),
        ]);
    }

    /**
     * @return array<string, array{
     *   budget_code:string,
     *   budget_value:string,
     *   comment:string,
     *   ref_status_name:string,
     *   sent_at:?string,
     *   trigger_source:string
     * }>
     */
    private function loadLatestSuccessfulOnePlanningSends(int $withdrawId): array
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_one_planning_send')) {
            return [];
        }

        $rows = DB::table('withdraw_one_planning_send')
            ->where('withdraw_id', $withdrawId)
            ->where('is_success', 1)
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->get([
                'ref_id',
                'budget_code',
                'budget_value',
                'comment',
                'ref_status_name',
                'trigger_source',
                'sent_at',
            ]);

        $latest = [];
        foreach ($rows as $row) {
            $budgetCode = trim((string) ($row->budget_code ?? ''));
            if ($budgetCode === '' || isset($latest[$budgetCode])) {
                continue;
            }
            $latest[$budgetCode] = [
                'budget_code' => $budgetCode,
                'budget_value' => number_format((float) ($row->budget_value ?? 0), 2, '.', ''),
                'comment' => trim((string) ($row->comment ?? '')),
                'ref_status_name' => trim((string) ($row->ref_status_name ?? '')),
                'trigger_source' => trim((string) ($row->trigger_source ?? '')),
                'ref_id' => trim((string) ($row->ref_id ?? '')),
                'sent_at' => $row->sent_at ? (string) $row->sent_at : null,
            ];
        }

        return $latest;
    }

    /**
     * ประวัติการส่ง One Planning ทั้งหมด จัดกลุ่มตามรหัสงบประมาณ (ใหม่สุดก่อน)
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function loadOnePlanningSendLogsByBudget(int $withdrawId): array
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_one_planning_send')) {
            return [];
        }

        $rows = DB::table('withdraw_one_planning_send')
            ->where('withdraw_id', $withdrawId)
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->get([
                'id',
                'ref_id',
                'ref_status_name',
                'budget_code',
                'budget_value',
                'comment',
                'budget_departments_id',
                'trigger_source',
                'is_success',
                'response_message',
                'response_json',
                'staff_id',
                'sent_at',
            ]);

        $grouped = [];
        foreach ($rows as $row) {
            $budgetCode = trim((string) ($row->budget_code ?? ''));
            if ($budgetCode === '') {
                continue;
            }

            $responseRaw = null;
            $responseJson = trim((string) ($row->response_json ?? ''));
            if ($responseJson !== '') {
                $decoded = json_decode($responseJson, true);
                $responseRaw = json_last_error() === JSON_ERROR_NONE ? $decoded : $responseJson;
            }

            $grouped[$budgetCode][] = [
                'id' => (int) ($row->id ?? 0),
                'ref_id' => trim((string) ($row->ref_id ?? '')),
                'ref_status_name' => trim((string) ($row->ref_status_name ?? '')),
                'budget_code' => $budgetCode,
                'budget_value' => number_format((float) ($row->budget_value ?? 0), 2, '.', ''),
                'comment' => trim((string) ($row->comment ?? '')),
                'budget_departments_id' => trim((string) ($row->budget_departments_id ?? '')),
                'trigger_source' => trim((string) ($row->trigger_source ?? '')),
                'is_success' => (int) ($row->is_success ?? 0) === 1,
                'response_message' => trim((string) ($row->response_message ?? '')),
                'response' => $responseRaw,
                'staff_id' => trim((string) ($row->staff_id ?? '')),
                'sent_at' => $row->sent_at ? (string) $row->sent_at : null,
            ];
        }

        return $grouped;
    }

    /**
     * @return array{
     *   rows:list<array<string,mixed>>,
     *   removed_rows:list<array<string,mixed>>,
     *   has_changes:bool,
     *   last_sent_at:?string
     * }
     */
    private function buildOnePlanningComparisonRows(object $row, string $refStatusName): array
    {
        $withdrawId = (int) ($row->withdraw_id ?? 0);
        $currentPayloads = $this->buildOnePlanningBudgetUsedPayloads($row, $refStatusName);
        $lastByBudget = $this->loadLatestSuccessfulOnePlanningSends($withdrawId);
        $sendLogsByBudget = $this->loadOnePlanningSendLogsByBudget($withdrawId);
        $hasChanges = false;
        $lastSentAt = null;
        $currentCodes = [];

        $rows = [];
        foreach ($currentPayloads as $payload) {
            $budgetCode = (string) ($payload['budget_code'] ?? '');
            $currentCodes[$budgetCode] = true;
            $previous = $lastByBudget[$budgetCode] ?? null;
            $sendLogs = $sendLogsByBudget[$budgetCode] ?? [];
            $latestLog = $sendLogs[0] ?? null;
            $changes = [];
            $isNew = $previous === null;
            $valueChanged = false;
            $commentChanged = false;
            $statusChanged = false;

            if ($isNew) {
                $changes[] = 'งบประมาณใหม่ที่ยังไม่เคยส่ง';
                $hasChanges = true;
            } else {
                $prevValue = number_format((float) ($previous['budget_value'] ?? 0), 2, '.', '');
                $currValue = number_format((float) ($payload['budget_value'] ?? 0), 2, '.', '');
                $prevComment = trim((string) ($previous['comment'] ?? ''));
                $currComment = trim((string) ($payload['comment'] ?? ''));
                $prevStatus = trim((string) ($previous['ref_status_name'] ?? ''));
                $currStatus = trim((string) ($payload['ref_status_name'] ?? $refStatusName));

                if ($prevValue !== $currValue) {
                    $valueChanged = true;
                    $changes[] = 'จำนวนเงินเปลี่ยนจาก '.$prevValue.' เป็น '.$currValue;
                    $hasChanges = true;
                }
                if ($prevComment !== $currComment) {
                    $commentChanged = true;
                    $changes[] = 'หมวดเปลี่ยนจาก "'.$prevComment.'" เป็น "'.$currComment.'"';
                    $hasChanges = true;
                }
                if ($prevStatus !== '' && $prevStatus !== $currStatus) {
                    $statusChanged = true;
                    $changes[] = 'สถานะเปลี่ยนจาก "'.$prevStatus.'" เป็น "'.$currStatus.'"';
                    $hasChanges = true;
                }
                if (! empty($previous['sent_at'])) {
                    $candidate = (string) $previous['sent_at'];
                    if ($lastSentAt === null || strcmp($candidate, $lastSentAt) > 0) {
                        $lastSentAt = $candidate;
                    }
                }
            }

            $rows[] = array_merge($payload, [
                'last_sent_at' => $previous['sent_at'] ?? null,
                'last_sent_budget_value' => $previous['budget_value'] ?? null,
                'last_sent_comment' => $previous['comment'] ?? null,
                'last_sent_ref_status_name' => $previous['ref_status_name'] ?? null,
                'is_new' => $isNew,
                'value_changed' => $valueChanged,
                'comment_changed' => $commentChanged,
                'status_changed' => $statusChanged,
                'has_changes' => $isNew || $valueChanged || $commentChanged || $statusChanged,
                'change_labels' => $changes,
                'send_logs' => $sendLogs,
                'last_send_log' => $latestLog,
            ]);
        }

        $removedRows = [];
        foreach ($lastByBudget as $budgetCode => $previous) {
            if (isset($currentCodes[$budgetCode])) {
                continue;
            }
            $hasChanges = true;
            if (! empty($previous['sent_at'])) {
                $candidate = (string) $previous['sent_at'];
                if ($lastSentAt === null || strcmp($candidate, $lastSentAt) > 0) {
                    $lastSentAt = $candidate;
                }
            }
            $sendLogs = $sendLogsByBudget[$budgetCode] ?? [];
            $removedRows[] = [
                'budget_code' => $budgetCode,
                'budget_value' => null,
                'comment' => null,
                'ref_status_name' => $refStatusName,
                'last_sent_at' => $previous['sent_at'] ?? null,
                'last_sent_budget_value' => $previous['budget_value'] ?? null,
                'last_sent_comment' => $previous['comment'] ?? null,
                'last_sent_ref_status_name' => $previous['ref_status_name'] ?? null,
                'is_removed' => true,
                'has_changes' => true,
                'change_labels' => ['งบประมาณนี้เคยส่งไปแล้ว แต่ไม่มีในระบบปัจจุบัน'],
                'send_logs' => $sendLogs,
                'last_send_log' => $sendLogs[0] ?? null,
            ];
        }

        return [
            'rows' => $rows,
            'removed_rows' => $removedRows,
            'has_changes' => $hasChanges,
            'last_sent_at' => $lastSentAt,
        ];
    }

    /**
     * รวมรายการเบิกตามรหัสงบประมาณ (งบละ 1 payload) สำหรับส่ง One Planning
     *
     * @return list<array{
     *   ref_code:string,
     *   ref_id:string,
     *   ref_status_name:string,
     *   ref_edit_status:string,
     *   budget_code:string,
     *   budget_departments_id:string,
     *   budget_value:string,
     *   comment:string,
     *   budget_manage_id:int,
     *   categories:list<string>
     * }>
     */
    private function buildOnePlanningBudgetUsedPayloads(object $row, string $refStatusName = 'อยู่ระหว่างดำเนินการ'): array
    {
        $withdrawId = (int) ($row->withdraw_id ?? 0);
        if ($withdrawId <= 0) {
            return [];
        }

        $items = $this->loadGeneralItems($withdrawId);
        if ($items === []) {
            return [];
        }

        $budgetManageIdsMissingCode = collect($items)
            ->filter(fn (array $item) => trim((string) ($item['budget_manage_code'] ?? '')) === '')
            ->map(fn (array $item) => (int) ($item['budget_manage_id'] ?? 0))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $budgetCodeByManageId = $this->resolveBudgetManageCodesByIds($budgetManageIdsMissingCode);

        /** @var array<string, array{budget_code:string,budget_manage_id:int,amount:float,categories:array<string,bool>}> $grouped */
        $grouped = [];
        foreach ($items as $item) {
            $budgetManageId = (int) ($item['budget_manage_id'] ?? 0);
            $budgetCode = trim((string) ($item['budget_manage_code'] ?? ''));
            if ($budgetCode === '' && $budgetManageId > 0) {
                $budgetCode = trim((string) ($budgetCodeByManageId[$budgetManageId] ?? ''));
            }
            $amount = (float) ($item['amount'] ?? 0);
            if ($budgetCode === '' || ! is_finite($amount) || $amount <= 0) {
                continue;
            }

            $category = trim((string) ($item['category'] ?? ''));
            if (! isset($grouped[$budgetCode])) {
                $grouped[$budgetCode] = [
                    'budget_code' => $budgetCode,
                    'budget_manage_id' => $budgetManageId,
                    'amount' => 0.0,
                    'categories' => [],
                ];
            }
            $grouped[$budgetCode]['amount'] += $amount;
            if ($grouped[$budgetCode]['budget_manage_id'] <= 0 && $budgetManageId > 0) {
                $grouped[$budgetCode]['budget_manage_id'] = $budgetManageId;
            }
            if ($category !== '') {
                $grouped[$budgetCode]['categories'][$category] = true;
            }
        }

        $refCode = (string) config('services.one_planning.ref_code', 'BGAPMC');
        $departmentId = (string) config('services.one_planning.budget_departments_id', '11');
        $normalizedStatus = trim($refStatusName) !== '' ? trim($refStatusName) : 'อยู่ระหว่างดำเนินการ';
        $lastByBudget = $this->loadLatestSuccessfulOnePlanningSends($withdrawId);

        return collect($grouped)
            ->values()
            ->map(function (array $group, int $idx) use ($withdrawId, $refCode, $departmentId, $normalizedStatus, $lastByBudget) {
                $categories = array_keys($group['categories']);
                $amount = round((float) $group['amount'], 2);
                $budgetCode = (string) $group['budget_code'];
                $previousRefId = (string) ($lastByBudget[$budgetCode]['ref_id'] ?? '');

                return [
                    'ref_code' => $refCode,
                    'ref_id' => $this->buildOnePlanningRefId($withdrawId, $idx + 1, $previousRefId),
                    'ref_status_name' => $normalizedStatus,
                    'ref_edit_status' => 'Y',
                    'budget_code' => $budgetCode,
                    'budget_departments_id' => $departmentId,
                    'budget_value' => number_format($amount, 2, '.', ''),
                    'comment' => implode(', ', $categories),
                    'budget_manage_id' => (int) $group['budget_manage_id'],
                    'categories' => $categories,
                ];
            })
            ->filter(fn (array $payload) => (float) $payload['budget_value'] > 0)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $budgetManageIds
     * @return array<int, string>
     */
    private function resolveBudgetManageCodesByIds(array $budgetManageIds): array
    {
        $budgetManageIds = array_values(array_filter(array_map('intval', $budgetManageIds), static fn ($id) => $id > 0));
        if ($budgetManageIds === [] || ! Schema::connection('budget')->hasTable('budget_manage')) {
            return [];
        }

        try {
            return DB::connection('budget')
                ->table('budget_manage')
                ->whereIn('budget_manage_id', $budgetManageIds)
                ->pluck('budget_manage_code', 'budget_manage_id')
                ->map(fn ($code) => trim((string) $code))
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function apiProceed(Request $request): JsonResponse
    {
        if (! Schema::hasTable('withdraw')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางข้อมูล'], 500);
        }

        $id = (int) $request->input('id', 0);
        $approvalComment = trim((string) ($request->input('approval_comment', $request->input('comment', ''))));
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'], 422);
        }

        $proceedRowColumns = [
            'withdraw_id',
            'withdraw_status',
            'withdraw_subject',
            'withdraw_to',
            'withdraw_total_amount',
            'withdraw_applicant_staff_id',
            'withdraw_reviewer_staff_id',
        ];
        if (Schema::hasColumn('withdraw', 'withdraw_sub_type')) {
            $proceedRowColumns[] = 'withdraw_sub_type';
        }
        $row = $this->withdrawQuery()->where('withdraw_id', $id)->first($proceedRowColumns);
        if (! $row) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูล'], 404);
        }

        $currentStaffId = trim((string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? ''));
        $currentStatus = trim((string) ($row->withdraw_status ?? ''));
        $action = trim((string) $request->input('action', ''));
        $subType = trim((string) ($row->withdraw_sub_type ?? ''));

        if ($subType === 'travel_leave' && $action === '' && in_array($currentStatus, ['saved', 'draft'], true)) {
            return $this->submitTravelLeaveAfterFinance($id, $row, $request, $currentStaffId, $currentStatus, $approvalComment);
        }

        if ($action === 'request_revision_to_finance') {
            if ($currentStaffId === '' || ! Schema::hasTable('withdraw_approval_step')) {
                return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ส่งกลับให้แก้ไข'], 403);
            }

            $isFinanceManagerApprover = DB::table('withdraw_approval_step')
                ->where('withdraw_id', $id)
                ->where('withdraw_approval_step_code', 'finance_manager')
                ->where('withdraw_approval_staff_id', $currentStaffId)
                ->where('withdraw_approval_status', 'pending')
                ->exists();
            if (! $isFinanceManagerApprover) {
                return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ส่งกลับให้แก้ไข'], 403);
            }

            if (in_array($currentStatus, ['saved', 'cancelled', 'approved', 'pending_revision'], true)) {
                return response()->json(['status' => 'error', 'message' => 'สถานะรายการนี้ไม่สามารถส่งกลับให้งานการเงินแก้ไขได้'], 422);
            }

            $revisionComment = trim((string) ($request->input('revision_comment', $approvalComment)));
            if ($revisionComment === '') {
                return response()->json(['status' => 'error', 'message' => 'กรุณาระบุรายละเอียดที่ต้องแก้ไข'], 422);
            }

            DB::transaction(function () use ($id, $currentStaffId, $revisionComment) {
                $this->ensureSalaryFinanceOfficerApprovalStep($id, $currentStaffId);

                $this->withdrawQuery()
                    ->where('withdraw_id', $id)
                    ->update([
                        'withdraw_status' => 'pending_finance_review',
                        'withdraw_date_modify' => now(),
                        'staff_modify' => $currentStaffId,
                    ]);

                $this->markApprovalStepAsApproved($id, 'finance_manager', '', $revisionComment, $currentStaffId, 'rejected');
                $this->markApprovalStepAsApproved($id, 'finance_officer', '', '', $currentStaffId, 'pending');
                $this->appendApprovalStepHistory(
                    $id,
                    'finance_manager',
                    [
                        'event' => 'revision_requested',
                        'label' => 'ส่งกลับให้แก้ไข',
                        'comment' => $revisionComment,
                        'status' => 'rejected',
                        'recorded_at' => now()->toDateTimeString(),
                        'recorded_by' => $currentStaffId,
                    ]
                );
            });

            $displayCode = $this->resolveWithdrawGeneralDisplayDocumentCode($row);
            if ($displayCode === '') {
                $displayCode = (string) $id;
            }
            $subjectText = trim((string) ($row->withdraw_subject ?? ''));
            $subjectText = $subjectText !== '' ? $subjectText : 'เรื่อง -';
            $amountText = number_format((float) ($row->withdraw_total_amount ?? 0), 2);

            $financeOfficerStaffId = trim((string) (DB::table('withdraw_approval_step')
                ->where('withdraw_id', $id)
                ->where('withdraw_approval_step_code', 'finance_officer')
                ->value('withdraw_approval_staff_id') ?? ''));

            $notificationDebug = [
                'notified_staff_ids' => [],
                'mail_sent_staff_ids' => [],
                'mail_errors' => [],
            ];

            if ($financeOfficerStaffId !== '') {
                try {
                    $createdBy = $currentStaffId !== '' ? $currentStaffId : 'system';
                    $notificationApiUrl = trim((string) config('services.notification_api.url', ''));
                    if ($notificationApiUrl !== '') {
                        Http::timeout(10)->post($notificationApiUrl, [
                            'staffId' => $financeOfficerStaffId,
                            'message' => 'มีรายการเบิกจ่ายเงินเดือน/ค่าจ้างส่งกลับให้แก้ไขจากผู้บริหาร - '.$subjectText.' ('.$displayCode.') เหตุผล: '.$revisionComment,
                            'systemName' => 'ระบบเบิกจ่าย',
                            'actionUrl' => WithdrawApprovalLink::nextApprover($id, 'salary', 'finance_officer'),
                            'createdBy' => $createdBy,
                        ]);
                    }
                    $notificationDebug['notified_staff_ids'][] = $financeOfficerStaffId;

                    $targetEmail = $this->resolveStaffEmailById($financeOfficerStaffId);
                    if ($targetEmail === '') {
                        $notificationDebug['mail_errors'][] = [
                            'staff_id' => $financeOfficerStaffId,
                            'error' => 'ไม่พบอีเมล',
                        ];
                    } else {
                        $staffNameMap = $this->resolveStaffNamesByIds([$financeOfficerStaffId]);
                        $recipientName = trim((string) ($staffNameMap[$financeOfficerStaffId] ?? ''));
                        if ($recipientName === '') {
                            $recipientName = $financeOfficerStaffId;
                        }

                        $mailTemplate = new WithdrawProceedNotificationMail([
                            'display_code' => $displayCode,
                            'subject_text' => $subjectText,
                            'recipient_name' => $recipientName,
                            'applicant_text' => $recipientName,
                            'amount_text' => $amountText,
                            'action_url' => WithdrawApprovalLink::nextApprover($id, 'salary', 'finance_officer'),
                            'mail_subject' => '[ระบบเบิกจ่าย] มีรายการส่งกลับให้แก้ไข '.$displayCode,
                            'header_title' => 'แจ้งเตือนรายการเบิกจ่ายเงินเดือน/ค่าจ้าง',
                            'header_description' => 'มีรายการถูกส่งกลับให้แก้ไขจากผู้บริหาร',
                            'status_badge' => 'รอการเงินตรวจสอบเอกสาร (แก้ไข)',
                            'intro_message' => 'มีรายการเบิกจ่ายเงินเดือน/ค่าจ้างถูกส่งกลับให้งานการเงินตรวจสอบและแก้ไข',
                            'button_label' => 'เปิดรายการเพื่อตรวจสอบ',
                            'extra_note' => 'รายละเอียดที่ต้องแก้ไข: '.$revisionComment,
                        ]);

                        $smtpHost = trim((string) env('MAIL_HOST', ''));
                        $smtpPort = (int) env('MAIL_PORT', 587);
                        $smtpUsername = trim((string) env('MAIL_USERNAME', ''));
                        $smtpPassword = (string) env('MAIL_PASSWORD', '');
                        $smtpFromAddress = trim((string) env('MAIL_FROM_ADDRESS', ''));
                        $smtpFromName = trim((string) env('MAIL_FROM_NAME', (string) config('app.name', 'PBM Withdraw')));
                        if ($smtpHost === '' || $smtpFromAddress === '') {
                            $notificationDebug['mail_errors'][] = [
                                'staff_id' => $financeOfficerStaffId,
                                'error' => 'SMTP host/from address ไม่ครบถ้วน',
                            ];
                        } else {
                            $mailer = new PHPMailer(true);
                            try {
                                $mailer->CharSet = 'UTF-8';
                                $mailer->Encoding = 'base64';
                                $mailer->isSMTP();
                                $mailer->Host = $smtpHost;
                                $mailer->SMTPAuth = true;
                                $mailer->Username = $smtpUsername;
                                $mailer->Password = $smtpPassword;
                                $mailer->Port = $smtpPort > 0 ? $smtpPort : 587;
                                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                $mailer->SMTPAutoTLS = true;
                                $mailer->SMTPOptions = [
                                    'ssl' => [
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                        'allow_self_signed' => true,
                                    ],
                                ];
                                $mailer->setFrom($smtpFromAddress, $smtpFromName !== '' ? $smtpFromName : 'PBM Withdraw');
                                $mailer->addAddress($targetEmail);
                                $mailer->Subject = $mailTemplate->subjectLine();
                                $mailer->Body = $mailTemplate->render();
                                $mailer->AltBody = $mailTemplate->plainMessage();
                                $mailer->isHTML(true);
                                $mailer->send();
                                $notificationDebug['mail_sent_staff_ids'][] = $financeOfficerStaffId;
                            } catch (PHPMailerException $e) {
                                $notificationDebug['mail_errors'][] = [
                                    'staff_id' => $financeOfficerStaffId,
                                    'error' => $e->getMessage(),
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $notificationDebug['mail_errors'][] = [
                        'staff_id' => $financeOfficerStaffId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $this->appendGeneralActionLog(
                $id,
                'request_revision_to_finance',
                'หัวหน้างานการเงินส่งกลับให้งานการเงินแก้ไข',
                $currentStatus,
                'pending_finance_review',
                $currentStaffId,
                $revisionComment,
                ['next_status_label' => 'รอการเงินตรวจสอบเอกสาร (แก้ไข)']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'ส่งกลับให้แก้ไขไปยังงานการเงินเรียบร้อยแล้ว',
                'data' => [
                    'next_status' => 'pending_finance_review',
                    'next_status_label' => 'รอการเงินตรวจสอบเอกสาร (แก้ไข)',
                    'notification_debug' => $notificationDebug,
                ],
            ]);
        }

        if ($action === 'request_revision') {
            $revisionComment = trim((string) ($request->input('revision_comment', $approvalComment)));
            if ($revisionComment === '') {
                return response()->json(['status' => 'error', 'message' => 'กรุณาระบุรายละเอียดที่ต้องแก้ไข'], 422);
            }
            if (in_array($currentStatus, ['saved', 'cancelled', 'approved', 'pending_revision'], true)) {
                return response()->json(['status' => 'error', 'message' => 'สถานะรายการนี้ไม่สามารถส่งกลับให้แก้ไขได้'], 422);
            }

            $revisionStepCode = 'finance_officer';
            $actionLogLabel = 'งานการเงินส่งกลับให้ผู้ขอแก้ไข';
            $pendingOwnedStep = null;
            $reviewerStaffId = trim((string) ($row->withdraw_reviewer_staff_id ?? ''));

            if ($currentStaffId !== '' && Schema::hasTable('withdraw_approval_step')) {
                $pendingOwnedStep = DB::table('withdraw_approval_step')
                    ->where('withdraw_id', $id)
                    ->where('withdraw_approval_staff_id', $currentStaffId)
                    ->where('withdraw_approval_status', 'pending')
                    ->where('withdraw_approval_step_code', '!=', 'finance_officer')
                    ->orderBy('withdraw_approval_step_order')
                    ->orderBy('withdraw_approval_step_id')
                    ->first([
                        'withdraw_approval_step_code',
                    ]);
            }

            if ($pendingOwnedStep) {
                $revisionStepCode = trim((string) ($pendingOwnedStep->withdraw_approval_step_code ?? ''));
                if ($revisionStepCode === '') {
                    return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ส่งกลับให้แก้ไข'], 403);
                }
                $actionLogLabel = match ($revisionStepCode) {
                    'reviewer_head' => 'ผู้ตรวจสอบ/หัวหน้างานส่งกลับให้ผู้ขอแก้ไข',
                    'additional_reviewer' => 'ผู้ตรวจสอบเพิ่มเติมส่งกลับให้ผู้ขอแก้ไข',
                    default => 'ผู้อนุมัติส่งกลับให้ผู้ขอแก้ไข',
                };
            } elseif (
                $currentStaffId !== ''
                && $reviewerStaffId !== ''
                && $currentStaffId === $reviewerStaffId
                && in_array($currentStatus, ['pending_approval', 'pending_primary_review'], true)
            ) {
                $revisionStepCode = 'reviewer_head';
                $actionLogLabel = 'ผู้ตรวจสอบ/หัวหน้างานส่งกลับให้ผู้ขอแก้ไข';
            } elseif ($this->currentStaffHasFinanceReviewerAccess($currentStaffId)) {
                if (! in_array($currentStatus, ['pending_approval', 'pending_finance_review'], true)) {
                    return response()->json(['status' => 'error', 'message' => 'สถานะรายการนี้ไม่สามารถส่งกลับให้แก้ไขได้'], 422);
                }
                $this->ensureSalaryFinanceOfficerApprovalStep($id, $currentStaffId);
                if (Schema::hasTable('withdraw_approval_step')) {
                    DB::table('withdraw_approval_step')
                        ->where('withdraw_id', $id)
                        ->where('withdraw_approval_step_code', 'finance_officer')
                        ->update([
                            'withdraw_approval_staff_id' => $currentStaffId !== '' ? $currentStaffId : null,
                            'withdraw_approval_date_modify' => now(),
                            'staff_modify' => $currentStaffId,
                        ]);
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ส่งกลับให้แก้ไข'], 403);
            }

            $this->withdrawQuery()
                ->where('withdraw_id', $id)
                ->update([
                    'withdraw_status' => 'pending_revision',
                    'withdraw_date_modify' => now(),
                    'staff_modify' => $currentStaffId,
                ]);

            $this->markApprovalStepAsApproved($id, $revisionStepCode, '', $revisionComment, $currentStaffId, 'rejected');
            $this->appendApprovalStepHistory(
                $id,
                $revisionStepCode,
                [
                    'event' => 'revision_requested',
                    'label' => 'ส่งกลับให้แก้ไข',
                    'comment' => $revisionComment,
                    'status' => 'rejected',
                    'recorded_at' => now()->toDateTimeString(),
                    'recorded_by' => $currentStaffId,
                ]
            );

            $displayCode = $this->resolveWithdrawGeneralDisplayDocumentCode($row);
            if ($displayCode === '') {
                $displayCode = (string) $id;
            }
            $subjectText = trim((string) ($row->withdraw_subject ?? ''));
            $subjectText = $subjectText !== '' ? $subjectText : 'เรื่อง -';
            $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));

            $notificationDebug = [
                'notified_staff_ids' => [],
                'mail_sent_staff_ids' => [],
                'mail_errors' => [],
            ];

            try {
                if ($applicantStaffId !== '') {
                    $createdBy = $currentStaffId !== '' ? $currentStaffId : 'system';
                    $notificationApiUrl = trim((string) config('services.notification_api.url', ''));
                    if ($notificationApiUrl !== '') {
                        Http::timeout(10)->post($notificationApiUrl, [
                            'staffId' => $applicantStaffId,
                            'message' => 'รายการเบิกจ่ายเงินเดือน/ค่าจ้างถูกส่งกลับให้แก้ไข - '.$subjectText.' ('.$displayCode.') เหตุผล: '.$revisionComment,
                            'systemName' => 'ระบบเบิกจ่าย',
                            'actionUrl' => url('/withdraw/salary'),
                            'createdBy' => $createdBy,
                        ]);
                    }
                    $notificationDebug['notified_staff_ids'][] = $applicantStaffId;

                    $targetEmail = $this->resolveStaffEmailById($applicantStaffId);
                    if ($targetEmail === '') {
                        $notificationDebug['mail_errors'][] = [
                            'staff_id' => $applicantStaffId,
                            'error' => 'ไม่พบอีเมล',
                        ];
                    } else {
                        $smtpHost = trim((string) env('MAIL_HOST', ''));
                        $smtpPort = (int) env('MAIL_PORT', 587);
                        $smtpUsername = trim((string) env('MAIL_USERNAME', ''));
                        $smtpPassword = (string) env('MAIL_PASSWORD', '');
                        $smtpFromAddress = trim((string) env('MAIL_FROM_ADDRESS', ''));
                        $smtpFromName = trim((string) env('MAIL_FROM_NAME', (string) config('app.name', 'PBM Withdraw')));

                        if ($smtpHost === '' || $smtpFromAddress === '') {
                            $notificationDebug['mail_errors'][] = [
                                'staff_id' => $applicantStaffId,
                                'error' => 'SMTP host/from address ไม่ครบถ้วน',
                            ];
                        } else {
                            $applicantNameMap = $this->resolveStaffNamesByIds([$applicantStaffId]);
                            $recipientName = trim((string) ($applicantNameMap[$applicantStaffId] ?? ''));
                            if ($recipientName === '') {
                                $recipientName = $applicantStaffId;
                            }

                            $mailTemplate = new WithdrawProceedNotificationMail([
                                'display_code' => $displayCode,
                                'subject_text' => $subjectText,
                                'recipient_name' => $recipientName,
                                'applicant_text' => $recipientName,
                                'amount_text' => number_format((float) ($row->withdraw_total_amount ?? 0), 2),
                                'action_url' => url('/withdraw/salary'),
                                'mail_subject' => '[ระบบเบิกจ่าย] รายการถูกส่งกลับให้แก้ไข '.$displayCode,
                                'header_title' => 'แจ้งเตือนรายการเบิกจ่ายเงินเดือน/ค่าจ้าง',
                                'header_description' => 'มีรายการถูกส่งกลับเพื่อให้แก้ไข',
                                'status_badge' => 'รอแก้ไข',
                                'intro_message' => 'รายการเบิกจ่ายเงินเดือน/ค่าจ้างของท่านถูกส่งกลับให้แก้ไข',
                                'button_label' => 'เปิดรายการเพื่อแก้ไข',
                                'extra_note' => 'รายละเอียดที่ต้องแก้ไข: '.$revisionComment,
                            ]);
                            $mailSubject = $mailTemplate->subjectLine();
                            $mailBodyText = $mailTemplate->plainMessage();
                            $mailBodyHtml = $mailTemplate->render();

                            $mailer = new PHPMailer(true);
                            try {
                                $mailer->CharSet = 'UTF-8';
                                $mailer->Encoding = 'base64';
                                $mailer->isSMTP();
                                $mailer->Host = $smtpHost;
                                $mailer->SMTPAuth = true;
                                $mailer->Username = $smtpUsername;
                                $mailer->Password = $smtpPassword;
                                $mailer->Port = $smtpPort > 0 ? $smtpPort : 587;
                                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                $mailer->SMTPAutoTLS = true;
                                $mailer->SMTPOptions = [
                                    'ssl' => [
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                        'allow_self_signed' => true,
                                    ],
                                ];
                                $mailer->setFrom($smtpFromAddress, $smtpFromName !== '' ? $smtpFromName : 'PBM Withdraw');
                                $mailer->addAddress($targetEmail);
                                $mailer->Subject = $mailSubject;
                                $mailer->Body = $mailBodyHtml;
                                $mailer->AltBody = $mailBodyText;
                                $mailer->isHTML(true);
                                $mailer->send();
                                $notificationDebug['mail_sent_staff_ids'][] = $applicantStaffId;
                            } catch (PHPMailerException $e) {
                                $notificationDebug['mail_errors'][] = [
                                    'staff_id' => $applicantStaffId,
                                    'error' => $e->getMessage(),
                                ];
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                $notificationDebug['mail_errors'][] = [
                    'staff_id' => $applicantStaffId,
                    'error' => $e->getMessage(),
                ];
            }

            $this->appendGeneralActionLog(
                $id,
                'request_revision',
                $actionLogLabel,
                $currentStatus,
                'pending_revision',
                $currentStaffId,
                $revisionComment
            );

            return response()->json([
                'status' => 'success',
                'message' => 'ส่งกลับให้ผู้ทำรายการแก้ไขเรียบร้อยแล้ว',
                'data' => [
                    'next_status' => 'pending_revision',
                    'notification_debug' => $notificationDebug,
                ],
            ]);
        }

        if ($action === 'cancel_proceed') {
            if (! in_array($currentStatus, ['pending_primary_review', 'processing', 'pending_additional_review', 'pending_finance_review', 'pending_approval'], true)) {
                return response()->json(['status' => 'error', 'message' => 'สถานะรายการนี้ไม่สามารถยกเลิกรายการได้'], 422);
            }

            $this->withdrawQuery()
                ->where('withdraw_id', $id)
                ->update([
                    'withdraw_status' => 'cancelled',
                    'withdraw_date_modify' => now(),
                    'staff_modify' => $currentStaffId,
                ]);

            if (Schema::hasTable('withdraw_approval_step')) {
                DB::table('withdraw_approval_step')
                    ->where('withdraw_id', $id)
                    ->update([
                        'withdraw_approval_status' => 'pending',
                        'withdraw_approval_comment' => null,
                        'withdraw_approval_approved_at' => null,
                        'withdraw_approval_date_modify' => now(),
                        'staff_modify' => $currentStaffId,
                    ]);
            }

            $this->appendGeneralActionLog(
                $id,
                'cancel_proceed',
                'ยกเลิกรายการ',
                $currentStatus,
                'cancelled',
                $currentStaffId,
                $approvalComment
            );

            return response()->json([
                'status' => 'success',
                'message' => 'ยกเลิกรายการเบิกจ่ายเรียบร้อยแล้ว',
                'data' => [
                    'next_status' => 'cancelled',
                ],
            ]);
        }

        $isAdditionalApprovalFlow = false;
        if (
            $currentStatus === 'pending_additional_review'
            && $currentStaffId !== ''
            && Schema::hasTable('withdraw_approval_step')
        ) {
            $isAdditionalApprovalFlow = DB::table('withdraw_approval_step')
                ->where('withdraw_id', $id)
                ->where('withdraw_approval_step_code', 'additional_reviewer')
                ->where('withdraw_approval_staff_id', $currentStaffId)
                ->whereIn('withdraw_approval_status', ['pending', 'approved'])
                ->exists();
        }

        if (in_array($currentStatus, ['pending_approval', 'pending_primary_review', 'processing'], true)) {
            $deferExecutiveApprovalForFinanceReview = $currentStatus === 'pending_approval'
                && $this->salaryNeedsFinanceDocumentReview($id)
                && $this->currentStaffHasFinanceReviewerAccess($currentStaffId);

            if (! $deferExecutiveApprovalForFinanceReview) {
            if ($currentStaffId === '' || ! Schema::hasTable('withdraw_approval_step')) {
                return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์อนุมัติรายการ'], 403);
            }

            $approvalSteps = DB::table('withdraw_approval_step')
                ->where('withdraw_id', $id)
                ->orderBy('withdraw_approval_step_order')
                ->orderBy('withdraw_approval_step_id')
                ->get([
                    'withdraw_approval_step_id',
                    'withdraw_approval_step_code',
                    'withdraw_approval_step_name',
                    'withdraw_approval_step_order',
                    'withdraw_approval_staff_id',
                    'withdraw_approval_status',
                ]);

            $pendingUserStep = null;
            foreach ($approvalSteps as $step) {
                $stepCode = trim((string) ($step->withdraw_approval_step_code ?? ''));
                if ($this->isSalaryApprovalRouteExcludedStepCode($stepCode)) {
                    continue;
                }

                $stepStatus = trim((string) ($step->withdraw_approval_status ?? ''));
                if ($stepStatus === 'approved') {
                    continue;
                }

                if (
                    $stepStatus === 'pending'
                    && trim((string) ($step->withdraw_approval_staff_id ?? '')) === $currentStaffId
                ) {
                    $pendingUserStep = $step;
                }
                break;
            }
            if (! $pendingUserStep) {
                return response()->json(['status' => 'error', 'message' => 'ยังไม่ถึงลำดับการอนุมัติของท่าน'], 422);
            }

            $pendingStepCode = trim((string) ($pendingUserStep->withdraw_approval_step_code ?? ''));
            $pendingStepOrder = (int) ($pendingUserStep->withdraw_approval_step_order ?? 0);
            if ($pendingStepCode === '') {
                return response()->json(['status' => 'error', 'message' => 'ไม่พบขั้นตอนอนุมัติของรายการ'], 422);
            }

            $nextApprovalStep = DB::table('withdraw_approval_step')
                ->where('withdraw_id', $id)
                ->where('withdraw_approval_step_order', '>', $pendingStepOrder)
                ->where('withdraw_approval_status', 'pending')
                ->whereNotNull('withdraw_approval_staff_id')
                ->where('withdraw_approval_staff_id', '!=', '')
                ->orderBy('withdraw_approval_step_order')
                ->orderBy('withdraw_approval_step_id')
                ->first([
                    'withdraw_approval_step_id',
                    'withdraw_approval_step_code',
                    'withdraw_approval_step_name',
                    'withdraw_approval_staff_id',
                ]);

            $mustForwardToFinance = false;
            $isFinalApprover = false;
            $targetStepCode = '';
            $targetStepName = '';
            $targetStepStaffId = '';
            $targetActionUrl = WithdrawApprovalLink::nextApprover($id, 'salary');
            $targetStatusAfterProceed = 'pending_approval';
            $actionLogCode = 'approval_step_approved';
            $actionLogLabel = 'อนุมัติรายการตามลำดับ';

            if (! $nextApprovalStep) {
                $isFinalApprover = true;
            }

            if ($isFinalApprover) {
                $targetStatusAfterProceed = 'approved';
                $actionLogCode = 'approval_final_approved';
                $actionLogLabel = 'ผู้อนุมัติคนสุดท้ายอนุมัติรายการ';
            } else {
                $targetStepCode = trim((string) ($nextApprovalStep->withdraw_approval_step_code ?? ''));
                $targetStepName = trim((string) ($nextApprovalStep->withdraw_approval_step_name ?? 'ผู้อนุมัติลำดับถัดไป'));
                $targetStepStaffId = trim((string) ($nextApprovalStep->withdraw_approval_staff_id ?? ''));
                $targetActionUrl = WithdrawApprovalLink::nextApprover($id, 'salary', $targetStepCode);
            }

            DB::transaction(function () use (
                $id,
                $currentStaffId,
                $pendingStepCode,
                $approvalComment,
                $targetStatusAfterProceed,
                $targetStepCode
            ) {
                $this->markApprovalStepAsApproved($id, $pendingStepCode, $currentStaffId, $approvalComment, $currentStaffId);

                $this->withdrawQuery()
                    ->where('withdraw_id', $id)
                    ->update([
                        'withdraw_status' => $targetStatusAfterProceed,
                        'withdraw_date_modify' => now(),
                        'staff_modify' => $currentStaffId,
                    ]);

                if ($targetStepCode !== '') {
                    DB::table('withdraw_approval_step')
                        ->where('withdraw_id', $id)
                        ->where('withdraw_approval_step_code', $targetStepCode)
                        ->where('withdraw_approval_status', 'pending')
                        ->update([
                            'withdraw_approval_notified_at' => now(),
                            'withdraw_approval_date_modify' => now(),
                            'staff_modify' => $currentStaffId,
                        ]);
                }
            });

            $displayCode = $this->resolveWithdrawGeneralDisplayDocumentCode($row);
            if ($displayCode === '') {
                $displayCode = (string) $id;
            }
            $subjectText = trim((string) ($row->withdraw_subject ?? ''));
            $subjectText = $subjectText !== '' ? $subjectText : 'เรื่อง -';
            $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
            $applicantNameMap = $this->resolveStaffNamesByIds($applicantStaffId !== '' ? [$applicantStaffId] : []);
            $applicantName = trim((string) ($applicantNameMap[$applicantStaffId] ?? ''));
            $applicantText = $applicantName !== '' ? $applicantName : ($applicantStaffId !== '' ? $applicantStaffId : '-');
            $amountText = number_format((float) ($row->withdraw_total_amount ?? 0), 2);

            $notificationDebug = [
                'notified_staff_ids' => [],
                'mail_sent_staff_ids' => [],
                'mail_errors' => [],
            ];
            if ($targetStepStaffId !== '' && $this->withdrawSetApproverNotificationsEnabled($targetStepCode, $targetStepStaffId)) {
                try {
                    $createdBy = $currentStaffId !== '' ? $currentStaffId : 'system';
                    $targetRoleText = $targetStepName !== '' ? $targetStepName : 'ผู้อนุมัติ';
                    $notificationApiUrl = trim((string) config('services.notification_api.url', ''));
                    if ($notificationApiUrl !== '') {
                        Http::timeout(10)->post($notificationApiUrl, [
                            'staffId' => $targetStepStaffId,
                            'message' => 'มีรายการเบิกจ่ายเงินเดือน/ค่าจ้างรออนุมัติ/ตรวจสอบจากท่าน ('.$targetRoleText.') - '.$subjectText.' ผู้ขอเบิก '.$applicantText.' จำนวนเงิน '.$amountText,
                            'systemName' => 'ระบบเบิกจ่าย',
                            'actionUrl' => $targetActionUrl,
                            'createdBy' => $createdBy,
                        ]);
                    }
                    $notificationDebug['notified_staff_ids'][] = $targetStepStaffId;

                    $targetEmail = $this->resolveStaffEmailById($targetStepStaffId);
                    if ($targetEmail === '') {
                        $notificationDebug['mail_errors'][] = [
                            'staff_id' => $targetStepStaffId,
                            'error' => 'ไม่พบอีเมล',
                        ];
                    } else {
                        $targetNameMap = $this->resolveStaffNamesByIds([$targetStepStaffId]);
                        $recipientName = trim((string) ($targetNameMap[$targetStepStaffId] ?? ''));
                        if ($recipientName === '') {
                            $recipientName = $targetStepStaffId;
                        }
                        $mailTemplate = new WithdrawProceedNotificationMail([
                            'display_code' => $displayCode,
                            'subject_text' => $subjectText,
                            'recipient_name' => $recipientName,
                            'applicant_text' => $applicantText,
                            'amount_text' => $amountText,
                            'action_url' => $targetActionUrl,
                            'mail_subject' => '[ระบบเบิกจ่าย] มีรายการรอดำเนินการ '.$displayCode,
                            'header_title' => 'แจ้งเตือนรายการเบิกจ่ายเงินเดือน/ค่าจ้าง',
                            'header_description' => 'มีรายการส่งต่อเพื่ออนุมัติ/ตรวจสอบตามลำดับ',
                            'status_badge' => $mustForwardToFinance ? 'รอการเงินตรวจสอบเอกสาร' : 'รออนุมัติ',
                            'intro_message' => 'มีรายการเบิกจ่ายเงินเดือน/ค่าจ้างที่รอดำเนินการจากท่าน',
                            'button_label' => 'เปิดเพื่ออนุมัติ',
                        ]);
                        $smtpHost = trim((string) env('MAIL_HOST', ''));
                        $smtpPort = (int) env('MAIL_PORT', 587);
                        $smtpUsername = trim((string) env('MAIL_USERNAME', ''));
                        $smtpPassword = (string) env('MAIL_PASSWORD', '');
                        $smtpFromAddress = trim((string) env('MAIL_FROM_ADDRESS', ''));
                        $smtpFromName = trim((string) env('MAIL_FROM_NAME', (string) config('app.name', 'PBM Withdraw')));
                        if ($smtpHost === '' || $smtpFromAddress === '') {
                            $notificationDebug['mail_errors'][] = [
                                'staff_id' => $targetStepStaffId,
                                'error' => 'SMTP host/from address ไม่ครบถ้วน',
                            ];
                        } else {
                            $mailer = new PHPMailer(true);
                            try {
                                $mailer->CharSet = 'UTF-8';
                                $mailer->Encoding = 'base64';
                                $mailer->isSMTP();
                                $mailer->Host = $smtpHost;
                                $mailer->SMTPAuth = true;
                                $mailer->Username = $smtpUsername;
                                $mailer->Password = $smtpPassword;
                                $mailer->Port = $smtpPort > 0 ? $smtpPort : 587;
                                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                $mailer->SMTPAutoTLS = true;
                                $mailer->SMTPOptions = [
                                    'ssl' => [
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                        'allow_self_signed' => true,
                                    ],
                                ];
                                $mailer->setFrom($smtpFromAddress, $smtpFromName !== '' ? $smtpFromName : 'PBM Withdraw');
                                $mailer->addAddress($targetEmail);
                                $mailer->Subject = $mailTemplate->subjectLine();
                                $mailer->Body = $mailTemplate->render();
                                $mailer->AltBody = $mailTemplate->plainMessage();
                                $mailer->isHTML(true);
                                $mailer->send();
                                $notificationDebug['mail_sent_staff_ids'][] = $targetStepStaffId;
                            } catch (PHPMailerException $e) {
                                $notificationDebug['mail_errors'][] = [
                                    'staff_id' => $targetStepStaffId,
                                    'error' => $e->getMessage(),
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $notificationDebug['mail_errors'][] = [
                        'staff_id' => $targetStepStaffId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $nextStatusLabel = $targetStatusAfterProceed === 'approved'
                ? 'อนุมัติ'
                : 'รออนุมัติ';
            $this->appendGeneralActionLog(
                $id,
                $actionLogCode,
                $actionLogLabel,
                $currentStatus,
                $targetStatusAfterProceed,
                $currentStaffId,
                $approvalComment,
                [
                    'approved_step_code' => $pendingStepCode,
                    'next_step_code' => $targetStepCode,
                    'next_step_staff_id' => $targetStepStaffId,
                    'next_status_label' => $nextStatusLabel,
                ]
            );

            $onePlanningDebug = null;
            if ($isFinalApprover && $targetStatusAfterProceed === 'approved') {
                try {
                    $freshRow = $this->withdrawQuery()->where('withdraw_id', $id)->first();
                    if ($freshRow) {
                        $finalPayloads = $this->buildOnePlanningPayloadsForFinalApproval($freshRow);
                        $onePlanningDebug = $this->dispatchOnePlanningBudgetUsed(
                            $freshRow,
                            'สิ้นสุดการดำเนินการแล้ว',
                            'final_approval',
                            $currentStaffId,
                            $finalPayloads
                        );
                    }
                } catch (\Throwable $e) {
                    $onePlanningDebug = [
                        'success_count' => 0,
                        'failed_count' => 1,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => $isFinalApprover
                    ? 'อนุมัติรายการเรียบร้อยแล้ว'
                    : 'อนุมัติรายการเรียบร้อยแล้ว และส่งต่อผู้อนุมัติลำดับถัดไปแล้ว',
                'data' => [
                    'next_status' => $targetStatusAfterProceed,
                    'next_step_code' => $targetStepCode,
                    'next_step_staff_id' => $targetStepStaffId,
                    'notification_debug' => $notificationDebug,
                    'one_planning' => $onePlanningDebug,
                ],
            ]);
            }
        }

        if (! $isAdditionalApprovalFlow && $currentStatus !== 'pending_approval') {
            if (! in_array($currentStatus, ['saved', 'pending_revision'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'สถานะรายการนี้ไม่สามารถส่งกลับไปเริ่มตรวจสอบใหม่ได้',
                ], 422);
            }
            $updatePayload = [
                'withdraw_status' => 'pending_primary_review',
                'withdraw_date_modify' => now(),
                'staff_modify' => (string) ($request->session()->get('staff_id') ?? ''),
            ];

            $documentMeta = $this->resolveWithdrawGeneralDocumentMeta($row);
            $this->assignWithdrawDocumentNumber(
                $id,
                'salary',
                (string) ($documentMeta['department_code'] ?? ''),
                (string) ($documentMeta['document_number'] ?? ''),
                (string) ($documentMeta['document_date'] ?? ''),
                (int) ($documentMeta['running_year'] ?? 0),
                (int) ($documentMeta['running_no'] ?? 0)
            );
            $salaryRequestType = Schema::hasColumn('withdraw', 'withdraw_sub_type')
                ? $this->normalizeSalaryRequestType((string) ($row->withdraw_sub_type ?? 'salary'))
                : 'salary';
            $this->assignSalaryPtsSecondDocumentNumberIfNeeded($id, $salaryRequestType, $documentMeta);

            $this->withdrawQuery()
                ->where('withdraw_id', $id)
                ->update($updatePayload);

            $this->syncApprovalSteps(
                $id,
                $this->loadSalaryProcessActorsFromApprovalSteps($id),
                (string) ($request->session()->get('staff_id') ?? '')
            );

            $notifyResult = $this->notifyNextPendingSalaryApprovalStep(
                $id,
                $row,
                $request
            );
            $mailDebug = $notifyResult['mail_debug'];

            $proceedStaffId = (string) ($request->session()->get('staff_id') ?? '');
            $this->appendGeneralActionLog(
                $id,
                'submit_for_reviewer',
                'ส่งรายการให้ผู้ตรวจสอบ',
                $currentStatus,
                'pending_primary_review',
                $proceedStaffId,
                $approvalComment
            );

            // เบิกจ่ายเงินเดือน: ส่ง One Planning รอบแรกตอนดำเนินการเบิกจ่าย
            $onePlanningDebug = ['skipped' => true];
            try {
                $freshRow = $this->withdrawQuery()->where('withdraw_id', $id)->first();
                if ($freshRow) {
                    $onePlanningDebug = $this->dispatchOnePlanningBudgetUsed(
                        $freshRow,
                        'อยู่ระหว่างดำเนินการ',
                        'proceed',
                        $proceedStaffId
                    );
                }
            } catch (\Throwable $e) {
                $onePlanningDebug = [
                    'skipped' => false,
                    'success_count' => 0,
                    'failed_count' => 1,
                    'error' => $e->getMessage(),
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => 'ส่งรายการให้ผู้ตรวจสอบเรียบร้อยแล้ว',
                'data' => [
                    'mail_debug' => $mailDebug,
                    'notification_debug' => $notifyResult['notification_debug'],
                    'one_planning' => $onePlanningDebug,
                ],
            ]);
        }

        if ($isAdditionalApprovalFlow) {
            $this->markApprovalStepAsApproved($id, 'additional_reviewer', $currentStaffId, $approvalComment, $currentStaffId);

            $remainingAdditionalReviewerCount = 0;
            if (Schema::hasTable('withdraw_approval_step')) {
                $remainingAdditionalReviewerCount = (int) DB::table('withdraw_approval_step')
                    ->where('withdraw_id', $id)
                    ->where('withdraw_approval_step_code', 'additional_reviewer')
                    ->where('withdraw_approval_status', 'pending')
                    ->count();
            }

            if ($remainingAdditionalReviewerCount > 0) {
                $this->appendGeneralActionLog(
                    $id,
                    'additional_reviewer_approved',
                    'ผู้ตรวจสอบเพิ่มเติมอนุมัติ',
                    $currentStatus,
                    'pending_additional_review',
                    $currentStaffId,
                    $approvalComment
                );
                return response()->json([
                    'status' => 'success',
                    'message' => 'บันทึกการตรวจสอบเพิ่มเติมเรียบร้อย',
                    'data' => [
                        'next_status' => 'pending_additional_review',
                    ],
                ]);
            }

            $this->withdrawQuery()
                ->where('withdraw_id', $id)
                ->update([
                    'withdraw_status' => 'pending_finance_review',
                    'withdraw_date_modify' => now(),
                    'staff_modify' => $currentStaffId,
                ]);

            $this->ensureSalaryFinanceOfficerApprovalStep($id, $currentStaffId);
            $this->markApprovalStepAsApproved($id, 'finance_officer', '', '', $currentStaffId, 'pending');

            $this->appendGeneralActionLog(
                $id,
                'additional_review_completed',
                'ตรวจสอบเพิ่มเติมครบ ส่งต่อฝ่ายการเงิน',
                $currentStatus,
                'pending_finance_review',
                $currentStaffId,
                $approvalComment
            );

            $notifyResult = $this->notifyNextPendingSalaryApprovalStep(
                $id,
                $row,
                $request
            );

            return response()->json([
                'status' => 'success',
                'message' => 'บันทึกการตรวจสอบเพิ่มเติมแล้ว และส่งต่อไปยังลำดับถัดไปเรียบร้อย',
                'data' => [
                    'next_status' => 'pending_finance_review',
                    'notification_debug' => $notifyResult['notification_debug'],
                ],
            ]);
        }

        if ($currentStatus === 'pending_finance_review' || ($currentStatus === 'pending_approval' && $this->salaryNeedsFinanceDocumentReview($id))) {
            if ($currentStaffId === '' || ! $this->currentStaffHasFinanceReviewerAccess($currentStaffId)) {
                return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ตรวจสอบรายการการเงิน'], 403);
            }
            if (! Schema::hasTable('withdraw_item')) {
                return response()->json(['status' => 'error', 'message' => 'ยังไม่พร้อมใช้งานการผูกผังบัญชี'], 500);
            }
            if (! Schema::hasColumn('withdraw_item', 'withdraw_item_expense_chart_id')
                || ! Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_id')
                || ! Schema::hasColumn('withdraw_item', 'withdraw_item_finance_amount')) {
                return response()->json(['status' => 'error', 'message' => 'โครงสร้างฐานข้อมูลยังไม่รองรับการผูกผังบัญชี/งบประมาณ กรุณารัน migrate'], 500);
            }

            $budgetManageId = (int) $request->input('budget_manage_id', 0);
            if ($budgetManageId <= 0) {
                return response()->json(['status' => 'error', 'message' => 'กรุณาเลือกงบประมาณก่อนยืนยันตรวจสอบ'], 422);
            }
            $budgetManageCode = trim((string) $request->input('budget_manage_code', ''));
            if ($budgetManageCode === '') {
                $budgetManageCode = WithdrawSchema::budgetManageCodeById($budgetManageId);
            }

            $expenseMappingsInput = $request->input('expense_mappings', []);
            if (is_string($expenseMappingsInput)) {
                $decodedMappings = json_decode($expenseMappingsInput, true);
                $expenseMappingsInput = is_array($decodedMappings) ? $decodedMappings : [];
            }
            // ดึงข้อมูลบันทึก
            $documentData = [
                'department_code' => trim((string) ($request->input('department_code', '12010401') ?? '12010401')),
                'document_number' => trim((string) ($request->input('document_number', '') ?? '')),
                'document_date' => trim((string) ($request->input('document_date', '') ?? '')),
                'subject' => trim((string) ($request->input('subject', '') ?? '')),
                'to' => trim((string) ($request->input('to', '') ?? '')),
                'paragraph1' => trim((string) ($request->input('paragraph1', '') ?? '')),
                'paragraph2' => trim((string) ($request->input('paragraph2', '') ?? '')),
                'paragraph3' => trim((string) ($request->input('paragraph3', '') ?? '')),
            ];

            $mappingByItemId = collect(is_array($expenseMappingsInput) ? $expenseMappingsInput : [])
                ->filter(fn ($row) => is_array($row))
                ->mapWithKeys(function (array $row) {
                    $itemId = (int) ($row['item_id'] ?? 0);
                    if ($itemId <= 0) {
                        return [];
                    }

                    return [$itemId => [
                        'expense_chart_id' => (int) ($row['expense_chart_id'] ?? 0),
                        'amount' => round((float) ($row['amount'] ?? 0), 2),
                    ]];
                })
                ->all();

            $generalItems = DB::table('withdraw_item')
                ->where('withdraw_id', $id)
                ->orderBy('withdraw_item_order')
                ->orderBy('withdraw_item_id')
                ->get([
                    'withdraw_item_id as id',
                    'withdraw_item_name as name',
                ]);
            if ($generalItems->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'ไม่พบรายการเบิกจ่ายสำหรับผูกผังบัญชี'], 422);
            }

            $missingMappingRows = $generalItems
                ->filter(function ($item) use ($mappingByItemId) {
                    $itemId = (int) ($item->id ?? 0);
                    return $itemId > 0 && (int) ($mappingByItemId[$itemId]['expense_chart_id'] ?? 0) <= 0;
                })
                ->values();
            if ($missingMappingRows->isNotEmpty()) {
                $firstMissing = $missingMappingRows->first();
                $itemName = trim((string) ($firstMissing->name ?? ''));
                return response()->json([
                    'status' => 'error',
                    'message' => $itemName !== ''
                        ? 'กรุณาเลือกผังบัญชีให้ครบทุกแถว (รายการ '.$itemName.')'
                        : 'กรุณาเลือกผังบัญชีให้ครบทุกแถว',
                ], 422);
            }
            $missingAmountRows = $generalItems
                ->filter(function ($item) use ($mappingByItemId) {
                    $itemId = (int) ($item->id ?? 0);
                    return $itemId > 0 && (float) ($mappingByItemId[$itemId]['amount'] ?? 0) <= 0;
                })
                ->values();
            if ($missingAmountRows->isNotEmpty()) {
                $firstMissingAmount = $missingAmountRows->first();
                $amountItemName = trim((string) ($firstMissingAmount->name ?? ''));
                return response()->json([
                    'status' => 'error',
                    'message' => $amountItemName !== ''
                        ? 'กรุณากรอกจำนวนเงินให้มากกว่า 0 (รายการ '.$amountItemName.')'
                        : 'กรุณากรอกจำนวนเงินให้มากกว่า 0 ทุกแถว',
                ], 422);
            }

            $onePlanningPayloadSnapshot = [];

            try {
                DB::transaction(function () use ($id, $currentStaffId, $budgetManageId, $budgetManageCode, $mappingByItemId, $approvalComment, $generalItems, $documentData, $request, $row, &$onePlanningPayloadSnapshot) {
                    $now = now();
                    $onePlanningPayloadSnapshot = $this->buildOnePlanningBudgetUsedPayloads($row, 'อยู่ระหว่างดำเนินการ');
                    $itemBudgetRows = DB::table('withdraw_item')
                        ->where('withdraw_id', $id)
                        ->get(['withdraw_item_id', 'withdraw_item_budget_manage_id']);
                    $financeAmountByBudgetId = [];
                    foreach ($itemBudgetRows as $itemBudgetRow) {
                        $mappedItemId = (int) ($itemBudgetRow->withdraw_item_id ?? 0);
                        $mappedBudgetId = (int) ($itemBudgetRow->withdraw_item_budget_manage_id ?? 0);
                        $mappedAmount = round((float) ($mappingByItemId[$mappedItemId]['amount'] ?? 0), 2);
                        if ($mappedBudgetId <= 0 || $mappedAmount <= 0) {
                            continue;
                        }
                        $financeAmountByBudgetId[$mappedBudgetId] = round(($financeAmountByBudgetId[$mappedBudgetId] ?? 0) + $mappedAmount, 2);
                    }
                    $onePlanningPayloadSnapshot = array_map(function (array $payload) use ($financeAmountByBudgetId) {
                        $payloadBudgetId = (int) ($payload['budget_manage_id'] ?? 0);
                        if ($payloadBudgetId > 0 && isset($financeAmountByBudgetId[$payloadBudgetId])) {
                            $payload['budget_value'] = number_format($financeAmountByBudgetId[$payloadBudgetId], 2, '.', '');
                        }

                        return $payload;
                    }, $onePlanningPayloadSnapshot);

                    $updatedItemCount = 0;
                    foreach ($mappingByItemId as $itemId => $mapping) {
                        $itemUpdate = [
                            'withdraw_item_expense_chart_id' => (int) ($mapping['expense_chart_id'] ?? 0),
                            'withdraw_item_finance_amount' => round((float) ($mapping['amount'] ?? 0), 2),
                            'withdraw_item_budget_manage_id' => $budgetManageId,
                            'withdraw_item_date_modify' => $now,
                            'staff_modify' => $currentStaffId,
                        ];
                        if (Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_code')) {
                            $itemUpdate['withdraw_item_budget_manage_code'] = $budgetManageCode !== '' ? $budgetManageCode : null;
                        }
                        $affected = DB::table('withdraw_item')
                            ->where('withdraw_id', $id)
                            ->where('withdraw_item_id', (int) $itemId)
                            ->update($itemUpdate);
                        if ($affected > 0) {
                            $updatedItemCount += 1;
                        }
                    }
                    if ($updatedItemCount !== $generalItems->count()) {
                        throw new \RuntimeException('ไม่สามารถบันทึกผังบัญชี/งบประมาณได้ครบทุกแถว');
                    }

                    $this->storeFinanceEsignUploads($request, $id, $currentStaffId, 'salary');

                    $normalizedFinanceDepartmentCode = $documentData['department_code'] !== ''
                        ? $documentData['department_code']
                        : '12010401';
                    [$financeRunningYear, $financeRunningNo] = $this->assignWithdrawDocumentNumber(
                        $id,
                        'salary_finance',
                        $normalizedFinanceDepartmentCode,
                        $documentData['document_number'],
                        $documentData['document_date']
                    );

                    $generalUpdatePayload = [
                        'withdraw_status' => 'pending_approval',
                        'withdraw_date_modify' => $now,
                        'staff_modify' => $currentStaffId,
                    ];
                    if (Schema::hasColumn('withdraw', 'withdraw_budget_manage_id')) {
                        $generalUpdatePayload['withdraw_budget_manage_id'] = $budgetManageId;
                    }
                    if (Schema::hasColumn('withdraw', 'withdraw_finance_document_date')) {
                        $generalUpdatePayload['withdraw_finance_document_date'] = $documentData['document_date'] !== '' ? $documentData['document_date'] : null;
                    }
                    if (Schema::hasColumn('withdraw', 'withdraw_finance_subject')) {
                        $generalUpdatePayload['withdraw_finance_subject'] = $documentData['subject'] !== '' ? $documentData['subject'] : null;
                    }
                    if (Schema::hasColumn('withdraw', 'withdraw_finance_to')) {
                        $generalUpdatePayload['withdraw_finance_to'] = $documentData['to'] !== '' ? $documentData['to'] : null;
                    }
                    if (Schema::hasColumn('withdraw', 'withdraw_finance_paragraph_1')) {
                        $generalUpdatePayload['withdraw_finance_paragraph_1'] = $documentData['paragraph1'] !== '' ? $documentData['paragraph1'] : null;
                    }
                    if (Schema::hasColumn('withdraw', 'withdraw_finance_paragraph_2')) {
                        $generalUpdatePayload['withdraw_finance_paragraph_2'] = $documentData['paragraph2'] !== '' ? $documentData['paragraph2'] : null;
                    }
                    if (Schema::hasColumn('withdraw', 'withdraw_finance_paragraph_3')) {
                        $generalUpdatePayload['withdraw_finance_paragraph_3'] = $documentData['paragraph3'] !== '' ? $documentData['paragraph3'] : null;
                    }

                    $updatedGeneral = $this->withdrawQuery()
                        ->where('withdraw_id', $id)
                        ->update($generalUpdatePayload);
                    if ($updatedGeneral <= 0) {
                        throw new \RuntimeException('ไม่สามารถอัปเดตสถานะรายการเป็นรออนุมัติได้');
                    }

                    if (Schema::hasTable('withdraw_approval_step')) {
                        DB::table('withdraw_approval_step')
                            ->where('withdraw_id', $id)
                            ->where('withdraw_approval_step_code', 'finance_officer')
                            ->update([
                                'withdraw_approval_staff_id' => $currentStaffId !== '' ? $currentStaffId : null,
                                'withdraw_approval_date_modify' => $now,
                                'staff_modify' => $currentStaffId,
                            ]);
                    }

                    $this->markApprovalStepAsApproved($id, 'finance_officer', '', $approvalComment, $currentStaffId);
                    $this->markApprovalStepAsApproved($id, 'finance_manager', '', '', $currentStaffId, 'pending');
                    $persistedCount = (int) DB::table('withdraw_item')
                        ->where('withdraw_id', $id)
                        ->where('withdraw_item_budget_manage_id', $budgetManageId)
                        ->whereNotNull('withdraw_item_expense_chart_id')
                        ->where('withdraw_item_expense_chart_id', '>', 0)
                        ->count();
                    if ($persistedCount !== $generalItems->count()) {
                        throw new \RuntimeException('บันทึกข้อมูลรายการเบิกจ่ายไม่ครบถ้วน');
                    }
                });
            } catch (\Throwable $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : 'ไม่สามารถบันทึกข้อมูลตรวจสอบเอกสารได้',
                ], 422);
            }

            $financeManagerStaffId = '';
            if (Schema::hasTable('withdraw_approval_step')) {
                $financeManagerStaffId = trim((string) (DB::table('withdraw_approval_step')
                    ->where('withdraw_id', $id)
                    ->where('withdraw_approval_step_code', 'finance_manager')
                    ->value('withdraw_approval_staff_id') ?? ''));
            }
            $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
            $notificationDebug = [
                'notified_staff_ids' => [],
                'mail_sent_staff_ids' => [],
                'mail_errors' => [],
            ];
            if ($financeManagerStaffId !== '') {
                try {
                    $displayCode = $this->resolveWithdrawGeneralDisplayDocumentCode($row);
                    if ($displayCode === '') {
                        $displayCode = (string) $id;
                    }
                    $subjectText = trim((string) ($row->withdraw_subject ?? ''));
                    $subjectText = $subjectText !== '' ? $subjectText : 'เรื่อง -';
                    $applicantNameMap = $this->resolveStaffNamesByIds($applicantStaffId !== '' ? [$applicantStaffId] : []);
                    $applicantName = trim((string) ($applicantNameMap[$applicantStaffId] ?? ''));
                    $applicantText = $applicantName !== '' ? $applicantName : ($applicantStaffId !== '' ? $applicantStaffId : '-');
                    $amountText = number_format((float) ($row->withdraw_total_amount ?? 0), 2);
                    $createdBy = $currentStaffId !== '' ? $currentStaffId : 'system';

                    $notificationApiUrl = trim((string) config('services.notification_api.url', ''));
                    if ($notificationApiUrl !== '') {
                        Http::timeout(10)->post($notificationApiUrl, [
                            'staffId' => $financeManagerStaffId,
                            'message' => 'มีรายการเบิกจ่ายเงินเดือน/ค่าจ้าง รออนุมัติจากท่าน - '.$subjectText.' ผู้ขอเบิก '.$applicantText.' จำนวนเงิน '.$amountText,
                            'systemName' => 'ระบบเบิกจ่าย',
                            'actionUrl' => WithdrawApprovalLink::nextApprover($id, 'salary', 'finance_manager'),
                            'createdBy' => $createdBy,
                        ]);
                    }
                    $notificationDebug['notified_staff_ids'][] = $financeManagerStaffId;

                    $targetEmail = $this->resolveStaffEmailById($financeManagerStaffId);
                    if ($targetEmail === '') {
                        $notificationDebug['mail_errors'][] = [
                            'staff_id' => $financeManagerStaffId,
                            'error' => 'ไม่พบอีเมล',
                        ];
                    } else {
                        $financeManagerNameMap = $this->resolveStaffNamesByIds([$financeManagerStaffId]);
                        $recipientName = trim((string) ($financeManagerNameMap[$financeManagerStaffId] ?? ''));
                        if ($recipientName === '') {
                            $recipientName = $financeManagerStaffId;
                        }

                        $mailTemplate = new WithdrawProceedNotificationMail([
                            'display_code' => $displayCode,
                            'subject_text' => $subjectText,
                            'recipient_name' => $recipientName,
                            'applicant_text' => $applicantText,
                            'amount_text' => $amountText,
                            'action_url' => WithdrawApprovalLink::nextApprover($id, 'salary', 'finance_manager'),
                            'mail_subject' => '[ระบบเบิกจ่าย] มีรายการรออนุมัติ '.$displayCode,
                            'header_title' => 'แจ้งเตือนรายการเบิกจ่ายเงินเดือน/ค่าจ้าง',
                            'header_description' => 'มีรายการรออนุมัติจากหัวหน้างานการเงิน',
                            'status_badge' => 'รออนุมัติ',
                            'intro_message' => 'มีรายการเบิกจ่ายเงินเดือน/ค่าจ้างที่รออนุมัติจากท่าน',
                            'button_label' => 'เปิดเพื่ออนุมัติ',
                        ]);
                        $smtpHost = trim((string) env('MAIL_HOST', ''));
                        $smtpPort = (int) env('MAIL_PORT', 587);
                        $smtpUsername = trim((string) env('MAIL_USERNAME', ''));
                        $smtpPassword = (string) env('MAIL_PASSWORD', '');
                        $smtpFromAddress = trim((string) env('MAIL_FROM_ADDRESS', ''));
                        $smtpFromName = trim((string) env('MAIL_FROM_NAME', (string) config('app.name', 'PBM Withdraw')));

                        if ($smtpHost === '' || $smtpFromAddress === '') {
                            $notificationDebug['mail_errors'][] = [
                                'staff_id' => $financeManagerStaffId,
                                'error' => 'SMTP host/from address ไม่ครบถ้วน',
                            ];
                        } else {
                            $mailer = new PHPMailer(true);
                            try {
                                $mailer->CharSet = 'UTF-8';
                                $mailer->Encoding = 'base64';
                                $mailer->isSMTP();
                                $mailer->Host = $smtpHost;
                                $mailer->SMTPAuth = true;
                                $mailer->Username = $smtpUsername;
                                $mailer->Password = $smtpPassword;
                                $mailer->Port = $smtpPort > 0 ? $smtpPort : 587;
                                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                $mailer->SMTPAutoTLS = true;
                                $mailer->SMTPOptions = [
                                    'ssl' => [
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                        'allow_self_signed' => true,
                                    ],
                                ];
                                $mailer->setFrom($smtpFromAddress, $smtpFromName !== '' ? $smtpFromName : 'PBM Withdraw');
                                $mailer->addAddress($targetEmail);
                                $mailer->Subject = $mailTemplate->subjectLine();
                                $mailer->Body = $mailTemplate->render();
                                $mailer->AltBody = $mailTemplate->plainMessage();
                                $mailer->isHTML(true);
                                $mailer->send();
                                $notificationDebug['mail_sent_staff_ids'][] = $financeManagerStaffId;
                            } catch (PHPMailerException $e) {
                                $notificationDebug['mail_errors'][] = [
                                    'staff_id' => $financeManagerStaffId,
                                    'error' => $e->getMessage(),
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $notificationDebug['mail_errors'][] = [
                        'staff_id' => $financeManagerStaffId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if ($applicantStaffId !== '') {
                try {
                    $displayCode = $this->resolveWithdrawGeneralDisplayDocumentCode($row);
                    if ($displayCode === '') {
                        $displayCode = (string) $id;
                    }
                    $subjectText = trim((string) ($row->withdraw_subject ?? ''));
                    $subjectText = $subjectText !== '' ? $subjectText : 'เรื่อง -';
                    $amountText = number_format((float) ($row->withdraw_total_amount ?? 0), 2);
                    $createdBy = $currentStaffId !== '' ? $currentStaffId : 'system';
                    $applicantNameMap = $this->resolveStaffNamesByIds([$applicantStaffId]);
                    $applicantName = trim((string) ($applicantNameMap[$applicantStaffId] ?? ''));
                    $applicantText = $applicantName !== '' ? $applicantName : $applicantStaffId;
                    $notificationMessage = 'งานการเงินตรวจสอบเอกสารรายการเบิกจ่ายเรียบร้อยแล้ว กรุณานำเอกสารประกอบการเบิกจ่ายตัวจริงมาส่งให้งานการเงิน - '
                        .$subjectText.' ผู้ขอเบิก '.$applicantText.' จำนวนเงิน '.$amountText;

                    $notificationApiUrl = trim((string) config('services.notification_api.url', ''));
                    if ($notificationApiUrl !== '') {
                        Http::timeout(10)->post($notificationApiUrl, [
                            'staffId' => $applicantStaffId,
                            'message' => $notificationMessage,
                            'systemName' => 'ระบบเบิกจ่าย',
                            'actionUrl' => url('/withdraw/general'),
                            'createdBy' => $createdBy,
                        ]);
                    }
                    $notificationDebug['notified_staff_ids'][] = $applicantStaffId;

                    $targetEmail = $this->resolveStaffEmailById($applicantStaffId);
                    if ($targetEmail === '') {
                        $notificationDebug['mail_errors'][] = [
                            'staff_id' => $applicantStaffId,
                            'error' => 'ไม่พบอีเมล',
                        ];
                    } else {
                        $recipientName = trim((string) ($applicantNameMap[$applicantStaffId] ?? ''));
                        if ($recipientName === '') {
                            $recipientName = $applicantStaffId;
                        }

                        $mailTemplate = new WithdrawProceedNotificationMail([
                            'display_code' => $displayCode,
                            'subject_text' => $subjectText,
                            'recipient_name' => $recipientName,
                            'applicant_text' => $recipientName,
                            'amount_text' => $amountText,
                            'action_url' => url('/withdraw/general'),
                            'mail_subject' => '[ระบบเบิกจ่าย] กรุณานำส่งเอกสารตัวจริง '.$displayCode,
                            'header_title' => 'แจ้งเตือนการนำส่งเอกสารตัวจริง',
                            'header_description' => 'งานการเงินตรวจสอบรายการแล้ว กรุณานำเอกสารตัวจริงมาส่ง',
                            'status_badge' => 'รอนำส่งเอกสาร',
                            'intro_message' => 'งานการเงินตรวจสอบเอกสารเรียบร้อยแล้ว กรุณานำเอกสารประกอบการเบิกจ่ายตัวจริงมาส่งให้งานการเงิน',
                            'button_label' => 'เปิดรายการเบิกจ่าย',
                            'extra_note' => 'กรุณานำส่งเอกสารตัวจริงให้ครบถ้วนตามรายการ',
                        ]);
                        $smtpHost = trim((string) env('MAIL_HOST', ''));
                        $smtpPort = (int) env('MAIL_PORT', 587);
                        $smtpUsername = trim((string) env('MAIL_USERNAME', ''));
                        $smtpPassword = (string) env('MAIL_PASSWORD', '');
                        $smtpFromAddress = trim((string) env('MAIL_FROM_ADDRESS', ''));
                        $smtpFromName = trim((string) env('MAIL_FROM_NAME', (string) config('app.name', 'PBM Withdraw')));

                        if ($smtpHost === '' || $smtpFromAddress === '') {
                            $notificationDebug['mail_errors'][] = [
                                'staff_id' => $applicantStaffId,
                                'error' => 'SMTP host/from address ไม่ครบถ้วน',
                            ];
                        } else {
                            $mailer = new PHPMailer(true);
                            try {
                                $mailer->CharSet = 'UTF-8';
                                $mailer->Encoding = 'base64';
                                $mailer->isSMTP();
                                $mailer->Host = $smtpHost;
                                $mailer->SMTPAuth = true;
                                $mailer->Username = $smtpUsername;
                                $mailer->Password = $smtpPassword;
                                $mailer->Port = $smtpPort > 0 ? $smtpPort : 587;
                                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                $mailer->SMTPAutoTLS = true;
                                $mailer->SMTPOptions = [
                                    'ssl' => [
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                        'allow_self_signed' => true,
                                    ],
                                ];
                                $mailer->setFrom($smtpFromAddress, $smtpFromName !== '' ? $smtpFromName : 'PBM Withdraw');
                                $mailer->addAddress($targetEmail);
                                $mailer->Subject = $mailTemplate->subjectLine();
                                $mailer->Body = $mailTemplate->render();
                                $mailer->AltBody = $mailTemplate->plainMessage();
                                $mailer->isHTML(true);
                                $mailer->send();
                                $notificationDebug['mail_sent_staff_ids'][] = $applicantStaffId;
                            } catch (PHPMailerException $e) {
                                $notificationDebug['mail_errors'][] = [
                                    'staff_id' => $applicantStaffId,
                                    'error' => $e->getMessage(),
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $notificationDebug['mail_errors'][] = [
                        'staff_id' => $applicantStaffId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $this->appendGeneralActionLog(
                $id,
                'finance_review_completed',
                'งานการเงินตรวจสอบเอกสารเรียบร้อย',
                $currentStatus,
                'pending_approval',
                $currentStaffId,
                $approvalComment,
                ['budget_manage_id' => $budgetManageId]
            );

            $onePlanningDebug = ['skipped' => true];
            try {
                $freshRow = $this->withdrawQuery()->where('withdraw_id', $id)->first();
                if ($freshRow) {
                    $onePlanningDebug = $this->dispatchOnePlanningBudgetUsed(
                        $freshRow,
                        'อยู่ระหว่างดำเนินการ',
                        'finance_review',
                        $currentStaffId,
                        $onePlanningPayloadSnapshot
                    );
                }
            } catch (\Throwable $e) {
                $onePlanningDebug = [
                    'skipped' => false,
                    'success_count' => 0,
                    'failed_count' => 1,
                    'error' => $e->getMessage(),
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => 'ตรวจสอบรายการและผูกงบประมาณเรียบร้อยแล้ว',
                'data' => [
                    'next_status' => 'pending_approval',
                    'budget_manage_id' => $budgetManageId,
                    'notification_debug' => $notificationDebug,
                    'one_planning' => $onePlanningDebug,
                ],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'สถานะรายการนี้ไม่สามารถดำเนินการได้',
        ], 422);
    }

    public function apiList(Request $request): JsonResponse
    {
        $scope = trim((string) $request->query('scope', ''));
        $currentStaffId = trim((string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? ''));

        return response()->json([
            'status' => 'success',
            'data' => $this->fetchApprovalListRows($scope, $currentStaffId)->values(),
        ]);
    }

    /**
     * Pending only when this staff's step is currently their turn
     * (all previous steps approved). Future pending assignments are not counted.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $steps
     */
    private function resolveActionableUserStepStatus($steps, string $staffId): string
    {
        $staffId = trim($staffId);
        $ordered = collect($steps)
            ->sortBy(fn ($row) => (int) ($row->withdraw_approval_step_order ?? 0))
            ->values();
        $hasApproved = false;
        $fallback = '';
        foreach ($ordered as $step) {
            if (trim((string) ($step->withdraw_approval_staff_id ?? '')) !== $staffId) {
                continue;
            }
            $status = trim((string) ($step->withdraw_approval_status ?? ''));
            if ($status === 'approved') {
                $hasApproved = true;
            }
            if ($fallback === '' && $status !== '') {
                $fallback = $status;
            }
            if ($status !== 'pending') {
                continue;
            }
            $order = (int) ($step->withdraw_approval_step_order ?? 0);
            $blockedByPrevious = $ordered->contains(function ($prev) use ($order) {
                if ((int) ($prev->withdraw_approval_step_order ?? 0) >= $order) {
                    return false;
                }

                return trim((string) ($prev->withdraw_approval_status ?? '')) !== 'approved';
            });
            if (! $blockedByPrevious) {
                return 'pending';
            }
        }

        return $hasApproved ? 'approved' : $fallback;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $steps
     */
    private function resolveActionableUserStepName($steps, string $staffId): string
    {
        $staffId = trim($staffId);
        $ordered = collect($steps)
            ->sortBy(fn ($row) => (int) ($row->withdraw_approval_step_order ?? 0))
            ->values();
        $fallbackName = '';
        foreach ($ordered as $step) {
            if (trim((string) ($step->withdraw_approval_staff_id ?? '')) !== $staffId) {
                continue;
            }
            $name = trim((string) ($step->withdraw_approval_step_name ?? ''));
            if ($fallbackName === '' && $name !== '') {
                $fallbackName = $name;
            }
            if (trim((string) ($step->withdraw_approval_status ?? '')) !== 'pending') {
                continue;
            }
            $order = (int) ($step->withdraw_approval_step_order ?? 0);
            $blockedByPrevious = $ordered->contains(function ($prev) use ($order) {
                if ((int) ($prev->withdraw_approval_step_order ?? 0) >= $order) {
                    return false;
                }

                return trim((string) ($prev->withdraw_approval_status ?? '')) !== 'approved';
            });
            if (! $blockedByPrevious && $name !== '') {
                return $name;
            }
        }

        return $fallbackName;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $steps
     */
    private function resolveActionableUserStepCode($steps, string $staffId): string
    {
        $staffId = trim($staffId);
        $ordered = collect($steps)
            ->sortBy(fn ($row) => (int) ($row->withdraw_approval_step_order ?? 0))
            ->values();
        $fallbackCode = '';
        foreach ($ordered as $step) {
            if (trim((string) ($step->withdraw_approval_staff_id ?? '')) !== $staffId) {
                continue;
            }
            $code = trim((string) ($step->withdraw_approval_step_code ?? ''));
            if ($fallbackCode === '' && $code !== '') {
                $fallbackCode = $code;
            }
            if (trim((string) ($step->withdraw_approval_status ?? '')) !== 'pending') {
                continue;
            }
            $order = (int) ($step->withdraw_approval_step_order ?? 0);
            $blockedByPrevious = $ordered->contains(function ($prev) use ($order) {
                if ((int) ($prev->withdraw_approval_step_order ?? 0) >= $order) {
                    return false;
                }

                return trim((string) ($prev->withdraw_approval_status ?? '')) !== 'approved';
            });
            if (! $blockedByPrevious && $code !== '') {
                return $code;
            }
        }

        return $fallbackCode;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function fetchApprovalListRows(string $scope = '', string $currentStaffId = ''): \Illuminate\Support\Collection
    {
        return $this->fetchGeneralRows($scope, $currentStaffId)
            ->map(function (array $row): array {
                $row['withdraw_kind'] = 'salary';

                return $row;
            });
    }

    public function apiNextRunningNo(Request $request): JsonResponse
    {
        $departmentCode = trim((string) $request->query('department_code', ''));
        $documentDate = trim((string) $request->query('document_date', ''));
        $preview = app(DocumentNumberClient::class)->preview($departmentCode);

        return response()->json([
            'status' => 'success',
            'data' => [
                'running_year' => $this->resolveDocumentRunningYear($documentDate),
                'running_no' => $preview['running_no'],
                'document_number' => $preview['document_number'],
                'full_number' => $preview['full_number'],
            ],
        ]);
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
            // Fallback to local tables when budget connection is unavailable.
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

    /**
     * Same as list column "เลขที่" / detail-modal fallback: append running no when present
     * (see fetchGeneralRows map + detail-modal.blade.php detailCodeEl).
     */
    private function resolveWithdrawGeneralDisplayDocumentCode(object $row): string
    {
        $documentMeta = $this->resolveWithdrawGeneralDocumentMeta($row);
        $documentNumber = trim((string) ($documentMeta['document_number'] ?? ''));
        $runningNo = (int) ($documentMeta['running_no'] ?? 0);
        $displayDocumentNumber = $documentNumber;
        if ($documentNumber !== '' && $runningNo > 0) {
            $displayDocumentNumber = $documentNumber.$runningNo;
        }

        return $displayDocumentNumber;
    }

    private function salaryPtsUsesDualDocumentNumbers(string $requestType): bool
    {
        return $this->normalizeSalaryRequestType($requestType) === 'pts';
    }

    private function resolveSalaryPtsSecondDisplayDocumentCode(object $row): string
    {
        $withdrawId = (int) ($row->withdraw_id ?? 0);
        if ($withdrawId <= 0) {
            return '';
        }

        $documentMeta = $this->resolveWithdrawDocumentMetaByType($withdrawId, 'salary_pts');
        $documentNumber = trim((string) ($documentMeta['document_number'] ?? ''));
        $runningNo = (int) ($documentMeta['running_no'] ?? 0);
        if ($documentNumber !== '' && $runningNo > 0) {
            return $documentNumber.$runningNo;
        }

        return '';
    }

    /**
     * @param array{department_code:string,document_number:string,document_date:string,running_year:int,running_no:int} $documentMeta
     */
    private function assignSalaryPtsSecondDocumentNumberIfNeeded(int $withdrawId, string $salaryRequestType, array $documentMeta): void
    {
        if (! $this->salaryPtsUsesDualDocumentNumbers($salaryRequestType)) {
            return;
        }

        $ptsSecondMeta = $this->resolveWithdrawDocumentMetaByType($withdrawId, 'salary_pts');
        $this->assignWithdrawDocumentNumber(
            $withdrawId,
            'salary_pts',
            (string) ($documentMeta['department_code'] ?? ''),
            (string) ($documentMeta['document_number'] ?? ''),
            (string) ($documentMeta['document_date'] ?? ''),
            (int) ($ptsSecondMeta['running_year'] ?? 0),
            (int) ($ptsSecondMeta['running_no'] ?? 0)
        );
    }

    /**
     * @return array{department_code:string,document_number:string,document_date:string,running_year:int,running_no:int}
     */
    private function resolveWithdrawDocumentMetaByType(int $withdrawId, string $withdrawType = 'salary'): array
    {
        $normalizedWithdrawType = trim($withdrawType) !== '' ? trim($withdrawType) : 'salary';
        $cacheKey = $normalizedWithdrawType.':'.$withdrawId;
        $fallback = [
            'department_code' => '',
            'document_number' => '',
            'document_date' => '',
            'running_year' => 0,
            'running_no' => 0,
        ];

        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_document')) {
            return $fallback;
        }

        if (array_key_exists($cacheKey, $this->withdrawSalaryDocumentMetaCache)) {
            return $this->withdrawSalaryDocumentMetaCache[$cacheKey];
        }

        $documentRow = DB::table('withdraw_document')
            ->where('withdraw_document_type', $normalizedWithdrawType)
            ->where('withdraw_id', $withdrawId)
            ->first([
                'department_code',
                'document_number',
                'document_date',
                'running_year',
                'running_no',
            ]);

        if (! $documentRow) {
            $this->withdrawSalaryDocumentMetaCache[$cacheKey] = $fallback;
            return $fallback;
        }

        $resolved = [
            'department_code' => trim((string) ($documentRow->department_code ?? '')),
            'document_number' => trim((string) ($documentRow->document_number ?? '')),
            'document_date' => trim((string) ($documentRow->document_date ?? '')),
            'running_year' => (int) ($documentRow->running_year ?? 0),
            'running_no' => (int) ($documentRow->running_no ?? 0),
        ];

        $this->withdrawSalaryDocumentMetaCache[$cacheKey] = $resolved;
        return $resolved;
    }

    /**
     * @return array{department_code:string,document_number:string,document_date:string,running_year:int,running_no:int}
     */
    private function resolveWithdrawGeneralDocumentMeta(object $row): array
    {
        $withdrawId = (int) ($row->withdraw_id ?? 0);
        return $this->resolveWithdrawDocumentMetaByType($withdrawId, 'salary');
    }

    private function withdrawQuery()
    {
        return DB::table('withdraw')->where('withdraw_type', 'salary');
    }

    private function fetchGeneralRows(string $scope = '', string $currentStaffId = '')
    {
        if (! Schema::hasTable('withdraw')) {
            return collect();
        }

        $isDocumentReviewScope = $scope === 'document-review';
        if ($isDocumentReviewScope) {
            if ($currentStaffId === '' || ! Schema::hasTable('withdraw_set_finance_reviewer')) {
                return collect();
            }

            $hasFinanceReviewerAccess = DB::table('withdraw_set_finance_reviewer')
                ->where('withdraw_set_finance_reviewer_staff_id', $currentStaffId)
                ->where('withdraw_set_finance_reviewer_status', 1)
                ->exists();

            if (! $hasFinanceReviewerAccess) {
                return collect();
            }
        }

        $rowsQuery = $this->withdrawQuery()
            ->orderByDesc('withdraw_id')
            ->limit(200);

        if ($scope === '' && Schema::hasColumn('withdraw', 'withdraw_sub_type')) {
            $rowsQuery->where(function ($query) {
                $query->whereNull('withdraw_sub_type')
                    ->orWhere('withdraw_sub_type', '!=', 'travel_leave');
            });
        } elseif ($scope === 'travel' && Schema::hasColumn('withdraw', 'withdraw_sub_type')) {
            $rowsQuery->where('withdraw_sub_type', 'travel_leave');
        }

        $isApprovalScope = in_array($scope, ['reviewer-pending', 'document-review'], true);
        if ($isDocumentReviewScope) {
            $rowsQuery->whereIn('withdraw_status', [
                'pending_finance_review',
                'pending_approval',
                'approved',
            ]);
        } elseif ($isApprovalScope) {
            if ($currentStaffId === '') {
                return collect();
            }
            if (! Schema::hasTable('withdraw_approval_step')) {
                return collect();
            }

            $rowsQuery->where(function ($visibilityQuery) use ($currentStaffId) {
                $visibilityQuery
                    ->whereExists(function ($subQuery) use ($currentStaffId) {
                        $subQuery->select(DB::raw(1))
                            ->from('withdraw_approval_step as current_step')
                            ->whereColumn('current_step.withdraw_id', 'withdraw.withdraw_id')
                            ->where('current_step.withdraw_approval_staff_id', $currentStaffId)
                            ->whereIn('current_step.withdraw_approval_status', ['pending', 'approved'])
                            ->where(function ($stepQuery) {
                                $stepQuery
                                    ->where('current_step.withdraw_approval_status', 'approved')
                                    ->orWhere(function ($pendingStepQuery) {
                                        $pendingStepQuery
                                            ->where('current_step.withdraw_approval_status', 'pending')
                                            ->whereNotExists(function ($prevQuery) {
                                                $prevQuery->select(DB::raw(1))
                                                    ->from('withdraw_approval_step as prev_step')
                                                    ->whereColumn('prev_step.withdraw_id', 'current_step.withdraw_id')
                                                    ->whereColumn('prev_step.withdraw_approval_step_order', '<', 'current_step.withdraw_approval_step_order')
                                                    ->whereNotNull('prev_step.withdraw_approval_staff_id')
                                                    ->whereRaw("TRIM(COALESCE(prev_step.withdraw_approval_staff_id, '')) <> ''")
                                                    ->where(function ($statusQuery) {
                                                        $statusQuery
                                                            ->whereNull('prev_step.withdraw_approval_status')
                                                            ->orWhere('prev_step.withdraw_approval_status', '!=', 'approved');
                                                    });
                                            });
                                    });
                            });
                    });
            });

            $rowsQuery->whereIn('withdraw_status', [
                'pending_primary_review',
                'processing',
                'pending_additional_review',
                'pending_finance_review',
                'pending_approval',
                'approved',
            ]);
        }

        $rows = $rowsQuery->get();
        $userStepStatusMap = collect();
        $userStepNameMap = collect();
        $userStepCodeMap = collect();

        if ($isDocumentReviewScope && $rows->count() > 0) {
            $userStepStatusMap = collect($rows)
                ->keyBy(function ($row) {
                    return (int) ($row->withdraw_id ?? 0);
                })
                ->map(function ($row) {
                    $status = trim((string) ($row->withdraw_status ?? ''));

                    return $status === 'pending_finance_review' ? 'pending' : 'approved';
                });
        } elseif ($isApprovalScope && $currentStaffId !== '' && Schema::hasTable('withdraw_approval_step') && $rows->count() > 0) {
            $generalIds = collect($rows)
                ->pluck('withdraw_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if (count($generalIds) > 0) {
                $allStepRows = DB::table('withdraw_approval_step')
                    ->whereIn('withdraw_id', $generalIds)
                    ->orderBy('withdraw_approval_step_order')
                    ->get([
                        'withdraw_id',
                        'withdraw_approval_staff_id',
                        'withdraw_approval_step_order',
                        'withdraw_approval_step_code',
                        'withdraw_approval_step_name',
                        'withdraw_approval_status',
                    ]);

                $stepsByWithdraw = $allStepRows->groupBy('withdraw_id');
                $userStepStatusMap = $stepsByWithdraw->map(
                    fn ($group) => $this->resolveActionableUserStepStatus($group, $currentStaffId)
                );
                $userStepNameMap = $stepsByWithdraw->map(
                    fn ($group) => $this->resolveActionableUserStepName($group, $currentStaffId)
                );
                $userStepCodeMap = $stepsByWithdraw->map(
                    fn ($group) => $this->resolveActionableUserStepCode($group, $currentStaffId)
                );
            }
        }

        $applicantIds = collect($rows)
            ->pluck('withdraw_applicant_staff_id')
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $applicantNameMap = $this->resolveStaffNamesByIds($applicantIds);

        $documentCountMap = collect();
        if (Schema::hasTable('withdraw_req_document')) {
            $documentCountMap = DB::table('withdraw_req_document')
                ->selectRaw('withdraw_id, COUNT(*) as total')
                ->groupBy('withdraw_id')
                ->pluck('total', 'withdraw_id');
        }

        $revisionCommentMap = collect();
        $executiveRevisionMap = collect();
        if (Schema::hasTable('withdraw_approval_step')) {
            $generalIds = collect($rows)
                ->pluck('withdraw_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if (count($generalIds) > 0) {
                $revisionCommentMap = DB::table('withdraw_approval_step')
                    ->whereIn('withdraw_id', $generalIds)
                    ->where('withdraw_approval_step_code', 'finance_officer')
                    ->where('withdraw_approval_status', 'rejected')
                    ->orderByDesc('withdraw_approval_step_id')
                    ->get([
                        'withdraw_id',
                        'withdraw_approval_comment',
                    ])
                    ->groupBy('withdraw_id')
                    ->map(function ($group) {
                        $first = collect($group)->first();
                        return trim((string) ($first->withdraw_approval_comment ?? ''));
                    });
                $executiveRevisionMap = DB::table('withdraw_approval_step')
                    ->whereIn('withdraw_id', $generalIds)
                    ->where('withdraw_approval_step_code', 'finance_manager')
                    ->where('withdraw_approval_status', 'rejected')
                    ->orderByDesc('withdraw_approval_step_id')
                    ->get([
                        'withdraw_id',
                    ])
                    ->groupBy('withdraw_id')
                    ->map(fn ($group) => collect($group)->isNotEmpty());
            }
        }

        $approvalStepsByWithdraw = collect();
        if (Schema::hasTable('withdraw_approval_step') && $rows->count() > 0) {
            $stepWithdrawIds = collect($rows)
                ->pluck('withdraw_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
            if (count($stepWithdrawIds) > 0) {
                $approvalStepsByWithdraw = DB::table('withdraw_approval_step')
                    ->whereIn('withdraw_id', $stepWithdrawIds)
                    ->orderBy('withdraw_approval_step_order')
                    ->orderBy('withdraw_approval_step_id')
                    ->get([
                        'withdraw_id',
                        'withdraw_approval_step_code as step_code',
                        'withdraw_approval_step_name as step_name',
                        'withdraw_approval_step_order as step_order',
                        'withdraw_approval_status as status',
                        'withdraw_approval_approved_at as approved_at',
                    ])
                    ->groupBy('withdraw_id')
                    ->map(function ($group) {
                        return collect($group)->map(function ($step) {
                            return [
                                'step_code' => trim((string) ($step->step_code ?? '')),
                                'step_name' => trim((string) ($step->step_name ?? '')),
                                'step_order' => (int) ($step->step_order ?? 0),
                                'status' => trim((string) ($step->status ?? 'pending')),
                                'approved_at' => trim((string) ($step->approved_at ?? '')),
                            ];
                        })->values()->all();
                    });
            }
        }

        $financeEsignCountMap = collect();
        if (Schema::hasTable('withdraw_file') && $rows->count() > 0) {
            $esignWithdrawIds = collect($rows)
                ->pluck('withdraw_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
            $esignTypeCodes = EsignAttachedPdf::financeEsignTypeCodes();
            if ($esignWithdrawIds !== [] && $esignTypeCodes !== []) {
                $financeEsignCountMap = DB::table('withdraw_file')
                    ->whereIn('withdraw_id', $esignWithdrawIds)
                    ->whereIn('withdraw_file_type_code', $esignTypeCodes)
                    ->selectRaw('withdraw_id, COUNT(*) as total')
                    ->groupBy('withdraw_id')
                    ->pluck('total', 'withdraw_id');
            }
        }

        return collect($rows)
            ->map(function ($row) use ($documentCountMap, $applicantNameMap, $userStepStatusMap, $userStepNameMap, $userStepCodeMap, $revisionCommentMap, $executiveRevisionMap, $approvalStepsByWithdraw, $financeEsignCountMap) {
                $rowId = (int) ($row->withdraw_id ?? 0);
                $documentCount = (int) ($documentCountMap[$rowId] ?? 0);
                $rawStatus = trim((string) ($row->withdraw_status ?? 'saved'));
                $isExecutiveRevision = $rawStatus === 'pending_finance_review' && (bool) ($executiveRevisionMap[$rowId] ?? false);
                $statusLabel = $isExecutiveRevision ? 'รอการเงินตรวจสอบเอกสาร (แก้ไข)' : $this->mapWithdrawStatusLabel($rawStatus);
                $userStepStatus = (string) ($userStepStatusMap[$rowId] ?? '');
                $userStepName = (string) ($userStepNameMap[$rowId] ?? '');
                $userStepCode = (string) ($userStepCodeMap[$rowId] ?? '');

                $documentMeta = $this->resolveWithdrawGeneralDocumentMeta($row);
                $date = trim((string) ($documentMeta['document_date'] ?? ''));
                if ($date === '' && ! empty($row->withdraw_date_create)) {
                    $date = (string) $row->withdraw_date_create;
                }
                $parsedDocumentDate = $this->parseInputDate($date);
                $documentMonth = $parsedDocumentDate !== null ? (int) $parsedDocumentDate->month : 0;
                $thaiDate = $this->formatThaiDate($date);
                $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
                $applicantName = trim((string) ($applicantNameMap[$applicantStaffId] ?? ''));
                $displayDocumentNumber = $this->resolveWithdrawGeneralDisplayDocumentCode($row);

                return [
                    'id' => $rowId,
                    'code' => $displayDocumentNumber !== '' ? $displayDocumentNumber : '-',
                    'subject' => trim((string) ($row->withdraw_subject ?? '')) ?: '-',
                    'applicant_staff_id' => $applicantStaffId,
                    'manager' => $applicantName !== '' ? $applicantName : ($applicantStaffId !== '' ? $applicantStaffId : '-'),
                    'date' => $thaiDate !== '' ? $thaiDate : '-',
                    'document_month' => $documentMonth,
                    'fiscal_year' => (int) ($row->withdraw_fiscal_year ?? 0),
                    'amount' => (float) ($row->withdraw_total_amount ?? 0),
                    'raw_status' => $rawStatus,
                    'status' => $statusLabel,
                    'user_step_status' => $userStepStatus,
                    'user_step_name' => $userStepName,
                    'user_step_code' => $userStepCode,
                    'revision_comment' => (string) ($revisionCommentMap[$rowId] ?? ''),
                    'salary_request_type' => Schema::hasColumn('withdraw', 'withdraw_sub_type')
                        ? $this->normalizeSalaryRequestType((string) ($row->withdraw_sub_type ?? 'salary'))
                        : 'salary_civil_servant',
                    'type' => $this->mapSalaryRequestTypeLabel(
                        Schema::hasColumn('withdraw', 'withdraw_sub_type')
                            ? (string) ($row->withdraw_sub_type ?? 'salary')
                            : 'salary'
                    ),
                    'document_count' => $documentCount,
                    'finance_esign_count' => (int) ($financeEsignCountMap[$rowId] ?? $financeEsignCountMap[(string) $rowId] ?? 0),
                    'approval_steps' => $approvalStepsByWithdraw->get($rowId)
                        ?? $approvalStepsByWithdraw->get((string) $rowId)
                        ?? [],
                ];
            });
    }

    private function loadGeneralItems(int $generalId): array
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_item')) {
            return [];
        }

        $hasExpenseChartColumn = Schema::hasColumn('withdraw_item', 'withdraw_item_expense_chart_id');
        $hasBudgetManageColumn = Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_id');
        $hasBudgetManageCodeColumn = Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_code');
        $hasFinanceAmountColumn = Schema::hasColumn('withdraw_item', 'withdraw_item_finance_amount');

        $selects = [
            'withdraw_item_id as id',
            'withdraw_item_category as category',
            'withdraw_item_name as name',
            'withdraw_item_amount as amount',
        ];
        $selects[] = $hasFinanceAmountColumn
            ? 'withdraw_item_finance_amount as finance_amount'
            : DB::raw('NULL as finance_amount');
        $selects[] = $hasExpenseChartColumn
            ? 'withdraw_item_expense_chart_id as expense_chart_id'
            : DB::raw('0 as expense_chart_id');
        $selects[] = $hasBudgetManageColumn
            ? 'withdraw_item_budget_manage_id as budget_manage_id'
            : DB::raw('0 as budget_manage_id');
        $selects[] = $hasBudgetManageCodeColumn
            ? 'withdraw_item_budget_manage_code as budget_manage_code'
            : DB::raw("'' as budget_manage_code");

        $rows = DB::table('withdraw_item')
            ->where('withdraw_id', $generalId)
            ->orderBy('withdraw_item_order')
            ->orderBy('withdraw_item_id')
            ->get($selects);

        $categoryCodes = collect($rows)
            ->pluck('category')
            ->map(fn ($category) => trim((string) $category))
            ->filter(fn ($category) => $category !== '' && ctype_digit($category))
            ->unique()
            ->values()
            ->all();
        $categoryNameMap = $this->resolvePayListNameByCodes($categoryCodes);

        $expenseChartIds = collect($rows)
            ->map(fn ($item) => (int) ($item->expense_chart_id ?? 0))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $expenseChartNameMap = [];
        if (count($expenseChartIds) > 0 && Schema::hasTable('withdraw_set_expenses')) {
            $expenseChartNameMap = DB::table('withdraw_set_expenses')
                ->whereIn('withdraw_set_expenses_id', $expenseChartIds)
                ->get([
                    'withdraw_set_expenses_id as id',
                    'withdraw_set_expenses_name as name',
                    'withdraw_set_expenses_detail as detail',
                ])
                ->mapWithKeys(function ($expenseRow) {
                    $expenseId = (int) ($expenseRow->id ?? 0);
                    if ($expenseId <= 0) {
                        return [];
                    }
                    $name = trim((string) ($expenseRow->name ?? ''));
                    $detail = trim((string) ($expenseRow->detail ?? ''));
                    $label = $name !== ''
                        ? ($detail !== '' && $detail !== '-' ? $name.' ('.$detail.')' : $name)
                        : $detail;

                    return [$expenseId => $label];
                })
                ->all();
        }

        return collect($rows)
            ->map(function ($item) use ($categoryNameMap, $expenseChartNameMap) {
                $rawCategory = trim((string) ($item->category ?? ''));
                $resolvedCategoryName = $rawCategory !== '' ? trim((string) ($categoryNameMap[$rawCategory] ?? '')) : '';
                $expenseChartId = (int) ($item->expense_chart_id ?? 0);
                $mappedItem = [
                    'id' => (int) ($item->id ?? 0),
                    'category_code' => $rawCategory,
                    'category' => $resolvedCategoryName !== '' ? $resolvedCategoryName : $rawCategory,
                    'name' => (string) ($item->name ?? ''),
                    'amount' => (float) ($item->amount ?? 0),
                    'finance_amount' => $item->finance_amount !== null ? (float) $item->finance_amount : null,
                    'expense_chart_id' => $expenseChartId,
                    'expense_chart_name' => $expenseChartId > 0 ? (string) ($expenseChartNameMap[$expenseChartId] ?? '') : '',
                    'budget_manage_id' => (int) ($item->budget_manage_id ?? 0),
                    'budget_manage_code' => trim((string) ($item->budget_manage_code ?? '')),
                ];
                $mappedItem['display_name'] = $this->resolveSalaryItemSaveName($mappedItem);
                if (trim((string) ($mappedItem['expense_chart_name'] ?? '')) === '') {
                    $mappedItem['expense_chart_name'] = (string) ($mappedItem['display_name'] ?? '');
                }

                return $mappedItem;
            })
            ->values()
            ->all();
    }

    private function normalizeItemCategoryCode(string $inputCategory): string
    {
        $category = trim($inputCategory);
        if ($category === '') {
            return '';
        }
        if (ctype_digit($category)) {
            return $category;
        }

        $source = $this->resolvePayListSource();
        if ($source === null) {
            return $category;
        }
        $connection = trim((string) ($source['connection'] ?? config('database.default')));
        $table = (string) ($source['table'] ?? '');
        $nameColumn = (string) ($source['name_column'] ?? '');
        $codeColumn = (string) ($source['code_column'] ?? '');
        if ($table === '' || $nameColumn === '' || $codeColumn === '') {
            return $category;
        }

        try {
            $resolved = DB::connection($connection)->table($table)
                ->where($nameColumn, $category)
                ->value($codeColumn);
            $resolvedCode = trim((string) ($resolved ?? ''));
            return $resolvedCode !== '' ? $resolvedCode : $category;
        } catch (\Throwable $e) {
            return $category;
        }
    }

    /**
     * @param array<int, string> $codes
     * @return array<string, string>
     */
    private function resolvePayListNameByCodes(array $codes): array
    {
        $normalizedCodes = collect($codes)
            ->map(fn ($code) => trim((string) $code))
            ->filter(fn ($code) => $code !== '' && ctype_digit($code))
            ->unique()
            ->values();
        if ($normalizedCodes->isEmpty()) {
            return [];
        }

        $source = $this->resolvePayListSource();
        if ($source === null) {
            return [];
        }
        $connection = trim((string) ($source['connection'] ?? config('database.default')));
        $payTable = trim((string) ($source['table'] ?? ''));
        $payCodeColumn = trim((string) ($source['code_column'] ?? ''));
        $payNameColumn = trim((string) ($source['name_column'] ?? ''));
        if ($payTable === '' || $payCodeColumn === '' || $payNameColumn === '') {
            return [];
        }

        try {
            return DB::connection($connection)
                ->table($payTable)
                ->whereIn($payCodeColumn, $normalizedCodes->all())
                ->pluck($payNameColumn, $payCodeColumn)
                ->map(fn ($name) => trim((string) $name))
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * คืนคำนำหน้าเลขที่การเงินจากค่าในคอลัมน์ withdraw_document.document_number
     * (รองรับแถวเก่าที่เก็บ "คำนำหน้า + running_no" ต่อในสตริงเดียวกับ running_no ในคอลัมน์).
     */
    private function resolveFinanceDocumentNumberBaseForApi(string $storedDocumentNumber, int $runningNo): string
    {
        $s = trim($storedDocumentNumber);
        if ($s === '' || $runningNo <= 0) {
            return $s;
        }
        if (preg_match('/^(.*\D)(\d+)$/u', $s, $m) && (int) $m[2] === $runningNo) {
            return rtrim($m[1]);
        }

        return $s;
    }

    /**
     * ก่อนบันทึก general_finance: ตัดเลขรันที่ต่อท้ายในสตริง (ถ้ามี) เพื่อให้ document_number ในฐานข้อมูลเป็นคำนำหน้าเดียวกับที่ใช้นับลำดับ.
     */
    private function normalizeGeneralFinanceDocumentNumberForStorage(string $documentNumber): string
    {
        $s = trim($documentNumber);
        if ($s === '' || ! preg_match('/^(.*\D)(\d+)$/u', $s, $m)) {
            return $s;
        }

        return rtrim($m[1]);
    }

    /**
     * แถว withdraw_document (การเงิน) นับร่วมชุดลำดับเดียวกับคำนำหน้า document_key หรือไม่
     * (รองรับทั้งคอลัมน์เก็บแค่คำนำหน้า และเก็บ "คำนำหน้า + เลข" ต่อในสตริงเดียว).
     */
    private function storedFinanceDocumentRowMatchesSequenceKey(string $storedDocumentNumber, string $documentKey): bool
    {
        $stored = trim($storedDocumentNumber);
        $documentKey = trim($documentKey);
        if ($documentKey === '' || $stored === '') {
            return false;
        }
        if ($stored === $documentKey) {
            return true;
        }
        $docLen = mb_strlen($documentKey, 'UTF-8');
        if (mb_strlen($stored, 'UTF-8') < $docLen) {
            return false;
        }
        if (mb_substr($stored, 0, $docLen, 'UTF-8') !== $documentKey) {
            return false;
        }
        $suffix = mb_substr($stored, $docLen, null, 'UTF-8');

        return $suffix !== '' && (bool) preg_match('/^[0-9]+$/u', $suffix);
    }

    /**
     * MAX(running_no) ตามคำนำหน้า document_number เท่านั้น (ไม่แยก department_code).
     * รองรับแถวที่เก็บ document_number เป็นคำนำหน้า หรือ "คำนำหน้า + เลข" ต่อในสตริงเดียว.
     *
     * @param  array<int, string>  $withdrawDocumentTypes
     */
    private function computeMaxRunningNoForDocumentNumberKey(array $withdrawDocumentTypes, string $documentKey): int
    {
        $documentKey = trim($documentKey);
        if ($documentKey === '' || $withdrawDocumentTypes === []) {
            return 0;
        }

        $types = array_values(array_filter(array_map('trim', $withdrawDocumentTypes), fn ($t) => $t !== ''));
        if ($types === []) {
            return 0;
        }

        $base = DB::table('withdraw_document')->whereIn('withdraw_document_type', $types);

        $maxExact = (int) ((clone $base)
            ->whereRaw('TRIM(COALESCE(document_number, \'\')) = ?', [$documentKey])
            ->max('running_no') ?? 0);

        $like = addcslashes($documentKey, '%_\\').'%';
        $maxLen = mb_strlen($documentKey, 'UTF-8') + 24;
        $candidates = (clone $base)
            ->whereRaw('TRIM(COALESCE(document_number, \'\')) LIKE ?', [$like])
            ->whereRaw('CHAR_LENGTH(TRIM(COALESCE(document_number, \'\'))) <= ?', [$maxLen])
            ->get(['document_number', 'running_no']);

        $maxLoose = 0;
        foreach ($candidates as $row) {
            if (! $this->storedFinanceDocumentRowMatchesSequenceKey((string) ($row->document_number ?? ''), $documentKey)) {
                continue;
            }
            $maxLoose = max($maxLoose, (int) ($row->running_no ?? 0));
        }

        return max($maxExact, $maxLoose);
    }

    /**
     * MAX(running_no) ตามหน่วย + คำนำหน้าเลขที่ สำหรับชุด withdraw_document_type ที่กำหนด (ใช้กับเลขที่บันทึกการเงิน).
     *
     * @param  array<int, string>  $withdrawDocumentTypes
     */
    private function computeMaxRunningNoForFinanceSequenceKey(array $withdrawDocumentTypes, string $departmentCode, string $documentKey): int
    {
        $documentKey = trim($documentKey);
        $dept = trim($departmentCode);
        if ($documentKey === '' || $withdrawDocumentTypes === []) {
            return 0;
        }

        $types = array_values(array_filter(array_map('trim', $withdrawDocumentTypes), fn ($t) => $t !== ''));
        if ($types === []) {
            return 0;
        }

        $base = DB::table('withdraw_document')
            ->whereIn('withdraw_document_type', $types)
            ->when($dept !== '', function ($query) use ($dept) {
                $query->where(function ($inner) use ($dept) {
                    $inner->whereRaw('TRIM(COALESCE(department_code, \'\')) = ?', [$dept])
                        ->orWhereNull('department_code')
                        ->orWhereRaw("TRIM(COALESCE(department_code, '')) = ''");
                });
            }, function ($query) {
                $query->where(function ($innerQuery) {
                    $innerQuery->whereNull('department_code')
                        ->orWhereRaw("TRIM(COALESCE(department_code, '')) = ''");
                });
            });

        $maxExact = (int) ((clone $base)
            ->whereRaw('TRIM(COALESCE(document_number, \'\')) = ?', [$documentKey])
            ->max('running_no') ?? 0);

        $like = addcslashes($documentKey, '%_\\').'%';
        $maxLen = mb_strlen($documentKey, 'UTF-8') + 24;
        $candidates = (clone $base)
            ->whereRaw('TRIM(COALESCE(document_number, \'\')) LIKE ?', [$like])
            ->whereRaw('CHAR_LENGTH(TRIM(COALESCE(document_number, \'\'))) <= ?', [$maxLen])
            ->get(['document_number', 'running_no']);

        $maxLoose = 0;
        foreach ($candidates as $row) {
            if (! $this->storedFinanceDocumentRowMatchesSequenceKey((string) ($row->document_number ?? ''), $documentKey)) {
                continue;
            }
            $maxLoose = max($maxLoose, (int) ($row->running_no ?? 0));
        }

        return max($maxExact, $maxLoose);
    }

    /**
     * MAX(running_no) สำหรับ general_finance ตามหน่วย + คำนำหน้าเลขที่ (รองรับรูปแบบเก็บในฐานข้อมูลหลายแบบ).
     *
     * ถ้ายังไม่มีแถว withdraw_document_type = general_finance ในตารางเลย จะ fallback ไปนับจาก general
     * (ข้อมูลเก่าที่เลขรันอยู่ในแถว general) — พอมี general_finance แล้วจะนับเฉพาะ type การเงินเท่านั้น.
     */
    private function computeGeneralFinanceMaxRunningNo(string $departmentCode, string $documentKey): int
    {
        $documentKey = $this->normalizeGeneralFinanceDocumentNumberForStorage(trim($documentKey));
        $fromFinanceType = $this->computeMaxRunningNoForDocumentNumberKey(['salary_finance'], $documentKey);
        $hasGeneralFinanceRows = DB::table('withdraw_document')
            ->where('withdraw_document_type', 'salary_finance')
            ->exists();
        if ($hasGeneralFinanceRows) {
            return $fromFinanceType;
        }

        return max(
            $fromFinanceType,
            $this->computeMaxRunningNoForDocumentNumberKey(['salary'], $documentKey)
        );
    }

    private function normalizeSalaryDocumentNumberForStorage(string $documentNumber): string
    {
        return $this->normalizeGeneralFinanceDocumentNumberForStorage($documentNumber);
    }

    /**
     * ประเภท withdraw ที่ใช้ลำดับเลขรันร่วมกันตาม document_number (ไม่แยกตามประเภทฟอร์ม).
     *
     * @return array<int, string>
     */
    private function documentNumberSharedWithdrawTypes(string $withdrawType = 'salary'): array
    {
        $normalized = trim($withdrawType) !== '' ? trim($withdrawType) : 'salary';

        return match ($normalized) {
            'salary', 'salary_finance', 'salary_pts', 'general', 'general_finance' => ['salary', 'salary_finance', 'salary_pts', 'general', 'general_finance'],
            default => [$normalized],
        };
    }

    /**
     * Next running number for withdraw_document: unique per document_number prefix only.
     * Same document_number must not reuse running_no across shared withdraw types.
     *
     * @return array{0:int,1:int}
     */
    private function nextRunningNumber(
        string $departmentCode = '',
        string $withdrawType = 'salary',
        string $documentDate = '',
        string $documentNumber = ''
    ): array {
        $year = $this->resolveDocumentRunningYear($documentDate);
        $normalizedWithdrawType = trim($withdrawType) !== '' ? trim($withdrawType) : 'salary';
        $normalizedDocumentNumber = trim($documentNumber);
        if ($normalizedDocumentNumber !== '') {
            $normalizedDocumentNumber = $normalizedWithdrawType === 'salary_finance'
                ? $this->normalizeGeneralFinanceDocumentNumberForStorage($normalizedDocumentNumber)
                : $this->normalizeSalaryDocumentNumberForStorage($normalizedDocumentNumber);
        }

        if (! Schema::hasTable('withdraw_document')) {
            return [$year, 1];
        }

        // Do not filter max() by running_year: legacy rows may have running_year = 0 while running_no is set.
        if ($normalizedDocumentNumber !== '') {
            $latestNo = $this->computeMaxRunningNoForDocumentNumberKey(
                $this->documentNumberSharedWithdrawTypes($normalizedWithdrawType),
                $normalizedDocumentNumber
            );
        } else {
            $latestNo = (int) (DB::table('withdraw_document')
                ->whereIn('withdraw_document_type', $this->documentNumberSharedWithdrawTypes($normalizedWithdrawType))
                ->where(function ($innerQuery) {
                    $innerQuery->whereNull('document_number')
                        ->orWhereRaw("TRIM(COALESCE(document_number, '')) = ''");
                })
                ->max('running_no') ?? 0);
        }

        $nextNo = $latestNo + 1;

        return [$year, $nextNo > 0 ? $nextNo : 1];
    }

    private function resolveDocumentRunningYear(string $documentDate = ''): int
    {
        $date = null;
        $normalizedDate = trim($documentDate);
        if ($normalizedDate !== '') {
            try {
                $date = Carbon::parse($normalizedDate);
            } catch (\Throwable $e) {
                $date = null;
            }
        }

        if (! $date) {
            $date = Carbon::now();
        }

        $year = (int) $date->year;
        return $year >= 2400 ? $year - 543 : $year;
    }

    private function upsertWithdrawDocumentMeta(
        int $withdrawId,
        string $withdrawType,
        string $departmentCode,
        string $documentNumber,
        string $documentDate
    ): void {
        $normalizedWithdrawType = trim($withdrawType) !== '' ? trim($withdrawType) : 'salary';
        $normalizedDepartmentCode = trim($departmentCode);
        $normalizedDocumentNumber = trim($documentNumber);
        if ($normalizedDocumentNumber !== '') {
            $normalizedDocumentNumber = $this->normalizeSalaryDocumentNumberForStorage($normalizedDocumentNumber);
        }
        $normalizedDocumentDate = trim($documentDate);

        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_document')) {
            return;
        }

        DB::transaction(function () use (
            $withdrawId,
            $normalizedWithdrawType,
            $normalizedDepartmentCode,
            $normalizedDocumentNumber,
            $normalizedDocumentDate
        ) {
            $existing = DB::table('withdraw_document')
                ->where('withdraw_document_type', $normalizedWithdrawType)
                ->where('withdraw_id', $withdrawId)
                ->lockForUpdate()
                ->first([
                    'withdraw_document_id',
                    'running_year',
                    'running_no',
                ]);

            if ($existing) {
                DB::table('withdraw_document')
                    ->where('withdraw_document_id', (int) ($existing->withdraw_document_id ?? 0))
                    ->update([
                        'department_code' => $normalizedDepartmentCode !== '' ? $normalizedDepartmentCode : null,
                        'document_number' => $normalizedDocumentNumber !== '' ? $normalizedDocumentNumber : null,
                        'document_date' => $normalizedDocumentDate !== '' ? $normalizedDocumentDate : null,
                        'updated_at' => now(),
                    ]);
                return;
            }

            DB::table('withdraw_document')->insert([
                'withdraw_document_type' => $normalizedWithdrawType,
                'withdraw_id' => $withdrawId,
                'department_code' => $normalizedDepartmentCode !== '' ? $normalizedDepartmentCode : null,
                'document_number' => $normalizedDocumentNumber !== '' ? $normalizedDocumentNumber : null,
                'document_date' => $normalizedDocumentDate !== '' ? $normalizedDocumentDate : null,
                'running_year' => 0,
                'running_no' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        foreach (array_keys($this->withdrawSalaryDocumentMetaCache) as $cacheKey) {
            if (str_ends_with($cacheKey, ':'.$withdrawId)) {
                unset($this->withdrawSalaryDocumentMetaCache[$cacheKey]);
            }
        }
    }

    /**
     * @return array{0:int,1:int,2:string}
     */
    private function issueCentralWithdrawNumber(int $withdrawId, string $withdrawType, string $departmentCode, string $documentDate): array
    {
        $issued = app(DocumentNumberClient::class)->issueForWithdraw($withdrawId, $withdrawType, $departmentCode);

        return [
            $this->resolveDocumentRunningYear($documentDate),
            (int) $issued['running_no'],
            (string) $issued['document_number'],
        ];
    }

    /**
     * @return array{0:int,1:int}
     */
    private function assignWithdrawDocumentNumber(
        int $withdrawId,
        string $withdrawType,
        string $departmentCode,
        string $documentNumber,
        string $documentDate,
        int $existingRunningYear = 0,
        int $existingRunningNo = 0
    ): array {
        $normalizedWithdrawType = trim($withdrawType) !== '' ? trim($withdrawType) : 'salary';
        $normalizedDepartmentCode = trim($departmentCode);
        $normalizedDocumentNumber = trim($documentNumber);
        if ($normalizedDocumentNumber !== '') {
            $normalizedDocumentNumber = $normalizedWithdrawType === 'salary_finance'
                ? $this->normalizeGeneralFinanceDocumentNumberForStorage($normalizedDocumentNumber)
                : $this->normalizeSalaryDocumentNumberForStorage($normalizedDocumentNumber);
        }
        $normalizedDocumentDate = trim($documentDate);

        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_document')) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถออกเลขที่หนังสือได้',
            ], 422));
        }

        $assigned = DB::transaction(function () use (
            $withdrawId,
            $normalizedWithdrawType,
            $normalizedDepartmentCode,
            $normalizedDocumentNumber,
            $normalizedDocumentDate,
            $existingRunningYear,
            $existingRunningNo
        ) {
            $existing = DB::table('withdraw_document')
                ->where('withdraw_document_type', $normalizedWithdrawType)
                ->where('withdraw_id', $withdrawId)
                ->lockForUpdate()
                ->first([
                    'withdraw_document_id',
                    'running_year',
                    'running_no',
                    'department_code',
                    'document_number',
                    'document_date',
                ]);

            if ($existing) {
                $dbRunningYear = (int) ($existing->running_year ?? 0);
                $dbRunningNo = (int) ($existing->running_no ?? 0);
                // Draft save uses upsertWithdrawDocumentMeta() which inserts running_year/running_no = 0.
                // Proceed must allocate a real running number instead of only updating header fields.
                $needsRunningAssignment = $dbRunningNo <= 0;

                $effectiveDepartmentCode = trim((string) ($normalizedDepartmentCode !== ''
                    ? $normalizedDepartmentCode
                    : (string) ($existing->department_code ?? '')));
                $effectiveDocumentNumber = trim((string) ($normalizedDocumentNumber !== ''
                    ? $normalizedDocumentNumber
                    : (string) ($existing->document_number ?? '')));
                $effectiveDocumentDate = trim((string) ($normalizedDocumentDate !== ''
                    ? $normalizedDocumentDate
                    : (string) ($existing->document_date ?? '')));

                $runningYear = $dbRunningYear;
                $runningNo = $dbRunningNo;
                if ($needsRunningAssignment) {
                    // Always allocate from the central issuer; do not skip when meta passes a stale running_no.
                    [$runningYear, $runningNo, $centralDocumentNumber] = $this->issueCentralWithdrawNumber(
                        $withdrawId,
                        $normalizedWithdrawType,
                        $effectiveDepartmentCode,
                        $effectiveDocumentDate
                    );
                    $normalizedDocumentNumber = $centralDocumentNumber;
                    if ($runningYear <= 0) {
                        $runningYear = $this->resolveDocumentRunningYear($effectiveDocumentDate);
                    }
                }

                $saveDepartment = trim((string) ($normalizedDepartmentCode !== ''
                    ? $normalizedDepartmentCode
                    : $effectiveDepartmentCode));
                $saveDocumentNumber = trim((string) ($normalizedDocumentNumber !== ''
                    ? $normalizedDocumentNumber
                    : $effectiveDocumentNumber));
                $saveDocumentDate = trim((string) ($normalizedDocumentDate !== ''
                    ? $normalizedDocumentDate
                    : $effectiveDocumentDate));
                $updatePayload = [
                    'department_code' => $saveDepartment !== '' ? $saveDepartment : null,
                    'document_number' => $saveDocumentNumber !== '' ? $saveDocumentNumber : null,
                    'document_date' => $saveDocumentDate !== '' ? $saveDocumentDate : null,
                    'updated_at' => now(),
                ];
                if ($needsRunningAssignment) {
                    $updatePayload['running_year'] = $runningYear;
                    $updatePayload['running_no'] = $runningNo;
                }

                DB::table('withdraw_document')
                    ->where('withdraw_document_id', (int) ($existing->withdraw_document_id ?? 0))
                    ->update($updatePayload);

                if ($needsRunningAssignment) {
                    return [$runningYear, $runningNo];
                }

                return [
                    $dbRunningYear > 0
                        ? $dbRunningYear
                        : $this->resolveDocumentRunningYear($normalizedDocumentDate),
                    $dbRunningNo > 0 ? $dbRunningNo : 1,
                ];
            }

            [$runningYear, $runningNo, $centralDocumentNumber] = $this->issueCentralWithdrawNumber(
                $withdrawId,
                $normalizedWithdrawType,
                $normalizedDepartmentCode,
                $normalizedDocumentDate
            );
            $normalizedDocumentNumber = $centralDocumentNumber;

            DB::table('withdraw_document')->insert([
                'withdraw_document_type' => $normalizedWithdrawType,
                'withdraw_id' => $withdrawId,
                'department_code' => $normalizedDepartmentCode !== '' ? $normalizedDepartmentCode : null,
                'document_number' => $normalizedDocumentNumber !== '' ? $normalizedDocumentNumber : null,
                'document_date' => $normalizedDocumentDate !== '' ? $normalizedDocumentDate : null,
                'running_year' => $runningYear,
                'running_no' => $runningNo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [$runningYear, $runningNo];
        });

        foreach (array_keys($this->withdrawSalaryDocumentMetaCache) as $cacheKey) {
            if (str_ends_with($cacheKey, ':'.$withdrawId)) {
                unset($this->withdrawSalaryDocumentMetaCache[$cacheKey]);
            }
        }
        return $assigned;
    }

    private function loadGeneralDocuments(int $generalId): array
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_req_document')) {
            return [];
        }

        return DB::table('withdraw_req_document')
            ->where('withdraw_id', $generalId)
            ->orderBy('withdraw_req_document_order')
            ->orderBy('withdraw_req_document_id')
            ->get([
                'withdraw_req_document_type_code as type',
                'withdraw_req_document_item_code as item_id',
                'withdraw_req_document_label as label',
            ])
            ->map(function ($document) {
                return [
                    'type' => (string) ($document->type ?? ''),
                    'item_id' => (string) ($document->item_id ?? ''),
                    'label' => (string) ($document->label ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    private function loadGeneralUploadedFiles(int $generalId)
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_file')) {
            return collect();
        }

        return DB::table('withdraw_file')
            ->where('withdraw_id', $generalId)
            ->orderBy('withdraw_file_type_code')
            ->orderBy('withdraw_file_item_code')
            ->orderBy('withdraw_file_id')
            ->get([
                'withdraw_file_id as file_id',
                'withdraw_file_type_code as type',
                'withdraw_file_item_code as item',
                'withdraw_file_name as name',
                'withdraw_file_mime as mime',
            ])
            ->map(function ($file) use ($generalId) {
                $fileId = (int) ($file->file_id ?? 0);
                $type = (string) ($file->type ?? '');
                $item = (string) ($file->item ?? '');
                return [
                    'file_id' => $fileId,
                    'type' => $type,
                    'item' => $item,
                    'name' => (string) ($file->name ?? ''),
                    'mime' => (string) ($file->mime ?? ''),
                    'url' => $generalId > 0 && $fileId > 0
                        ? route('withdraw.salary.api.file', [
                            'id' => $generalId,
                            'file_id' => $fileId,
                        ])
                        : '',
                ];
            })
            ->values();
    }

    private function loadGeneralAdditionalReviewers(int $generalId): array
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_approval_step')) {
            return [];
        }

        return DB::table('withdraw_approval_step')
            ->where('withdraw_id', $generalId)
            ->where('withdraw_approval_step_code', 'additional_reviewer')
            ->orderBy('withdraw_approval_step_order')
            ->orderBy('withdraw_approval_step_id')
            ->get([
                'withdraw_approval_staff_id as staff_id',
                'withdraw_approval_custom_status as status',
            ])
            ->map(function ($actor) {
                return [
                    'staff_id' => (string) ($actor->staff_id ?? ''),
                    'status' => (string) ($actor->status ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    private function loadGeneralApprovalSteps(int $generalId): array
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_approval_step')) {
            return [];
        }

        $selects = [
            'withdraw_approval_step_code as step_code',
            'withdraw_approval_step_name as step_name',
            'withdraw_approval_step_order as step_order',
            'withdraw_approval_staff_id as staff_id',
            'withdraw_approval_custom_status as custom_status',
            'withdraw_approval_status as status',
            'withdraw_approval_comment as comment',
            'withdraw_approval_approved_at as approved_at',
        ];
        if (Schema::hasColumn('withdraw_approval_step', 'withdraw_approval_notified_at')) {
            $selects[] = 'withdraw_approval_notified_at as notified_at';
        }

        $rows = DB::table('withdraw_approval_step')
            ->where('withdraw_id', $generalId)
            ->orderBy('withdraw_approval_step_order')
            ->orderBy('withdraw_approval_step_id')
            ->get($selects);

        $staffIds = collect($rows)
            ->pluck('staff_id')
            ->map(fn ($staffId) => trim((string) $staffId))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $staffNameMap = $this->resolveStaffNamesByIds($staffIds);

        return collect($rows)->map(function ($step) use ($staffNameMap) {
            $staffId = trim((string) ($step->staff_id ?? ''));
            return [
                'step_code' => (string) ($step->step_code ?? ''),
                'step_name' => (string) ($step->step_name ?? ''),
                'step_order' => (int) ($step->step_order ?? 0),
                'staff_id' => $staffId,
                'staff_name' => (string) ($staffNameMap[$staffId] ?? ''),
                'custom_status' => (string) ($step->custom_status ?? ''),
                'status' => (string) ($step->status ?? 'pending'),
                'comment' => (string) ($step->comment ?? ''),
                'approved_at' => (string) ($step->approved_at ?? ''),
                'notified_at' => trim((string) ($step->notified_at ?? '')),
            ];
        })->values()->all();
    }

    /**
     * @return array<int, string>
     */
    private function salaryApprovalRouteExcludedStepCodes(): array
    {
        return ['finance_officer', 'reviewer_head'];
    }

    private function isSalaryApprovalRouteExcludedStepCode(string $stepCode): bool
    {
        return in_array(trim($stepCode), $this->salaryApprovalRouteExcludedStepCodes(), true);
    }

    /**
     * @param array<int, array<string, mixed>> $steps
     * @return array<int, array<string, mixed>>
     */
    private function filterSalaryApprovalRouteSteps(array $steps): array
    {
        return collect($steps)
            ->filter(fn ($step) => ! $this->isSalaryApprovalRouteExcludedStepCode((string) ($step['step_code'] ?? '')))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $approvalSteps
     * @param  array<string, string>  $staffNameMap
     * @return array{staff_id: string, name: string, step_name: string, position: string, status: string, approved: bool}
     */
    private function resolveSalaryMemoDeanSigner(
        array $approvalSteps,
        array $staffNameMap = []
    ): array {
        $steps = $this->filterSalaryApprovalRouteSteps($approvalSteps);
        $normalizeSigner = static function (?array $step) use ($staffNameMap): array {
            if (! is_array($step)) {
                return [
                    'staff_id' => '',
                    'name' => '',
                    'step_name' => 'คณบดี',
                    'position' => 'คณบดีคณะเภสัชศาสตร์',
                    'status' => '',
                    'approved' => false,
                    'approved_at' => '',
                ];
            }

            $staffId = trim((string) ($step['staff_id'] ?? ''));
            $name = trim((string) ($step['staff_name'] ?? ''));
            if ($name === '') {
                $name = trim((string) ($staffNameMap[$staffId] ?? ''));
            }
            $stepName = trim((string) ($step['step_name'] ?? ''));
            if ($stepName === '') {
                $stepName = 'คณบดี';
            }
            $status = strtolower(trim((string) ($step['status'] ?? '')));

            return [
                'staff_id' => $staffId,
                'name' => $name,
                'step_name' => $stepName,
                'position' => '',
                'status' => $status,
                'approved' => $status === 'approved',
                'approved_at' => trim((string) ($step['approved_at'] ?? '')),
            ];
        };

        foreach (['dean', 'vice_dean'] as $stepCode) {
            $matched = collect($steps)->first(
                static fn ($step) => is_array($step) && trim((string) ($step['step_code'] ?? '')) === $stepCode
            );
            if (is_array($matched)) {
                return $normalizeSigner($matched);
            }
        }

        $lastApproved = collect($steps)
            ->filter(static fn ($step) => is_array($step) && strtolower(trim((string) ($step['status'] ?? ''))) === 'approved')
            ->last();
        if (is_array($lastApproved)) {
            return $normalizeSigner($lastApproved);
        }

        $lastStep = collect($steps)->last();

        return $normalizeSigner(is_array($lastStep) ? $lastStep : null);
    }

    /**
     * @param  array<int, array<string, mixed>>  $approvalSteps
     * @return array{staff_id: string, step_status: string}
     */
    private function resolveSalaryFinanceManagerSignerFromSteps(array $approvalSteps): array
    {
        foreach ($approvalSteps as $step) {
            if (! is_array($step)) {
                continue;
            }
            if (trim((string) ($step['step_code'] ?? '')) !== 'finance_manager') {
                continue;
            }

            return [
                'staff_id' => trim((string) ($step['staff_id'] ?? '')),
                'step_status' => trim((string) ($step['status'] ?? '')),
            ];
        }

        return [
            'staff_id' => '',
            'step_status' => '',
        ];
    }

    /**
     * @return array<int, array{staff_id:string,status:string}>
     */
    private function loadSalaryProcessActorsFromApprovalSteps(int $generalId): array
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_approval_step')) {
            return [];
        }

        return DB::table('withdraw_approval_step')
            ->where('withdraw_id', $generalId)
            ->where('withdraw_approval_step_code', 'additional_reviewer')
            ->orderBy('withdraw_approval_step_order')
            ->orderBy('withdraw_approval_step_id')
            ->get([
                'withdraw_approval_staff_id',
                'withdraw_approval_custom_status',
            ])
            ->map(function ($row) {
                return [
                    'staff_id' => trim((string) ($row->withdraw_approval_staff_id ?? '')),
                    'status' => trim((string) ($row->withdraw_approval_custom_status ?? '')),
                ];
            })
            ->filter(fn (array $actor) => $actor['staff_id'] !== '')
            ->values()
            ->all();
    }

    private function resolveNextPendingSalaryApprovalStep(int $withdrawId): ?object
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_approval_step')) {
            return null;
        }

        return DB::table('withdraw_approval_step')
            ->where('withdraw_id', $withdrawId)
            ->where('withdraw_approval_status', 'pending')
            ->whereNotIn('withdraw_approval_step_code', $this->salaryApprovalRouteExcludedStepCodes())
            ->whereNotNull('withdraw_approval_staff_id')
            ->where('withdraw_approval_staff_id', '!=', '')
            ->orderBy('withdraw_approval_step_order')
            ->orderBy('withdraw_approval_step_id')
            ->first([
                'withdraw_approval_step_id',
                'withdraw_approval_step_code',
                'withdraw_approval_step_name',
                'withdraw_approval_staff_id',
            ]);
    }

    /**
     * @return array{notification_debug:array{notified_staff_ids:array,mail_sent_staff_ids:array,mail_errors:array},mail_debug:array{attempted:bool,sent:bool,to:string,subject:string,message:string,error:string,error_class:string}}
     */
    private function notifyNextPendingSalaryApprovalStep(
        int $withdrawId,
        object $row,
        Request $request,
        string $actionUrl = ''
    ): array {
        $notificationDebug = [
            'notified_staff_ids' => [],
            'mail_sent_staff_ids' => [],
            'mail_errors' => [],
        ];
        $mailDebug = [
            'attempted' => false,
            'sent' => false,
            'to' => '',
            'subject' => '',
            'message' => '',
            'error' => '',
            'error_class' => '',
        ];

        $nextStep = $this->resolveNextPendingSalaryApprovalStep($withdrawId);
        if (! $nextStep) {
            return [
                'notification_debug' => $notificationDebug,
                'mail_debug' => $mailDebug,
            ];
        }

        $targetStaffId = trim((string) ($nextStep->withdraw_approval_staff_id ?? ''));
        $targetStepCode = trim((string) ($nextStep->withdraw_approval_step_code ?? ''));
        $targetStepName = trim((string) ($nextStep->withdraw_approval_step_name ?? 'ผู้อนุมัติ'));
        if ($targetStaffId === '' || ! $this->withdrawSetApproverNotificationsEnabled($targetStepCode, $targetStaffId)) {
            return [
                'notification_debug' => $notificationDebug,
                'mail_debug' => $mailDebug,
            ];
        }

        $displayCode = $this->resolveWithdrawGeneralDisplayDocumentCode($row);
        if ($displayCode === '') {
            $displayCode = (string) $withdrawId;
        }
        $subjectText = trim((string) ($row->withdraw_subject ?? ''));
        $subjectText = $subjectText !== '' ? $subjectText : 'เรื่อง -';
        $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
        $applicantNameMap = $this->resolveStaffNamesByIds($applicantStaffId !== '' ? [$applicantStaffId] : []);
        $applicantName = trim((string) ($applicantNameMap[$applicantStaffId] ?? ''));
        $applicantText = $applicantName !== '' ? $applicantName : ($applicantStaffId !== '' ? $applicantStaffId : '-');
        $amountText = number_format((float) ($row->withdraw_total_amount ?? 0), 2);
        $createdBy = trim((string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? ''));
        $targetActionUrl = WithdrawApprovalLink::nextApprover($withdrawId, 'salary', $targetStepCode);
        $targetRoleText = $targetStepName !== '' ? $targetStepName : 'ผู้อนุมัติ';
        $notificationMessage = $targetStepCode === 'reviewer_head'
            ? 'มีรายการเบิกจ่ายเงินเดือน/ค่าจ้าง รอการตรวจสอบจากท่าน - '.$subjectText.' ผู้ขอเบิก '.$applicantText.' จำนวนเงิน '.$amountText
            : 'มีรายการเบิกจ่ายเงินเดือน/ค่าจ้างรออนุมัติ/ตรวจสอบจากท่าน ('.$targetRoleText.') - '.$subjectText.' ผู้ขอเบิก '.$applicantText.' จำนวนเงิน '.$amountText;

        try {
            $notificationApiUrl = trim((string) config('services.notification_api.url', ''));
            if ($notificationApiUrl !== '') {
                Http::timeout(10)->post($notificationApiUrl, [
                    'staffId' => $targetStaffId,
                    'message' => $notificationMessage,
                    'systemName' => 'ระบบเบิกจ่าย',
                    'actionUrl' => $targetActionUrl,
                    'createdBy' => $createdBy !== '' ? $createdBy : 'system',
                ]);
            }
            $notificationDebug['notified_staff_ids'][] = $targetStaffId;

            $targetEmail = $this->resolveStaffEmailById($targetStaffId);
            if ($targetEmail === '') {
                $notificationDebug['mail_errors'][] = [
                    'staff_id' => $targetStaffId,
                    'error' => 'ไม่พบอีเมล',
                ];
            } else {
                $targetNameMap = $this->resolveStaffNamesByIds([$targetStaffId]);
                $recipientName = trim((string) ($targetNameMap[$targetStaffId] ?? ''));
                if ($recipientName === '') {
                    $recipientName = $targetStaffId;
                }
                $mailTemplate = new WithdrawProceedNotificationMail([
                    'display_code' => $displayCode,
                    'subject_text' => $subjectText,
                    'recipient_name' => $recipientName,
                    'applicant_text' => $applicantText,
                    'amount_text' => $amountText,
                    'action_url' => $targetActionUrl,
                    'mail_subject' => '[ระบบเบิกจ่าย] มีรายการรอดำเนินการ '.$displayCode,
                    'header_title' => 'แจ้งเตือนรายการเบิกจ่ายเงินเดือน/ค่าจ้าง',
                    'header_description' => 'มีรายการส่งต่อเพื่ออนุมัติ/ตรวจสอบตามลำดับ',
                    'status_badge' => $targetStepCode === 'reviewer_head' ? 'รอตรวจสอบ' : 'รออนุมัติ',
                    'intro_message' => 'มีรายการเบิกจ่ายเงินเดือน/ค่าจ้างที่รอดำเนินการจากท่าน',
                    'button_label' => 'เปิดเพื่ออนุมัติ',
                ]);
                $smtpHost = trim((string) env('MAIL_HOST', ''));
                $smtpPort = (int) env('MAIL_PORT', 587);
                $smtpUsername = trim((string) env('MAIL_USERNAME', ''));
                $smtpPassword = (string) env('MAIL_PASSWORD', '');
                $smtpFromAddress = trim((string) env('MAIL_FROM_ADDRESS', ''));
                $smtpFromName = trim((string) env('MAIL_FROM_NAME', (string) config('app.name', 'PBM Withdraw')));
                $mailDebug['attempted'] = true;
                $mailDebug['to'] = $targetEmail;
                $mailDebug['subject'] = $mailTemplate->subjectLine();
                $mailDebug['message'] = $mailTemplate->plainMessage();
                if ($smtpHost === '' || $smtpFromAddress === '') {
                    $notificationDebug['mail_errors'][] = [
                        'staff_id' => $targetStaffId,
                        'error' => 'SMTP host/from address ไม่ครบถ้วน',
                    ];
                    $mailDebug['error'] = 'SMTP host/from address ไม่ครบถ้วน';
                    $mailDebug['error_class'] = 'MailConfigException';
                } else {
                    $mailer = new PHPMailer(true);
                    try {
                        $mailer->CharSet = 'UTF-8';
                        $mailer->Encoding = 'base64';
                        $mailer->isSMTP();
                        $mailer->Host = $smtpHost;
                        $mailer->SMTPAuth = true;
                        $mailer->Username = $smtpUsername;
                        $mailer->Password = $smtpPassword;
                        $mailer->Port = $smtpPort > 0 ? $smtpPort : 587;
                        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mailer->SMTPAutoTLS = true;
                        $mailer->SMTPOptions = [
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true,
                            ],
                        ];
                        $mailer->setFrom($smtpFromAddress, $smtpFromName !== '' ? $smtpFromName : 'PBM Withdraw');
                        $mailer->addAddress($targetEmail);
                        $mailer->Subject = $mailTemplate->subjectLine();
                        $mailer->Body = $mailTemplate->render();
                        $mailer->AltBody = $mailTemplate->plainMessage();
                        $mailer->isHTML(true);
                        $mailer->send();
                        $notificationDebug['mail_sent_staff_ids'][] = $targetStaffId;
                        $mailDebug['sent'] = true;
                    } catch (PHPMailerException $e) {
                        $notificationDebug['mail_errors'][] = [
                            'staff_id' => $targetStaffId,
                            'error' => $e->getMessage(),
                        ];
                        $mailDebug['error'] = $e->getMessage();
                        $mailDebug['error_class'] = get_class($e);
                    }
                }
            }

            if (Schema::hasTable('withdraw_approval_step')) {
                DB::table('withdraw_approval_step')
                    ->where('withdraw_approval_step_id', (int) ($nextStep->withdraw_approval_step_id ?? 0))
                    ->update([
                        'withdraw_approval_notified_at' => now(),
                        'withdraw_approval_date_modify' => now(),
                        'staff_modify' => $createdBy !== '' ? $createdBy : 'system',
                    ]);
            }
        } catch (\Throwable $e) {
            $notificationDebug['mail_errors'][] = [
                'staff_id' => $targetStaffId,
                'error' => $e->getMessage(),
            ];
            $mailDebug['error'] = $e->getMessage();
            $mailDebug['error_class'] = get_class($e);
        }

        return [
            'notification_debug' => $notificationDebug,
            'mail_debug' => $mailDebug,
        ];
    }

    private function salaryNeedsFinanceDocumentReview(int $salaryId): bool
    {
        if ($salaryId <= 0) {
            return false;
        }

        if (
            Schema::hasTable('withdraw_approval_step')
            && DB::table('withdraw_approval_step')
                ->where('withdraw_id', $salaryId)
                ->where('withdraw_approval_step_code', 'finance_officer')
                ->where('withdraw_approval_status', '!=', 'approved')
                ->exists()
        ) {
            return true;
        }

        if (! Schema::hasTable('withdraw_item')) {
            return false;
        }

        if (! Schema::hasColumn('withdraw_item', 'withdraw_item_expense_chart_id')) {
            return true;
        }

        return DB::table('withdraw_item')
            ->where('withdraw_id', $salaryId)
            ->where(function ($query) {
                $query->whereNull('withdraw_item_expense_chart_id')
                    ->orWhere('withdraw_item_expense_chart_id', '<=', 0);
            })
            ->exists();
    }

    private function currentStaffHasFinanceReviewerAccess(string $staffId): bool
    {
        if ($staffId === '' || ! Schema::hasTable('withdraw_set_finance_reviewer')) {
            return false;
        }

        return DB::table('withdraw_set_finance_reviewer')
            ->where('withdraw_set_finance_reviewer_staff_id', $staffId)
            ->where('withdraw_set_finance_reviewer_status', 1)
            ->exists();
    }

    private function loadApprovalStepHistory(int $generalId): array
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_approval_step')) {
            return [];
        }

        $rows = DB::table('withdraw_approval_step')
            ->where('withdraw_id', $generalId)
            ->orderBy('withdraw_approval_step_order')
            ->orderBy('withdraw_approval_step_id')
            ->get([
                'withdraw_approval_step_code as step_code',
                'withdraw_approval_step_name as step_name',
                'withdraw_approval_step_order as step_order',
                'withdraw_approval_custom_status as custom_status',
                'withdraw_approval_staff_id as staff_id',
            ]);

        return collect($rows)
            ->flatMap(function ($row) {
                $raw = trim((string) ($row->custom_status ?? ''));
                if ($raw === '' || ! str_starts_with($raw, '[')) {
                    return [];
                }
                $decoded = json_decode($raw, true);
                if (! is_array($decoded)) {
                    return [];
                }
                return collect($decoded)
                    ->filter(fn ($item) => is_array($item) && trim((string) ($item['event'] ?? '')) !== '')
                    ->map(function (array $item) use ($row) {
                        return [
                            'step_code' => (string) ($row->step_code ?? ''),
                            'step_name' => (string) ($row->step_name ?? ''),
                            'step_order' => (int) ($row->step_order ?? 0),
                            'staff_id' => (string) ($row->staff_id ?? ''),
                            'event' => (string) ($item['event'] ?? ''),
                            'label' => (string) ($item['label'] ?? ''),
                            'comment' => (string) ($item['comment'] ?? ''),
                            'status' => (string) ($item['status'] ?? ''),
                            'recorded_at' => (string) ($item['recorded_at'] ?? ''),
                            'recorded_by' => (string) ($item['recorded_by'] ?? ''),
                        ];
                    })
                    ->values()
                    ->all();
            })
            ->values()
            ->all();
    }

    private function loadGeneralActionLogs(int $generalId): array
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_action_logs')) {
            return [];
        }

        return DB::table('withdraw_action_logs')
            ->where('withdraw_id', $generalId)
            ->orderBy('action_at')
            ->orderBy('id')
            ->get([
                'id',
                'action_code',
                'action_label',
                'from_status',
                'to_status',
                'actor_staff_id',
                'actor_name',
                'comment',
                'meta_json',
                'action_at',
            ])
            ->map(function ($row) {
                $meta = null;
                $rawMeta = trim((string) ($row->meta_json ?? ''));
                if ($rawMeta !== '') {
                    $decoded = json_decode($rawMeta, true);
                    if (is_array($decoded)) {
                        $meta = $decoded;
                    }
                }
                return [
                    'id' => (int) ($row->id ?? 0),
                    'action_code' => (string) ($row->action_code ?? ''),
                    'action_label' => (string) ($row->action_label ?? ''),
                    'from_status' => (string) ($row->from_status ?? ''),
                    'to_status' => (string) ($row->to_status ?? ''),
                    'actor_staff_id' => (string) ($row->actor_staff_id ?? ''),
                    'actor_name' => (string) ($row->actor_name ?? ''),
                    'comment' => (string) ($row->comment ?? ''),
                    'meta' => $meta,
                    'action_at' => (string) ($row->action_at ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array<int, mixed> $processActors
     */
    private function syncApprovalSteps(int $generalId, array $processActors, string $staffId = ''): void
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_approval_step')) {
            return;
        }

        $generalTotalAmount = 0.0;
        if (Schema::hasTable('withdraw')) {
            $generalTotalAmount = (float) ($this->withdrawQuery()
                ->where('withdraw_id', $generalId)
                ->value('withdraw_total_amount') ?? 0);
        }

        $approverStaffByStepCode = [];
        if (Schema::hasTable('withdraw_set_approver')) {
            $approverStaffByStepCode = DB::table('withdraw_set_approver')
                ->where('withdraw_set_approver_status', 1)
                ->get([
                    'withdraw_set_approver_step_code',
                    'withdraw_set_approver_staff_id',
                ])
                ->reduce(function (array $carry, $row) {
                    $stepCode = trim((string) ($row->withdraw_set_approver_step_code ?? ''));
                    $staffId = trim((string) ($row->withdraw_set_approver_staff_id ?? ''));
                    if ($stepCode !== '' && $staffId !== '') {
                        $carry[$stepCode] = $staffId;
                    }
                    return $carry;
                }, []);
        }

        $now = now();

        $rows = [];

        $actorOrder = 1;
        foreach ($processActors as $actor) {
            if (! is_array($actor)) {
                continue;
            }
            $actorStaffId = trim((string) ($actor['staff_id'] ?? ''));
            $actorStatus = trim((string) ($actor['status'] ?? ''));
            if ($actorStaffId === '') {
                continue;
            }
            $rows[] = [
                'withdraw_id' => $generalId,
                'withdraw_approval_step_code' => 'additional_reviewer',
                'withdraw_approval_step_name' => 'ผู้ตรวจสอบเพิ่มเติม',
                'withdraw_approval_step_order' => 20 + $actorOrder,
                'withdraw_approval_staff_id' => $actorStaffId,
                'withdraw_approval_custom_status' => $actorStatus !== '' ? $actorStatus : null,
                'withdraw_approval_status' => 'pending',
                'withdraw_approval_comment' => null,
                'withdraw_approval_approved_at' => null,
                'withdraw_approval_notified_at' => null,
                'withdraw_approval_date_create' => $now,
                'withdraw_approval_date_modify' => $now,
                'staff_create' => $staffId,
                'staff_modify' => $staffId,
            ];
            $actorOrder += 1;
        }

        $fixedSteps = [
            ['code' => 'finance_officer', 'name' => 'งานการเงิน', 'order' => 35],
            ['code' => 'finance_manager', 'name' => 'หัวหน้างานการเงิน', 'order' => 40],
            ['code' => 'faculty_secretary', 'name' => 'เลขาคณะ', 'order' => 50],
            ['code' => 'vice_dean', 'name' => 'รองคณบดี', 'order' => 60],
            ['code' => 'dean', 'name' => 'คณบดี', 'order' => 70],
        ];

        // ยอดไม่เกิน 100,000 บาท อนุมัติสูงสุดที่รองคณบดี (ไม่ต้องถึงคณบดี)
        if ($generalTotalAmount <= 100000) {
            $fixedSteps = array_values(array_filter($fixedSteps, static function (array $step): bool {
                return (string) ($step['code'] ?? '') !== 'dean';
            }));
        }

        $salarySubType = 'salary';
        if (Schema::hasTable('withdraw') && Schema::hasColumn('withdraw', 'withdraw_sub_type')) {
            $salarySubType = trim((string) ($this->withdrawQuery()
                ->where('withdraw_id', $generalId)
                ->value('withdraw_sub_type') ?? 'salary'));
        }
        if ($salarySubType === 'travel_leave') {
            $fixedSteps = WithdrawMemoTemplate::filterFixedApprovalSteps($fixedSteps, 'travel', 'default');
        } else {
            $fixedSteps = WithdrawMemoTemplate::filterFixedApprovalSteps(
                $fixedSteps,
                'salary',
                $this->normalizeSalaryRequestType($salarySubType)
            );
        }

        foreach ($fixedSteps as $fixedStep) {
            $fixedStepCode = (string) $fixedStep['code'];
            $rows[] = [
                'withdraw_id' => $generalId,
                'withdraw_approval_step_code' => $fixedStepCode,
                'withdraw_approval_step_name' => (string) $fixedStep['name'],
                'withdraw_approval_step_order' => (int) $fixedStep['order'],
                'withdraw_approval_staff_id' => (string) ($approverStaffByStepCode[$fixedStepCode] ?? '') !== ''
                    ? (string) ($approverStaffByStepCode[$fixedStepCode] ?? '')
                    : null,
                'withdraw_approval_custom_status' => null,
                'withdraw_approval_status' => 'pending',
                'withdraw_approval_comment' => null,
                'withdraw_approval_approved_at' => null,
                'withdraw_approval_notified_at' => null,
                'withdraw_approval_date_create' => $now,
                'withdraw_approval_date_modify' => $now,
                'staff_create' => $staffId,
                'staff_modify' => $staffId,
            ];
        }

        $existingRows = DB::table('withdraw_approval_step')
            ->where('withdraw_id', $generalId)
            ->get([
                'withdraw_approval_step_id',
                'withdraw_approval_step_code',
                'withdraw_approval_status',
            ]);

        $existingByCode = $existingRows
            ->filter(fn ($row) => trim((string) ($row->withdraw_approval_step_code ?? '')) !== 'additional_reviewer')
            ->keyBy(fn ($row) => trim((string) ($row->withdraw_approval_step_code ?? '')));

        $desiredCodes = [];
        $additionalRows = [];
        foreach ($rows as $row) {
            $stepCode = trim((string) ($row['withdraw_approval_step_code'] ?? ''));
            if ($stepCode === '') {
                continue;
            }
            if ($stepCode === 'additional_reviewer') {
                $additionalRows[] = $row;
                continue;
            }

            $desiredCodes[] = $stepCode;
            $existing = $existingByCode->get($stepCode);
            if ($existing) {
                DB::table('withdraw_approval_step')
                    ->where('withdraw_approval_step_id', (int) $existing->withdraw_approval_step_id)
                    ->update([
                        'withdraw_approval_step_name' => (string) ($row['withdraw_approval_step_name'] ?? ''),
                        'withdraw_approval_step_order' => (int) ($row['withdraw_approval_step_order'] ?? 0),
                        'withdraw_approval_staff_id' => $row['withdraw_approval_staff_id'] ?? null,
                        'withdraw_approval_custom_status' => $row['withdraw_approval_custom_status'] ?? null,
                        'withdraw_approval_date_modify' => $now,
                        'staff_modify' => $staffId,
                    ]);
                continue;
            }

            DB::table('withdraw_approval_step')->insert($row);
        }

        DB::table('withdraw_approval_step')
            ->where('withdraw_id', $generalId)
            ->where('withdraw_approval_step_code', 'additional_reviewer')
            ->where('withdraw_approval_status', 'pending')
            ->delete();
        foreach ($additionalRows as $additionalRow) {
            DB::table('withdraw_approval_step')->insert($additionalRow);
        }

        $deleteIds = $existingRows
            ->filter(function ($row) use ($desiredCodes) {
                $stepCode = trim((string) ($row->withdraw_approval_step_code ?? ''));
                if ($stepCode === '' || $stepCode === 'additional_reviewer' || in_array($stepCode, $desiredCodes, true)) {
                    return false;
                }

                return trim((string) ($row->withdraw_approval_status ?? 'pending')) === 'pending';
            })
            ->pluck('withdraw_approval_step_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
        if (count($deleteIds) > 0) {
            DB::table('withdraw_approval_step')
                ->whereIn('withdraw_approval_step_id', $deleteIds)
                ->delete();
        }

        DB::table('withdraw_approval_step')
            ->where('withdraw_id', $generalId)
            ->where('withdraw_approval_step_code', 'reviewer_head')
            ->delete();
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, array{category:string,name:string,expense_chart_id:int,budget_manage_id:int,amount:float}>
     */
    private function normalizeSalaryDraftItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): array {
                $categoryCode = $this->normalizeItemCategoryCode(
                    trim((string) ($item['category_code'] ?? $item['category'] ?? ''))
                );

                return [
                    'category' => $categoryCode,
                    'name' => $this->resolveSalaryItemSaveName($item),
                    'expense_chart_id' => (int) ($item['expense_chart_id'] ?? 0),
                    'budget_manage_id' => (int) ($item['budget_manage_id'] ?? 0),
                    'budget_manage_code' => trim((string) ($item['budget_manage_code'] ?? '')),
                    'amount' => (float) ($item['amount'] ?? 0),
                ];
            })
            ->filter(function (array $row): bool {
                return (int) ($row['budget_manage_id'] ?? 0) > 0
                    && trim((string) ($row['category'] ?? '')) !== ''
                    && trim((string) ($row['name'] ?? '')) !== ''
                    && (float) ($row['amount'] ?? 0) > 0;
            })
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolveSalaryItemSaveName(array $item): string
    {
        $fallbackName = trim((string) ($item['name'] ?? ''));
        $expenseChartId = (int) ($item['expense_chart_id'] ?? 0);
        if ($expenseChartId <= 0 || ! Schema::hasTable('withdraw_set_expenses')) {
            return $fallbackName;
        }

        $row = DB::table('withdraw_set_expenses')
            ->where('withdraw_set_expenses_id', $expenseChartId)
            ->first([
                'withdraw_set_expenses_name as name',
                'withdraw_set_expenses_detail as detail',
            ]);
        if ($row === null) {
            return $fallbackName;
        }

        $detail = trim((string) ($row->detail ?? ''));
        if ($detail !== '' && $detail !== '-') {
            return $detail;
        }

        $chartName = trim((string) ($row->name ?? ''));

        return $chartName !== '' ? $chartName : $fallbackName;
    }

    /**
     * @param array<int, array{budget_manage_id:int,amount:float}> $items
     * @return array<int, array{budget_manage_id:int,amount:float}>
     */
    private function buildBudgetBreakdownFromItems(array $items): array
    {
        $aggregated = [];
        foreach ($items as $item) {
            $budgetManageId = (int) ($item['budget_manage_id'] ?? 0);
            $amount = (float) ($item['amount'] ?? 0);
            if ($budgetManageId <= 0 || $amount <= 0) {
                continue;
            }
            if (! isset($aggregated[$budgetManageId])) {
                $aggregated[$budgetManageId] = 0.0;
            }
            $aggregated[$budgetManageId] += $amount;
        }

        return collect($aggregated)
            ->map(fn (float $amount, int|string $budgetManageId) => [
                'budget_manage_id' => (int) $budgetManageId,
                'amount' => round($amount, 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @param array<int, mixed> $items
     */
    private function syncGeneralItems(int $generalId, array $items, string $staffId = '', int $defaultBudgetManageId = 0): void
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_item')) {
            return;
        }

        $hasBudgetManageColumn = Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_id');
        $hasBudgetManageCodeColumn = Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_code');
        $hasExpenseChartColumn = Schema::hasColumn('withdraw_item', 'withdraw_item_expense_chart_id');
        $rows = $this->normalizeSalaryDraftItems($items);
        if (count($rows) === 0) {
            $rows = collect($items)
                ->filter(fn ($item) => is_array($item))
                ->map(function ($item) use ($defaultBudgetManageId) {
                    $itemBudgetManageId = (int) ($item['budget_manage_id'] ?? 0);

                    return [
                        'category' => $this->normalizeItemCategoryCode((string) ($item['category_code'] ?? $item['category'] ?? '')),
                        'name' => trim((string) ($item['name'] ?? '')),
                        'expense_chart_id' => (int) ($item['expense_chart_id'] ?? 0),
                        'budget_manage_id' => $itemBudgetManageId > 0 ? $itemBudgetManageId : $defaultBudgetManageId,
                        'budget_manage_code' => trim((string) ($item['budget_manage_code'] ?? '')),
                        'amount' => (float) ($item['amount'] ?? 0),
                    ];
                })
                ->filter(function (array $row): bool {
                    return (int) ($row['budget_manage_id'] ?? 0) > 0
                        && trim((string) ($row['category'] ?? '')) !== ''
                        && trim((string) ($row['name'] ?? '')) !== ''
                        && (float) ($row['amount'] ?? 0) > 0;
                })
                ->values()
                ->all();
        }

        $codeById = WithdrawSchema::budgetManageCodesByIds(
            array_map(static fn (array $row) => (int) ($row['budget_manage_id'] ?? 0), $rows)
        );

        $existingRows = DB::table('withdraw_item')
            ->where('withdraw_id', $generalId)
            ->orderBy('withdraw_item_order')
            ->orderBy('withdraw_item_id')
            ->get(['withdraw_item_id'])
            ->values();

        $now = now();
        foreach ($rows as $index => $row) {
            $targetOrder = $index + 1;
            $payload = [
                'withdraw_item_category' => (string) ($row['category'] ?? ''),
                'withdraw_item_name' => (string) ($row['name'] ?? ''),
                'withdraw_item_amount' => (float) ($row['amount'] ?? 0),
                'withdraw_item_order' => $targetOrder,
                'withdraw_item_date_modify' => $now,
                'staff_modify' => $staffId,
            ];
            if ($hasBudgetManageColumn) {
                $budgetManageId = (int) ($row['budget_manage_id'] ?? 0);
                $payload['withdraw_item_budget_manage_id'] = $budgetManageId > 0 ? $budgetManageId : 0;
                if ($hasBudgetManageCodeColumn) {
                    $budgetManageCode = trim((string) ($row['budget_manage_code'] ?? ''));
                    if ($budgetManageCode === '' && $budgetManageId > 0) {
                        $budgetManageCode = trim((string) ($codeById[$budgetManageId] ?? ''));
                    }
                    $payload['withdraw_item_budget_manage_code'] = $budgetManageCode !== '' ? $budgetManageCode : null;
                }
            }
            if ($hasExpenseChartColumn) {
                $expenseChartId = (int) ($row['expense_chart_id'] ?? 0);
                $payload['withdraw_item_expense_chart_id'] = $expenseChartId > 0 ? $expenseChartId : null;
            }

            $existing = $existingRows->get($index);
            if ($existing) {
                DB::table('withdraw_item')
                    ->where('withdraw_item_id', (int) $existing->withdraw_item_id)
                    ->update($payload);
            } else {
                DB::table('withdraw_item')->insert(array_merge($payload, [
                    'withdraw_id' => $generalId,
                    'withdraw_item_date_create' => $now,
                    'staff_create' => $staffId,
                ]));
            }
        }

        if ($existingRows->count() > count($rows)) {
            $deleteIds = $existingRows
                ->slice(count($rows))
                ->pluck('withdraw_item_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();
            if (count($deleteIds) > 0) {
                DB::table('withdraw_item')
                    ->whereIn('withdraw_item_id', $deleteIds)
                    ->delete();
            }
        }
    }

    /**
     * @return array<int, array{budget_manage_id:int,amount:float}>
     */
    private function parseBudgetBreakdownInput(Request $request): array
    {
        $budgetBreakdown = json_decode((string) $request->input('budget_breakdown', '[]'), true);
        if (! is_array($budgetBreakdown)) {
            $budgetBreakdown = [];
        }

        $rows = collect($budgetBreakdown)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) {
                $budgetManageId = (int) ($row['budget_manage_id'] ?? $row['id'] ?? 0);
                return [
                    'budget_manage_id' => $budgetManageId,
                    'amount' => (float) ($row['amount'] ?? 0),
                ];
            })
            ->filter(fn (array $row) => (int) ($row['budget_manage_id'] ?? 0) > 0)
            ->values();

        if ($rows->isNotEmpty()) {
            return $rows->all();
        }

        $budgetManageIds = json_decode((string) $request->input('budget_manage_ids', '[]'), true);
        if (! is_array($budgetManageIds)) {
            return [];
        }

        return collect($budgetManageIds)
            ->map(function ($entry) {
                if (is_array($entry)) {
                    return [
                        'budget_manage_id' => (int) ($entry['budget_manage_id'] ?? $entry['id'] ?? 0),
                        'amount' => (float) ($entry['amount'] ?? 0),
                    ];
                }

                return [
                    'budget_manage_id' => (int) $entry,
                    'amount' => 0.0,
                ];
            })
            ->filter(fn (array $row) => (int) ($row['budget_manage_id'] ?? 0) > 0)
            ->values()
            ->all();
    }

    /**
     * @param array<int, array{budget_manage_id:int,amount:float}> $budgetRows
     */
    private function syncSalaryBudgets(int $salaryId, array $budgetRows, string $staffId = ''): void
    {
        if ($salaryId <= 0 || ! Schema::hasTable('withdraw_budget')) {
            return;
        }

        $rows = collect($budgetRows)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) {
                return [
                    'budget_manage_id' => (int) ($row['budget_manage_id'] ?? 0),
                    'amount' => (float) ($row['amount'] ?? 0),
                ];
            })
            ->filter(fn (array $row) => (int) ($row['budget_manage_id'] ?? 0) > 0)
            ->values()
            ->all();

        $existingRows = DB::table('withdraw_budget')
            ->where('withdraw_id', $salaryId)
            ->orderBy('withdraw_budget_order')
            ->orderBy('withdraw_budget_id')
            ->get(['withdraw_budget_id'])
            ->values();

        $now = now();
        foreach ($rows as $index => $row) {
            $targetOrder = $index + 1;
            $payload = [
                'withdraw_budget_manage_id' => (int) ($row['budget_manage_id'] ?? 0),
                'withdraw_budget_amount' => (float) ($row['amount'] ?? 0),
                'withdraw_budget_order' => $targetOrder,
                'withdraw_budget_date_modify' => $now,
                'staff_modify' => $staffId,
            ];

            $existing = $existingRows->get($index);
            if ($existing) {
                DB::table('withdraw_budget')
                    ->where('withdraw_budget_id', (int) $existing->withdraw_budget_id)
                    ->update($payload);
            } else {
                DB::table('withdraw_budget')->insert(array_merge($payload, [
                    'withdraw_id' => $salaryId,
                    'withdraw_budget_date_create' => $now,
                    'staff_create' => $staffId,
                ]));
            }
        }

        if ($existingRows->count() > count($rows)) {
            $deleteIds = $existingRows
                ->slice(count($rows))
                ->pluck('withdraw_budget_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();
            if (count($deleteIds) > 0) {
                DB::table('withdraw_budget')
                    ->whereIn('withdraw_budget_id', $deleteIds)
                    ->delete();
            }
        }
    }

    /**
     * @param  array<int, array{budget_manage_id:int,amount:float}>  $budgetBreakdown
     * @return array<int, array{label:string,amount:float,amount_formatted:string,amount_text:string}>
     */
    private function buildMemoParagraph2BudgetLines(array $budgetBreakdown): array
    {
        if (count($budgetBreakdown) < 2) {
            return [];
        }

        $budgetManageIds = collect($budgetBreakdown)
            ->pluck('budget_manage_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $sourcePathMap = $this->resolveBudgetSourcePathMapByManageIds($budgetManageIds);
        $lines = [];
        foreach ($budgetBreakdown as $row) {
            $budgetManageId = (int) ($row['budget_manage_id'] ?? 0);
            $amount = (float) ($row['amount'] ?? 0);
            if ($budgetManageId <= 0 || ! is_finite($amount) || $amount <= 0) {
                continue;
            }
            $label = $this->normalizeBudgetMemoLabel((string) ($sourcePathMap[$budgetManageId] ?? ''));
            if ($label === '') {
                continue;
            }
            $lines[] = [
                'label' => \App\Support\ThaiDigits::inString($label),
                'amount' => $amount,
                'amount_formatted' => \App\Support\ThaiDigits::formatAmount($amount),
                'amount_text' => $this->toThaiBahtText($amount),
            ];
        }

        return count($lines) >= 2 ? $lines : [];
    }

    /**
     * @param  array<int, int>  $budgetManageIds
     * @return array<int, string>
     */
    private function resolveBudgetSourcePathMapByManageIds(array $budgetManageIds): array
    {
        $budgetManageIds = array_values(array_filter(array_map('intval', $budgetManageIds), static fn ($id) => $id > 0));
        if ($budgetManageIds === [] || ! Schema::connection('budget')->hasTable('budget_manage')) {
            return [];
        }

        $rows = DB::connection('budget')
            ->table('budget_manage as bm')
            ->leftJoin('budget_set_source as bs', 'bm.budget_source_code', '=', 'bs.budget_source_code')
            ->leftJoin('budget_set_plan as bp', 'bm.budget_plan_code', '=', 'bp.budget_plan_code')
            ->leftJoin('budget_set_event as be', 'bm.budget_event_code', '=', 'be.budget_event_code')
            ->leftJoin('budget_set_activity as ba', 'bm.budget_activity_code', '=', 'ba.budget_activity_code')
            ->leftJoin('budget_set_fund as bf', 'bm.budget_fund_code', '=', 'bf.budget_fund_code')
            ->leftJoin('budget_set_category_budget as bcb', 'bm.budget_category_budget_code', '=', 'bcb.budget_category_budget_code')
            ->leftJoin('budget_set_category_pay as bcp', 'bm.budget_category_pay_code', '=', 'bcp.budget_category_pay_code')
            ->whereIn('bm.budget_manage_id', $budgetManageIds)
            ->get([
                'bm.budget_manage_id as id',
                'bs.budget_source_name',
                'bm.budget_manage_year',
                'bp.budget_plan_name',
                'be.budget_event_name',
                'ba.budget_activity_name',
                'bf.budget_fund_name',
                'bcb.budget_category_budget_name',
                'bcp.budget_category_pay_name',
                'bm.budget_category_pay_detail',
            ]);

        $map = [];
        foreach ($rows as $budgetRow) {
            $activityName = trim((string) ($budgetRow->budget_activity_name ?? ''));
            $payDetail = trim((string) ($budgetRow->budget_category_pay_detail ?? ''));
            $sourcePath = trim((string) ($budgetRow->budget_source_name ?? '')).'ปี '.trim((string) ($budgetRow->budget_manage_year ?? ''))
                .'/'.trim((string) ($budgetRow->budget_plan_name ?? ''))
                .'/'.trim((string) ($budgetRow->budget_event_name ?? ''))
                .($activityName !== '' ? '-'.$activityName : '')
                .'/'.trim((string) ($budgetRow->budget_fund_name ?? ''))
                .'/'.trim((string) ($budgetRow->budget_category_budget_name ?? ''))
                .'/'.trim((string) ($budgetRow->budget_category_pay_name ?? ''))
                .($payDetail !== '' ? '-'.$payDetail : '');
            $map[(int) ($budgetRow->id ?? 0)] = trim($sourcePath);
        }

        return $map;
    }

    private function normalizeBudgetMemoLabel(string $sourcePath): string
    {
        $label = trim($sourcePath);
        if ($label === '') {
            return '';
        }
        $label = preg_replace('/\s*\/\s*/u', ' ', $label) ?? $label;
        $label = preg_replace('/\s+/u', ' ', $label) ?? $label;

        return trim($label);
    }

    /**
     * @return array<int, array{budget_manage_id:int,amount:float}>
     */
    private function loadSalaryBudgets(int $salaryId): array
    {
        if ($salaryId <= 0 || ! Schema::hasTable('withdraw_budget')) {
            return [];
        }

        return DB::table('withdraw_budget')
            ->where('withdraw_id', $salaryId)
            ->orderBy('withdraw_budget_order')
            ->orderBy('withdraw_budget_id')
            ->get([
                'withdraw_budget_manage_id as budget_manage_id',
                'withdraw_budget_amount as amount',
            ])
            ->map(fn ($row) => [
                'budget_manage_id' => (int) ($row->budget_manage_id ?? 0),
                'amount' => (float) ($row->amount ?? 0),
            ])
            ->filter(fn (array $row) => (int) ($row['budget_manage_id'] ?? 0) > 0)
            ->values()
            ->all();
    }

    private function normalizeSalaryRequestType(string $requestType): string
    {
        $normalized = trim(strtolower($requestType));
        if ($normalized === 'salary') {
            $normalized = 'salary_civil_servant';
        }
        $allowed = ['salary_civil_servant', 'salary_university_staff', 'position_allowance', 'pts', 'travel_leave'];

        return in_array($normalized, $allowed, true) ? $normalized : 'salary_civil_servant';
    }

    private function mapSalaryRequestTypeLabel(string $requestType): string
    {
        return match ($this->normalizeSalaryRequestType($requestType)) {
            'salary_university_staff' => 'เงินเดือนพนักงานมหาวิทยาลัย',
            'position_allowance' => 'เงินประจำตำแหน่ง',
            'pts' => 'เงิน พ.ต.ส.',
            'travel_leave' => 'เบิกจ่ายในการเดินทางไปปฏิบัติงาน',
            default => 'เงินเดือนข้าราชการ',
        };
    }

    private function resolveSalaryMemoPdfBodyView(string $salaryRequestType): string
    {
        return match ($this->normalizeSalaryRequestType($salaryRequestType)) {
            'salary_university_staff' => 'withdraw.salary.pdf.memo-body-salary-university-staff',
            'position_allowance' => 'withdraw.salary.pdf.memo-body-position-allowance',
            'pts' => 'withdraw.salary.pdf.memo-body-pts',
            'travel_leave' => 'withdraw.salary.pdf.memo-body-travel',
            default => 'withdraw.salary.pdf.memo-body-salary-civil-servant',
        };
    }

    /**
     * @return list<array{document_number_display:string,copy_index:int,copy_total:int}>
     */
    private function buildSalaryMemoPdfCopies(object $row, string $salaryRequestType): array
    {
        $primaryDisplay = $this->resolveWithdrawGeneralDisplayDocumentCode($row);
        $copies = [
            [
                'document_number_display' => $primaryDisplay,
                'copy_index' => 1,
                'copy_total' => 1,
            ],
        ];

        if (! $this->salaryPtsUsesDualDocumentNumbers($salaryRequestType)) {
            return $copies;
        }

        $secondDisplay = $this->resolveSalaryPtsSecondDisplayDocumentCode($row);
        if ($secondDisplay === '' || $secondDisplay === $primaryDisplay) {
            return $copies;
        }

        $copies[0]['copy_total'] = 2;
        $copies[] = [
            'document_number_display' => $secondDisplay,
            'copy_index' => 2,
            'copy_total' => 2,
        ];

        return $copies;
    }

    /**
     * @param array<int, mixed> $documents
     * @return array<int, string>
     */
    private function syncGeneralDocuments(int $generalId, array $documents, string $staffId = ''): array
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_req_document')) {
            return [];
        }

        $rows = collect($documents)
            ->filter(fn ($document) => is_array($document))
            ->map(function ($document) {
                $type = trim((string) ($document['type'] ?? ''));
                $item = trim((string) ($document['item_id'] ?? $document['item'] ?? ''));
                return [
                    'type' => $type,
                    'item' => $item,
                    'label' => trim((string) ($document['label'] ?? '')),
                    'key' => $type.'::'.$item,
                ];
            })
            ->filter(fn ($document) => (string) ($document['type'] ?? '') !== '' && (string) ($document['item'] ?? '') !== '')
            ->values()
            ->all();

        $existingRows = DB::table('withdraw_req_document')
            ->where('withdraw_id', $generalId)
            ->orderBy('withdraw_req_document_order')
            ->orderBy('withdraw_req_document_id')
            ->get(['withdraw_req_document_id'])
            ->values();

        $now = now();
        foreach ($rows as $index => $row) {
            $targetOrder = $index + 1;
            $payload = [
                'withdraw_req_document_type_code' => (string) ($row['type'] ?? ''),
                'withdraw_req_document_item_code' => (string) ($row['item'] ?? ''),
                'withdraw_req_document_label' => (string) ($row['label'] ?? ''),
                'withdraw_req_document_order' => $targetOrder,
                'withdraw_req_document_date_modify' => $now,
                'staff_modify' => $staffId,
            ];

            $existing = $existingRows->get($index);
            if ($existing) {
                DB::table('withdraw_req_document')
                    ->where('withdraw_req_document_id', (int) $existing->withdraw_req_document_id)
                    ->update($payload);
            } else {
                DB::table('withdraw_req_document')->insert(array_merge($payload, [
                    'withdraw_id' => $generalId,
                    'withdraw_req_document_date_create' => $now,
                    'staff_create' => $staffId,
                ]));
            }
        }

        if ($existingRows->count() > count($rows)) {
            $deleteIds = $existingRows
                ->slice(count($rows))
                ->pluck('withdraw_req_document_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();
            if (count($deleteIds) > 0) {
                DB::table('withdraw_req_document')
                    ->whereIn('withdraw_req_document_id', $deleteIds)
                    ->delete();
            }
        }

        return collect($rows)
            ->pluck('key')
            ->filter(fn ($key) => trim((string) $key) !== '::')
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $activeKeys
     */
    private function cleanupGeneralFilesByDocumentKeys(int $generalId, array $activeKeys): void
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_file')) {
            return;
        }

        $normalizedKeys = collect($activeKeys)
            ->map(fn ($key) => trim((string) $key))
            ->filter(fn ($key) => $key !== '')
            ->unique()
            ->values()
            ->all();

        $existingFiles = DB::table('withdraw_file')
            ->where('withdraw_id', $generalId)
            ->get([
                'withdraw_file_id as id',
                'withdraw_file_type_code as type',
                'withdraw_file_item_code as item',
            ]);

        $deleteIds = collect($existingFiles)
            ->filter(function ($file) use ($normalizedKeys) {
                $key = trim((string) ($file->type ?? '')).'::'.trim((string) ($file->item ?? ''));
                return ! in_array($key, $normalizedKeys, true);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if (count($deleteIds) > 0) {
            DB::table('withdraw_file')
                ->whereIn('withdraw_file_id', $deleteIds)
                ->delete();
        }
    }

    /**
     * @param array<int, mixed> $removeFileIds
     */
    private function deleteGeneralFilesByIds(int $generalId, mixed $removeFileIds): void
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_file') || ! is_array($removeFileIds)) {
            return;
        }

        $ids = collect($removeFileIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        if (count($ids) === 0) {
            return;
        }

        DB::table('withdraw_file')
            ->where('withdraw_id', $generalId)
            ->whereIn('withdraw_file_id', $ids)
            ->delete();
    }

    /**
     * @param array{type:string,item:string,path:string,name:string,size:int,mime:string} $file
     */
    private function insertGeneralFile(int $generalId, array $file, string $staffId = ''): void
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_file')) {
            return;
        }

        $typeCode = trim((string) ($file['type'] ?? ''));
        $itemCode = trim((string) ($file['item'] ?? ''));
        if ($typeCode === '' || $itemCode === '') {
            return;
        }

        $now = now();
        DB::table('withdraw_file')->insert([
            'withdraw_id' => $generalId,
            'withdraw_file_type_code' => $typeCode,
            'withdraw_file_item_code' => $itemCode,
            'withdraw_file_path' => (string) ($file['path'] ?? ''),
            'withdraw_file_name' => (string) ($file['name'] ?? ''),
            'withdraw_file_size' => (int) ($file['size'] ?? 0),
            'withdraw_file_mime' => (string) ($file['mime'] ?? ''),
            'withdraw_file_date_create' => $now,
            'withdraw_file_date_modify' => $now,
            'staff_create' => $staffId,
            'staff_modify' => $staffId,
        ]);
    }

    /**
     * บันทึกไฟล์เอกสาร e-Sign ที่เจ้าหน้าที่การเงินอัปโหลดตอนตรวจสอบเอกสาร
     */
    private function storeFinanceEsignUploads(Request $request, int $withdrawId, string $staffId = '', string $storageKind = 'salary'): int
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_file')) {
            return 0;
        }

        $files = $request->file('esign_files', []);
        if (! is_array($files) || $files === []) {
            $single = $request->file('esign_files');
            $files = $single ? [$single] : [];
        }
        $files = array_values(array_filter($files, static fn ($file) => $file && method_exists($file, 'isValid') && $file->isValid()));
        if ($files === []) {
            return 0;
        }

        [$typeCode, $itemCode, $itemLabel] = $this->resolveEsignDocumentCodes();
        if ($typeCode === '' || $itemCode === '') {
            throw new \RuntimeException('ยังไม่ได้ตั้งค่าประเภทเอกสาร e-Sign ในระบบ');
        }

        $storageKind = trim($storageKind) !== '' ? trim($storageKind) : 'salary';
        $labels = $request->input('esign_names', []);
        if (! is_array($labels)) {
            $labels = [];
        }
        $saved = 0;
        $lastLabel = $itemLabel;
        foreach ($files as $index => $file) {
            $mime = strtolower((string) ($file->getClientMimeType() ?? ''));
            $extension = strtolower((string) ($file->getClientOriginalExtension() ?? ''));
            if (! str_contains($mime, 'pdf') && $extension !== 'pdf') {
                throw new \RuntimeException('เอกสาร e-Sign รองรับเฉพาะไฟล์ PDF');
            }

            $customName = trim((string) ($labels[$index] ?? ''));
            $originalName = (string) $file->getClientOriginalName();
            $displayName = $customName !== '' ? $customName : $originalName;

            $path = $file->store("withdraw/{$storageKind}/{$withdrawId}", 'public');
            $this->insertGeneralFile($withdrawId, [
                'type' => $typeCode,
                'item' => $itemCode,
                'path' => $path,
                'name' => $displayName,
                'size' => (int) $file->getSize(),
                'mime' => (string) ($file->getClientMimeType() ?? 'application/pdf'),
            ], $staffId);
            if ($customName !== '') {
                $lastLabel = $customName;
            }
            $saved += 1;
        }

        if ($saved > 0) {
            $this->ensureEsignReqDocument($withdrawId, $typeCode, $itemCode, $lastLabel, $staffId);
        }

        return $saved;
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function resolveEsignDocumentCodes(): array
    {
        if (! Schema::hasTable('withdraw_set_document_type')) {
            return ['', '', ''];
        }

        $typeRow = DB::table('withdraw_set_document_type')
            ->where('withdraw_set_document_type_status', 1)
            ->where(function ($query) {
                $query->whereRaw('LOWER(withdraw_set_document_type_name) LIKE ?', ['%e-sign%'])
                    ->orWhereRaw('LOWER(withdraw_set_document_type_name) LIKE ?', ['%esign%']);
            })
            ->orderBy('withdraw_set_document_type_code')
            ->first([
                'withdraw_set_document_type_code',
                'withdraw_set_document_type_name',
            ]);

        $typeCode = trim((string) ($typeRow->withdraw_set_document_type_code ?? ''));
        if ($typeCode === '') {
            return ['', '', ''];
        }

        $itemCode = '';
        $itemLabel = trim((string) ($typeRow->withdraw_set_document_type_name ?? 'เอกสาร e-Sign'));
        if (Schema::hasTable('withdraw_set_document_item')) {
            $itemRow = DB::table('withdraw_set_document_item')
                ->where('withdraw_set_document_type_code', $typeCode)
                ->where('withdraw_set_document_item_status', 1)
                ->orderBy('withdraw_set_document_item_id')
                ->first([
                    'withdraw_set_document_item_code',
                    'withdraw_set_document_item_name',
                ]);
            $itemCode = trim((string) ($itemRow->withdraw_set_document_item_code ?? ''));
            $itemName = trim((string) ($itemRow->withdraw_set_document_item_name ?? ''));
            if ($itemName !== '') {
                $itemLabel = $itemName;
            }
        }

        if ($itemCode === '') {
            $itemCode = '1';
        }

        return [$typeCode, $itemCode, $itemLabel];
    }

    private function ensureEsignReqDocument(
        int $withdrawId,
        string $typeCode,
        string $itemCode,
        string $label = '',
        string $staffId = ''
    ): void {
        if ($withdrawId <= 0 || $typeCode === '' || $itemCode === '' || ! Schema::hasTable('withdraw_req_document')) {
            return;
        }

        $exists = DB::table('withdraw_req_document')
            ->where('withdraw_id', $withdrawId)
            ->where('withdraw_req_document_type_code', $typeCode)
            ->where('withdraw_req_document_item_code', $itemCode)
            ->exists();
        if ($exists) {
            if ($label !== '') {
                DB::table('withdraw_req_document')
                    ->where('withdraw_id', $withdrawId)
                    ->where('withdraw_req_document_type_code', $typeCode)
                    ->where('withdraw_req_document_item_code', $itemCode)
                    ->update([
                        'withdraw_req_document_label' => $label,
                        'withdraw_req_document_date_modify' => now(),
                        'staff_modify' => $staffId,
                    ]);
            }

            return;
        }

        $maxOrder = (int) (DB::table('withdraw_req_document')
            ->where('withdraw_id', $withdrawId)
            ->max('withdraw_req_document_order') ?? 0);
        $now = now();
        DB::table('withdraw_req_document')->insert([
            'withdraw_id' => $withdrawId,
            'withdraw_req_document_type_code' => $typeCode,
            'withdraw_req_document_item_code' => $itemCode,
            'withdraw_req_document_label' => $label !== '' ? $label : 'เอกสาร e-Sign',
            'withdraw_req_document_order' => $maxOrder + 1,
            'withdraw_req_document_date_create' => $now,
            'withdraw_req_document_date_modify' => $now,
            'staff_create' => $staffId,
            'staff_modify' => $staffId,
        ]);
    }

    private function ensureSalaryFinanceOfficerApprovalStep(int $salaryId, string $staffId = ''): void
    {
        if ($salaryId <= 0 || ! Schema::hasTable('withdraw_approval_step')) {
            return;
        }

        $exists = DB::table('withdraw_approval_step')
            ->where('withdraw_id', $salaryId)
            ->where('withdraw_approval_step_code', 'finance_officer')
            ->exists();
        if ($exists) {
            return;
        }

        $now = now();
        DB::table('withdraw_approval_step')->insert([
            'withdraw_id' => $salaryId,
            'withdraw_approval_step_code' => 'finance_officer',
            'withdraw_approval_step_name' => 'งานการเงิน',
            'withdraw_approval_step_order' => 35,
            'withdraw_approval_staff_id' => null,
            'withdraw_approval_custom_status' => null,
            'withdraw_approval_status' => 'pending',
            'withdraw_approval_comment' => null,
            'withdraw_approval_approved_at' => null,
            'withdraw_approval_notified_at' => null,
            'withdraw_approval_date_create' => $now,
            'withdraw_approval_date_modify' => $now,
            'staff_create' => $staffId,
            'staff_modify' => $staffId,
        ]);
    }

    private function submitTravelLeaveAfterFinance(
        int $id,
        object $row,
        Request $request,
        string $currentStaffId,
        string $currentStatus,
        string $approvalComment
    ): JsonResponse {
        if ($currentStaffId === '') {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบผู้ทำรายการ'], 403);
        }

        $documentMeta = $this->resolveWithdrawGeneralDocumentMeta($row);
        $this->assignWithdrawDocumentNumber(
            $id,
            'salary',
            (string) ($documentMeta['department_code'] ?? ''),
            (string) ($documentMeta['document_number'] ?? ''),
            (string) ($documentMeta['document_date'] ?? ''),
            (int) ($documentMeta['running_year'] ?? 0),
            (int) ($documentMeta['running_no'] ?? 0)
        );

        $this->syncApprovalSteps($id, $this->loadSalaryProcessActorsFromApprovalSteps($id), $currentStaffId);

        $this->withdrawQuery()->where('withdraw_id', $id)->update([
            'withdraw_status' => 'pending_approval',
            'withdraw_applicant_staff_id' => $currentStaffId,
            'withdraw_date_modify' => now(),
            'staff_modify' => $currentStaffId,
        ]);

        if (Schema::hasTable('withdraw_approval_step')) {
            DB::table('withdraw_approval_step')
                ->where('withdraw_id', $id)
                ->where('withdraw_approval_step_code', 'finance_officer')
                ->update([
                    'withdraw_approval_staff_id' => $currentStaffId,
                    'withdraw_approval_date_modify' => now(),
                    'staff_modify' => $currentStaffId,
                ]);
        }

        $this->markApprovalStepAsApproved($id, 'finance_officer', $currentStaffId, $approvalComment, $currentStaffId);
        $this->markApprovalStepAsApproved($id, 'finance_manager', '', '', $currentStaffId, 'pending');

        $notifyResult = $this->notifyNextPendingSalaryApprovalStep($id, $row, $request);
        $this->appendGeneralActionLog(
            $id,
            'travel_finance_submit',
            'งานการเงินส่งเบิกจ่ายในการเดินทางไปปฏิบัติงานตามลำดับ',
            $currentStatus,
            'pending_approval',
            $currentStaffId,
            $approvalComment
        );

        // เบิกจ่ายในการเดินทาง: ส่ง One Planning รอบแรกตอนดำเนินการเบิกจ่าย
        $onePlanningDebug = ['skipped' => true];
        try {
            $freshRow = $this->withdrawQuery()->where('withdraw_id', $id)->first();
            if ($freshRow) {
                $onePlanningDebug = $this->dispatchOnePlanningBudgetUsed(
                    $freshRow,
                    'อยู่ระหว่างดำเนินการ',
                    'proceed',
                    $currentStaffId
                );
            }
        } catch (\Throwable $e) {
            $onePlanningDebug = [
                'skipped' => false,
                'success_count' => 0,
                'failed_count' => 1,
                'error' => $e->getMessage(),
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'ส่งรายการต่อไปตามลำดับอนุมัติแล้ว',
            'data' => [
                'next_status' => 'pending_approval',
                'mail_debug' => $notifyResult['mail_debug'] ?? null,
                'notification_debug' => $notifyResult['notification_debug'] ?? null,
                'one_planning' => $onePlanningDebug,
            ],
        ]);
    }

    private function markApprovalStepAsApproved(
        int $generalId,
        string $stepCode,
        string $staffId = '',
        string $comment = '',
        string $modifiedBy = '',
        ?string $status = 'approved'
    ): void {
        if ($generalId <= 0 || trim($stepCode) === '' || ! Schema::hasTable('withdraw_approval_step')) {
            return;
        }

        $query = DB::table('withdraw_approval_step')
            ->where('withdraw_id', $generalId)
            ->where('withdraw_approval_step_code', trim($stepCode));

        if (trim($staffId) !== '') {
            $query->where('withdraw_approval_staff_id', trim($staffId));
        }

        $payload = [
            'withdraw_approval_comment' => trim($comment) !== '' ? trim($comment) : null,
            'withdraw_approval_date_modify' => now(),
            'staff_modify' => $modifiedBy,
        ];
        $normalizedStatus = $status !== null ? trim((string) $status) : '';
        if (in_array($normalizedStatus, ['approved', 'rejected'], true)) {
            $payload['withdraw_approval_approved_at'] = now();
        } elseif ($normalizedStatus === 'pending') {
            $payload['withdraw_approval_approved_at'] = null;
            if (trim($comment) === '') {
                $payload['withdraw_approval_comment'] = null;
            }
        }
        if ($status !== null && $normalizedStatus !== '') {
            $payload['withdraw_approval_status'] = $normalizedStatus;
        }

        $query->update($payload);
    }

    private function appendApprovalStepHistory(int $generalId, string $stepCode, array $eventPayload): void
    {
        $normalizedStepCode = trim($stepCode);
        if ($generalId <= 0 || $normalizedStepCode === '' || ! Schema::hasTable('withdraw_approval_step')) {
            return;
        }

        $stepRow = DB::table('withdraw_approval_step')
            ->where('withdraw_id', $generalId)
            ->where('withdraw_approval_step_code', $normalizedStepCode)
            ->orderByDesc('withdraw_approval_step_id')
            ->first([
                'withdraw_approval_step_id as id',
                'withdraw_approval_custom_status as custom_status',
            ]);
        if (! $stepRow || (int) ($stepRow->id ?? 0) <= 0) {
            return;
        }

        $currentRaw = trim((string) ($stepRow->custom_status ?? ''));
        $history = [];
        if ($currentRaw !== '' && str_starts_with($currentRaw, '[')) {
            $decoded = json_decode($currentRaw, true);
            if (is_array($decoded)) {
                $history = $decoded;
            }
        }

        $history[] = [
            'event' => (string) ($eventPayload['event'] ?? ''),
            'label' => (string) ($eventPayload['label'] ?? ''),
            'comment' => (string) ($eventPayload['comment'] ?? ''),
            'status' => (string) ($eventPayload['status'] ?? ''),
            'recorded_at' => (string) ($eventPayload['recorded_at'] ?? now()->toDateTimeString()),
            'recorded_by' => (string) ($eventPayload['recorded_by'] ?? ''),
        ];

        $history = array_values($history);
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }

        DB::table('withdraw_approval_step')
            ->where('withdraw_approval_step_id', (int) $stepRow->id)
            ->update([
                'withdraw_approval_custom_status' => json_encode($history, JSON_UNESCAPED_UNICODE),
                'withdraw_approval_date_modify' => now(),
                'staff_modify' => (string) ($eventPayload['recorded_by'] ?? ''),
            ]);
    }

    private function appendGeneralActionLog(
        int $generalId,
        string $actionCode,
        string $actionLabel,
        string $fromStatus = '',
        string $toStatus = '',
        string $actorStaffId = '',
        string $comment = '',
        array $meta = []
    ): void {
        if ($generalId <= 0 || trim($actionCode) === '' || trim($actionLabel) === '' || ! Schema::hasTable('withdraw_action_logs')) {
            return;
        }

        $actorId = trim($actorStaffId);
        $actorName = '';
        if ($actorId !== '') {
            $nameMap = $this->resolveStaffNamesByIds([$actorId]);
            $actorName = trim((string) ($nameMap[$actorId] ?? ''));
        }

        DB::table('withdraw_action_logs')->insert([
            'withdraw_id' => $generalId,
            'action_code' => trim($actionCode),
            'action_label' => trim($actionLabel),
            'from_status' => trim($fromStatus) !== '' ? trim($fromStatus) : null,
            'to_status' => trim($toStatus) !== '' ? trim($toStatus) : null,
            'actor_staff_id' => $actorId !== '' ? $actorId : null,
            'actor_name' => $actorName !== '' ? $actorName : null,
            'comment' => trim($comment) !== '' ? trim($comment) : null,
            'meta_json' => count($meta) > 0 ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'action_at' => now(),
        ]);
    }

    /**
     * @param array<int, string> $staffIds
     * @return array<string, string>
     */
    private function resolveStaffNamesByIds(array $staffIds): array
    {
        $result = [];
        if (count($staffIds) === 0) {
            return $result;
        }

        $normalizedIds = collect($staffIds)
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values();

        // 1) Resolve from local users table first
        if (Schema::hasTable('users')) {
            $userRows = DB::table('users')
                ->whereIn('user_id', $normalizedIds->all())
                ->orWhereIn('staff_code', $normalizedIds->all())
                ->orWhereIn('id', $normalizedIds->all())
                ->get(['id', 'user_id', 'staff_code', 'name', 'name_th', 'name_en']);

            foreach ($userRows as $row) {
                $resolvedName = trim((string) ($row->name_th ?? ''));
                if ($resolvedName === '') {
                    $resolvedName = trim((string) ($row->name ?? ''));
                }
                if ($resolvedName === '') {
                    $resolvedName = trim((string) ($row->name_en ?? ''));
                }
                if ($resolvedName === '') {
                    continue;
                }

                $userId = trim((string) ($row->user_id ?? ''));
                $staffCode = trim((string) ($row->staff_code ?? ''));
                $localUserId = trim((string) ($row->id ?? ''));
                if ($userId !== '') {
                    $result[$userId] = $resolvedName;
                }
                if ($staffCode !== '') {
                    $result[$staffCode] = $resolvedName;
                }
                if ($localUserId !== '') {
                    $result[$localUserId] = $resolvedName;
                }
            }
        }

        $tokenRefreshUrl = trim((string) config('services.staff_rx_api.token_refresh_url', ''));
        $checkStaffUrl = trim((string) config('services.staff_rx_api.check_staff_url', ''));
        $listStaffUrl = trim((string) config('services.staff_rx_api.list_staff_url', ''));
        if ($tokenRefreshUrl === '' || $checkStaffUrl === '') {
            return $result;
        }

        try {
            $refresh = Http::acceptJson()->timeout(10)->get($tokenRefreshUrl);
            if (! $refresh->successful()) {
                return $result;
            }

            $bearerHeaderToken = trim((string) $refresh->json('token', ''));
            $rawBearerToken = trim((string) $refresh->json('raw_token', ''));
            if ($bearerHeaderToken === '' && $rawBearerToken === '') {
                return $result;
            }

            $applyAuth = static function (\Illuminate\Http\Client\PendingRequest $http, string $bearerHeaderToken, string $rawBearerToken) {
                if ($rawBearerToken !== '') {
                    return $http->withToken($rawBearerToken);
                }
                if (str_starts_with($bearerHeaderToken, 'Bearer ')) {
                    return $http->withHeaders(['Authorization' => $bearerHeaderToken]);
                }

                return $http->withToken($bearerHeaderToken);
            };

            foreach ($normalizedIds as $staffId) {
                $id = trim((string) $staffId);
                if ($id === '') {
                    continue;
                }
                $http = $applyAuth(Http::acceptJson()->timeout(10), $bearerHeaderToken, $rawBearerToken);
                $row = null;
                $queries = [
                    ['staffID' => $id],
                    ['staff_id' => $id],
                    ['id' => $id],
                ];
                foreach ($queries as $query) {
                    $response = $http->get($checkStaffUrl, $query);
                    if (! $response->successful()) {
                        continue;
                    }
                    $payload = $response->json();
                    $candidate = is_array($payload['data'] ?? null) ? $payload['data'] : (is_array($payload) ? $payload : []);
                    if (is_array($candidate) && count($candidate) > 0) {
                        $row = $candidate;
                        break;
                    }
                }
                if (! is_array($row)) {
                    continue;
                }

                $name = \App\Support\StaffApiName::composeDisplayName($row);
                if ($name !== '') {
                    $result[$id] = $name;
                }
            }

            if ($listStaffUrl !== '') {
                $missingIds = $normalizedIds->filter(fn ($id) => ! isset($result[(string) $id]))->values();
                if ($missingIds->count() > 0) {
                    $http = $applyAuth(Http::acceptJson()->timeout(20), $bearerHeaderToken, $rawBearerToken);
                    $response = $http->get($listStaffUrl);
                    if ($response->successful()) {
                        $payload = $response->json();
                        $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];
                        if (is_array($rows)) {
                            foreach ($rows as $row) {
                                if (! is_array($row)) {
                                    continue;
                                }
                                $candidateIds = [
                                    trim((string) ($row['code'] ?? '')),
                                    trim((string) ($row['staff_id'] ?? '')),
                                    trim((string) ($row['id'] ?? '')),
                                ];
                                $name = \App\Support\StaffApiName::composeDisplayName($row);
                                if ($name === '') {
                                    continue;
                                }
                                foreach ($candidateIds as $candidateId) {
                                    if ($candidateId === '' || ! $missingIds->contains($candidateId)) {
                                        continue;
                                    }
                                    $result[$candidateId] = $name;
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            return $result;
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $staffIds
     * @return array<string, string>
     */
    private function resolveStaffPositionsByIds(array $staffIds): array
    {
        $result = [];
        $normalizedIds = collect($staffIds)
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values();
        if ($normalizedIds->count() === 0) {
            return $result;
        }

        [$bearerHeaderToken, $rawBearerToken] = $this->resolveStaffApiTokens();
        $listStaffUrl = trim((string) config('services.staff_rx_api.list_staff_url', ''));
        if ($listStaffUrl === '' || ($bearerHeaderToken === '' && $rawBearerToken === '')) {
            return $result;
        }

        try {
            $http = $this->applyStaffApiAuth(Http::acceptJson()->timeout(20), $bearerHeaderToken, $rawBearerToken);
            $response = $http->get($listStaffUrl);
            if (! $response->successful()) {
                return $result;
            }

            $payload = $response->json();
            $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $position = trim((string) ($row['name_position'] ?? $row['position'] ?? ''));
                if ($position === '') {
                    continue;
                }
                $candidateIds = array_filter([
                    trim((string) ($row['id'] ?? '')),
                    trim((string) ($row['staff_id'] ?? '')),
                    trim((string) ($row['code'] ?? '')),
                ], static fn ($id) => $id !== '');
                foreach ($candidateIds as $candidateId) {
                    if ($normalizedIds->contains($candidateId)) {
                        $result[$candidateId] = $position;
                    }
                }
            }
        } catch (\Throwable $e) {
            return $result;
        }

        return $result;
    }

    /**
     * ตำแหน่งการบริหารจาก StaffRX checkStaffRX (dean_name — ไม่มีใน listStaffRX)
     *
     * @param  array<int, string>  $staffIds
     * @return array<string, string>
     */
    private function resolveStaffAdminPositionsByIds(array $staffIds): array
    {
        $result = [];
        $normalizedIds = collect($staffIds)
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values();
        if ($normalizedIds->count() === 0) {
            return $result;
        }

        [$bearerHeaderToken, $rawBearerToken] = $this->resolveStaffApiTokens();
        $checkStaffUrl = trim((string) config('services.staff_rx_api.check_staff_url', ''));
        if ($checkStaffUrl === '' || ($bearerHeaderToken === '' && $rawBearerToken === '')) {
            return $result;
        }

        $rememberAdminPosition = static function (array $row) use (&$result, $normalizedIds): void {
            $adminPosition = trim((string) ($row['dean_name'] ?? ''));
            if ($adminPosition === '') {
                return;
            }

            $candidateIds = array_filter([
                trim((string) ($row['id'] ?? '')),
                trim((string) ($row['staff_id'] ?? '')),
                trim((string) ($row['code'] ?? '')),
            ], static fn ($id) => $id !== '');
            foreach ($candidateIds as $candidateId) {
                if ($normalizedIds->contains($candidateId)) {
                    $result[$candidateId] = $adminPosition;
                }
            }
        };

        try {
            $http = $this->applyStaffApiAuth(Http::acceptJson()->timeout(10), $bearerHeaderToken, $rawBearerToken);
            foreach ($normalizedIds as $staffId) {
                $id = trim((string) $staffId);
                if ($id === '') {
                    continue;
                }

                $row = null;
                foreach ([['staffID' => $id], ['staff_id' => $id], ['id' => $id]] as $query) {
                    $response = $http->get($checkStaffUrl, $query);
                    if (! $response->successful()) {
                        continue;
                    }

                    $candidate = $response->json('data');
                    if (! is_array($candidate)) {
                        $candidate = $response->json();
                    }
                    if (is_array($candidate) && (trim((string) ($candidate['fname'] ?? '')) !== '' || trim((string) ($candidate['dean_name'] ?? '')) !== '')) {
                        $row = $candidate;
                        break;
                    }
                }

                if (is_array($row)) {
                    $rememberAdminPosition($row);
                }
            }
        } catch (\Throwable $e) {
            return $result;
        }

        return $result;
    }

    /**
     * Additional reviewer signature blocks for memo PDF; mirrors detail-modal.blade.php
     * (approval_steps additional_reviewer vs process_actors fallback).
     *
     * @param  array<string, mixed>  $detail
     * @param  array<string, string>  $staffNameMap
     * @param  array<string, string>  $staffPositionMap
     * @return list<array{show_sig: bool, sig_uri: string, status_line: string, name: string, position: string, role: string}>
     */
    private function buildMemoAdditionalSignerCards(array $detail, array $staffNameMap, array $staffPositionMap): array
    {
        $approvalSteps = is_array($detail['approval_steps'] ?? null) ? $detail['approval_steps'] : [];
        $additionalSteps = [];
        foreach ($approvalSteps as $step) {
            if (! is_array($step)) {
                continue;
            }
            if (trim((string) ($step['step_code'] ?? '')) !== 'additional_reviewer') {
                continue;
            }
            $additionalSteps[] = $step;
        }

        $actors = is_array($detail['process_actors'] ?? null) ? $detail['process_actors'] : [];
        $actorByStaffId = [];
        foreach ($actors as $actor) {
            if (! is_array($actor)) {
                continue;
            }
            $sid = trim((string) ($actor['staff_id'] ?? ''));
            if ($sid !== '') {
                $actorByStaffId[$sid] = $actor;
            }
        }

        if (count($additionalSteps) > 0) {
            $cards = [];
            foreach ($additionalSteps as $step) {
                if (! is_array($step)) {
                    continue;
                }
                $staffId = trim((string) ($step['staff_id'] ?? ''));
                $actor = $actorByStaffId[$staffId] ?? [];
                $isApproved = strtolower(trim((string) ($step['status'] ?? ''))) === 'approved';
                $sigUri = trim((string) ($actor['signature_base64'] ?? $actor['signature_url'] ?? ''));
                $showSig = $isApproved && $sigUri !== '' && ! str_starts_with($sigUri, 'data:image/svg+xml');
                $name = trim((string) ($step['staff_name'] ?? ''));
                if ($name === '') {
                    $name = trim((string) ($staffNameMap[$staffId] ?? ''));
                }
                if ($name === '') {
                    $name = $staffId !== '' ? $staffId : '-';
                }
                $position = trim((string) ($staffPositionMap[$staffId] ?? ''));
                $position = $position !== '' ? $position : '-';
                $customStatus = trim((string) ($step['custom_status'] ?? ''));
                $role = $customStatus !== '' ? $customStatus : '-';
                $statusLine = $isApproved ? trim((string) ($actor['signature_status'] ?? '')) : '';
                $cards[] = [
                    'show_sig' => $showSig,
                    'sig_uri' => $sigUri,
                    'status_line' => $statusLine,
                    'name' => $name,
                    'position' => $position,
                    'role' => $role,
                ];
            }

            return $cards;
        }

        $cards = [];
        foreach ($actors as $actor) {
            if (! is_array($actor)) {
                continue;
            }
            $staffId = trim((string) ($actor['staff_id'] ?? ''));
            $sigUri = trim((string) ($actor['signature_base64'] ?? $actor['signature_url'] ?? ''));
            $showSig = $sigUri !== '' && ! str_starts_with($sigUri, 'data:image/svg+xml');
            $name = trim((string) ($actor['staff_name'] ?? ''));
            if ($name === '') {
                $name = trim((string) ($staffNameMap[$staffId] ?? ''));
            }
            if ($name === '') {
                $name = $staffId !== '' ? $staffId : '-';
            }
            $position = trim((string) ($staffPositionMap[$staffId] ?? ''));
            $position = $position !== '' ? $position : '-';
            $role = trim((string) ($actor['status'] ?? ''));
            $role = $role !== '' ? $role : '-';
            $statusLine = trim((string) ($actor['signature_status'] ?? ''));
            $cards[] = [
                'show_sig' => $showSig,
                'sig_uri' => $sigUri,
                'status_line' => $statusLine,
                'name' => $name,
                'position' => $position,
                'role' => $role,
            ];
        }

        return $cards;
    }

    private function resolveStaffEmailById(string $staffId): string
    {
        $id = trim((string) $staffId);
        if ($id === '') {
            return '';
        }

        [$bearerHeaderToken, $rawBearerToken] = $this->resolveStaffApiTokens();
        $checkStaffUrl = trim((string) config('services.staff_rx_api.check_staff_url', ''));
        if (($bearerHeaderToken === '' && $rawBearerToken === '') || $checkStaffUrl === '') {
            return '';
        }

        try {
            $http = $this->applyStaffApiAuth(Http::acceptJson()->timeout(10), $bearerHeaderToken, $rawBearerToken);
            $queries = [
                ['staffID' => $id],
                ['staff_id' => $id],
                ['id' => $id],
            ];
            foreach ($queries as $query) {
                $response = $http->get($checkStaffUrl, $query);
                if (! $response->successful()) {
                    continue;
                }
                $payload = $response->json();
                $row = is_array($payload['data'] ?? null)
                    ? $payload['data']
                    : (is_array($payload) ? $payload : []);
                if (! is_array($row) || count($row) === 0) {
                    continue;
                }

                $emailCandidates = [
                    trim((string) ($row['email'] ?? '')),
                    trim((string) ($row['mail'] ?? '')),
                    trim((string) ($row['email_address'] ?? '')),
                    trim((string) ($row['cmu_mail'] ?? '')),
                ];
                $email = (string) (collect($emailCandidates)->first(function ($value) {
                    return $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL);
                }) ?? '');
                if ($email !== '') {
                    return $email;
                }
            }
        } catch (\Throwable $e) {
            return '';
        }

        return '';
    }

    private function formatThaiDate(string $dateText): string
    {
        $date = $this->parseInputDate($dateText);
        if (! $date) {
            return '';
        }

        $thaiMonths = [
            1 => 'ม.ค.',
            2 => 'ก.พ.',
            3 => 'มี.ค.',
            4 => 'เม.ย.',
            5 => 'พ.ค.',
            6 => 'มิ.ย.',
            7 => 'ก.ค.',
            8 => 'ส.ค.',
            9 => 'ก.ย.',
            10 => 'ต.ค.',
            11 => 'พ.ย.',
            12 => 'ธ.ค.',
        ];

        $day = (int) $date->day;
        $month = $thaiMonths[(int) $date->month] ?? '';
        $year = ((int) $date->year + 543) % 100;
        $yy = str_pad((string) $year, 2, '0', STR_PAD_LEFT);

        return $day.' '.$month.' '.$yy;
    }

    /**
     * Matches JavaScript formatThaiDate in withdraw/general/partials/detail-modal.blade.php
     * (toLocaleDateString: th-TH, 2-digit day, long month name, Buddhist year).
     */
    private function formatThaiDateDetailModalStyle(string $dateText): string
    {
        $input = trim($dateText);
        if ($input === '') {
            return '';
        }

        $date = $this->parseInputDate($dateText);
        if (! $date) {
            return $input;
        }

        $longMonths = [
            1 => 'มกราคม',
            2 => 'กุมภาพันธ์',
            3 => 'มีนาคม',
            4 => 'เมษายน',
            5 => 'พฤษภาคม',
            6 => 'มิถุนายน',
            7 => 'กรกฎาคม',
            8 => 'สิงหาคม',
            9 => 'กันยายน',
            10 => 'ตุลาคม',
            11 => 'พฤศจิกายน',
            12 => 'ธันวาคม',
        ];

        $day = str_pad((string) $date->day, 2, '0', STR_PAD_LEFT);
        $month = $longMonths[(int) $date->month] ?? '';
        $beYear = (int) $date->year + 543;

        return $day.' '.$month.' '.$beYear;
    }

    private function toThaiBahtText(float $amount): string
    {
        if (! is_finite($amount) || $amount < 0) {
            return '-';
        }

        $digitText = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
        $unitText = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
        $convertInteger = function (string $numText) use ($digitText, $unitText): string {
            if ($numText === '' || preg_match('/^0+$/', $numText)) {
                return 'ศูนย์';
            }

            $output = '';
            $length = strlen($numText);
            for ($index = 0; $index < $length; $index += 1) {
                $digit = (int) $numText[$index];
                $position = $length - $index - 1;
                $unitIndex = $position % 6;
                if ($digit === 0) {
                    if ($unitIndex === 0 && $position >= 6) {
                        $output .= $unitText[6];
                    }
                    continue;
                }
                if ($unitIndex === 0 && $position >= 6) {
                    $output .= $unitText[6];
                }
                if ($unitIndex === 1) {
                    if ($digit === 1) {
                        $output .= $unitText[1];
                    } elseif ($digit === 2) {
                        $output .= 'ยี่'.$unitText[1];
                    } else {
                        $output .= $digitText[$digit].$unitText[1];
                    }
                    continue;
                }
                if ($unitIndex === 0 && $digit === 1 && $length > 1) {
                    $output .= 'เอ็ด';
                    continue;
                }
                $output .= $digitText[$digit].$unitText[$unitIndex];
            }
            return $output;
        };

        $fixed = number_format($amount, 2, '.', '');
        [$bahtPartRaw, $satangPartRaw] = explode('.', $fixed);
        $bahtText = $convertInteger($bahtPartRaw);
        if ((int) $satangPartRaw === 0) {
            return $bahtText.'บาทถ้วน';
        }

        return $bahtText.'บาท'.$convertInteger($satangPartRaw).'สตางค์';
    }

    private function resolveFiscalYear(string $documentDate): int
    {
        $date = $this->parseInputDate($documentDate) ?? now();

        $calendarYear = (int) $date->year;
        if ($calendarYear >= 2400) {
            $calendarYear -= 543;
        }

        $fiscalYearCe = (int) ($date->month >= 10 ? $calendarYear + 1 : $calendarYear);

        return $fiscalYearCe + 543;
    }

    private function parseInputDate(string $value): ?Carbon
    {
        $input = trim($value);
        if ($input === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y'];
        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $input);
                if ($parsed !== false) {
                    return $parsed;
                }
            } catch (\Throwable $e) {
                // Try next format
            }
        }

        try {
            return Carbon::parse($input);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param array<int, string> $staffIds
     * @return array<string, string>
     */
    private function resolveStaffSignaturesByIds(array $staffIds): array
    {
        $result = [];
        $normalizedIds = collect($staffIds)->map(fn ($id) => trim((string) $id))->filter()->unique()->values();
        if ($normalizedIds->count() === 0) return $result;

        [$bearerHeaderToken, $rawBearerToken] = $this->resolveStaffApiTokens();
        $rawUrlMap = $this->resolveStaffSignatureRawUrlsByIds($normalizedIds->all());

        foreach ($normalizedIds as $staffId) {
            $id = trim((string) $staffId);
            $signatureCacheKey = 'withdraw:signature:data-uri:'.$id;
            $cachedSignature = trim((string) Cache::get($signatureCacheKey, ''));
            if ($cachedSignature !== '' && ! str_starts_with($cachedSignature, 'data:image/svg+xml')) {
                if (str_starts_with($cachedSignature, 'data:image/')) {
                    $result[$id] = $cachedSignature;
                    continue;
                }
                Cache::forget($signatureCacheKey);
            }

            $rawUrl = trim((string) ($rawUrlMap[$id] ?? ''));
            if ($rawUrl === '') {
                $result[$id] = url('/withdraw/api/general/signature-proxy?staff_id='.rawurlencode($id));
                continue;
            }
            $resolvedDataUri = $this->resolveSignatureAsDataUri($rawUrl, $bearerHeaderToken, $rawBearerToken);
            if ($resolvedDataUri === '' || str_starts_with($resolvedDataUri, 'data:image/svg+xml')) {
                // One extra attempt for transient upstream/API failures.
                $resolvedDataUri = $this->resolveSignatureAsDataUri($rawUrl, $bearerHeaderToken, $rawBearerToken);
            }
            $resolvedSignature = ($resolvedDataUri !== '' && ! str_starts_with($resolvedDataUri, 'data:image/svg+xml'))
                ? $resolvedDataUri
                : ($rawUrl !== '' ? $rawUrl : url('/withdraw/api/general/signature-proxy?staff_id='.rawurlencode($id)));
            if ($resolvedSignature !== '' && ! str_starts_with($resolvedSignature, 'data:image/svg+xml')) {
                if (str_starts_with($resolvedSignature, 'data:image/')) {
                    Cache::put($signatureCacheKey, $resolvedSignature, now()->addMinutes(10));
                }
            }
            $result[$id] = $resolvedSignature;
        }

        return $result;
    }

    /**
     * @param array<int, string> $staffIds
     * @return array<string, string>
     */
    private function resolveStaffSignatureStatusesByIds(array $staffIds): array
    {
        $result = [];
        $normalizedIds = collect($staffIds)
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values();
        if ($normalizedIds->count() === 0) {
            return $result;
        }

        [$bearerHeaderToken, $rawBearerToken] = $this->resolveStaffApiTokens();
        if ($bearerHeaderToken === '' && $rawBearerToken === '') {
            foreach ($normalizedIds as $staffId) {
                $result[(string) $staffId] = 'ไม่สามารถรับโทเคนสำหรับตรวจสอบลายเซ็น';
            }
            return $result;
        }

        foreach ($normalizedIds as $staffId) {
            $id = trim((string) $staffId);
            if ($id === '') {
                continue;
            }

            try {
                $candidateIds = $this->resolveStaffIdentityCandidates($id, $bearerHeaderToken, $rawBearerToken);
                $signUrl = $this->fetchStaffSignatureRawUrlByCandidateIds($candidateIds, $bearerHeaderToken, $rawBearerToken);
                if ($signUrl === '') {
                    $result[$id] = 'ไม่พบ sign_url จาก API';
                    continue;
                }

                $http = $this->applyStaffApiAuth(Http::acceptJson()->timeout(20), $bearerHeaderToken, $rawBearerToken);
                $fileResponse = $http->get($signUrl);
                if (! $fileResponse->successful()) {
                    $result[$id] = 'ลายเซ็นจาก API ต้นทางไม่พบไฟล์ (HTTP '.$fileResponse->status().')';
                    continue;
                }

                $contentType = strtolower(trim((string) $fileResponse->header('Content-Type', '')));
                if (! str_starts_with($contentType, 'image/')) {
                    $result[$id] = 'ไฟล์ลายเซ็นไม่ใช่รูปภาพ';
                    continue;
                }

                $result[$id] = '';
            } catch (\Throwable $e) {
                $result[$id] = 'ตรวจสอบลายเซ็นไม่สำเร็จ';
            }
        }

        return $result;
    }

    /**
     * @param array<int, string> $staffIds
     * @return array<string, string>
     */
    private function resolveStaffSignatureRawUrlsByIds(array $staffIds): array
    {
        $result = [];
        $normalizedIds = collect($staffIds)
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values();
        if ($normalizedIds->count() === 0) {
            return $result;
        }

        [$bearerHeaderToken, $rawBearerToken] = $this->resolveStaffApiTokens();
        if ($bearerHeaderToken === '' && $rawBearerToken === '') {
            return $result;
        }

        foreach ($normalizedIds as $staffId) {
            $id = trim((string) $staffId);
            if ($id === '') {
                continue;
            }
            $rawUrlCacheKey = 'withdraw:signature:raw-url:'.$id;
            $cachedRawUrl = trim((string) Cache::get($rawUrlCacheKey, ''));
            if ($cachedRawUrl !== '') {
                $result[$id] = $cachedRawUrl;
                continue;
            }

            try {
                $candidateIds = $this->resolveStaffIdentityCandidates($id, $bearerHeaderToken, $rawBearerToken);
                $resolvedRawUrl = trim((string) $this->fetchStaffSignatureRawUrlByCandidateIds($candidateIds, $bearerHeaderToken, $rawBearerToken));
                if ($resolvedRawUrl !== '') {
                    Cache::put($rawUrlCacheKey, $resolvedRawUrl, now()->addMinutes(10));
                }
                $result[$id] = $resolvedRawUrl;
            } catch (\Throwable $e) {
                $result[$id] = '';
            }
        }

        return $result;
    }

    /**
     * @param array<mixed> $payload
     */
    private function extractSignatureUrlFromPayload(array $payload): string
    {
        $candidates = [
            data_get($payload, 'data.signature'),
            data_get($payload, 'data.0.signature'),
            data_get($payload, 'data.sign_url'),
            data_get($payload, 'data.0.sign_url'),
            data_get($payload, 'data.signature_url'),
            data_get($payload, 'data.0.signature_url'),
            data_get($payload, 'data.url'),
            data_get($payload, 'data.0.url'),
            data_get($payload, 'sign_url'),
            data_get($payload, 'signature'),
            data_get($payload, 'signature_url'),
            data_get($payload, 'url'),
        ];

        return (string) (collect($candidates)
            ->map(fn ($value) => $this->normalizeStaffSignatureUrl((string) $value))
            ->first(fn ($value) => $value !== '') ?? '');
    }

    /**
     * Stamp e-Sign attached PDFs with finance uploader, finance-manager, secretary, and last-approver signatures.
     */
    private function stampEsignAttachedPdfIfNeeded(
        object $row,
        string $absolutePath,
        string $typeCode,
        string $mime = '',
        string $fileName = '',
        string $uploaderStaffId = ''
    ): ?string {
        if (! EsignAttachedPdf::isEsignTypeCode($typeCode)) {
            return null;
        }

        $looksLikePdf = str_starts_with(strtolower($mime), 'application/pdf')
            || str_ends_with(strtolower($fileName), '.pdf')
            || str_ends_with(strtolower($absolutePath), '.pdf');
        if (! $looksLikePdf) {
            $header = (string) @file_get_contents($absolutePath, false, null, 0, 5);
            $looksLikePdf = $header === '%PDF-';
        }
        if (! $looksLikePdf) {
            return null;
        }

        $staffIds = EsignAttachedPdf::resolveSalarySignerStaffIds($row, $uploaderStaffId);
        $uniqueIds = collect($staffIds)->filter()->unique()->values()->all();
        $signatureMap = $this->resolveStaffSignaturesByIds($uniqueIds);
        [$bearerHeaderToken, $rawBearerToken] = $this->resolveStaffApiTokens();
        $uris = [];
        foreach ($staffIds as $role => $staffId) {
            $normalizedStaffId = trim((string) $staffId);
            if ($normalizedStaffId === '') {
                continue;
            }
            $fromMap = trim((string) ($signatureMap[$normalizedStaffId] ?? ''));
            $uri = '';
            if (str_starts_with($fromMap, 'data:image/') && ! str_starts_with($fromMap, 'data:image/svg')) {
                $uri = $fromMap;
            } elseif ($fromMap !== '') {
                $resolved = $this->resolveSignatureAsDataUri($fromMap, $bearerHeaderToken, $rawBearerToken);
                if (str_starts_with($resolved, 'data:image/') && ! str_starts_with($resolved, 'data:image/svg')) {
                    $uri = $resolved;
                }
            }
            if ($uri !== '') {
                $uris[$role] = $uri;
            }
        }

        $requesterCaption = [];
        if (isset($staffIds[EsignAttachedPdf::ROLE_REQUESTER])) {
            $requesterCaption = EsignAttachedPdf::fetchStaffCaption(
                trim((string) ($staffIds[EsignAttachedPdf::ROLE_REQUESTER] ?? '')),
                EsignAttachedPdf::resolveSalaryLastApproverStepCode($row)
            );
            if (trim((string) ($requesterCaption['name'] ?? '')) === '') {
                $requesterStaffId = trim((string) ($staffIds[EsignAttachedPdf::ROLE_REQUESTER] ?? ''));
                if ($requesterStaffId !== '') {
                    $names = $this->resolveStaffNamesByIds([$requesterStaffId]);
                    $requesterCaption['name'] = trim((string) ($names[$requesterStaffId] ?? ''));
                }
            }
            $requesterCaption['approved_date'] = EsignAttachedPdf::resolveSalaryLastApproverApprovedDate($row);
        }

        return EsignAttachedPdf::stamp($absolutePath, $uris, $requesterCaption);
    }

    private function resolveSignatureAsDataUri(string $signaturePathOrUrl, string $bearerHeaderToken = '', string $rawBearerToken = ''): string
    {
        $input = $this->normalizeStaffSignatureUrl($signaturePathOrUrl);
        if ($input === '') {
            return $this->defaultSignaturePlaceholderDataUri();
        }
        if (str_starts_with($input, 'data:image/')) {
            return $input;
        }

        $decoded = $input;
        $mimeType = 'image/png';
        $binary = null;
        $remoteCandidates = [];
        $fallbackDisplayUrl = '';

        try {
            if (filter_var($decoded, FILTER_VALIDATE_URL)) {
                $remoteCandidates[] = $decoded;
            } else {
                $apiBase = 'https://api.pharmacy.cmu.ac.th';
                $normalizedPath = ltrim($decoded, '/');
                if ($normalizedPath !== '') {
                    $remoteCandidates[] = $this->normalizeStaffSignatureUrl($apiBase.'/'.rawurlencode($normalizedPath));
                    $remoteCandidates[] = $this->normalizeStaffSignatureUrl($apiBase.'/'.str_replace('%2F', '/', rawurlencode($normalizedPath)));
                    $remoteCandidates[] = $this->normalizeStaffSignatureUrl($apiBase.'/'.str_replace(' ', '%20', $normalizedPath));
                }
            }
            $fallbackDisplayUrl = (string) ($remoteCandidates[0] ?? '');

            foreach ($remoteCandidates as $candidateUrl) {
                for ($attempt = 0; $attempt < 2; $attempt++) {
                    $httpCandidates = [];
                    $authHttp = Http::timeout(15);
                    if ($rawBearerToken !== '') {
                        $authHttp = $authHttp->withToken($rawBearerToken);
                    } elseif ($bearerHeaderToken !== '') {
                        if (str_starts_with($bearerHeaderToken, 'Bearer ')) {
                            $authHttp = $authHttp->withHeaders(['Authorization' => $bearerHeaderToken]);
                        } else {
                            $authHttp = $authHttp->withToken($bearerHeaderToken);
                        }
                    }
                    $httpCandidates[] = $authHttp;
                    // Some signature hosts are public and may reject auth headers.
                    $httpCandidates[] = Http::timeout(15);

                    foreach ($httpCandidates as $httpClient) {
                        $response = $httpClient->get($candidateUrl);
                        if (! $response->successful()) {
                            continue;
                        }

                        $contentType = strtolower(trim((string) $response->header('Content-Type', '')));
                        if (str_contains($contentType, 'application/json') || str_contains($contentType, 'text/plain')) {
                            $payload = $response->json();
                            if (is_array($payload)) {
                                $resolvedUrlFromPayload = $this->extractSignatureUrlFromPayload($payload);
                                if ($resolvedUrlFromPayload !== '') {
                                    $resolvedUrlFromPayload = $this->normalizeStaffSignatureUrl($resolvedUrlFromPayload);
                                    if ($resolvedUrlFromPayload !== '' && ! in_array($resolvedUrlFromPayload, $remoteCandidates, true)) {
                                        $remoteCandidates[] = $resolvedUrlFromPayload;
                                    }
                                }
                            }
                            continue;
                        }
                        // Accept only real image responses. HTML/login/error pages must be ignored.
                        if (! str_starts_with($contentType, 'image/')) {
                            continue;
                        }

                        $binary = $response->body();
                        $headerMime = trim((string) $response->header('Content-Type', ''));
                        if ($headerMime !== '') {
                            $normalizedHeaderMime = strtolower(explode(';', $headerMime)[0] ?: '');
                            if (str_starts_with($normalizedHeaderMime, 'image/')) {
                                $mimeType = $normalizedHeaderMime;
                            } else {
                                $pathExt = strtolower((string) pathinfo(parse_url($candidateUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                                $mimeType = $this->guessSignatureMimeByExtension($pathExt);
                            }
                        } else {
                            $pathExt = strtolower((string) pathinfo(parse_url($candidateUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                            $mimeType = $this->guessSignatureMimeByExtension($pathExt);
                        }
                        break;
                    }
                }
                if (is_string($binary) && $binary !== '') {
                    break;
                }
            }

            if ((! is_string($binary) || $binary === '') && file_exists($decoded)) {
                $binary = file_get_contents($decoded);
                $pathExt = strtolower((string) pathinfo($decoded, PATHINFO_EXTENSION));
                $mimeType = $this->guessSignatureMimeByExtension($pathExt);
            }
        } catch (\Throwable $e) {
            $binary = null;
        }

        if (! is_string($binary) || $binary === '') {
            return $this->defaultSignaturePlaceholderDataUri();
        }

        return 'data:'.$mimeType.';base64,'.base64_encode($binary);
    }

    private function guessSignatureMimeByExtension(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };
    }

    private function defaultSignaturePlaceholderDataUri(): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->defaultSignaturePlaceholderSvg());
    }

    /**
     * Prefer a temp file for mPDF <img src>; fall back to data URI if the cache dir is not writable.
     */
    private function fetchStaffSignatureRawUrlByCandidateIds(array $candidateIds, string $bearerHeaderToken, string $rawBearerToken): string
    {
        $normalizedCandidates = collect($candidateIds)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($normalizedCandidates->count() === 0) {
            return '';
        }

        foreach ($normalizedCandidates as $candidateId) {
            try {
                $http = $this->applyStaffApiAuth(Http::acceptJson()->timeout(15), $bearerHeaderToken, $rawBearerToken);
                $signatureResponse = $http->get('https://api.pharmacy.cmu.ac.th/api/staffSignature', [
                    'staffID' => (string) $candidateId,
                ]);
                if ($signatureResponse->successful()) {
                    $payload = $signatureResponse->json();
                    $resolvedUrl = $this->extractSignatureUrlFromPayload(is_array($payload) ? $payload : []);
                    if ($resolvedUrl !== '') {
                        return $this->normalizeStaffSignatureUrl($resolvedUrl);
                    }
                }

                // Fallback for environments using different query keys.
                $queryVariants = [
                    ['staff_id' => (string) $candidateId],
                    ['id' => (string) $candidateId],
                    ['code' => (string) $candidateId],
                ];
                foreach ($queryVariants as $query) {
                    $fallbackResponse = $http->get('https://api.pharmacy.cmu.ac.th/api/staffSignature', $query);
                    if (! $fallbackResponse->successful()) {
                        continue;
                    }
                    $payload = $fallbackResponse->json();
                    $resolvedUrl = $this->extractSignatureUrlFromPayload(is_array($payload) ? $payload : []);
                    if ($resolvedUrl !== '') {
                        return $this->normalizeStaffSignatureUrl($resolvedUrl);
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    private function resolveStaffIdentityCandidates(string $staffId, string $bearerHeaderToken, string $rawBearerToken): array
    {
        $id = trim((string) $staffId);
        if ($id === '') {
            return [];
        }

        $candidates = [$id];
        $checkStaffUrl = trim((string) config('services.staff_rx_api.check_staff_url', ''));
        if ($checkStaffUrl === '' || ($bearerHeaderToken === '' && $rawBearerToken === '')) {
            return array_values(array_unique(array_filter($candidates)));
        }

        try {
            $http = $this->applyStaffApiAuth(Http::acceptJson()->timeout(10), $bearerHeaderToken, $rawBearerToken);
            $queries = [
                ['staffID' => $id],
                ['staff_id' => $id],
                ['id' => $id],
            ];

            $row = null;
            foreach ($queries as $query) {
                $response = $http->get($checkStaffUrl, $query);
                if (! $response->successful()) {
                    continue;
                }
                $payload = $response->json();
                $candidate = is_array($payload['data'] ?? null)
                    ? $payload['data']
                    : (is_array($payload) ? $payload : []);
                if (is_array($candidate) && count($candidate) > 0) {
                    $row = $candidate;
                    break;
                }
            }

            if (is_array($row)) {
                $candidates[] = trim((string) ($row['code'] ?? ''));
                $candidates[] = trim((string) ($row['staff_id'] ?? ''));
                $candidates[] = trim((string) ($row['id'] ?? ''));
                $candidates[] = trim((string) ($row['user_id'] ?? ''));
            } else {
                $listStaffUrl = trim((string) config('services.staff_rx_api.list_staff_url', ''));
                if ($listStaffUrl !== '') {
                    $listResponse = $http->timeout(20)->get($listStaffUrl);
                    if ($listResponse->successful()) {
                        $payload = $listResponse->json();
                        $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];
                        foreach ($rows as $listRow) {
                            if (! is_array($listRow)) {
                                continue;
                            }
                            $candidateIds = [
                                trim((string) ($listRow['id'] ?? '')),
                                trim((string) ($listRow['code'] ?? '')),
                                trim((string) ($listRow['staff_id'] ?? '')),
                                trim((string) ($listRow['user_id'] ?? '')),
                            ];
                            if (! in_array($id, $candidateIds, true)) {
                                continue;
                            }
                            $candidates = array_merge($candidates, $candidateIds);
                            break;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore and return base candidate only.
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function applyStaffApiAuth(\Illuminate\Http\Client\PendingRequest $http, string $bearerHeaderToken, string $rawBearerToken): \Illuminate\Http\Client\PendingRequest
    {
        if ($rawBearerToken !== '') {
            return $http->withToken($rawBearerToken);
        }
        if (str_starts_with($bearerHeaderToken, 'Bearer ')) {
            return $http->withHeaders(['Authorization' => $bearerHeaderToken]);
        }

        return $http->withToken($bearerHeaderToken);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveStaffApiTokens(?string $tokenRefreshUrl = null): array
    {
        $tokenRefreshUrl = trim((string) ($tokenRefreshUrl ?? config('services.staff_rx_api.token_refresh_url', env('RX_TOKEN_REFRESH', ''))));
        if ($tokenRefreshUrl === '') {
            return ['', ''];
        }

        try {
            $refresh = Http::acceptJson()->timeout(10)->get($tokenRefreshUrl);
            if (! $refresh->successful()) {
                return ['', ''];
            }

            $bearerHeaderToken = trim((string) $refresh->json('token', ''));
            $rawBearerToken = trim((string) $refresh->json('raw_token', ''));

            return [$bearerHeaderToken, $rawBearerToken];
        } catch (\Throwable $e) {
            return ['', ''];
        }
    }

    private function normalizeStaffSignatureUrl(string $url): string
    {
        $normalized = trim($url);
        if ($normalized === '') {
            return '';
        }

        // Mirror MIS behavior for staff signature proxy URLs.
        $normalized = preg_replace('#/signatureProxy\.php\?#i', '/api/signatureProxy?', $normalized) ?? $normalized;
        $normalized = preg_replace('#^http://#i', 'https://', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function defaultSignaturePlaceholderSvg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="30" viewBox="0 0 100 30"><rect width="100" height="30" fill="#f8f9fa"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-size="10" fill="#adb5bd">Signature</text></svg>';
    }

    private function mapWithdrawStatusLabel(string $rawStatus): string
    {
        $normalized = trim($rawStatus);
        $statusMap = $this->loadWithdrawStatusLabelMap();

        if ($normalized !== '' && array_key_exists($normalized, $statusMap)) {
            return (string) $statusMap[$normalized];
        }

        return 'ร่าง';
    }

    /**
     * @return array<string, string>
     */
    private function loadWithdrawStatusLabelMap(): array
    {
        static $cachedMap = null;
        if (is_array($cachedMap)) {
            return $cachedMap;
        }

        $fallbackMap = [
            'saved' => 'ร่าง',
            'cancelled' => 'ยกเลิกรายการ',
            'pending_primary_review' => 'รอตรวจสอบ/อนุมัติ',
            'processing' => 'รอตรวจสอบ/อนุมัติ',
            'pending_additional_review' => 'รอตรวจสอบ/อนุมัติ',
            'pending_finance_review' => 'รอการเงินตรวจสอบเอกสาร',
            'pending_revision' => 'รอแก้ไข',
            'pending_approval' => 'รออนุมัติ',
            'approved' => 'อนุมัติ',
        ];

        if (! Schema::hasTable('withdraw_status')) {
            $cachedMap = $fallbackMap;
            return $cachedMap;
        }

        try {
            $rows = DB::table('withdraw_status')
                ->where('withdraw_status_active', 1)
                ->orderBy('withdraw_status_order')
                ->get([
                    'withdraw_status_code as code',
                    'withdraw_status_name as name',
                ]);

            $map = [];
            foreach ($rows as $row) {
                $code = trim((string) ($row->code ?? ''));
                $name = trim((string) ($row->name ?? ''));
                if ($code === '' || $name === '') {
                    continue;
                }
                $map[$code] = $name;
            }

            $cachedMap = array_merge($fallbackMap, $map);
            return $cachedMap;
        } catch (\Throwable $e) {
            $cachedMap = $fallbackMap;
            return $cachedMap;
        }
    }

    /**
     * Mirrors JS reference loading in withdraw/general/partials/detail-modal.blade.php.
     *
     * @return array{name: array<string, string>, meta: array<string, array<string, string>>}
     */
    private function loadDepartmentApiReferenceMaps(): array
    {
        $nameMap = [];
        $metaMap = [];
        $url = trim((string) config('services.department_api.url', ''));
        if ($url === '') {
            return ['name' => $nameMap, 'meta' => $metaMap];
        }

        try {
            $request = Http::acceptJson()->timeout(15);
            $token = trim((string) config('services.department_api.token', ''));
            if ($token !== '') {
                if (str_starts_with($token, 'Bearer ')) {
                    $request = $request->withHeaders(['Authorization' => $token]);
                } else {
                    $request = $request->withToken($token);
                }
            }

            $response = $request->get($url);
            if (! $response->successful()) {
                return ['name' => $nameMap, 'meta' => $metaMap];
            }

            $payload = $response->json();
            $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];

            foreach ($rows as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $depCode = trim((string) ($item['dep_code'] ?? $item['department_code'] ?? $item['code'] ?? ''));
                $depName = trim((string) ($item['dep_name'] ?? $item['department_name'] ?? $item['name'] ?? ''));
                $jobCode = trim((string) ($item['job_code'] ?? ''));
                $jobName = trim((string) ($item['job_name'] ?? ''));
                $unitCode = trim((string) ($item['unit_code'] ?? ''));
                $agencyName = trim((string) ($item['agency_name'] ?? $item['unit_name'] ?? ''));
                $depPhone = trim((string) ($item['dep_phone'] ?? ''));
                $jobPhone = trim((string) ($item['job_phone'] ?? ''));
                $unitPhone = trim((string) ($item['phone'] ?? ''));

                if ($depCode === '' || $depName === '') {
                    continue;
                }

                $nameMap[$depCode] = $depName;
                $metaMap[$depCode] = [
                    'departmentName' => $depName,
                    'jobName' => '',
                    'unitName' => '',
                    'depPhone' => $depPhone,
                    'jobPhone' => '',
                    'unitPhone' => '',
                    'label' => $depName,
                ];

                if ($jobCode !== '' && $jobName !== '') {
                    $label = $depName.' > '.$jobName;
                    $nameMap[$jobCode] = $label;
                    $metaMap[$jobCode] = [
                        'departmentName' => $depName,
                        'jobName' => $jobName,
                        'unitName' => '',
                        'depPhone' => $depPhone,
                        'jobPhone' => $jobPhone,
                        'unitPhone' => '',
                        'label' => $label,
                    ];
                }

                if ($agencyName !== '') {
                    $agencyValue = $unitCode !== '' ? $unitCode : ($jobCode !== '' ? $jobCode : $depCode);
                    $labelParts = array_filter([$depName, $jobName, $agencyName], static fn ($part) => $part !== '');
                    $label = implode(' > ', $labelParts);
                    $nameMap[$agencyValue] = $label;
                    $metaMap[$agencyValue] = [
                        'departmentName' => $depName,
                        'jobName' => $jobName,
                        'unitName' => $agencyName,
                        'depPhone' => $depPhone,
                        'jobPhone' => $jobPhone,
                        'unitPhone' => $unitPhone,
                        'label' => $label,
                    ];
                }
            }
        } catch (\Throwable $e) {
            return ['name' => $nameMap, 'meta' => $metaMap];
        }

        return ['name' => $nameMap, 'meta' => $metaMap];
    }

    /**
     * Same display rules as detail-modal department resolution (API + fallbacks).
     *
     * @param  array<string, string>  $nameMap
     * @param  array<string, array<string, string>>  $metaMap
     * @param  array<string, string>  $rowFieldOverrides  Optional department_name, unit_name, job_name when available
     */
    private function resolveWithdrawGeneralDepartmentDisplay(
        string $departmentCode,
        array $nameMap,
        array $metaMap,
        array $rowFieldOverrides = [],
        bool $jobOnly = false
    ): string {
        $departmentCode = trim($departmentCode);
        $mappedMeta = isset($metaMap[$departmentCode]) && is_array($metaMap[$departmentCode])
            ? $metaMap[$departmentCode]
            : [];
        $mappedLabel = trim((string) ($nameMap[$departmentCode] ?? ''));
        $mappedParts = array_values(array_filter(array_map('trim', explode('>', $mappedLabel))));

        $departmentName = trim((string) ($rowFieldOverrides['department_name'] ?? $mappedMeta['departmentName'] ?? ($mappedParts[0] ?? '')));
        $unitName = trim((string) ($rowFieldOverrides['unit_name'] ?? $mappedMeta['unitName'] ?? ($mappedParts[2] ?? $mappedParts[0] ?? '')));
        $jobName = trim((string) ($rowFieldOverrides['job_name'] ?? $mappedMeta['jobName'] ?? ($mappedParts[1] ?? '')));
        $jobOrDepartmentName = $jobName !== '' ? $jobName : $departmentName;

        if ($jobOnly) {
            if ($jobOrDepartmentName !== '') {
                return $jobOrDepartmentName;
            }

            return $mappedLabel !== '' ? $mappedLabel : ($departmentCode !== '' ? $departmentCode : '-');
        }

        if ($unitName !== '' && $jobOrDepartmentName !== '') {
            return $unitName.'  '.$jobOrDepartmentName;
        }

        $fallback = $unitName !== '' ? $unitName : ($jobOrDepartmentName !== '' ? $jobOrDepartmentName : ($mappedLabel !== '' ? $mappedLabel : $departmentCode));

        return $fallback !== '' ? $fallback : '-';
    }

    /**
     * @param  array<string, array<string, string>>  $metaMap
     */
    private function resolveWithdrawGeneralDepartmentPhone(string $departmentCode, array $metaMap): string
    {
        $departmentCode = trim($departmentCode);
        $mappedMeta = isset($metaMap[$departmentCode]) && is_array($metaMap[$departmentCode])
            ? $metaMap[$departmentCode]
            : [];

        foreach (['unitPhone', 'jobPhone', 'depPhone'] as $phoneKey) {
            $phone = trim((string) ($mappedMeta[$phoneKey] ?? ''));
            if ($phone !== '') {
                return $phone;
            }
        }

        return '';
    }
}
