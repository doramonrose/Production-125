<?php

namespace App\Http\Controllers\Withdraw;

use App\Mail\WithdrawProceedNotificationMail;
use App\Http\Controllers\Controller;
use App\Support\DocumentNumberClient;
use App\Support\EsignAttachedPdf;
use App\Support\ProjectWithdrawExternalAccess;
use App\Support\ProjectWithdrawHandoff;
use App\Support\WithdrawApprovalLink;
use App\Support\WithdrawMemoTemplate;
use App\Support\WithdrawOnePlanningSender;
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

class WithdrawGeneralController extends Controller
{
    /** @var array<string, array{department_code:string,document_number:string,document_date:string,running_year:int,running_no:int}> */
    private array $withdrawGeneralDocumentMetaCache = [];

    public function index(Request $request)
    {
        $scope = trim((string) ($request->query('scope', $request->route('scope', ''))));
        $approvalScopes = ['reviewer-pending', 'document-review'];
        $isReviewerApprovalMode = in_array($scope, $approvalScopes, true);
        $resolvedScope = $isReviewerApprovalMode ? $scope : '';
        $currentStaffId = trim((string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? ''));
        $withdrawKind = $this->currentWithdrawType();
        $isProjectKind = $withdrawKind === WithdrawSchema::TYPE_PROJECT;
        $withdrawRows = $this->fetchUnifiedApprovalRows($resolvedScope, $currentStaffId);

        $targetView = 'withdraw.general.index';
        if ($scope === 'document-review') {
            $targetView = 'withdraw.approval.finance';
        } elseif ($isReviewerApprovalMode) {
            $targetView = 'withdraw.approval.index';
        }

        $viewData = [
            'title' => $scope === 'document-review'
                ? 'ตรวจสอบเอกสารเบิกจ่าย'
                : ($isReviewerApprovalMode
                    ? 'อนุมัติเบิกจ่าย'
                    : ($isProjectKind ? 'รายการเบิกจ่ายเงินโครงการ' : 'รายการเบิกจ่ายทั่วไป')),
            'withdrawRows' => $withdrawRows,
            'isReviewerApprovalMode' => $isReviewerApprovalMode,
            'isDocumentReviewMode' => $scope === 'document-review',
            'withdrawKind' => $withdrawKind,
            'allowManualCreate' => ! $isProjectKind,
            'projectHandoffToken' => $isProjectKind ? trim((string) $request->query('handoff', '')) : '',
            'projectHandoffApiUrl' => url('/withdraw/api/project/handoff'),
            'generalListApiUrl' => $isReviewerApprovalMode
                ? url('/withdraw/api/general/list?scope='.rawurlencode($resolvedScope))
                : ($isProjectKind
                    ? url('/withdraw/api/general/list?withdraw_type=project')
                    : url('/withdraw/api/general/list')),
        ];

        if ($targetView === 'withdraw.general.index') {
            $memoKind = $isProjectKind ? 'project' : 'general';
            $viewData['approvalStepOptions'] = WithdrawMemoTemplate::approvalStepOptions();
            $viewData['memoTemplate'] = WithdrawMemoTemplate::resolve($memoKind, 'default') ?? [
                'subject' => $isProjectKind ? 'ขออนุมัติเบิกค่าใช้จ่ายโครงการ' : 'ขออนุมัติเบิกจ่าย........',
                'to' => 'คณบดีคณะเภสัชศาสตร์',
                'paragraph1' => $isProjectKind
                    ? 'ตามที่.......... ได้รับอนุมัติให้จัดโครงการ .......... รหัสโครงการ .......... โดยเบิกจ่ายจาก .......... จำนวน .......... บาท นั้น'
                    : 'ตามที่...............................................................................................',
                'paragraph2' => $isProjectKind
                    ? 'ในการนี้...........ได้ดำเนินการ.......... เรียบร้อยแล้ว จึงมีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย จำนวนเงินทั้งสิ้น ....... บาท  โดยมีรายละเอียดดังนี้.-'
                    : 'ในการนี้.........................................................................................................จึงมีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย ดังนี้.-',
                'paragraph3' => $isProjectKind ? 'จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ' : 'จึงเรียนมาเพื่อโปรดพิจารณา',
                'paragraph4' => '',
                'paragraph_enabled' => WithdrawMemoTemplate::defaultParagraphEnabled(),
                'block_order' => ['paragraph1', 'paragraph2', 'items', 'paragraph3', 'paragraph4'],
                'approval_step_codes' => WithdrawMemoTemplate::DEFAULT_GENERAL_APPROVAL_STEP_CODES,
                'process_actors_enabled' => false,
            ];
            if ($isProjectKind && is_array($viewData['memoTemplate'])) {
                if (trim((string) ($viewData['memoTemplate']['subject'] ?? '')) === '') {
                    $viewData['memoTemplate']['subject'] = 'ขออนุมัติเบิกค่าใช้จ่ายโครงการ';
                }
                if (! array_key_exists('paragraph4', $viewData['memoTemplate'])) {
                    $viewData['memoTemplate']['paragraph4'] = '';
                }
            }
        }

        return view($targetView, $viewData);
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
                    // Use approved withdraw docs as the primary source for "อนุมัติแล้ว" in finance summary.
                    // Keep compatibility with existing budget system values by taking the larger value.
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

    public function apiProjectAmount(Request $request): JsonResponse
    {
        $projectId = trim((string) $request->query('project_id', $request->input('project_id', '')));
        $projectCode = trim((string) $request->query('project_code', $request->input('project_code', '')));
        $excludeId = (int) $request->query('exclude_id', $request->input('exclude_id', 0));
        $amount = ProjectWithdrawHandoff::resolveProjectAmount($projectId, $projectCode);
        $approvedAlready = $this->resolveProjectApprovedAlready($projectId, $projectCode, $excludeId);

        return response()->json([
            'status' => 'success',
            'data' => [
                'project_id' => $projectId,
                'project_code' => $projectCode,
                'amount' => $amount,
                'approved_already' => $approvedAlready,
            ],
        ]);
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
        $reviewerStaffId = trim((string) $request->input('reviewer_staff_id', ''));
        $fiscalYear = $this->resolveFiscalYear($documentDate);
        $items = json_decode((string) $request->input('items', '[]'), true);
        $documents = json_decode((string) $request->input('documents', '[]'), true);
        $processActors = json_decode((string) $request->input('process_actors', '[]'), true);
        $requestedType = trim((string) $request->input('withdraw_type', $this->currentWithdrawType()));
        $withdrawType = in_array($requestedType, [WithdrawSchema::TYPE_GENERAL, WithdrawSchema::TYPE_PROJECT], true)
            ? $requestedType
            : WithdrawSchema::TYPE_GENERAL;
        $projectId = trim((string) $request->input('project_id', ''));
        $projectCode = trim((string) $request->input('project_code', ''));
        $projectName = trim((string) $request->input('project_name', ''));
        $projectActivityId = trim((string) $request->input('project_activity_id', ''));
        $projectActivityName = trim((string) $request->input('project_activity_name', ''));
        $projectActivityDetail = trim((string) $request->input('project_activity_detail', ''));
        $projectHandoffToken = trim((string) $request->input('project_handoff_token', ''));
        $projectAmount = ProjectWithdrawHandoff::toAmount($request->input('project_amount', 0));
        $headerBudgetManageId = (int) $request->input('budget_manage_id', 0);
        if (! is_array($items)) {
            $items = [];
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

        $totalAmount = collect(is_array($items) ? $items : [])->sum(function ($row) {
            return (float) ($row['amount'] ?? 0);
        });

        $payload = [
            'withdraw_type' => $withdrawType,
            'withdraw_sub_type' => 'default',
            'withdraw_subject' => $subject,
            'withdraw_fiscal_year' => $fiscalYear,
            'withdraw_to' => $to,
            'withdraw_paragraph_1' => $paragraph1,
            'withdraw_paragraph_2' => $paragraph2,
            'withdraw_paragraph_3' => $paragraph3,
            'withdraw_applicant_staff_id' => $applicantStaffId,
            'withdraw_reviewer_staff_id' => $reviewerStaffId,
            'withdraw_total_amount' => $totalAmount,
            'withdraw_date_modify' => now(),
            'staff_modify' => (string) ($request->session()->get('staff_id') ?? ''),
        ];
        if (Schema::hasColumn('withdraw', 'withdraw_paragraph_4')) {
            $payload['withdraw_paragraph_4'] = $paragraph4 !== '' ? $paragraph4 : null;
        }
        if (Schema::hasColumn('withdraw', 'withdraw_budget_manage_id') && $headerBudgetManageId > 0) {
            $payload['withdraw_budget_manage_id'] = $headerBudgetManageId;
        }
        if (Schema::hasColumn('withdraw', 'withdraw_project_id')) {
            $payload['withdraw_project_id'] = $projectId !== '' ? $projectId : null;
        }
        if (Schema::hasColumn('withdraw', 'withdraw_project_code')) {
            $payload['withdraw_project_code'] = $projectCode !== '' ? $projectCode : null;
        }
        if (Schema::hasColumn('withdraw', 'withdraw_project_name')) {
            $payload['withdraw_project_name'] = $projectName !== '' ? $projectName : null;
        }
        if (Schema::hasColumn('withdraw', 'withdraw_project_activity_id')) {
            $payload['withdraw_project_activity_id'] = $projectActivityId !== '' ? $projectActivityId : null;
        }
        if (Schema::hasColumn('withdraw', 'withdraw_project_activity_name')) {
            $payload['withdraw_project_activity_name'] = $projectActivityName !== '' ? $projectActivityName : null;
        }
        if (Schema::hasColumn('withdraw', 'withdraw_project_activity_detail')) {
            $payload['withdraw_project_activity_detail'] = $projectActivityDetail !== '' ? $projectActivityDetail : null;
        }
        if (Schema::hasColumn('withdraw', 'withdraw_project_amount')) {
            $payload['withdraw_project_amount'] = $projectAmount > 0 ? $projectAmount : null;
        }
        if (Schema::hasColumn('withdraw', 'withdraw_project_handoff_token')) {
            $payload['withdraw_project_handoff_token'] = $projectHandoffToken !== '' ? $projectHandoffToken : null;
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
            $withdrawType,
            $departmentCode,
            $documentNumber,
            $documentDate
        );

        $sessionStaffId = (string) ($request->session()->get('staff_id') ?? '');
        $this->syncGeneralItems($recordId, is_array($items) ? $items : [], $sessionStaffId);
        $activeDocumentKeys = $this->syncGeneralDocuments($recordId, is_array($documents) ? $documents : [], $sessionStaffId);
        $this->cleanupGeneralFilesByDocumentKeys($recordId, $activeDocumentKeys);
        $this->deleteGeneralFilesByIds($recordId, $request->input('remove_file_ids', []));

        $editableStatuses = ['saved', 'draft'];
        if (in_array(strtolower($currentRecordStatus), $editableStatuses, true)) {
            $this->syncApprovalSteps(
                $recordId,
                $reviewerStaffId,
                is_array($processActors) ? $processActors : [],
                (string) ($request->session()->get('staff_id') ?? '')
            );
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
                        $path = $file->store("withdraw/general/{$recordId}", 'public');
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

        $revisionExplanation = trim((string) $request->input('revision_explanation', ''));
        $isRevisionUpdate = $recordId > 0
            && in_array(strtolower($currentRecordStatus), ['pending_revision'], true)
            && $isRevisionUpdateInput;
        if ($isRevisionUpdate && $withdrawType === WithdrawSchema::TYPE_PROJECT && $revisionExplanation === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณากรอกคำชี้แจงการแก้ไข',
            ], 422);
        }
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
            $revisionTargetStatus = 'pending_finance_review';
            $revisionNotifyActionUrl = WithdrawApprovalLink::nextApprover($recordId, 'general', 'finance_officer');
            $revisionNotifyMessage = 'ผู้ขอเบิกได้แก้ไขรายการเบิกจ่ายและส่งกลับให้ท่านตรวจสอบแล้ว - '.$subject.' ('.($documentNumber !== '' ? $documentNumber : (string) $recordId).')';
            if ($revisionExplanation !== '') {
                $revisionNotifyMessage .= ' คำชี้แจงการแก้ไข: '.$revisionExplanation;
            }
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
                    // ผู้ตรวจสอบต้องกลับเข้า flow ตรวจสอบ ไม่ใช่ pending_approval (จะข้ามงานการเงิน)
                    if ($rejectedStepCode === 'reviewer_head') {
                        $revisionTargetStatus = 'pending_primary_review';
                    } elseif ($rejectedStepCode === 'additional_reviewer') {
                        $revisionTargetStatus = 'pending_additional_review';
                    } else {
                        $revisionTargetStatus = 'pending_approval';
                    }
                    $revisionNotifyActionUrl = WithdrawApprovalLink::nextApprover(
                        $recordId,
                        'general',
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
                    if ($revisionExplanation !== '') {
                        $revisionNotifyMessage .= ' คำชี้แจงการแก้ไข: '.$revisionExplanation;
                    }

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
                        'header_title' => 'แจ้งเตือนรายการเบิกจ่ายทั่วไป',
                        'header_description' => 'ผู้ขอเบิกได้แก้ไขรายการและส่งกลับให้ตรวจสอบ',
                        'status_badge' => 'รอการเงินตรวจสอบเอกสาร',
                        'intro_message' => 'มีรายการที่แก้ไขแล้ว รอการตรวจสอบจากท่าน',
                        'button_label' => 'เปิดรายการเพื่อตรวจสอบ',
                        'extra_note' => trim(implode(' | ', array_filter([
                            $revisionExplanation !== '' ? 'คำชี้แจงการแก้ไข: '.$revisionExplanation : '',
                            $revisionComment !== '' ? 'หมายเหตุการส่งกลับก่อนหน้า: '.$revisionComment : '',
                        ]))),
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
            $isRevisionUpdate ? ($revisionTargetStatus ?? 'pending_finance_review') : 'saved',
            $sessionStaffId,
            $isRevisionUpdate ? $revisionExplanation : ''
        );

        if ($projectHandoffToken !== '') {
            ProjectWithdrawHandoff::forget($projectHandoffToken);
        }

        return response()->json([
            'status' => 'success',
            'message' => $isRevisionUpdate
                ? (($revisionTargetStatus ?? 'pending_finance_review') === 'pending_approval'
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
        $staffIdsForSignatures = collect($processActors)
            ->pluck('staff_id')
            ->push((string) ($row->withdraw_applicant_staff_id ?? ''))
            ->push((string) ($row->withdraw_reviewer_staff_id ?? ''))
            ->map(fn ($staffId) => trim((string) $staffId))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $signatureMap = $this->resolveStaffSignaturesByIds($staffIdsForSignatures);
        $signatureRawUrlMap = $this->resolveStaffSignatureRawUrlsByIds($staffIdsForSignatures);
        $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
        $reviewerStaffId = trim((string) ($row->withdraw_reviewer_staff_id ?? ''));
        $staffNameMap = $this->resolveStaffNamesByIds(array_values(array_filter([$applicantStaffId, $reviewerStaffId])));
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
        $financeDocumentMeta = $this->resolveWithdrawDocumentMetaByType($generalId, 'general_finance');
        $financeDocumentRaw = trim((string) ($financeDocumentMeta['document_number'] ?? ''));
        $financeRunningNo = (int) ($financeDocumentMeta['running_no'] ?? 0);
        $financeDocumentBase = $this->resolveFinanceDocumentNumberBaseForApi($financeDocumentRaw, $financeRunningNo);
        $financeDocumentDisplay = $financeDocumentBase;
        if ($financeDocumentBase !== '' && $financeRunningNo > 0) {
            $financeDocumentDisplay = $financeDocumentBase.$financeRunningNo;
        }

        $approvalSteps = $this->loadGeneralApprovalSteps($generalId);
        $financeOfficerStep = collect($approvalSteps)->first(function ($step) {
            return is_array($step) && trim((string) ($step['step_code'] ?? '')) === 'finance_officer';
        });
        $financeOfficerStaffId = is_array($financeOfficerStep)
            ? trim((string) ($financeOfficerStep['staff_id'] ?? ''))
            : '';
        $staffPositionMap = $this->resolveStaffPositionsByIds(array_values(array_filter([
            $applicantStaffId,
            $reviewerStaffId,
            $financeOfficerStaffId,
        ])));

        $partitionedDocuments = EsignAttachedPdf::partitionFinanceEsignDocuments(
            $this->appendProjectRelatedDocuments(
                $this->loadGeneralDocuments($generalId),
                $row
            )
        );
        $partitionedFiles = EsignAttachedPdf::partitionFinanceEsignUploadedFiles(
            $this->appendProjectRelatedUploadedFiles(
                $this->loadGeneralUploadedFiles($generalId),
                $row
            )
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
                'withdraw_type' => trim((string) ($row->withdraw_type ?? 'general')),
                'project_id' => Schema::hasColumn('withdraw', 'withdraw_project_id')
                    ? trim((string) ($row->withdraw_project_id ?? ''))
                    : '',
                'project_code' => Schema::hasColumn('withdraw', 'withdraw_project_code')
                    ? trim((string) ($row->withdraw_project_code ?? ''))
                    : '',
                'project_name' => $this->resolveWithdrawProjectName($row),
                'project_activity_id' => Schema::hasColumn('withdraw', 'withdraw_project_activity_id')
                    ? trim((string) ($row->withdraw_project_activity_id ?? ''))
                    : '',
                'project_activity_name' => Schema::hasColumn('withdraw', 'withdraw_project_activity_name')
                    ? trim((string) ($row->withdraw_project_activity_name ?? ''))
                    : '',
                'project_activity_detail' => $this->resolveWithdrawProjectActivityDetail($row),
                'project_amount' => (static function () use ($row): float {
                    $stored = Schema::hasColumn('withdraw', 'withdraw_project_amount')
                        ? (float) ($row->withdraw_project_amount ?? 0)
                        : 0.0;
                    if ($stored > 0) {
                        return $stored;
                    }

                    return ProjectWithdrawHandoff::resolveProjectAmount(
                        Schema::hasColumn('withdraw', 'withdraw_project_id')
                            ? trim((string) ($row->withdraw_project_id ?? ''))
                            : '',
                        Schema::hasColumn('withdraw', 'withdraw_project_code')
                            ? trim((string) ($row->withdraw_project_code ?? ''))
                            : ''
                    );
                })(),
                'approved_already' => trim((string) ($row->withdraw_type ?? '')) === WithdrawSchema::TYPE_PROJECT
                    ? $this->resolveProjectApprovedAlready(
                        Schema::hasColumn('withdraw', 'withdraw_project_id')
                            ? trim((string) ($row->withdraw_project_id ?? ''))
                            : '',
                        Schema::hasColumn('withdraw', 'withdraw_project_code')
                            ? trim((string) ($row->withdraw_project_code ?? ''))
                            : '',
                        (int) $generalId
                    )
                    : 0.0,
                'budget_name' => $this->resolveWithdrawBudgetSourceName($row),
                'revision_comment' => $revisionComment,
                'department_code' => $departmentCode,
                'department_display' => $departmentDisplay,
                'document_number' => (string) ($documentMeta['document_number'] ?? ''),
                'running_no' => (int) ($documentMeta['running_no'] ?? 0),
                'document_number_display' => $this->resolveWithdrawGeneralDisplayDocumentCode($row),
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
                'paragraph3' => $this->resolveWithdrawParagraph3($row),
                'paragraph4' => $this->resolveWithdrawParagraph4($row),
                'paragraph_enabled' => WithdrawMemoTemplate::paragraphEnabledForWithdraw($row),
                'applicant_staff_id' => $applicantStaffId,
                'applicant_name' => (string) ($staffNameMap[$applicantStaffId] ?? ''),
                'applicant_position' => trim((string) ($staffPositionMap[$applicantStaffId] ?? '')),
                'applicant_signature_base64' => (string) ($signatureMap[$applicantStaffId] ?? ''),
                'applicant_signature_proxy_url' => $buildSignatureProxyUrl($applicantStaffId),
                'applicant_signature_url' => (string) ($signatureMap[$applicantStaffId] ?? ''),
                'applicant_signature_status' => '',
                'applicant_signature_raw_url' => (string) ($signatureRawUrlMap[$applicantStaffId] ?? ''),
                'reviewer_staff_id' => $reviewerStaffId,
                'reviewer_name' => (string) ($staffNameMap[$reviewerStaffId] ?? ''),
                'reviewer_signature_base64' => (string) ($signatureMap[$reviewerStaffId] ?? ''),
                'reviewer_signature_proxy_url' => $buildSignatureProxyUrl($reviewerStaffId),
                'reviewer_signature_url' => (string) ($signatureMap[$reviewerStaffId] ?? ''),
                'reviewer_signature_status' => '',
                'reviewer_signature_raw_url' => (string) ($signatureRawUrlMap[$reviewerStaffId] ?? ''),
                'items' => $this->loadGeneralItems($generalId),
                'documents' => $partitionedDocuments['documents'],
                'uploaded_files' => $partitionedFiles['uploaded_files'],
                'finance_esign_files' => $partitionedFiles['finance_esign_files'],
                'project_approve_memo_pdf' => $this->buildProjectApproveMemoPdfMeta($row),
                'project_print_pdf' => $this->buildProjectPrintPdfMeta($row),
                'approval_steps' => $approvalSteps,
                'finance_proposer_staff_id' => $financeOfficerStaffId,
                'finance_proposer_name' => is_array($financeOfficerStep)
                    ? trim((string) ($financeOfficerStep['staff_name'] ?? ''))
                    : '',
                'finance_proposer_position' => trim((string) ($staffPositionMap[$financeOfficerStaffId] ?? '')),
                'finance_proposer_approved' => is_array($financeOfficerStep)
                    && strtolower(trim((string) ($financeOfficerStep['status'] ?? ''))) === 'approved',
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

    public function projectApproveMemoPdf(Request $request)
    {
        return $this->proxyProjectPdf(
            $request,
            (string) config('services.project_app.approve_memo_pdf_path', '/User/api_approve_memo_pdf.php'),
            'project-approve-memo'
        );
    }

    public function projectPrintPdf(Request $request)
    {
        return $this->proxyProjectPdf(
            $request,
            (string) config('services.project_app.print_pdf_path', '/User/api_project_print_pdf.php'),
            'project-detail'
        );
    }

    private function proxyProjectPdf(Request $request, string $pdfPath, string $filenamePrefix)
    {
        $withdrawId = (int) $request->query('withdraw_id', 0);
        $projectCode = trim((string) (
            $request->query('project_code', '')
            ?: $request->query('code_pre_project', '')
        ));

        if ($withdrawId > 0 && Schema::hasTable('withdraw')) {
            $withdrawRow = $this->withdrawQuery()->where('withdraw_id', $withdrawId)->first();
            if ($withdrawRow) {
                if ($projectCode === '') {
                    $projectCode = trim((string) ($withdrawRow->withdraw_project_code ?? ''));
                    if ($projectCode === '') {
                        $rawProjectRef = trim((string) ($withdrawRow->withdraw_project_id ?? ''));
                        if ($rawProjectRef !== '' && ! ctype_digit($rawProjectRef)) {
                            $projectCode = $rawProjectRef;
                        }
                    }
                }
            }
        }

        if ($projectCode === '') {
            abort(404, 'ไม่พบรหัสโครงการ');
        }

        $baseUrl = rtrim((string) config('services.project_app.base_url', 'http://172.17.1.145:10011/project'), '/');
        $pdfPath = trim($pdfPath);
        if ($baseUrl === '' || $pdfPath === '') {
            abort(500, 'ยังไม่ได้ตั้งค่า PROJECT_APP_BASE_URL');
        }

        $upstreamPdfUrl = $baseUrl.$pdfPath;

        try {
            $client = new \GuzzleHttp\Client([
                'http_errors' => false,
                'allow_redirects' => true,
                'timeout' => 60,
                'connect_timeout' => 10,
            ]);

            $upstream = $client->request('GET', $upstreamPdfUrl, [
                'query' => [
                    'project_code' => $projectCode,
                ],
            ]);
            $status = (int) $upstream->getStatusCode();
            $body = (string) $upstream->getBody();
            $contentType = trim((string) ($upstream->getHeaderLine('Content-Type') ?: ''));

            if ($status >= 200 && $status < 300 && (
                str_contains(strtolower($contentType), 'pdf')
                || str_starts_with($body, '%PDF')
            )) {
                $safePrefix = preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $filenamePrefix) ?: 'project-pdf';

                return response($body, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.$safePrefix.'-'.$projectCode.'.pdf"',
                    'Cache-Control' => 'private, max-age=60',
                ]);
            }

            $message = trim(strip_tags($body));
            if ($message === '') {
                $message = 'ไม่สามารถดึงไฟล์ PDF จากระบบโครงการได้';
            }

            return response($message, $status >= 400 ? $status : 404, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        } catch (\Throwable $e) {
            report($e);
            abort(502, 'ไม่สามารถเชื่อมต่อระบบโครงการเพื่อดึงไฟล์ PDF ได้');
        }
    }

    /**
     * @return array{project_code:string,project_id:int}|null
     */
    private function resolveProjectPdfIdentity(object $row): ?array
    {
        if (trim((string) ($row->withdraw_type ?? '')) !== WithdrawSchema::TYPE_PROJECT) {
            return null;
        }

        $projectCode = trim((string) ($row->withdraw_project_code ?? ''));
        $rawProjectRef = trim((string) ($row->withdraw_project_id ?? ''));
        if ($projectCode === '' && $rawProjectRef !== '' && ! ctype_digit($rawProjectRef)) {
            $projectCode = $rawProjectRef;
        }
        $projectId = ($rawProjectRef !== '' && ctype_digit($rawProjectRef)) ? (int) $rawProjectRef : 0;
        if ($projectCode === '') {
            return null;
        }

        return [
            'project_code' => $projectCode,
            'project_id' => $projectId,
        ];
    }

    /**
     * @return array{url:string,name:string,mime:string,project_id:int,project_code:string}|null
     */
    private function buildProjectApproveMemoPdfMeta(object $row): ?array
    {
        $identity = $this->resolveProjectPdfIdentity($row);
        if ($identity === null) {
            return null;
        }

        $projectCode = $identity['project_code'];
        $query = array_filter([
            'withdraw_id' => (int) ($row->withdraw_id ?? 0) > 0 ? (int) $row->withdraw_id : null,
            'project_code' => $projectCode,
        ], static fn ($value) => $value !== null && $value !== '');

        return [
            'url' => url('/withdraw/api/project/approve-memo-pdf').'?'.http_build_query($query),
            'name' => 'บันทึกอนุมัติโครงการ'.($projectCode !== '' ? ' '.$projectCode : '').'.pdf',
            'mime' => 'application/pdf',
            'project_id' => (int) $identity['project_id'],
            'project_code' => $projectCode,
        ];
    }

    /**
     * @return array{url:string,name:string,mime:string,project_id:int,project_code:string}|null
     */
    private function buildProjectPrintPdfMeta(object $row): ?array
    {
        $identity = $this->resolveProjectPdfIdentity($row);
        if ($identity === null) {
            return null;
        }

        $projectCode = $identity['project_code'];
        $query = array_filter([
            'withdraw_id' => (int) ($row->withdraw_id ?? 0) > 0 ? (int) $row->withdraw_id : null,
            'project_code' => $projectCode,
        ], static fn ($value) => $value !== null && $value !== '');

        return [
            'url' => url('/withdraw/api/project/print-pdf').'?'.http_build_query($query),
            'name' => 'รายละเอียดโครงการ'.($projectCode !== '' ? ' '.$projectCode : '').'.pdf',
            'mime' => 'application/pdf',
            'project_id' => (int) $identity['project_id'],
            'project_code' => $projectCode,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, array<string, mixed>>
     */
    private function appendProjectRelatedDocuments(array $documents, object $row): array
    {
        $documents = $this->appendProjectPrintPdfDocument($documents, $row);

        return $this->appendProjectApproveMemoDocument($documents, $row);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $files
     * @return array<int, array<string, mixed>>
     */
    private function appendProjectRelatedUploadedFiles($files, object $row): array
    {
        $list = $this->appendProjectPrintPdfUploadedFile($files, $row);

        return $this->appendProjectApproveMemoUploadedFile($list, $row);
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, array<string, mixed>>
     */
    private function appendProjectApproveMemoDocument(array $documents, object $row): array
    {
        $meta = $this->buildProjectApproveMemoPdfMeta($row);
        if ($meta === null) {
            return $documents;
        }

        array_unshift($documents, [
            'type' => 'project_approve_memo',
            'item_id' => 'pdf',
            'label' => 'บันทึกอนุมัติโครงการ',
        ]);

        return $documents;
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, array<string, mixed>>
     */
    private function appendProjectPrintPdfDocument(array $documents, object $row): array
    {
        $meta = $this->buildProjectPrintPdfMeta($row);
        if ($meta === null) {
            return $documents;
        }

        array_unshift($documents, [
            'type' => 'project_print',
            'item_id' => 'pdf',
            'label' => 'รายละเอียดโครงการ',
        ]);

        return $documents;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $files
     * @return array<int, array<string, mixed>>
     */
    private function appendProjectApproveMemoUploadedFile($files, object $row): array
    {
        $list = collect($files)->values()->all();
        $meta = $this->buildProjectApproveMemoPdfMeta($row);
        if ($meta === null) {
            return $list;
        }

        array_unshift($list, [
            'file_id' => 0,
            'type' => 'project_approve_memo',
            'item' => 'pdf',
            'name' => (string) ($meta['name'] ?? 'บันทึกอนุมัติโครงการ.pdf'),
            'mime' => 'application/pdf',
            'url' => (string) ($meta['url'] ?? ''),
        ]);

        return $list;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $files
     * @return array<int, array<string, mixed>>
     */
    private function appendProjectPrintPdfUploadedFile($files, object $row): array
    {
        $list = collect($files)->values()->all();
        $meta = $this->buildProjectPrintPdfMeta($row);
        if ($meta === null) {
            return $list;
        }

        array_unshift($list, [
            'file_id' => 0,
            'type' => 'project_print',
            'item' => 'pdf',
            'name' => (string) ($meta['name'] ?? 'รายละเอียดโครงการ.pdf'),
            'mime' => 'application/pdf',
            'url' => (string) ($meta['url'] ?? ''),
        ]);

        return $list;
    }

    private function resolveProjectNumericIdFromWithdrawRow(object $row): int
    {
        $rawId = trim((string) ($row->withdraw_project_id ?? ''));
        if ($rawId !== '' && ctype_digit($rawId)) {
            return (int) $rawId;
        }

        $code = trim((string) ($row->withdraw_project_code ?? ''));
        if ($code === '') {
            $code = $rawId;
        }

        return $this->resolveProjectNumericIdByCode($code);
    }

    private function resolveProjectNumericIdByCode(string $projectCode): int
    {
        $code = trim($projectCode);
        if ($code === '') {
            return 0;
        }

        try {
            return (int) (DB::table('project.pre_project')
                ->where('code_pre_project', $code)
                ->value('id') ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @return array{code:string,email:string}
     */
    private function resolveStaffCodeAndEmailById(string $staffId): array
    {
        $id = trim($staffId);
        if ($id === '') {
            return ['code' => '', 'email' => ''];
        }

        [$bearerHeaderToken, $rawBearerToken] = $this->resolveStaffApiTokens();
        $checkStaffUrl = trim((string) config('services.staff_rx_api.check_staff_url', ''));
        if (($bearerHeaderToken === '' && $rawBearerToken === '') || $checkStaffUrl === '') {
            return ['code' => '', 'email' => $this->resolveStaffEmailById($id)];
        }

        try {
            $http = $this->applyStaffApiAuth(Http::acceptJson()->timeout(10), $bearerHeaderToken, $rawBearerToken);
            foreach ([['staffID' => $id], ['staff_id' => $id], ['id' => $id]] as $query) {
                $response = $http->get($checkStaffUrl, $query);
                if (! $response->successful()) {
                    continue;
                }
                $payload = $response->json();
                $row = is_array($payload['data'] ?? null)
                    ? $payload['data']
                    : (is_array($payload) ? $payload : []);
                if (! is_array($row) || $row === []) {
                    continue;
                }

                $code = trim((string) ($row['code'] ?? $row['staff_code'] ?? $row['PharCode'] ?? ''));
                $emailCandidates = [
                    trim((string) ($row['email'] ?? '')),
                    trim((string) ($row['mail'] ?? '')),
                    trim((string) ($row['email_address'] ?? '')),
                    trim((string) ($row['cmu_mail'] ?? '')),
                ];
                $email = (string) (collect($emailCandidates)->first(static function ($value) {
                    return $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL);
                }) ?? '');

                if ($code !== '' || $email !== '') {
                    return ['code' => $code, 'email' => $email];
                }
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return ['code' => '', 'email' => $this->resolveStaffEmailById($id)];
    }

    public function memoPdf(string $id)
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

        $isProjectWithdraw = trim((string) ($row->withdraw_type ?? '')) === WithdrawSchema::TYPE_PROJECT;
        if ($isProjectWithdraw && request()->routeIs('withdraw.general.memo-pdf')) {
            return redirect()->route('withdraw.project.memo-pdf', ['id' => (int) $id]);
        }
        if (! $isProjectWithdraw && request()->routeIs('withdraw.project.memo-pdf')) {
            return redirect()->route('withdraw.general.memo-pdf', ['id' => (int) $id]);
        }

        $generalId = (int) ($row->withdraw_id ?? 0);
        $processActors = $this->loadGeneralAdditionalReviewers($generalId);
        $approvalStepsForMemo = $this->loadGeneralApprovalSteps($generalId);
        $approvalStepStaffIds = collect($approvalStepsForMemo)
            ->pluck('staff_id')
            ->map(fn ($staffId) => trim((string) $staffId))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $staffIdsForSignatures = collect($processActors)
            ->pluck('staff_id')
            ->push((string) ($row->withdraw_applicant_staff_id ?? ''))
            ->push((string) ($row->withdraw_reviewer_staff_id ?? ''))
            ->merge($approvalStepStaffIds)
            ->map(fn ($staffId) => trim((string) $staffId))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $signatureMap = $this->resolveStaffSignaturesByIds($staffIdsForSignatures);
        $signatureRawUrlMap = $this->resolveStaffSignatureRawUrlsByIds($staffIdsForSignatures);
        $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
        $reviewerStaffId = trim((string) ($row->withdraw_reviewer_staff_id ?? ''));
        $actorStaffIds = collect($processActors)
            ->pluck('staff_id')
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->values()
            ->all();
        $allStaffIdsForMemoRoles = array_values(array_unique(array_filter(array_merge(
            [$applicantStaffId, $reviewerStaffId],
            $actorStaffIds,
            $approvalStepStaffIds
        ))));
        $staffNameMap = $this->resolveStaffNamesByIds($allStaffIdsForMemoRoles);
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

        $staffPositionMap = $this->resolveStaffPositionsByIds($allStaffIdsForMemoRoles);
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
            'paragraph3' => $this->resolveWithdrawParagraph3($row),
            'paragraph4' => $this->resolveWithdrawParagraph4($row),
            'applicant_staff_id' => $applicantStaffId,
            'applicant_name' => (string) ($staffNameMap[$applicantStaffId] ?? ''),
            'applicant_position' => trim((string) ($staffPositionMap[$applicantStaffId] ?? '')),
            'applicant_signature_base64' => (string) ($signatureMap[$applicantStaffId] ?? ''),
            'applicant_signature_proxy_url' => $buildSignatureProxyUrl($applicantStaffId),
            'applicant_signature_url' => (string) ($signatureMap[$applicantStaffId] ?? ''),
            'applicant_signature_status' => '',
            'applicant_signature_raw_url' => (string) ($signatureRawUrlMap[$applicantStaffId] ?? ''),
            'reviewer_staff_id' => $reviewerStaffId,
            'reviewer_name' => (string) ($staffNameMap[$reviewerStaffId] ?? ''),
            'reviewer_position' => trim((string) ($staffPositionMap[$reviewerStaffId] ?? '')),
            'reviewer_signature_base64' => (string) ($signatureMap[$reviewerStaffId] ?? ''),
            'reviewer_signature_proxy_url' => $buildSignatureProxyUrl($reviewerStaffId),
            'reviewer_signature_url' => (string) ($signatureMap[$reviewerStaffId] ?? ''),
            'reviewer_signature_status' => '',
            'reviewer_signature_raw_url' => (string) ($signatureRawUrlMap[$reviewerStaffId] ?? ''),
            'items' => $this->loadGeneralItems($generalId),
            'documents' => $this->loadGeneralDocuments($generalId),
            'uploaded_files' => $this->loadGeneralUploadedFiles($generalId),
            'approval_steps' => $approvalStepsForMemo,
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
            []
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
        $memoKind = trim((string) ($row->withdraw_type ?? 'general')) === WithdrawSchema::TYPE_PROJECT
            ? WithdrawSchema::TYPE_PROJECT
            : 'general';
        $memoTemplate = WithdrawMemoTemplate::resolve($memoKind, 'default');
        $memoBlockOrder = is_array($memoTemplate['block_order'] ?? null) && $memoTemplate['block_order'] !== []
            ? $memoTemplate['block_order']
            : ['paragraph1', 'paragraph2', 'items', 'paragraph3', 'paragraph4'];
        $memoBlockOrder = WithdrawMemoTemplate::filterEnabledBlocks(
            $memoBlockOrder,
            WithdrawMemoTemplate::normalizeParagraphEnabled($memoTemplate['paragraph_enabled'] ?? null)
        );
        $projectBudgetSummary = null;
        if ($isProjectWithdraw) {
            $projectId = Schema::hasColumn('withdraw', 'withdraw_project_id')
                ? trim((string) ($row->withdraw_project_id ?? ''))
                : '';
            $projectCode = Schema::hasColumn('withdraw', 'withdraw_project_code')
                ? trim((string) ($row->withdraw_project_code ?? ''))
                : '';
            $projectAmount = Schema::hasColumn('withdraw', 'withdraw_project_amount')
                ? (float) ($row->withdraw_project_amount ?? 0)
                : 0.0;
            if ($projectAmount <= 0) {
                $projectAmount = ProjectWithdrawHandoff::resolveProjectAmount($projectId, $projectCode);
            }
            $approvedAlready = $this->resolveProjectApprovedAlready($projectId, $projectCode, $generalId);
            $approvedThisTime = (float) ($detail['total_amount'] ?? 0);
            if ($approvedThisTime <= 0) {
                $approvedThisTime = $itemsTotalAmount;
            }
            $totalApproved = $approvedAlready + $approvedThisTime;
            $projectBudgetSummary = [
                'amount' => $projectAmount,
                'approved_already' => $approvedAlready,
                'approved_this_time' => $approvedThisTime,
                'total_approved' => $totalApproved,
                'remaining' => $projectAmount - $totalApproved,
            ];
        }

        $html = view('withdraw.general.pdf.memo', [
            'detail' => $detail,
            'memoBlockOrder' => $memoBlockOrder,
            'isProjectMemo' => $memoKind === WithdrawSchema::TYPE_PROJECT,
            'projectBudgetSummary' => $projectBudgetSummary,
            'thaiDate' => $this->formatThaiDateDetailModalStyle((string) ($detail['document_date'] ?? '')),
            'totalText' => $this->toThaiBahtText((float) ($detail['total_amount'] ?? 0)),
            'itemsTotalAmount' => $itemsTotalAmount,
            'itemsTotalText' => $this->toThaiBahtText($itemsTotalAmount),
            'additionalSignerCards' => $this->buildMemoAdditionalSignerCards(
                $detail,
                $staffNameMap,
                $staffPositionMap,
                $signatureMap
            ),
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

    public function memoFinancePdf(string $id)
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

        $generalId = (int) ($row->withdraw_id ?? 0);
        $processActors = $this->loadGeneralAdditionalReviewers($generalId);
        $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
        $reviewerStaffId = trim((string) ($row->withdraw_reviewer_staff_id ?? ''));
        $actorStaffIds = collect($processActors)
            ->pluck('staff_id')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();
        $allStaffIds = array_values(array_unique(array_filter(array_merge([$applicantStaffId, $reviewerStaffId], $actorStaffIds))));
        $staffNameMap = $this->resolveStaffNamesByIds($allStaffIds);
        $documentMeta = $this->resolveWithdrawGeneralDocumentMeta($row);

        $detail = [
            'id' => $generalId,
            'raw_status' => (string) ($row->withdraw_status ?? 'saved'),
            'status' => $this->mapWithdrawStatusLabel((string) ($row->withdraw_status ?? 'saved')),
            'department_code' => (string) ($documentMeta['department_code'] ?? ''),
            'document_number' => (string) ($documentMeta['document_number'] ?? ''),
            'document_date' => (string) ($documentMeta['document_date'] ?? ''),
            'subject' => (string) ($row->withdraw_subject ?? ''),
            'total_amount' => (float) ($row->withdraw_total_amount ?? 0),
            'to' => (string) ($row->withdraw_to ?? ''),
            'paragraph1' => (string) ($row->withdraw_paragraph_1 ?? ''),
            'paragraph2' => (string) ($row->withdraw_paragraph_2 ?? ''),
            'paragraph3' => $this->resolveWithdrawParagraph3($row),
            'paragraph4' => $this->resolveWithdrawParagraph4($row),
            'applicant_staff_id' => $applicantStaffId,
            'applicant_name' => (string) ($staffNameMap[$applicantStaffId] ?? ''),
            'reviewer_staff_id' => $reviewerStaffId,
            'reviewer_name' => (string) ($staffNameMap[$reviewerStaffId] ?? ''),
            'items' => $this->loadGeneralItems($generalId),
            'documents' => $this->loadGeneralDocuments($generalId),
            'approval_steps' => $this->loadGeneralApprovalSteps($generalId),
            'approval_step_history' => $this->loadApprovalStepHistory($generalId),
        ];

        $departmentRef = $this->loadDepartmentApiReferenceMaps();
        $requestDepartmentDisplay = $this->resolveWithdrawGeneralDepartmentDisplay(
            (string) ($detail['department_code'] ?? ''),
            $departmentRef['name'],
            $departmentRef['meta'],
            []
        );
        $financeDepartmentDisplay = $this->resolveWithdrawGeneralDepartmentDisplay(
            '12010401',
            $departmentRef['name'],
            $departmentRef['meta'],
            []
        );
        $detail['department_display'] = $requestDepartmentDisplay;
        $isProjectWithdraw = trim((string) ($row->withdraw_type ?? '')) === WithdrawSchema::TYPE_PROJECT;
        $financeDocumentMeta = $this->resolveWithdrawDocumentMetaByType($generalId, 'general_finance');
        $financeDocumentBase = $this->resolveFinanceDocumentNumberBaseForApi(
            trim((string) ($financeDocumentMeta['document_number'] ?? '')),
            (int) ($financeDocumentMeta['running_no'] ?? 0)
        );
        $financeRunningNo = (int) ($financeDocumentMeta['running_no'] ?? 0);
        $financeDocumentDisplay = $financeDocumentBase;
        if ($financeDocumentBase !== '' && $financeRunningNo > 0) {
            $financeDocumentDisplay = $financeDocumentBase.$financeRunningNo;
        }
        $detail['document_number'] = $financeDocumentDisplay;
        $detail['document_number_display'] = $financeDocumentDisplay;
        $financeDocumentDate = trim((string) ($row->withdraw_finance_document_date ?? ''));
        if ($financeDocumentDate === '') {
            $financeDocumentDate = trim((string) ($financeDocumentMeta['document_date'] ?? ''));
        }
        $financeSubject = trim((string) ($row->withdraw_finance_subject ?? ''));
        if ($financeSubject === '' && $isProjectWithdraw) {
            $projectName = trim((string) $this->resolveWithdrawProjectName($row));
            $financeSubject = 'ขออนุมัติค่าใช้จ่ายโครงการ'.($projectName !== '' ? ' '.$projectName : '');
        }

        $approvalSteps = is_array($detail['approval_steps'] ?? null) ? $detail['approval_steps'] : [];
        $financeOfficerStep = collect($approvalSteps)->first(function ($step) {
            return is_array($step) && trim((string) ($step['step_code'] ?? '')) === 'finance_officer';
        });
        $financeManagerStep = collect($approvalSteps)->first(function ($step) {
            return is_array($step) && trim((string) ($step['step_code'] ?? '')) === 'finance_manager';
        });
        $facultySecretaryStep = collect($approvalSteps)->first(function ($step) {
            return is_array($step) && trim((string) ($step['step_code'] ?? '')) === 'faculty_secretary';
        });
        $viceDeanStep = collect($approvalSteps)->first(function ($step) {
            return is_array($step) && trim((string) ($step['step_code'] ?? '')) === 'vice_dean';
        });
        $deanStep = collect($approvalSteps)->first(function ($step) {
            return is_array($step) && trim((string) ($step['step_code'] ?? '')) === 'dean';
        });
        $fmStaffId = is_array($financeManagerStep) ? trim((string) ($financeManagerStep['staff_id'] ?? '')) : '';
        if ($fmStaffId === '') {
            $fallbackFmStaffId = '';
            if (Schema::hasTable('withdraw_set_approver')) {
                $fallbackFmStaffId = trim((string) (DB::table('withdraw_set_approver')
                    ->where('withdraw_set_approver_step_code', 'finance_manager')
                    ->where('withdraw_set_approver_status', 1)
                    ->value('withdraw_set_approver_staff_id') ?? ''));
            }
            if ($fallbackFmStaffId !== '') {
                $fallbackNameMap = $this->resolveStaffNamesByIds([$fallbackFmStaffId]);
                $staffNameMap[$fallbackFmStaffId] = (string) ($fallbackNameMap[$fallbackFmStaffId] ?? '');
                if (! is_array($financeManagerStep)) {
                    $financeManagerStep = [
                        'step_code' => 'finance_manager',
                        'step_name' => 'หัวหน้างานการเงิน',
                        'staff_id' => $fallbackFmStaffId,
                        'staff_name' => (string) ($fallbackNameMap[$fallbackFmStaffId] ?? ''),
                        'status' => 'pending',
                    ];
                } else {
                    $financeManagerStep['staff_id'] = $fallbackFmStaffId;
                    if (trim((string) ($financeManagerStep['staff_name'] ?? '')) === '') {
                        $financeManagerStep['staff_name'] = (string) ($fallbackNameMap[$fallbackFmStaffId] ?? '');
                    }
                }
            }
        }
        $approvedSteps = collect($approvalSteps)
            ->filter(function ($step) {
                if (! is_array($step)) {
                    return false;
                }
                return strtolower(trim((string) ($step['status'] ?? ''))) === 'approved';
            })
            ->sortBy(function ($step) {
                return (int) ($step['step_order'] ?? 0);
            })
            ->values();
        $lastApproverStep = $approvedSteps->last();
        if (! is_array($lastApproverStep)) {
            $lastApproverStep = collect($approvalSteps)
                ->filter(fn ($step) => is_array($step) && trim((string) ($step['staff_id'] ?? '')) !== '')
                ->sortBy(fn ($step) => (int) ($step['step_order'] ?? 0))
                ->last();
        }

        $memoSignerStaffIds = collect([
            is_array($financeOfficerStep) ? trim((string) ($financeOfficerStep['staff_id'] ?? '')) : '',
            is_array($financeManagerStep) ? trim((string) ($financeManagerStep['staff_id'] ?? '')) : '',
            is_array($facultySecretaryStep) ? trim((string) ($facultySecretaryStep['staff_id'] ?? '')) : '',
            is_array($viceDeanStep) ? trim((string) ($viceDeanStep['staff_id'] ?? '')) : '',
            is_array($deanStep) ? trim((string) ($deanStep['staff_id'] ?? '')) : '',
            is_array($lastApproverStep) ? trim((string) ($lastApproverStep['staff_id'] ?? '')) : '',
        ])->filter(fn ($id) => $id !== '')->unique()->values()->all();
        $memoSignerPositionMap = $this->resolveStaffPositionsByIds($memoSignerStaffIds);
        $memoSignerSignatureMap = $this->resolveStaffSignaturesByIds($memoSignerStaffIds);
        $memoSignerSignatureRawUrlMap = $this->resolveStaffSignatureRawUrlsByIds($memoSignerStaffIds);
        $buildSignatureProxyUrl = static function (string $staffId): string {
            $normalizedStaffId = trim((string) $staffId);
            if ($normalizedStaffId === '') {
                return '';
            }
            return url('/withdraw/api/general/signature-proxy?staff_id=' . rawurlencode($normalizedStaffId));
        };
        [$memoPdfBearerHeaderToken, $memoPdfRawBearerToken] = $this->resolveStaffApiTokens();

        $buildMemoSignerCard = function ($step, string $fallbackRole, bool $positionFromApprovalStep = false) use (
            $staffNameMap,
            $memoSignerPositionMap,
            $memoSignerSignatureMap,
            $memoSignerSignatureRawUrlMap,
            $buildSignatureProxyUrl,
            $memoPdfBearerHeaderToken,
            $memoPdfRawBearerToken,
        ) {
            $staffId = is_array($step) ? trim((string) ($step['staff_id'] ?? '')) : '';
            $name = is_array($step) ? trim((string) ($step['staff_name'] ?? '')) : '';
            if ($name === '') {
                $name = trim((string) ($staffNameMap[$staffId] ?? ''));
            }
            $position = '';
            if ($positionFromApprovalStep && is_array($step)) {
                $position = trim((string) ($step['step_name'] ?? ''));
            }
            if ($position === '') {
                $position = trim((string) ($memoSignerPositionMap[$staffId] ?? ''));
            }
            $signatureRawUrl = trim((string) ($memoSignerSignatureRawUrlMap[$staffId] ?? ''));
            $signatureProxyUrl = $buildSignatureProxyUrl($staffId);
            $stepApproved = ! is_array($step) || strtolower(trim((string) ($step['status'] ?? ''))) === 'approved';
            $sigUri = $stepApproved && $staffId !== ''
                ? $this->resolveMemoPdfSignerDataUri($staffId, $memoSignerSignatureMap, $memoPdfBearerHeaderToken, $memoPdfRawBearerToken)
                : '';
            $showSig = $sigUri !== '';
            $statusLine = '';
            return [
                'name' => $name !== '' ? $name : '-',
                'position' => $position !== '' ? $position : '-',
                'role' => $fallbackRole,
                'show_sig' => $showSig,
                'sig_uri' => $sigUri,
                'sign_url' => $signatureRawUrl,
                'signature_proxy_url' => $signatureProxyUrl,
                'status_line' => $statusLine,
            ];
        };
        $routeKind = trim((string) ($row->withdraw_type ?? 'general')) === WithdrawSchema::TYPE_PROJECT
            ? WithdrawSchema::TYPE_PROJECT
            : 'general';
        $routeStepCodes = array_map(
            static fn (array $step) => (string) ($step['code'] ?? ''),
            WithdrawMemoTemplate::resolveApprovalRouteSteps($routeKind, 'default', (float) ($detail['total_amount'] ?? 0))
        );
        $memoSignerCards = [];
        if (in_array('finance_officer', $routeStepCodes, true)) {
            $memoSignerCards[] = $buildMemoSignerCard($financeOfficerStep, 'ผู้จัดทำ');
        }
        if (in_array('finance_manager', $routeStepCodes, true)) {
            $memoSignerCards[] = $buildMemoSignerCard($financeManagerStep, 'หัวหน้างานการเงิน');
        }
        $executiveRouteCodes = array_values(array_intersect($routeStepCodes, ['faculty_secretary', 'vice_dean', 'dean']));
        if (count($executiveRouteCodes) > 0) {
            $lastExecutiveStep = collect($approvalSteps)
                ->filter(function ($step) use ($executiveRouteCodes) {
                    if (! is_array($step)) {
                        return false;
                    }
                    $code = trim((string) ($step['step_code'] ?? ''));

                    return in_array($code, $executiveRouteCodes, true);
                })
                ->sortBy(fn ($step) => (int) ($step['step_order'] ?? 0))
                ->last();
            if (is_array($lastExecutiveStep)) {
                $lastApproverStep = $lastExecutiveStep;
            }
            $lastApproverCode = is_array($lastApproverStep) ? trim((string) ($lastApproverStep['step_code'] ?? '')) : '';
            if (is_array($facultySecretaryStep) && $lastApproverCode !== 'faculty_secretary') {
                $memoSignerCards[] = $buildMemoSignerCard($facultySecretaryStep, 'เลขานุการคณะ');
            }
            $deanIsApprover = in_array('dean', $routeStepCodes, true)
                && (float) ($detail['total_amount'] ?? 0) > 100000;
            if ($deanIsApprover) {
                if (is_array($viceDeanStep)) {
                    $memoSignerCards[] = $buildMemoSignerCard($viceDeanStep, 'รองคณบดี');
                }
                if (is_array($deanStep)) {
                    $memoSignerCards[] = $buildMemoSignerCard($deanStep, 'ผู้อนุมัติ', true);
                }
            } else {
                $approverCard = $buildMemoSignerCard($lastApproverStep, 'ผู้อนุมัติ', true);
                if ($lastApproverCode === 'vice_dean') {
                    $approverPosition = trim((string) ($approverCard['position'] ?? ''));
                    if ($approverPosition === '' || $approverPosition === '-') {
                        $approverPosition = 'รองคณบดี';
                    }
                    $actingSuffix = 'ปฏิบัติรายการแทน คณบดีคณะเภสัชศาสตร์';
                    if (! str_contains($approverPosition, $actingSuffix)) {
                        $approverCard['position'] = $approverPosition.' '.$actingSuffix;
                    }
                }
                $memoSignerCards[] = $approverCard;
            }
        }
        foreach ($memoSignerCards as $cardIndex => $signerCard) {
            if (! is_array($signerCard) || empty($signerCard['show_sig'])) {
                continue;
            }
            $embeddedPath = $this->persistSignatureDataUriForMpdf((string) ($signerCard['sig_uri'] ?? ''));
            if ($embeddedPath === '') {
                $memoSignerCards[$cardIndex]['show_sig'] = false;
                $memoSignerCards[$cardIndex]['sig_uri'] = '';
                continue;
            }
            $memoSignerCards[$cardIndex]['sig_uri'] = $embeddedPath;
        }

        $financeApprovedStep = collect($approvalSteps)->first(function ($step) {
            $stepCode = trim((string) ($step['step_code'] ?? ''));
            $status = strtolower(trim((string) ($step['status'] ?? '')));
            return in_array($stepCode, ['finance_officer', 'finance_manager'], true) && $status === 'approved';
        });
        $financeApprovedAt = trim((string) ($financeApprovedStep['approved_at'] ?? ''));
        $memoDate = $financeDocumentDate !== ''
            ? $financeDocumentDate
            : ($financeApprovedAt !== '' ? $financeApprovedAt : (string) ($detail['document_date'] ?? ''));

        $items = is_array($detail['items'] ?? null) ? $detail['items'] : [];
        $expenseChartIds = collect($items)
            ->map(fn ($item) => is_array($item) ? (int) ($item['expense_chart_id'] ?? 0) : 0)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $expenseChartNameMap = [];
        if (count($expenseChartIds) > 0 && Schema::hasTable('withdraw_set_expenses')) {
            $expenseChartNameMap = DB::table('withdraw_set_expenses')
                ->whereIn('withdraw_set_expenses_id', $expenseChartIds)
                ->pluck('withdraw_set_expenses_name', 'withdraw_set_expenses_id')
                ->map(fn ($name) => trim((string) $name))
                ->all();
        }
        $items = collect($items)->map(function ($item) use ($expenseChartNameMap) {
            if (! is_array($item)) {
                return $item;
            }
            $expenseChartId = (int) ($item['expense_chart_id'] ?? 0);
            $item['expense_chart_name'] = (string) ($expenseChartNameMap[$expenseChartId] ?? '');
            $financeAmount = $item['finance_amount'] ?? null;
            if ($financeAmount !== null && (float) $financeAmount > 0) {
                $item['amount'] = (float) $financeAmount;
            }
            return $item;
        })->values()->all();
        $detail['items'] = $items;
        $itemsTotalAmount = collect($items)->sum(static fn ($item) => is_array($item) ? (float) ($item['amount'] ?? 0) : 0.0);

        $budgetSummaryMap = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $budgetManageId = (int) ($item['budget_manage_id'] ?? 0);
            $amount = (float) ($item['amount'] ?? 0);
            if ($budgetManageId <= 0 || ! is_finite($amount) || $amount <= 0) {
                continue;
            }
            $budgetSummaryMap[$budgetManageId] = ($budgetSummaryMap[$budgetManageId] ?? 0.0) + $amount;
        }
        $budgetSummaryById = collect($budgetSummaryMap)->map(fn ($amount) => (float) $amount);
        $budgetManageIds = array_map('intval', array_keys($budgetSummaryMap));

        $budgetSourceMap = [];
        if (count($budgetManageIds) > 0 && Schema::connection('budget')->hasTable('budget_manage')) {
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
                    'bm.budget_manage_amount as amount',
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
            $approvedAmountByManageId = [];
            if (Schema::connection('budget')->hasTable('budget_manage_list')) {
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
                $budgetSourceMap[(int) ($budgetRow->id ?? 0)] = [
                    'label' => trim($sourcePath),
                    'amount' => (float) ($budgetRow->amount ?? 0),
                    'approved_amount' => (float) ($approvedAmountByManageId[(int) ($budgetRow->id ?? 0)] ?? 0),
                ];
            }
        }

        $budgetSummaryRows = [];
        foreach ($budgetSummaryById as $budgetManageId => $approvedThisTime) {
            $budgetManageId = (int) $budgetManageId;
            $source = $budgetSourceMap[$budgetManageId] ?? [];
            $budgetAmount = (float) ($source['amount'] ?? 0);
            $approvedAmountFromSystem = (float) ($source['approved_amount'] ?? 0);
            // For approved documents, approved_amount may already include this document.
            // Split out "อนุมัติแล้ว" as amount before this transaction to avoid double counting.
            $approvedAlready = max(0, $approvedAmountFromSystem - (float) $approvedThisTime);
            $totalApproved = $approvedAlready + (float) $approvedThisTime;
            $budgetSummaryRows[] = [
                'label' => (string) ($source['label'] ?? ''),
                'amount' => $budgetAmount,
                'approved_already' => $approvedAlready,
                'approved_this_time' => (float) $approvedThisTime,
                'total_approved' => $totalApproved,
                'remaining' => $budgetAmount - $totalApproved,
            ];
        }

        $primaryBudgetLabel = '';
        foreach ($budgetSummaryRows as $summaryRow) {
            $candidate = trim((string) ($summaryRow['label'] ?? ''));
            if ($candidate !== '') {
                $primaryBudgetLabel = $candidate;
                break;
            }
        }
        $paragraph1Finance = 'ด้วย '.($requestDepartmentDisplay !== '' ? $requestDepartmentDisplay : '........................').' มีความประสงค์ขออนุมัติเบิกค่าใช้จ่าย ดังนี้';
        $itemsTotalText = $this->toThaiBahtText($itemsTotalAmount);
        $paragraph3Finance = 'จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ โดยเบิกจ่ายจาก '.($primaryBudgetLabel !== '' ? $primaryBudgetLabel : '........................')
            .' จำนวนเงิน '.number_format($itemsTotalAmount, 2).' บาท ('.$itemsTotalText.')';
        if ($financeSubject === '') {
            $financeSubject = str_replace('ขออนุมัติเบิกจ่าย', 'ขออนุมัติค่าใช้จ่าย', (string) ($detail['subject'] ?? ''));
        }
        $subjectFinance = $financeSubject;

        $filenameDocumentNo = trim((string) ($detail['document_number_display'] ?? $detail['document_number'] ?? ''));
        $filenameSubject = trim((string) $subjectFinance);
        $filenameParts = array_values(array_filter([$filenameDocumentNo, $filenameSubject], static fn ($part) => $part !== ''));
        $filenameCode = count($filenameParts) > 0 ? implode(' - ', $filenameParts) : 'withdraw-general-finance-'.$generalId;
        $safeFilenameCode = trim((string) preg_replace('/[\\\\\/:\*\?"<>\|\x00-\x1F]+/u', '-', $filenameCode), " .-\t\n\r\0\x0B");
        if ($safeFilenameCode === '') {
            $safeFilenameCode = 'withdraw-general-finance-'.$generalId;
        }

        $html = view('withdraw.general.pdf.memo-finance', [
            'detail' => $detail,
            'thaiDate' => $this->formatThaiDateDetailModalStyle($memoDate),
            'departmentDisplay' => $financeDepartmentDisplay !== '' ? $financeDepartmentDisplay : $requestDepartmentDisplay,
            'subjectFinance' => trim((string) $subjectFinance),
            'useArabicDigits' => $isProjectWithdraw,
            'paragraph1Finance' => $paragraph1Finance,
            'paragraph3Finance' => $paragraph3Finance,
            'itemsTotalAmount' => $itemsTotalAmount,
            'itemsTotalText' => $itemsTotalText,
            'budgetSummaryRows' => $budgetSummaryRows,
            'memoSignerCards' => $memoSignerCards,
        ])->render();

        $tempDir = storage_path('framework/cache/mpdf');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontDir = array_merge($defaultConfig['fontDir'], [storage_path('fonts')]);
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

        $existing = $this->withdrawQuery()
            ->where('withdraw_id', $id)
            ->first(['withdraw_id', 'withdraw_status', 'withdraw_type']);
        if (! $existing) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูล'], 404);
        }

        $currentStatus = trim((string) ($existing->withdraw_status ?? ''));
        $currentStaffId = trim((string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? ''));

        try {
            DB::transaction(function () use ($id, $currentStatus, $currentStaffId) {
                // บันทึกประวัติการลบไว้ก่อน แล้วคง action logs ทั้งหมดของรายการนี้ไว้
                $this->appendGeneralActionLog(
                    $id,
                    'delete',
                    'ลบรายการ',
                    $currentStatus,
                    'deleted',
                    $currentStaffId
                );

                // ไฟล์แนบของรายการเบิกจ่าย
                if (Schema::hasTable('withdraw_file')) {
                    $fileRows = DB::table('withdraw_file')
                        ->where('withdraw_id', $id)
                        ->get(['withdraw_file_path']);
                    foreach ($fileRows as $fileRow) {
                        $path = trim((string) ($fileRow->withdraw_file_path ?? ''));
                        if ($path !== '' && Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->delete($path);
                        }
                    }
                    DB::table('withdraw_file')->where('withdraw_id', $id)->delete();
                }

                // เอกสารจัดซื้อจัดจ้าง + ไฟล์แนบ
                if (Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)) {
                    $procurementIds = DB::table(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)
                        ->where('withdraw_id', $id)
                        ->pluck('withdraw_project_procurement_id')
                        ->map(fn ($value) => (int) $value)
                        ->filter(fn ($value) => $value > 0)
                        ->values()
                        ->all();
                    if ($procurementIds !== [] && Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)) {
                        $procurementFiles = DB::table(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)
                            ->whereIn('withdraw_project_procurement_id', $procurementIds)
                            ->get(['withdraw_project_procurement_file_path']);
                        foreach ($procurementFiles as $fileRow) {
                            $path = trim((string) ($fileRow->withdraw_project_procurement_file_path ?? ''));
                            if ($path !== '' && Storage::disk('public')->exists($path)) {
                                Storage::disk('public')->delete($path);
                            }
                        }
                        DB::table(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)
                            ->whereIn('withdraw_project_procurement_id', $procurementIds)
                            ->delete();
                    }
                    DB::table(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)->where('withdraw_id', $id)->delete();
                }

                if (Schema::hasTable('withdraw_item')) {
                    DB::table('withdraw_item')->where('withdraw_id', $id)->delete();
                }
                if (Schema::hasTable('withdraw_req_document')) {
                    DB::table('withdraw_req_document')->where('withdraw_id', $id)->delete();
                }
                if (Schema::hasTable('withdraw_approval_step')) {
                    DB::table('withdraw_approval_step')->where('withdraw_id', $id)->delete();
                }
                if (Schema::hasTable('withdraw_document')) {
                    DB::table('withdraw_document')->where('withdraw_id', $id)->delete();
                }
                if (Schema::hasTable(WithdrawSchema::BUDGET_TABLE)) {
                    DB::table(WithdrawSchema::BUDGET_TABLE)->where('withdraw_id', $id)->delete();
                }

                // คงประวัติการดำเนินการ (withdraw_action_logs) ไว้ ไม่ลบ
                $this->withdrawQuery()->where('withdraw_id', $id)->delete();
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถลบรายการและข้อมูลที่เกี่ยวข้องได้',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'ลบรายการและข้อมูลที่เกี่ยวข้องเรียบร้อยแล้ว',
        ]);
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

        $row = $this->withdrawQuery()->where('withdraw_id', $id)->first([
            'withdraw_id',
            'withdraw_type',
            'withdraw_status',
            'withdraw_subject',
            'withdraw_to',
            'withdraw_total_amount',
            'withdraw_applicant_staff_id',
            'withdraw_reviewer_staff_id',
        ]);
        if (! $row) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูล'], 404);
        }

        $currentStaffId = trim((string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? ''));
        $reviewerStaffId = trim((string) ($row->withdraw_reviewer_staff_id ?? ''));
        $currentStatus = trim((string) ($row->withdraw_status ?? ''));
        $action = trim((string) $request->input('action', ''));

        if ($action === 'request_revision_to_finance') {
            if ($currentStatus !== 'pending_approval') {
                return response()->json(['status' => 'error', 'message' => 'สถานะรายการนี้ไม่สามารถส่งกลับให้งานการเงินแก้ไขได้'], 422);
            }
            if ($currentStaffId === '' || ! Schema::hasTable('withdraw_approval_step')) {
                return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ส่งกลับให้แก้ไข'], 403);
            }

            $isFinanceManagerApprover = DB::table('withdraw_approval_step')
                ->where('withdraw_id', $id)
                ->where('withdraw_approval_step_code', 'finance_manager')
                ->where('withdraw_approval_staff_id', $currentStaffId)
                ->whereIn('withdraw_approval_status', ['pending', 'approved'])
                ->exists();
            if (! $isFinanceManagerApprover) {
                return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ส่งกลับให้แก้ไข'], 403);
            }

            $revisionComment = trim((string) ($request->input('revision_comment', $approvalComment)));
            if ($revisionComment === '') {
                return response()->json(['status' => 'error', 'message' => 'กรุณาระบุรายละเอียดที่ต้องแก้ไข'], 422);
            }

            DB::transaction(function () use ($id, $currentStaffId, $revisionComment) {
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
                            'message' => 'มีรายการเบิกจ่ายทั่วไปส่งกลับให้แก้ไขจากผู้บริหาร - '.$subjectText.' ('.$displayCode.') เหตุผล: '.$revisionComment,
                            'systemName' => 'ระบบเบิกจ่าย',
                            'actionUrl' => WithdrawApprovalLink::nextApprover($id, 'general', 'finance_officer'),
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
                            'action_url' => WithdrawApprovalLink::nextApprover($id, 'general', 'finance_officer'),
                            'mail_subject' => '[ระบบเบิกจ่าย] มีรายการส่งกลับให้แก้ไข '.$displayCode,
                            'header_title' => 'แจ้งเตือนรายการเบิกจ่ายทั่วไป',
                            'header_description' => 'มีรายการถูกส่งกลับให้แก้ไขจากผู้บริหาร',
                            'status_badge' => 'รอการเงินตรวจสอบเอกสาร (แก้ไข)',
                            'intro_message' => 'มีรายการเบิกจ่ายทั่วไปถูกส่งกลับให้งานการเงินตรวจสอบและแก้ไข',
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

            if ($currentStaffId !== '' && Schema::hasTable('withdraw_approval_step')) {
                // Allow any pending step assigned to current staff (reviewer_head / additional_reviewer / executives).
                // finance_officer uses withdraw_set_finance_reviewer below.
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
                && in_array($currentStatus, ['pending_approval'], true)
            ) {
                $revisionStepCode = 'reviewer_head';
                $actionLogLabel = 'ผู้ตรวจสอบ/หัวหน้างานส่งกลับให้ผู้ขอแก้ไข';
            } elseif (
                $currentStaffId !== ''
                && Schema::hasTable('withdraw_set_finance_reviewer')
                && $currentStatus === 'pending_finance_review'
            ) {
                $hasFinanceReviewerAccess = DB::table('withdraw_set_finance_reviewer')
                    ->where('withdraw_set_finance_reviewer_staff_id', $currentStaffId)
                    ->where('withdraw_set_finance_reviewer_status', 1)
                    ->exists();
                if (! $hasFinanceReviewerAccess) {
                    return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ส่งกลับให้แก้ไข'], 403);
                }

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
            $withdrawType = $this->resolveWithdrawTypeFromRow($row);
            $withdrawTypeLabel = $this->resolveWithdrawTypeLabel($withdrawType);
            $applicantActionUrl = $this->resolveApplicantWithdrawUrl($row, $id);

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
                            'message' => 'รายการ'.$withdrawTypeLabel.'ถูกส่งกลับให้แก้ไข - '.$subjectText.' ('.$displayCode.') เหตุผล: '.$revisionComment,
                            'systemName' => 'ระบบเบิกจ่าย',
                            'actionUrl' => $applicantActionUrl,
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
                                'action_url' => $applicantActionUrl,
                                'mail_subject' => '[ระบบเบิกจ่าย] รายการถูกส่งกลับให้แก้ไข '.$displayCode,
                                'header_title' => 'แจ้งเตือนรายการ'.$withdrawTypeLabel,
                                'header_description' => 'มีรายการถูกส่งกลับเพื่อให้แก้ไข',
                                'status_badge' => 'รอแก้ไข',
                                'intro_message' => 'รายการ'.$withdrawTypeLabel.'ของท่านถูกส่งกลับให้แก้ไข',
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
            if (! in_array($currentStatus, ['pending_primary_review', 'processing', 'pending_additional_review', 'pending_finance_review'], true)) {
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

        $isReviewerApprovalFlow = in_array($currentStatus, ['pending_primary_review', 'processing'], true)
            && $reviewerStaffId !== ''
            && $currentStaffId !== ''
            && $currentStaffId === $reviewerStaffId;
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

        if ($currentStatus === 'pending_approval') {
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
            $targetActionUrl = WithdrawApprovalLink::nextApprover($id, 'general');
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
                if ($targetStepCode === 'finance_officer') {
                    $mustForwardToFinance = true;
                    $targetStatusAfterProceed = 'pending_finance_review';
                    $targetActionUrl = WithdrawApprovalLink::nextApprover($id, 'general', 'finance_officer');
                    $actionLogLabel = 'ส่งต่อให้งานการเงินตรวจสอบเอกสาร';
                } elseif ($targetStepCode === 'additional_reviewer') {
                    $targetStatusAfterProceed = 'pending_additional_review';
                    $targetActionUrl = WithdrawApprovalLink::nextApprover($id, 'general', $targetStepCode);
                } else {
                    $targetActionUrl = WithdrawApprovalLink::nextApprover($id, 'general', $targetStepCode);
                }
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
                            'message' => 'มีรายการเบิกจ่ายทั่วไปรออนุมัติ/ตรวจสอบจากท่าน ('.$targetRoleText.') - '.$subjectText.' ผู้ขอเบิก '.$applicantText.' จำนวนเงิน '.$amountText,
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
                            'header_title' => 'แจ้งเตือนรายการเบิกจ่ายทั่วไป',
                            'header_description' => 'มีรายการส่งต่อเพื่ออนุมัติ/ตรวจสอบตามลำดับ',
                            'status_badge' => $mustForwardToFinance ? 'รอการเงินตรวจสอบเอกสาร' : 'รออนุมัติ',
                            'intro_message' => 'มีรายการเบิกจ่ายทั่วไปที่รอดำเนินการจากท่าน',
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
                ],
            ]);
        }

        if (! $isReviewerApprovalFlow && ! $isAdditionalApprovalFlow && $currentStatus !== 'pending_finance_review') {
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
            $withdrawType = trim((string) ($row->withdraw_type ?? ''));
            if ($withdrawType === '') {
                $withdrawType = WithdrawSchema::TYPE_GENERAL;
            }
            $this->assignWithdrawDocumentNumber(
                $id,
                $withdrawType,
                (string) ($documentMeta['department_code'] ?? ''),
                (string) ($documentMeta['document_number'] ?? ''),
                (string) ($documentMeta['document_date'] ?? ''),
                (int) ($documentMeta['running_year'] ?? 0),
                (int) ($documentMeta['running_no'] ?? 0)
            );
            unset($this->withdrawGeneralDocumentMetaCache[$withdrawType.':'.$id]);
            if ($withdrawType === WithdrawSchema::TYPE_PROJECT) {
                $assignedMemoMeta = $this->resolveWithdrawDocumentMetaByType($id, $withdrawType);
                $this->assignProjectProcurementDocumentNumbers($id, $assignedMemoMeta);
            }

            $this->withdrawQuery()
                ->where('withdraw_id', $id)
                ->update($updatePayload);

            $mailDebug = [
                'attempted' => false,
                'sent' => false,
                'to' => '',
                'subject' => '',
                'message' => '',
                'error' => '',
                'error_class' => '',
            ];

            // Notify reviewer (best effort; never block proceed).
            try {
                if ($reviewerStaffId !== '') {
                    $displayCode = $this->resolveWithdrawGeneralDisplayDocumentCode($row);
                    if ($displayCode === '') {
                        $displayCode = (string) $id;
                    }
                    $subject = trim((string) ($row->withdraw_subject ?? ''));
                    $subjectText = $subject !== '' ? ($subject) : 'เรื่อง -';
                    $reviewerNameMap = $this->resolveStaffNamesByIds([$reviewerStaffId]);
                    $reviewerName = trim((string) ($reviewerNameMap[$reviewerStaffId] ?? ''));
                    $recipientName = $reviewerName !== '' ? $reviewerName : $reviewerStaffId;
                    $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
                    $applicantNameMap = $this->resolveStaffNamesByIds($applicantStaffId !== '' ? [$applicantStaffId] : []);
                    $applicantName = trim((string) ($applicantNameMap[$applicantStaffId] ?? ''));
                    $applicantText = $applicantName !== '' ? $applicantName : ($applicantStaffId !== '' ? $applicantStaffId : '-');
                    $amount = (float) ($row->withdraw_total_amount ?? 0);
                    $amountText = number_format($amount, 2);
                    $createdBy = trim((string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? ''));

                    $notificationApiUrl = trim((string) config('services.notification_api.url', ''));
                    if ($notificationApiUrl !== '') {
                        Http::timeout(10)
                            ->post($notificationApiUrl, [
                            'staffId' => $reviewerStaffId,
                            'message' => 'มีรายการเบิกจ่ายทั่วไป รอการตรวจสอบจากท่าน - '.$subjectText.' ผู้ขอเบิก '.$applicantText.' จำนวนเงิน '.$amountText,
                            'systemName' => 'ระบบเบิกจ่าย',
                            'actionUrl' => WithdrawApprovalLink::nextApprover($id, 'general', 'reviewer_head'),
                            'createdBy' => $createdBy !== '' ? $createdBy : 'system',
                            ]);
                    }

                    $reviewerEmail = $this->resolveStaffEmailById($reviewerStaffId);
                    if ($reviewerEmail !== '') {
                        $mailTemplate = new WithdrawProceedNotificationMail([
                            'display_code' => $displayCode,
                            'subject_text' => $subjectText,
                            'recipient_name' => $recipientName,
                            'applicant_text' => $applicantText,
                            'amount_text' => $amountText,
                            'action_url' => WithdrawApprovalLink::nextApprover($id, 'general', 'reviewer_head'),
                        ]);
                        $mailSubject = $mailTemplate->subjectLine();
                        $mailBodyText = $mailTemplate->plainMessage();
                        $mailBodyHtml = $mailTemplate->render();
                        $mailDebug['attempted'] = true;
                        $mailDebug['to'] = $reviewerEmail;
                        $mailDebug['subject'] = $mailSubject;
                        $mailDebug['message'] = $mailBodyText;

                        $smtpHost = trim((string) env('MAIL_HOST', ''));
                        $smtpPort = (int) env('MAIL_PORT', 587);
                        $smtpUsername = trim((string) env('MAIL_USERNAME', ''));
                        $smtpPassword = (string) env('MAIL_PASSWORD', '');
                        $smtpFromAddress = trim((string) env('MAIL_FROM_ADDRESS', ''));
                        $smtpFromName = trim((string) env('MAIL_FROM_NAME', (string) config('app.name', 'PBM Withdraw')));

                        if ($smtpHost === '' || $smtpFromAddress === '') {
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
                                $mailer->addAddress($reviewerEmail);
                                $mailer->Subject = $mailSubject;
                                $mailer->Body = $mailBodyHtml;
                                $mailer->AltBody = $mailBodyText;
                                $mailer->isHTML(true);
                                $mailer->send();
                                $mailDebug['sent'] = true;
                            } catch (PHPMailerException $e) {
                                $mailDebug['error'] = $e->getMessage();
                                $mailDebug['error_class'] = get_class($e);
                            }
                        }
                    } else {
                        $mailDebug['error'] = 'ไม่พบอีเมลผู้ตรวจสอบจาก API checkStaffRX';
                    }
                }
            } catch (\Throwable $e) {
                $mailDebug['error'] = $e->getMessage();
                $mailDebug['error_class'] = get_class($e);
            }

            $this->appendGeneralActionLog(
                $id,
                'submit_for_reviewer',
                'ส่งรายการให้ผู้ตรวจสอบ',
                $currentStatus,
                'pending_primary_review',
                (string) ($request->session()->get('staff_id') ?? ''),
                $approvalComment
            );

            return response()->json([
                'status' => 'success',
                'message' => 'ส่งรายการให้ผู้ตรวจสอบเรียบร้อยแล้ว',
                'data' => [
                    'mail_debug' => $mailDebug,
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

            return response()->json([
                'status' => 'success',
                'message' => 'บันทึกการตรวจสอบเพิ่มเติมแล้ว และส่งต่อไปยังฝ่ายการเงินเรียบร้อย',
                'data' => [
                    'next_status' => 'pending_finance_review',
                ],
            ]);
        }

        if ($currentStatus === 'pending_finance_review') {
            if ($currentStaffId === '' || ! Schema::hasTable('withdraw_set_finance_reviewer')) {
                return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ตรวจสอบรายการการเงิน'], 403);
            }
            $hasFinanceReviewerAccess = DB::table('withdraw_set_finance_reviewer')
                ->where('withdraw_set_finance_reviewer_staff_id', $currentStaffId)
                ->where('withdraw_set_finance_reviewer_status', 1)
                ->exists();
            if (! $hasFinanceReviewerAccess) {
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

            try {
                $withdrawType = trim((string) ($row->withdraw_type ?? ''));
                DB::transaction(function () use ($id, $currentStaffId, $budgetManageId, $budgetManageCode, $mappingByItemId, $approvalComment, $generalItems, $documentData, $request) {
                    $now = now();
                    $updatedItemCount = 0;
                    foreach ($mappingByItemId as $itemId => $mapping) {
                        $expenseChartId = (int) ($mapping['expense_chart_id'] ?? 0);
                        $itemUpdate = [
                            'withdraw_item_expense_chart_id' => $expenseChartId,
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

                    $this->storeFinanceEsignUploads($request, $id, $currentStaffId, 'general');

                    $normalizedFinanceDepartmentCode = $documentData['department_code'] !== ''
                        ? $documentData['department_code']
                        : '12010401';
                    $this->assignWithdrawDocumentNumber(
                        $id,
                        'general_finance',
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
                            'message' => 'มีรายการเบิกจ่ายทั่วไป รออนุมัติจากท่าน - '.$subjectText.' ผู้ขอเบิก '.$applicantText.' จำนวนเงิน '.$amountText,
                            'systemName' => 'ระบบเบิกจ่าย',
                            'actionUrl' => WithdrawApprovalLink::nextApprover($id, 'general', 'finance_manager'),
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
                            'action_url' => WithdrawApprovalLink::nextApprover($id, 'general', 'finance_manager'),
                            'mail_subject' => '[ระบบเบิกจ่าย] มีรายการรออนุมัติ '.$displayCode,
                            'header_title' => 'แจ้งเตือนรายการเบิกจ่ายทั่วไป',
                            'header_description' => 'มีรายการรออนุมัติจากหัวหน้างานการเงิน',
                            'status_badge' => 'รออนุมัติ',
                            'intro_message' => 'มีรายการเบิกจ่ายทั่วไปที่รออนุมัติจากท่าน',
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
                    $applicantActionUrl = $this->resolveApplicantWithdrawUrl($row, $id);
                    if ($notificationApiUrl !== '') {
                        Http::timeout(10)->post($notificationApiUrl, [
                            'staffId' => $applicantStaffId,
                            'message' => $notificationMessage,
                            'systemName' => 'ระบบเบิกจ่าย',
                            'actionUrl' => $applicantActionUrl,
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
                            'action_url' => $applicantActionUrl,
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

            // เบิกจ่ายทั่วไป/ประเภทที่ต้องให้การเงินตรวจก่อน: ส่ง One Planning ตอนงานการเงินตรวจสอบแล้ว
            $onePlanningDebug = ['skipped' => true];
            try {
                $withdrawType = trim((string) ($row->withdraw_type ?? 'general'));
                $refPrefix = $withdrawType !== '' ? $withdrawType : 'general';
                $onePlanningDebug = WithdrawOnePlanningSender::send(
                    $id,
                    'อยู่ระหว่างดำเนินการ',
                    'finance_review',
                    $currentStaffId,
                    null,
                    $refPrefix
                );
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

        // Reviewer approval flow:
        // - Save reviewer approval/comment
        // - If has additional reviewers, notify them
        // - Else move to finance review status
        $additionalStaffIds = [];
        if (Schema::hasTable('withdraw_approval_step')) {
            $additionalStaffIds = DB::table('withdraw_approval_step')
                ->where('withdraw_id', $id)
                ->where('withdraw_approval_step_code', 'additional_reviewer')
                ->pluck('withdraw_approval_staff_id')
                ->map(fn ($staffId) => trim((string) $staffId))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }
        $hasAdditionalReviewers = count($additionalStaffIds) > 0;

        $reviewerUpdatePayload = [
            'withdraw_status' => $hasAdditionalReviewers ? 'pending_additional_review' : 'pending_finance_review',
            'withdraw_date_modify' => now(),
            'staff_modify' => $currentStaffId,
        ];

        $this->withdrawQuery()
            ->where('withdraw_id', $id)
            ->update($reviewerUpdatePayload);

        $this->markApprovalStepAsApproved($id, 'reviewer_head', $currentStaffId, $approvalComment, $currentStaffId);

        $notificationDebug = [
            'notified_staff_ids' => [],
            'mail_sent_staff_ids' => [],
            'mail_errors' => [],
        ];

        $displayCode = $this->resolveWithdrawGeneralDisplayDocumentCode($row);
        if ($displayCode === '') {
            $displayCode = (string) $id;
        }
        $subject = trim((string) ($row->withdraw_subject ?? ''));
        $subjectText = $subject !== '' ? ($subject) : 'เรื่อง -';
        $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
        $applicantNameMap = $this->resolveStaffNamesByIds($applicantStaffId !== '' ? [$applicantStaffId] : []);
        $applicantName = trim((string) ($applicantNameMap[$applicantStaffId] ?? ''));
        $applicantText = $applicantName !== '' ? $applicantName : ($applicantStaffId !== '' ? $applicantStaffId : '-');
        $amount = (float) ($row->withdraw_total_amount ?? 0);
        $amountText = number_format($amount, 2);

        try {
            if ($hasAdditionalReviewers) {
                if (Schema::hasTable('withdraw_approval_step')) {
                    DB::table('withdraw_approval_step')
                        ->where('withdraw_id', $id)
                        ->where('withdraw_approval_step_code', 'additional_reviewer')
                        ->whereIn('withdraw_approval_staff_id', $additionalStaffIds)
                        ->update([
                            'withdraw_approval_status' => 'pending',
                            'withdraw_approval_notified_at' => now(),
                            'withdraw_approval_date_modify' => now(),
                            'staff_modify' => $currentStaffId,
                        ]);
                }

                $createdBy = $currentStaffId !== '' ? $currentStaffId : 'system';
                $staffNameMap = $this->resolveStaffNamesByIds($additionalStaffIds);
                $notificationApiUrl = trim((string) config('services.notification_api.url', ''));
                foreach ($additionalStaffIds as $staffId) {
                    if ($notificationApiUrl !== '') {
                        Http::timeout(10)->post($notificationApiUrl, [
                            'staffId' => $staffId,
                            'message' => 'มีรายการเบิกจ่ายทั่วไป รอตรวจสอบเพิ่มเติม - '.$subjectText.' ผู้ขอเบิก '.$applicantText.' จำนวนเงิน '.$amountText,
                            'systemName' => 'ระบบเบิกจ่าย',
                            'actionUrl' => WithdrawApprovalLink::nextApprover($id, 'general', 'additional_reviewer'),
                            'createdBy' => $createdBy,
                        ]);
                    }
                    $notificationDebug['notified_staff_ids'][] = $staffId;

                    $targetEmail = $this->resolveStaffEmailById((string) $staffId);
                    if ($targetEmail === '') {
                        $notificationDebug['mail_errors'][] = [
                            'staff_id' => $staffId,
                            'error' => 'ไม่พบอีเมล',
                        ];
                        continue;
                    }
                    $recipientName = trim((string) ($staffNameMap[$staffId] ?? ''));
                    if ($recipientName === '') {
                        $recipientName = $staffId;
                    }

                    $mailTemplate = new WithdrawProceedNotificationMail([
                        'display_code' => $displayCode,
                        'subject_text' => $subjectText,
                        'recipient_name' => $recipientName,
                        'applicant_text' => $applicantText,
                        'amount_text' => $amountText,
                        'action_url' => WithdrawApprovalLink::nextApprover($id, 'general', 'additional_reviewer'),
                    ]);
                    $mailSubject = $mailTemplate->subjectLine();
                    $mailBodyText = $mailTemplate->plainMessage();
                    $mailBodyHtml = $mailTemplate->render();

                    $smtpHost = trim((string) env('MAIL_HOST', ''));
                    $smtpPort = (int) env('MAIL_PORT', 587);
                    $smtpUsername = trim((string) env('MAIL_USERNAME', ''));
                    $smtpPassword = (string) env('MAIL_PASSWORD', '');
                    $smtpFromAddress = trim((string) env('MAIL_FROM_ADDRESS', ''));
                    $smtpFromName = trim((string) env('MAIL_FROM_NAME', (string) config('app.name', 'PBM Withdraw')));

                    if ($smtpHost === '' || $smtpFromAddress === '') {
                        $notificationDebug['mail_errors'][] = [
                            'staff_id' => $staffId,
                            'error' => 'SMTP host/from address ไม่ครบถ้วน',
                        ];
                        continue;
                    }
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
                        $notificationDebug['mail_sent_staff_ids'][] = $staffId;
                    } catch (PHPMailerException $e) {
                        $notificationDebug['mail_errors'][] = [
                            'staff_id' => $staffId,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            $notificationDebug['mail_errors'][] = [
                'staff_id' => '',
                'error' => $e->getMessage(),
            ];
        }

        if (! $hasAdditionalReviewers) {
            $this->markApprovalStepAsApproved($id, 'finance_officer', '', '', $currentStaffId, 'pending');
        }

        $responseMessage = $hasAdditionalReviewers
            ? 'บันทึกการอนุมัติแล้ว และแจ้งผู้ตรวจสอบเพิ่มเติมเรียบร้อย'
            : 'บันทึกการอนุมัติแล้ว และส่งต่อไปยังฝ่ายการเงินเรียบร้อย';

        $this->appendGeneralActionLog(
            $id,
            'reviewer_approved',
            'ผู้ตรวจสอบอนุมัติรายการ',
            $currentStatus,
            $hasAdditionalReviewers ? 'pending_additional_review' : 'pending_finance_review',
            $currentStaffId,
            $approvalComment,
            ['has_additional_reviewers' => $hasAdditionalReviewers]
        );

        return response()->json([
            'status' => 'success',
            'message' => $responseMessage,
            'data' => [
                'has_additional_reviewers' => $hasAdditionalReviewers,
                'next_status' => $hasAdditionalReviewers ? 'pending_additional_review' : 'pending_finance_review',
                'notification_debug' => $notificationDebug,
            ],
        ]);
    }

    public function apiList(Request $request): JsonResponse
    {
        $scope = trim((string) $request->query('scope', ''));
        $currentStaffId = trim((string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? ''));

        return response()->json([
            'status' => 'success',
            'data' => $this->fetchUnifiedApprovalRows($scope, $currentStaffId)->values(),
        ]);
    }

    public function countReviewerPendingApprovals(string $currentStaffId = ''): int
    {
        $currentStaffId = trim($currentStaffId);
        if ($currentStaffId === '') {
            return 0;
        }

        try {
            return (int) $this->fetchUnifiedApprovalRows('reviewer-pending', $currentStaffId)
                ->filter(static function (array $row): bool {
                    if (trim((string) ($row['user_step_status'] ?? '')) !== 'pending') {
                        return false;
                    }
                    $rawStatus = trim((string) ($row['raw_status'] ?? ''));
                    $statusLabel = trim((string) ($row['status'] ?? ''));
                    if ($rawStatus === 'pending_finance_review' || str_contains($statusLabel, 'รอการเงินตรวจสอบเอกสาร')) {
                        return false;
                    }

                    return true;
                })
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
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
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function fetchUnifiedApprovalRows(string $scope = '', string $currentStaffId = ''): \Illuminate\Support\Collection
    {
        $rows = $this->fetchGeneralRows($scope, $currentStaffId, WithdrawSchema::TYPE_GENERAL)
            ->map(function (array $row): array {
                $row['withdraw_kind'] = 'general';

                return $row;
            });

        if ($scope === '') {
            $listType = trim((string) request()->query('withdraw_type', $this->currentWithdrawType()));
            if ($listType === WithdrawSchema::TYPE_PROJECT) {
                return $this->fetchGeneralRows('', $currentStaffId, WithdrawSchema::TYPE_PROJECT)
                    ->map(function (array $row): array {
                        $row['withdraw_kind'] = 'project';

                        return $row;
                    });
            }

            return $rows;
        }

        if (! in_array($scope, ['reviewer-pending', 'document-review'], true)) {
            return $rows;
        }

        $projectRows = $this->fetchGeneralRows($scope, $currentStaffId, WithdrawSchema::TYPE_PROJECT)
            ->map(function (array $row): array {
                $row['withdraw_kind'] = 'project';

                return $row;
            });

        $salaryRows = app(WithdrawSalaryController::class)->fetchApprovalListRows($scope, $currentStaffId);

        return $rows
            ->concat($projectRows)
            ->concat($salaryRows)
            ->sortByDesc(fn (array $row) => (int) ($row['id'] ?? 0))
            ->values();
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

    private function resolveProjectApprovedAlready(string $projectId, string $projectCode, int $excludeId = 0): float
    {
        $projectId = trim($projectId);
        $projectCode = trim($projectCode);
        if ($projectId === '' && $projectCode === '') {
            return 0.0;
        }

        if (! Schema::hasTable('withdraw') || ! Schema::hasColumn('withdraw', 'withdraw_total_amount')) {
            return 0.0;
        }

        $query = DB::table('withdraw')
            ->where('withdraw_type', WithdrawSchema::TYPE_PROJECT)
            ->where('withdraw_status', 'approved');

        if ($excludeId > 0 && Schema::hasColumn('withdraw', 'withdraw_id')) {
            $query->where('withdraw_id', '!=', $excludeId);
        }

        $query->where(function ($inner) use ($projectId, $projectCode) {
            $matched = false;
            if ($projectId !== '' && Schema::hasColumn('withdraw', 'withdraw_project_id')) {
                $inner->orWhere('withdraw_project_id', $projectId);
                $matched = true;
            }
            if ($projectCode !== '' && Schema::hasColumn('withdraw', 'withdraw_project_code')) {
                $inner->orWhere('withdraw_project_code', $projectCode);
                $matched = true;
            }
            if (! $matched) {
                $inner->whereRaw('1 = 0');
            }
        });

        return (float) ($query->sum('withdraw_total_amount') ?? 0);
    }

    private function resolveWithdrawProjectName(object $row): string
    {
        if (Schema::hasColumn('withdraw', 'withdraw_project_name')) {
            $stored = trim((string) ($row->withdraw_project_name ?? ''));
            if ($stored !== '' && $stored !== '..........') {
                return $stored;
            }
        }

        $paragraph1 = trim((string) ($row->withdraw_paragraph_1 ?? ''));
        if ($paragraph1 !== '' && preg_match('/จัดโครงการ\s+(.+?)\s+รหัสโครงการ/u', $paragraph1, $matches) === 1) {
            $fromParagraph = trim((string) ($matches[1] ?? ''));
            if ($fromParagraph !== '' && $fromParagraph !== '..........') {
                return $fromParagraph;
            }
        }

        return '';
    }

    private function resolveWithdrawProjectActivityDetail(object $row): string
    {
        if (Schema::hasColumn('withdraw', 'withdraw_project_activity_detail')) {
            $stored = trim((string) ($row->withdraw_project_activity_detail ?? ''));
            if ($stored !== '') {
                return $stored;
            }
        }

        $paragraph2 = trim((string) ($row->withdraw_paragraph_2 ?? ''));
        if ($paragraph2 !== '') {
            foreach ([
                ['รายละเอียดการจัดกิจกรรม', 'รายละเอียดค่าใช้จ่ายทั้งสิ้น'],
                ['รายละเอียดการดำเนินการ', 'รายละเอียดค่าใช้จ่ายทั้งสิ้น'],
                ['รายละเอียดกิจกรรม', 'รายละเอียดค่าใช้จ่ายทั้งสิ้น'],
            ] as [$label, $nextLabel]) {
                $value = $this->extractLabeledMemoValue($paragraph2, $label, $nextLabel);
                if ($value !== '') {
                    return $value;
                }
            }

            $activityLabeled = $this->extractLineAnchoredMemoValue(
                $paragraph2,
                'กิจกรรม',
                ['รายละเอียดการดำเนินการ', 'รายละเอียดการจัดกิจกรรม', 'รายละเอียดกิจกรรม', 'รายละเอียดค่าใช้จ่ายทั้งสิ้น']
            );
            if ($activityLabeled !== '') {
                return $activityLabeled;
            }
        }

        // ไม่ fallback เป็นชื่อกิจกรรม — ใช้เฉพาะรายละเอียดการจัดกิจกรรมจาก API/คอลัมน์ที่เก็บไว้
        return '';
    }

    /**
     * @param  list<string>  $nextLabels
     */
    private function extractLineAnchoredMemoValue(string $text, string $label, array $nextLabels = []): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = preg_split("/\n/u", $normalized) ?: [];
        $startIndex = null;
        foreach ($lines as $index => $line) {
            $trimmed = trim((string) $line);
            if ($trimmed === $label || str_starts_with($trimmed, $label.' ') || str_starts_with($trimmed, $label."\t")) {
                $startIndex = $index;
                break;
            }
        }
        if ($startIndex === null) {
            return '';
        }

        $startLine = trim((string) $lines[$startIndex]);
        $parts = [];
        if ($startLine !== $label && str_starts_with($startLine, $label)) {
            $sameLine = trim((string) mb_substr($startLine, mb_strlen($label, 'UTF-8'), null, 'UTF-8'));
            if ($sameLine !== '') {
                $parts[] = $sameLine;
            }
        }

        for ($i = $startIndex + 1, $n = count($lines); $i < $n; $i++) {
            $trimmed = trim((string) $lines[$i]);
            if ($trimmed === '') {
                if ($parts !== []) {
                    break;
                }
                continue;
            }
            foreach ($nextLabels as $nextLabel) {
                if ($trimmed === $nextLabel || str_starts_with($trimmed, $nextLabel.' ') || str_starts_with($trimmed, $nextLabel."\t")) {
                    break 2;
                }
            }
            $parts[] = $trimmed;
        }

        $value = trim(implode(' ', $parts));
        if ($value === '' || $value === '..........') {
            return '';
        }

        return $value;
    }

    private function resolveWithdrawParagraph3(object $row): string
    {
        $stored = trim((string) ($row->withdraw_paragraph_3 ?? ''));
        $isProject = trim((string) ($row->withdraw_type ?? '')) === WithdrawSchema::TYPE_PROJECT;
        if (! $isProject) {
            return $stored;
        }

        $template = WithdrawMemoTemplate::resolve(WithdrawSchema::TYPE_PROJECT, 'default');
        $templateParagraph3 = trim((string) ($template['paragraph3'] ?? ''));
        if ($templateParagraph3 === '') {
            $templateParagraph3 = 'จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ';
        }

        // รายการเก่าที่สร้างจากค่า hardcode / เทมเพลตเดิม — ใช้ข้อความจากเทมเพลตปัจจุบัน
        if (
            $stored === ''
            || $stored === 'จึงเรียนมาเพื่อโปรดพิจารณา'
            || $stored === 'จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ'
        ) {
            return $templateParagraph3;
        }

        return $stored;
    }

    private function resolveWithdrawParagraph4(object $row): string
    {
        if (! Schema::hasColumn('withdraw', 'withdraw_paragraph_4')) {
            return '';
        }

        return trim((string) ($row->withdraw_paragraph_4 ?? ''));
    }

    private function extractLabeledMemoValue(string $text, string $label, string $nextLabel = ''): string
    {
        $start = mb_strpos($text, $label, 0, 'UTF-8');
        if ($start === false) {
            return '';
        }

        $from = $start + mb_strlen($label, 'UTF-8');
        $end = mb_strlen($text, 'UTF-8');
        if ($nextLabel !== '') {
            $nextIndex = mb_strpos($text, $nextLabel, $from, 'UTF-8');
            if ($nextIndex !== false) {
                $end = $nextIndex;
            }
        }

        $value = trim((string) mb_substr($text, $from, $end - $from, 'UTF-8'));
        if ($value === '' || $value === '..........') {
            return '';
        }

        return $value;
    }

    private function resolveWithdrawBudgetSourceName(object $row): string
    {
        $withdrawId = (int) ($row->withdraw_id ?? 0);
        $budgetManageId = 0;
        if (Schema::hasColumn('withdraw', 'withdraw_budget_manage_id')) {
            $budgetManageId = (int) ($row->withdraw_budget_manage_id ?? 0);
        }
        if (
            $budgetManageId <= 0
            && $withdrawId > 0
            && Schema::hasTable('withdraw_item')
            && Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_id')
        ) {
            $budgetManageId = (int) (DB::table('withdraw_item')
                ->where('withdraw_id', $withdrawId)
                ->orderBy('withdraw_item_order')
                ->orderBy('withdraw_item_id')
                ->value('withdraw_item_budget_manage_id') ?? 0);
        }
        if ($budgetManageId <= 0) {
            return '';
        }

        try {
            if (! Schema::connection('budget')->hasTable('budget_manage')) {
                return '';
            }

            $budgetRow = DB::connection('budget')
                ->table('budget_manage as bm')
                ->leftJoin('budget_set_source as bs', 'bm.budget_source_code', '=', 'bs.budget_source_code')
                ->leftJoin('budget_set_plan as bp', 'bm.budget_plan_code', '=', 'bp.budget_plan_code')
                ->leftJoin('budget_set_event as be', 'bm.budget_event_code', '=', 'be.budget_event_code')
                ->leftJoin('budget_set_activity as ba', 'bm.budget_activity_code', '=', 'ba.budget_activity_code')
                ->leftJoin('budget_set_fund as bf', 'bm.budget_fund_code', '=', 'bf.budget_fund_code')
                ->leftJoin('budget_set_category_budget as bcb', 'bm.budget_category_budget_code', '=', 'bcb.budget_category_budget_code')
                ->leftJoin('budget_set_category_pay as bcp', 'bm.budget_category_pay_code', '=', 'bcp.budget_category_pay_code')
                ->where('bm.budget_manage_id', $budgetManageId)
                ->first([
                    'bs.budget_source_name',
                    'bm.budget_manage_year',
                    'bm.budget_manage_code',
                    'bp.budget_plan_name',
                    'be.budget_event_name',
                    'ba.budget_activity_name',
                    'bf.budget_fund_name',
                    'bcb.budget_category_budget_name',
                    'bcp.budget_category_pay_name',
                    'bm.budget_category_pay_detail',
                ]);
            $activityName = trim((string) ($budgetRow->budget_activity_name ?? ''));
            $payDetail = trim((string) ($budgetRow->budget_category_pay_detail ?? ''));
            $parts = [
                trim(trim((string) ($budgetRow->budget_source_name ?? '')).'ปี '.trim((string) ($budgetRow->budget_manage_year ?? ''))),
                trim((string) ($budgetRow->budget_plan_name ?? '')),
                trim(trim((string) ($budgetRow->budget_event_name ?? '')).($activityName !== '' ? '-'.$activityName : '')),
                trim((string) ($budgetRow->budget_fund_name ?? '')),
                trim((string) ($budgetRow->budget_category_budget_name ?? '')),
                trim(trim((string) ($budgetRow->budget_category_pay_name ?? '')).($payDetail !== '' ? '-'.$payDetail : '')),
            ];
            $fullName = trim(implode(' ', array_values(array_filter($parts, static fn ($part) => $part !== ''))));
            if ($fullName !== '') {
                return $fullName;
            }

            return trim((string) ($budgetRow?->budget_manage_code ?? ''));
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Same as list column "เลขที่" / detail-modal fallback: append running no when present
     * (see fetchGeneralRows map + detail-modal.blade.php detailCodeEl).
     */
    private function resolveWithdrawGeneralDisplayDocumentCode(object $row): string
    {
        $withdrawType = trim((string) ($row->withdraw_type ?? ''));
        if ($withdrawType === WithdrawSchema::TYPE_PROJECT) {
            $this->ensureProjectWithdrawDocumentNumbers($row);
        }

        $documentMeta = $this->resolveWithdrawGeneralDocumentMeta($row);
        $documentNumber = trim((string) ($documentMeta['document_number'] ?? ''));
        $runningNo = (int) ($documentMeta['running_no'] ?? 0);
        $displayDocumentNumber = $documentNumber;
        if ($documentNumber !== '' && $runningNo > 0) {
            $displayDocumentNumber = $documentNumber.$runningNo;
        }

        return $displayDocumentNumber;
    }

    public function ensureIssuedProjectProcurementNumbers(int $withdrawId): void
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw')) {
            return;
        }

        $row = DB::table('withdraw')->where('withdraw_id', $withdrawId)->first();
        if ($row === null) {
            return;
        }

        $this->ensureProjectWithdrawDocumentNumbers($row);
    }

    /**
     * ซ่อมเลขที่หนังสือของเบิกจ่ายโครงการที่ดำเนินการแล้วแต่ยังไม่ถูกออกเลข
     * (กรณี bug เดิมที่ assign เป็น type general)
     */
    private function ensureProjectWithdrawDocumentNumbers(object $row): void
    {
        $withdrawId = (int) ($row->withdraw_id ?? 0);
        $withdrawType = trim((string) ($row->withdraw_type ?? ''));
        if ($withdrawId <= 0 || $withdrawType !== WithdrawSchema::TYPE_PROJECT) {
            return;
        }

        $status = strtolower(trim((string) ($row->withdraw_status ?? '')));
        if (in_array($status, ['', 'saved', 'draft', 'cancelled'], true)) {
            return;
        }

        $documentMeta = $this->resolveWithdrawDocumentMetaByType($withdrawId, $withdrawType);
        $needsMemoRunning = (int) ($documentMeta['running_no'] ?? 0) <= 0
            && trim((string) ($documentMeta['document_number'] ?? '')) !== '';

        if ($needsMemoRunning) {
            $this->assignWithdrawDocumentNumber(
                $withdrawId,
                $withdrawType,
                (string) ($documentMeta['department_code'] ?? ''),
                (string) ($documentMeta['document_number'] ?? ''),
                (string) ($documentMeta['document_date'] ?? ''),
                (int) ($documentMeta['running_year'] ?? 0),
                (int) ($documentMeta['running_no'] ?? 0)
            );
            unset($this->withdrawGeneralDocumentMetaCache[$withdrawType.':'.$withdrawId]);
            $documentMeta = $this->resolveWithdrawDocumentMetaByType($withdrawId, $withdrawType);
        }

        if (trim((string) ($documentMeta['document_number'] ?? '')) !== '') {
            $this->assignProjectProcurementDocumentNumbers($withdrawId, $documentMeta);
        }
    }

    /**
     * @return array{department_code:string,document_number:string,document_date:string,running_year:int,running_no:int}
     */
    private function resolveWithdrawDocumentMetaByType(int $withdrawId, string $withdrawType = 'general'): array
    {
        $normalizedWithdrawType = trim($withdrawType) !== '' ? trim($withdrawType) : 'general';
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

        if (array_key_exists($cacheKey, $this->withdrawGeneralDocumentMetaCache)) {
            return $this->withdrawGeneralDocumentMetaCache[$cacheKey];
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
            $this->withdrawGeneralDocumentMetaCache[$cacheKey] = $fallback;
            return $fallback;
        }

        $resolved = [
            'department_code' => trim((string) ($documentRow->department_code ?? '')),
            'document_number' => trim((string) ($documentRow->document_number ?? '')),
            'document_date' => trim((string) ($documentRow->document_date ?? '')),
            'running_year' => (int) ($documentRow->running_year ?? 0),
            'running_no' => (int) ($documentRow->running_no ?? 0),
        ];

        $this->withdrawGeneralDocumentMetaCache[$cacheKey] = $resolved;
        return $resolved;
    }

    /**
     * @return array{department_code:string,document_number:string,document_date:string,running_year:int,running_no:int}
     */
    private function resolveWithdrawGeneralDocumentMeta(object $row): array
    {
        $withdrawId = (int) ($row->withdraw_id ?? 0);
        $type = trim((string) ($row->withdraw_type ?? 'general'));
        if ($type === '') {
            $type = 'general';
        }

        return $this->resolveWithdrawDocumentMetaByType($withdrawId, $type);
    }

    private function currentWithdrawType(): string
    {
        $fromRoute = trim((string) (request()?->route('withdraw_kind') ?? ''));
        if ($fromRoute === WithdrawSchema::TYPE_PROJECT) {
            return WithdrawSchema::TYPE_PROJECT;
        }

        $fromQuery = trim((string) (request()?->query('withdraw_type') ?? request()?->input('withdraw_type', '')));
        if ($fromQuery === WithdrawSchema::TYPE_PROJECT) {
            return WithdrawSchema::TYPE_PROJECT;
        }

        return WithdrawSchema::TYPE_GENERAL;
    }

    private function resolveWithdrawTypeFromRow(object $row): string
    {
        $type = trim((string) ($row->withdraw_type ?? ''));

        return $type === WithdrawSchema::TYPE_PROJECT
            ? WithdrawSchema::TYPE_PROJECT
            : WithdrawSchema::TYPE_GENERAL;
    }

    private function resolveWithdrawTypeLabel(string $withdrawType): string
    {
        return $withdrawType === WithdrawSchema::TYPE_PROJECT
            ? 'เบิกจ่ายเงินโครงการ'
            : 'เบิกจ่ายทั่วไป';
    }

    /**
     * Applicant-facing list/edit URL for notifications (email / in-app).
     */
    private function resolveApplicantWithdrawUrl(object|string $rowOrType, int $withdrawId = 0): string
    {
        $type = is_object($rowOrType)
            ? $this->resolveWithdrawTypeFromRow($rowOrType)
            : (
                trim((string) $rowOrType) === WithdrawSchema::TYPE_PROJECT
                    ? WithdrawSchema::TYPE_PROJECT
                    : WithdrawSchema::TYPE_GENERAL
            );
        $path = $type === WithdrawSchema::TYPE_PROJECT
            ? '/withdraw/project'
            : '/withdraw/general';

        if ($withdrawId > 0) {
            return url($path).'?'.http_build_query(['edit_id' => $withdrawId]);
        }

        return url($path);
    }

    private function withdrawQuery(?string $type = null)
    {
        $query = DB::table('withdraw');
        $resolved = is_string($type) ? trim($type) : '';
        if ($resolved === WithdrawSchema::TYPE_GENERAL || $resolved === WithdrawSchema::TYPE_PROJECT) {
            return $query->where('withdraw_type', $resolved);
        }

        return $query->whereIn('withdraw_type', [
            WithdrawSchema::TYPE_GENERAL,
            WithdrawSchema::TYPE_PROJECT,
        ]);
    }

    private function fetchGeneralRows(string $scope = '', string $currentStaffId = '', string $type = 'general')
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

        $rowsQuery = $this->withdrawQuery($type)
            ->orderByDesc('withdraw_id')
            ->limit(200);

        $isApprovalScope = in_array($scope, ['reviewer-pending', 'document-review'], true);
        if ($scope === 'document-review') {
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

            $rowsQuery
                ->whereIn('withdraw_status', [
                    'pending_primary_review',
                    'processing',
                    'pending_additional_review',
                    'pending_finance_review',
                    'pending_approval',
                    'approved',
                ])
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
                                                ->where(function ($statusQuery) {
                                                    $statusQuery
                                                        ->whereNull('prev_step.withdraw_approval_status')
                                                        ->orWhere('prev_step.withdraw_approval_status', '!=', 'approved');
                                                });
                                        });
                                });
                        });
                });
        }

        $rows = $rowsQuery->get();
        $userStepStatusMap = collect();
        $userStepNameMap = collect();

        if ($scope === 'document-review' && $rows->count() > 0) {
            $generalIds = collect($rows)
                ->pluck('withdraw_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $financeOfficerPendingMap = collect();
            if ($generalIds !== [] && Schema::hasTable('withdraw_approval_step')) {
                $financeOfficerPendingMap = DB::table('withdraw_approval_step')
                    ->whereIn('withdraw_id', $generalIds)
                    ->where('withdraw_approval_step_code', 'finance_officer')
                    ->where('withdraw_approval_status', 'pending')
                    ->pluck('withdraw_id')
                    ->map(fn ($id) => (int) $id)
                    ->flip();
            }

            $userStepStatusMap = collect($rows)
                ->keyBy(function ($row) {
                    return (int) ($row->withdraw_id ?? 0);
                })
                ->map(function ($row) use ($financeOfficerPendingMap) {
                    $id = (int) ($row->withdraw_id ?? 0);
                    $status = trim((string) ($row->withdraw_status ?? ''));
                    if ($status === 'pending_finance_review') {
                        return 'pending';
                    }
                    // กรณีข้ามสถานะแต่ยังค้างที่งานการเงิน — แสดงในคิวตรวจสอบเอกสาร
                    if ($status === 'pending_approval' && $financeOfficerPendingMap->has($id)) {
                        return 'pending';
                    }

                    return 'approved';
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

        $financeEsignCountMap = collect();
        if (Schema::hasTable('withdraw_file') && $rows->count() > 0) {
            $allWithdrawIds = collect($rows)
                ->pluck('withdraw_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
            $financeEsignCountMap = $this->buildFinanceEsignCountMap($allWithdrawIds);
        }

        $procurementCountMap = collect();
        $procurementTypeMap = collect();
        if ($type === WithdrawSchema::TYPE_PROJECT && Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_TABLE) && $rows->count() > 0) {
            $projectWithdrawIds = collect($rows)
                ->pluck('withdraw_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
            if ($projectWithdrawIds !== [] && Schema::hasColumn(WithdrawSchema::PROJECT_PROCUREMENT_TABLE, 'withdraw_project_procurement_type')) {
                $procurementRows = DB::table(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)
                    ->whereIn('withdraw_id', $projectWithdrawIds)
                    ->get(['withdraw_id', 'withdraw_project_procurement_type']);
                $procurementCountMap = $procurementRows
                    ->groupBy(fn ($procurementRow) => (int) ($procurementRow->withdraw_id ?? 0))
                    ->map(fn ($group) => $group->count());
                $procurementTypeMap = $procurementRows
                    ->groupBy(fn ($procurementRow) => (int) ($procurementRow->withdraw_id ?? 0))
                    ->map(function ($group) {
                        $types = $group
                            ->map(fn ($procurementRow) => trim((string) ($procurementRow->withdraw_project_procurement_type ?? '')))
                            ->filter(fn ($procurementType) => in_array($procurementType, ['w119', 'eight'], true))
                            ->unique()
                            ->values()
                            ->all();
                        usort($types, static function (string $left, string $right): int {
                            $order = ['w119' => 0, 'eight' => 1];

                            return ($order[$left] ?? 9) <=> ($order[$right] ?? 9);
                        });

                        return $types;
                    });
            }
        }

        $revisionCommentMap = collect();
        $executiveRevisionMap = collect();
        $approvalStepsByWithdraw = collect();
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
                $approvalStepsByWithdraw = DB::table('withdraw_approval_step')
                    ->whereIn('withdraw_id', $generalIds)
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

        return collect($rows)
            ->map(function ($row) use ($documentCountMap, $financeEsignCountMap, $procurementCountMap, $procurementTypeMap, $applicantNameMap, $userStepStatusMap, $userStepNameMap, $revisionCommentMap, $executiveRevisionMap, $approvalStepsByWithdraw, $type) {
                $rowId = (int) ($row->withdraw_id ?? 0);
                $documentCount = (int) ($documentCountMap[$rowId] ?? 0);
                $rawStatus = trim((string) ($row->withdraw_status ?? 'saved'));
                $isExecutiveRevision = $rawStatus === 'pending_finance_review' && (bool) ($executiveRevisionMap[$rowId] ?? false);
                $statusLabel = $isExecutiveRevision ? 'รอการเงินตรวจสอบเอกสาร (แก้ไข)' : $this->mapWithdrawStatusLabel($rawStatus);

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
                    'project_name' => $this->resolveWithdrawProjectName($row),
                    'project_id' => Schema::hasColumn('withdraw', 'withdraw_project_id')
                        ? trim((string) ($row->withdraw_project_id ?? ''))
                        : '',
                    'project_code' => Schema::hasColumn('withdraw', 'withdraw_project_code')
                        ? trim((string) ($row->withdraw_project_code ?? ''))
                        : '',
                    'applicant_staff_id' => $applicantStaffId,
                    'manager' => $applicantName !== '' ? $applicantName : ($applicantStaffId !== '' ? $applicantStaffId : '-'),
                    'date' => $thaiDate !== '' ? $thaiDate : '-',
                    'document_month' => $documentMonth,
                    'fiscal_year' => (int) ($row->withdraw_fiscal_year ?? 0),
                    'amount' => (float) ($row->withdraw_total_amount ?? 0),
                    'raw_status' => $rawStatus,
                    'status' => $statusLabel,
                    'user_step_status' => (string) ($userStepStatusMap[$rowId] ?? ''),
                    'user_step_name' => (string) ($userStepNameMap[$rowId] ?? ''),
                    'withdraw_type' => $type,
                    'revision_comment' => (string) ($revisionCommentMap[$rowId] ?? ''),
                    'type' => $type === WithdrawSchema::TYPE_PROJECT ? 'เบิกจ่ายเงินโครงการ' : 'เบิกจ่ายทั่วไป',
                    'document_count' => $documentCount,
                    'finance_esign_count' => (int) ($financeEsignCountMap[$rowId] ?? 0),
                    'procurement_count' => (int) ($procurementCountMap[$rowId] ?? 0),
                    'procurement_types' => array_values($procurementTypeMap[$rowId] ?? []),
                    'approval_steps' => $approvalStepsByWithdraw->get($rowId)
                        ?? $approvalStepsByWithdraw->get((string) $rowId)
                        ?? [],
                ];
            });
    }

    /**
     * @param  array<int, int|string>  $withdrawIds
     */
    private function buildFinanceEsignCountMap(array $withdrawIds): \Illuminate\Support\Collection
    {
        $withdrawIds = array_values(array_filter(
            array_map(fn ($id) => (int) $id, $withdrawIds),
            fn ($id) => $id > 0
        ));
        if ($withdrawIds === [] || ! Schema::hasTable('withdraw_file')) {
            return collect();
        }

        $typeCodes = EsignAttachedPdf::financeEsignTypeCodes();
        if ($typeCodes === []) {
            return collect();
        }

        return DB::table('withdraw_file')
            ->whereIn('withdraw_id', $withdrawIds)
            ->whereIn('withdraw_file_type_code', $typeCodes)
            ->selectRaw('withdraw_id, COUNT(*) as total')
            ->groupBy('withdraw_id')
            ->pluck('total', 'withdraw_id');
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
        $expenseChartDetailMap = [];
        if (count($expenseChartIds) > 0 && Schema::hasTable('withdraw_set_expenses')) {
            $expenseRows = DB::table('withdraw_set_expenses')
                ->whereIn('withdraw_set_expenses_id', $expenseChartIds)
                ->get([
                    'withdraw_set_expenses_id as id',
                    'withdraw_set_expenses_name as name',
                    'withdraw_set_expenses_detail as detail',
                ]);
            foreach ($expenseRows as $expenseRow) {
                $expenseId = (int) ($expenseRow->id ?? 0);
                if ($expenseId <= 0) {
                    continue;
                }
                $expenseChartNameMap[$expenseId] = trim((string) ($expenseRow->name ?? ''));
                $expenseChartDetailMap[$expenseId] = trim((string) ($expenseRow->detail ?? ''));
            }
        }

        return collect($rows)
            ->map(function ($item) use ($categoryNameMap, $expenseChartNameMap, $expenseChartDetailMap) {
                $rawCategory = trim((string) ($item->category ?? ''));
                $resolvedCategoryName = $rawCategory !== '' ? trim((string) ($categoryNameMap[$rawCategory] ?? '')) : '';
                $expenseChartId = (int) ($item->expense_chart_id ?? 0);
                $expenseChartName = $expenseChartId > 0 ? (string) ($expenseChartNameMap[$expenseChartId] ?? '') : '';
                $expenseChartDetail = $expenseChartId > 0 ? (string) ($expenseChartDetailMap[$expenseChartId] ?? '') : '';
                $financeSelectedName = $expenseChartName !== ''
                    ? ($expenseChartDetail !== '' && $expenseChartDetail !== '-'
                        ? $expenseChartName.' ('.$expenseChartDetail.')'
                        : $expenseChartName)
                    : '';

                return [
                    'id' => (int) ($item->id ?? 0),
                    'category_code' => $rawCategory,
                    'category' => $resolvedCategoryName !== '' ? $resolvedCategoryName : $rawCategory,
                    'name' => (string) ($item->name ?? ''),
                    'amount' => (float) ($item->amount ?? 0),
                    'finance_amount' => $item->finance_amount !== null ? (float) $item->finance_amount : null,
                    'expense_chart_id' => $expenseChartId,
                    'expense_chart_name' => $financeSelectedName !== '' ? $financeSelectedName : $expenseChartName,
                    'budget_manage_id' => (int) ($item->budget_manage_id ?? 0),
                    'budget_manage_code' => trim((string) ($item->budget_manage_code ?? '')),
                ];
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

        $base = DB::table('withdraw_document');
        $base = $this->applyWithdrawDocumentTypeSequenceFilter($base, $types);
        $base = $base
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
        $fromFinanceType = $this->computeMaxRunningNoForFinanceSequenceKey(['general_finance'], $departmentCode, $documentKey);
        $hasGeneralFinanceRows = DB::table('withdraw_document')
            ->where('withdraw_document_type', 'general_finance')
            ->exists();
        if ($hasGeneralFinanceRows) {
            return $fromFinanceType;
        }

        return max(
            $fromFinanceType,
            $this->computeMaxRunningNoForFinanceSequenceKey(['general'], $departmentCode, $documentKey)
        );
    }

    /**
     * ประเภท withdraw ที่ใช้ลำดับเลขรันร่วมกันตามหน่วย + คำนำหน้าเลขที่
     * (เบิกจ่ายทั่วไป / เงินเดือน / พตส. ใช้สมุดเลขชุดเดียวกัน)
     *
     * @return array<int, string>
     */
    private function documentNumberSharedWithdrawTypes(string $withdrawType = 'general'): array
    {
        $normalized = trim($withdrawType) !== '' ? trim($withdrawType) : 'general';
        if (str_starts_with($normalized, 'project_procurement:')) {
            $normalized = WithdrawSchema::TYPE_PROJECT;
        }

        return match ($normalized) {
            'general', 'general_finance', 'project', 'project_finance', 'salary', 'salary_finance', 'salary_pts'
                => ['general', 'general_finance', 'project', 'project_finance', 'salary', 'salary_finance', 'salary_pts'],
            default => [$normalized],
        };
    }

    /**
     * @param  array<int, string>  $withdrawDocumentTypes
     */
    private function applyWithdrawDocumentTypeSequenceFilter($query, array $withdrawDocumentTypes)
    {
        $types = array_values(array_filter(array_map('trim', $withdrawDocumentTypes), static fn ($t) => $t !== ''));
        $includeProjectProcurement = count(array_intersect($types, [
            'general',
            'general_finance',
            'project',
            'project_finance',
            'salary',
            'salary_finance',
            'salary_pts',
        ])) > 0;

        return $query->where(function ($inner) use ($types, $includeProjectProcurement) {
            if ($types !== []) {
                $inner->whereIn('withdraw_document_type', $types);
            }
            if ($includeProjectProcurement) {
                $method = $types !== [] ? 'orWhere' : 'where';
                $inner->{$method}('withdraw_document_type', 'like', 'project_procurement:%');
            }
        });
    }

    /**
     * Next running number for withdraw_document: unique per department + document_number prefix,
     * shared across general and salary withdraw types so the same book does not restart at 1.
     *
     * @return array{0:int,1:int}
     */
    private function nextRunningNumber(
        string $departmentCode = '',
        string $withdrawType = 'general',
        string $documentDate = '',
        string $documentNumber = ''
    ): array {
        $year = $this->resolveDocumentRunningYear($documentDate);
        $normalizedDepartmentCode = trim($departmentCode);
        $normalizedWithdrawType = trim($withdrawType) !== '' ? trim($withdrawType) : 'general';
        $normalizedDocumentNumber = trim($documentNumber);
        if ($normalizedDocumentNumber !== '') {
            $normalizedDocumentNumber = $this->normalizeGeneralFinanceDocumentNumberForStorage($normalizedDocumentNumber);
        }

        if (! Schema::hasTable('withdraw_document')) {
            return [$year, 1];
        }

        $sharedTypes = $this->documentNumberSharedWithdrawTypes($normalizedWithdrawType);

        // Sequence is per (department, document_number) across shared types. Do not filter max()
        // by running_year: drafts may have running_year = 0 while other types already issued numbers.
        if ($normalizedDocumentNumber !== '') {
            $latestNo = $this->computeMaxRunningNoForFinanceSequenceKey(
                $sharedTypes,
                $normalizedDepartmentCode,
                $normalizedDocumentNumber
            );
        } else {
            $latestNoQuery = DB::table('withdraw_document');
            $latestNoQuery = $this->applyWithdrawDocumentTypeSequenceFilter($latestNoQuery, $sharedTypes);
            $latestNo = (int) ($latestNoQuery
                ->when($normalizedDepartmentCode !== '', function ($query) use ($normalizedDepartmentCode) {
                    $query->where(function ($inner) use ($normalizedDepartmentCode) {
                        $inner->whereRaw('TRIM(COALESCE(department_code, \'\')) = ?', [$normalizedDepartmentCode])
                            ->orWhereNull('department_code')
                            ->orWhereRaw("TRIM(COALESCE(department_code, '')) = ''");
                    });
                }, function ($query) {
                    $query->where(function ($innerQuery) {
                        $innerQuery->whereNull('department_code')
                            ->orWhereRaw("TRIM(COALESCE(department_code, '')) = ''");
                    });
                })
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
        $normalizedWithdrawType = trim($withdrawType) !== '' ? trim($withdrawType) : 'general';
        $normalizedDepartmentCode = trim($departmentCode);
        $normalizedDocumentNumber = trim($documentNumber);
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

        foreach (array_keys($this->withdrawGeneralDocumentMetaCache) as $cacheKey) {
            if (str_ends_with($cacheKey, ':'.$withdrawId)) {
                unset($this->withdrawGeneralDocumentMetaCache[$cacheKey]);
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
        $normalizedWithdrawType = trim($withdrawType) !== '' ? trim($withdrawType) : 'general';
        $normalizedDepartmentCode = trim($departmentCode);
        $normalizedDocumentNumber = trim($documentNumber);
        if ($normalizedWithdrawType === 'general_finance' && $normalizedDocumentNumber !== '') {
            $normalizedDocumentNumber = $this->normalizeGeneralFinanceDocumentNumberForStorage($normalizedDocumentNumber);
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

        foreach (array_keys($this->withdrawGeneralDocumentMetaCache) as $cacheKey) {
            if (str_ends_with($cacheKey, ':'.$withdrawId)) {
                unset($this->withdrawGeneralDocumentMetaCache[$cacheKey]);
            }
        }
        return $assigned;
    }

    /**
     * ออกเลขที่หนังสือให้เอกสารจัดซื้อจัดจ้างทุกรายการของเบิกจ่าย (ทั่วไป/โครงการ)
     * (รันลำดับร่วมกับบันทึกข้อความตามหน่วยงาน + คำนำหน้าเลขที่)
     *
     * @param  array{department_code?:string,document_number?:string,document_date?:string}  $documentMeta
     */
    private function assignProjectProcurementDocumentNumbers(int $withdrawId, array $documentMeta): void
    {
        if ($withdrawId <= 0 || ! Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)) {
            return;
        }

        $departmentCode = trim((string) ($documentMeta['department_code'] ?? ''));
        $memoDocumentNumber = trim((string) ($documentMeta['document_number'] ?? ''));
        $memoDocumentDate = trim((string) ($documentMeta['document_date'] ?? ''));

        $rows = DB::table(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)
            ->where('withdraw_id', $withdrawId)
            ->orderBy('withdraw_project_procurement_id')
            ->get([
                'withdraw_project_procurement_id',
                'withdraw_project_procurement_type',
                'withdraw_project_procurement_payload',
            ]);

        foreach ($rows as $row) {
            $procurementId = (int) ($row->withdraw_project_procurement_id ?? 0);
            if ($procurementId <= 0) {
                continue;
            }

            $payload = [];
            $rawPayload = $row->withdraw_project_procurement_payload ?? null;
            if (is_string($rawPayload) && $rawPayload !== '') {
                $decoded = json_decode($rawPayload, true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            } elseif (is_array($rawPayload)) {
                $payload = $rawPayload;
            }

            $payloadPrefix = $this->normalizeGeneralFinanceDocumentNumberForStorage(
                trim((string) ($payload['document_number'] ?? ''))
            );
            $documentNumber = $payloadPrefix !== '' ? $payloadPrefix : $memoDocumentNumber;
            $documentDate = trim((string) ($payload['document_date'] ?? ''));
            if ($documentDate === '') {
                $documentDate = $memoDocumentDate;
            }

            $documentType = 'project_procurement:'.$procurementId;
            $existingMeta = $this->resolveWithdrawDocumentMetaByType($withdrawId, $documentType);
            [$runningYear, $runningNo] = $this->assignWithdrawDocumentNumber(
                $withdrawId,
                $documentType,
                $departmentCode,
                $documentNumber,
                $documentDate,
                (int) ($existingMeta['running_year'] ?? 0),
                (int) ($existingMeta['running_no'] ?? 0)
            );

            if ($documentNumber !== '') {
                $payload['document_number'] = $documentNumber;
            }
            if ($documentDate !== '') {
                $payload['document_date'] = $documentDate;
            }
            $payload['running_no'] = $runningNo;
            $payload['running_year'] = $runningYear;
            $firstDisplay = $documentNumber !== '' && $runningNo > 0
                ? $documentNumber.$runningNo
                : $documentNumber;
            $payload['document_number_display'] = $firstDisplay;

            $procurementType = trim((string) ($row->withdraw_project_procurement_type ?? ($payload['type'] ?? '')));
            if ($procurementType === 'eight' && $documentNumber !== '' && $runningNo > 0) {
                $considerType = 'project_procurement:'.$procurementId.':consider';
                $existingConsider = $this->resolveWithdrawDocumentMetaByType($withdrawId, $considerType);
                [$considerYear, $considerRunning] = $this->assignWithdrawDocumentNumber(
                    $withdrawId,
                    $considerType,
                    $departmentCode,
                    $documentNumber,
                    $documentDate,
                    (int) ($existingConsider['running_year'] ?? 0),
                    (int) ($existingConsider['running_no'] ?? 0)
                );
                $considerDisplay = $considerRunning > 0 ? $documentNumber.$considerRunning : '';
                if ($considerDisplay !== '') {
                    $payload['consider_number'] = $considerDisplay;
                    $payload['consider_running_no'] = $considerRunning;
                    $payload['consider_running_year'] = $considerYear;
                    $payload['consider_ref_number'] = $firstDisplay;
                }
            }

            DB::table(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)
                ->where('withdraw_project_procurement_id', $procurementId)
                ->update([
                    'withdraw_project_procurement_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'withdraw_project_procurement_date_modify' => now(),
                ]);
        }
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
                        ? route('withdraw.general.api.file', [
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

        $rows = DB::table('withdraw_approval_step')
            ->where('withdraw_id', $generalId)
            ->orderBy('withdraw_approval_step_order')
            ->orderBy('withdraw_approval_step_id')
            ->get([
                'withdraw_approval_step_code as step_code',
                'withdraw_approval_step_name as step_name',
                'withdraw_approval_step_order as step_order',
                'withdraw_approval_staff_id as staff_id',
                'withdraw_approval_custom_status as custom_status',
                'withdraw_approval_status as status',
                'withdraw_approval_comment as comment',
                'withdraw_approval_approved_at as approved_at',
            ]);

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
            ];
        })->values()->all();
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
    private function syncApprovalSteps(int $generalId, string $reviewerStaffId, array $processActors, string $staffId = ''): void
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
        $routeKind = $this->currentWithdrawType();
        $routeSteps = WithdrawMemoTemplate::resolveApprovalRouteSteps($routeKind, 'default', $generalTotalAmount);

        foreach ($routeSteps as $routeStep) {
            $stepCode = (string) ($routeStep['code'] ?? '');
            if ($stepCode === '') {
                continue;
            }

            if ($stepCode === 'reviewer_head') {
                $rows[] = [
                    'withdraw_id' => $generalId,
                    'withdraw_approval_step_code' => 'reviewer_head',
                    'withdraw_approval_step_name' => (string) ($routeStep['name'] ?? 'ผู้ตรวจสอบ/หัวหน้างาน'),
                    'withdraw_approval_step_order' => (int) ($routeStep['order'] ?? 10),
                    'withdraw_approval_staff_id' => trim($reviewerStaffId) !== '' ? trim($reviewerStaffId) : null,
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
                continue;
            }

            $rows[] = [
                'withdraw_id' => $generalId,
                'withdraw_approval_step_code' => $stepCode,
                'withdraw_approval_step_name' => (string) ($routeStep['name'] ?? $stepCode),
                'withdraw_approval_step_order' => (int) ($routeStep['order'] ?? 0),
                'withdraw_approval_staff_id' => (string) ($approverStaffByStepCode[$stepCode] ?? '') !== ''
                    ? (string) ($approverStaffByStepCode[$stepCode] ?? '')
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

        // ผู้ตรวจสอบ/อนุมัติ เพิ่มเติม: หลัง reviewer_head (10) และก่อน finance_officer (40)
        $additionalRows = [];
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
            $additionalRows[] = [
                'withdraw_id' => $generalId,
                'withdraw_approval_step_code' => 'additional_reviewer',
                'withdraw_approval_step_name' => 'ผู้ตรวจสอบ/อนุมัติ เพิ่มเติม',
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

        if ($additionalRows !== []) {
            $merged = [];
            $additionalInserted = false;
            foreach ($rows as $row) {
                $merged[] = $row;
                if (($row['withdraw_approval_step_code'] ?? '') === 'reviewer_head') {
                    foreach ($additionalRows as $additionalRow) {
                        $merged[] = $additionalRow;
                    }
                    $additionalInserted = true;
                }
            }
            if (! $additionalInserted) {
                // ไม่มีหัวหน้างาน: แทรกก่อนขั้นตอนการเงิน/อนุมัติอื่นตาม step_order
                $merged = array_merge($additionalRows, $rows);
            }
            $rows = $merged;
            usort($rows, static function (array $left, array $right): int {
                $leftOrder = (int) ($left['withdraw_approval_step_order'] ?? 0);
                $rightOrder = (int) ($right['withdraw_approval_step_order'] ?? 0);
                if ($leftOrder === $rightOrder) {
                    return strcmp(
                        (string) ($left['withdraw_approval_step_code'] ?? ''),
                        (string) ($right['withdraw_approval_step_code'] ?? '')
                    );
                }

                return $leftOrder <=> $rightOrder;
            });
        }

        $existingRows = DB::table('withdraw_approval_step')
            ->where('withdraw_id', $generalId)
            ->orderBy('withdraw_approval_step_order')
            ->orderBy('withdraw_approval_step_id')
            ->get(['withdraw_approval_step_id', 'withdraw_approval_status'])
            ->values();

        foreach ($rows as $index => $row) {
            $existing = $existingRows->get($index);
            if ($existing) {
                DB::table('withdraw_approval_step')
                    ->where('withdraw_approval_step_id', (int) $existing->withdraw_approval_step_id)
                    ->update([
                        'withdraw_approval_step_code' => (string) ($row['withdraw_approval_step_code'] ?? ''),
                        'withdraw_approval_step_name' => (string) ($row['withdraw_approval_step_name'] ?? ''),
                        'withdraw_approval_step_order' => (int) ($row['withdraw_approval_step_order'] ?? 0),
                        'withdraw_approval_staff_id' => $row['withdraw_approval_staff_id'] ?? null,
                        'withdraw_approval_custom_status' => $row['withdraw_approval_custom_status'] ?? null,
                        'withdraw_approval_date_modify' => $now,
                        'staff_modify' => $staffId,
                    ]);
            } else {
                DB::table('withdraw_approval_step')->insert($row);
            }
        }

        if ($existingRows->count() > count($rows)) {
            $deleteIds = $existingRows
                ->slice(count($rows))
                ->filter(fn ($row) => trim((string) ($row->withdraw_approval_status ?? 'pending')) === 'pending')
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
        }
    }

    /**
     * @param array<int, mixed> $items
     */
    private function syncGeneralItems(int $generalId, array $items, string $staffId = ''): void
    {
        if ($generalId <= 0 || ! Schema::hasTable('withdraw_item')) {
            return;
        }

        $rows = collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function ($item) {
                return [
                    'category' => $this->normalizeItemCategoryCode((string) ($item['category'] ?? $item['category_code'] ?? '')),
                    'name' => trim((string) ($item['name'] ?? '')),
                    'amount' => (float) ($item['amount'] ?? 0),
                    'budget_manage_id' => (int) ($item['budget_manage_id'] ?? 0),
                    'budget_manage_code' => trim((string) ($item['budget_manage_code'] ?? '')),
                ];
            })
            ->values()
            ->all();

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
            if (Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_id')) {
                $itemBudgetId = (int) ($row['budget_manage_id'] ?? 0);
                $payload['withdraw_item_budget_manage_id'] = $itemBudgetId > 0 ? $itemBudgetId : null;
            }
            if (Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_code')) {
                $itemBudgetCode = trim((string) ($row['budget_manage_code'] ?? ''));
                if ($itemBudgetCode === '' && (int) ($row['budget_manage_id'] ?? 0) > 0) {
                    $itemBudgetCode = WithdrawSchema::budgetManageCodeById((int) $row['budget_manage_id']);
                }
                $payload['withdraw_item_budget_manage_code'] = $itemBudgetCode !== '' ? $itemBudgetCode : null;
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
     *
     * @return int จำนวนไฟล์ที่บันทึกสำเร็จ
     */
    private function storeFinanceEsignUploads(Request $request, int $withdrawId, string $staffId = '', string $storageKind = 'general'): int
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

        $storageKind = trim($storageKind) !== '' ? trim($storageKind) : 'general';
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
     * @return array{0:string,1:string,2:string} [typeCode, itemCode, itemLabel]
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
     * Signature blocks after ผู้ขอเบิก for memo PDF:
     * หัวหน้างาน → additional_reviewer (ถ้ามี) → ผู้ตรวจสอบ (+ลายเซ็นห้อยหัวหน้างานการเงิน/เลขาคณะ) → ผู้อนุมัติ
     *
     * @param  array<string, mixed>  $detail
     * @param  array<string, string>  $staffNameMap
     * @param  array<string, string>  $staffPositionMap
     * @param  array<string, string>  $signatureMap
     * @return list<array{show_sig: bool, sig_uri: string, status_line: string, name: string, position: string, role: string, hanging_signers?: list<array<string, mixed>>}>
     */
    private function buildMemoAdditionalSignerCards(
        array $detail,
        array $staffNameMap,
        array $staffPositionMap,
        array $signatureMap = []
    ): array {
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
        usort($additionalSteps, static function (array $left, array $right): int {
            return ((int) ($left['step_order'] ?? 0)) <=> ((int) ($right['step_order'] ?? 0));
        });

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

        [$bearerHeaderToken, $rawBearerToken] = $this->resolveStaffApiTokens();

        $makeCardFromStep = function (?array $step, string $role, bool $useStepNameAsPosition = false) use (
            $staffNameMap,
            $staffPositionMap,
            $signatureMap,
            $actorByStaffId,
            $bearerHeaderToken,
            $rawBearerToken
        ): ?array {
            if (! is_array($step)) {
                return null;
            }
            $staffId = trim((string) ($step['staff_id'] ?? ''));
            $isApproved = strtolower(trim((string) ($step['status'] ?? ''))) === 'approved';
            $actor = $actorByStaffId[$staffId] ?? [];
            $sigUri = '';
            if ($isApproved && $staffId !== '') {
                $sigUri = $this->resolveMemoPdfSignerDataUri(
                    $staffId,
                    $signatureMap,
                    $bearerHeaderToken,
                    $rawBearerToken
                );
                if ($sigUri === '') {
                    $sigUri = trim((string) ($actor['signature_base64'] ?? $actor['signature_url'] ?? $signatureMap[$staffId] ?? ''));
                }
            }
            if ($sigUri !== '' && str_starts_with($sigUri, 'data:image/')) {
                $embedded = $this->persistSignatureDataUriForMpdf($sigUri);
                if ($embedded !== '') {
                    $sigUri = $embedded;
                }
            }
            $showSig = $sigUri !== '' && ! str_starts_with($sigUri, 'data:image/svg+xml');
            $name = trim((string) ($step['staff_name'] ?? ''));
            if ($name === '') {
                $name = trim((string) ($staffNameMap[$staffId] ?? ''));
            }
            if ($name === '') {
                $name = $staffId !== '' ? $staffId : '-';
            }
            $position = '';
            if ($useStepNameAsPosition) {
                $position = trim((string) ($step['step_name'] ?? ''));
            }
            if ($position === '') {
                $position = trim((string) ($staffPositionMap[$staffId] ?? ''));
            }
            if ($position === '') {
                $position = '-';
            }

            return [
                'show_sig' => $showSig,
                'sig_uri' => $showSig ? $sigUri : '',
                'status_line' => '',
                'name' => $name,
                'position' => $position,
                'role' => $role,
            ];
        };

        $cards = [];

        $findStep = static function (string $code) use ($approvalSteps): ?array {
            foreach ($approvalSteps as $step) {
                if (! is_array($step)) {
                    continue;
                }
                if (trim((string) ($step['step_code'] ?? '')) === $code) {
                    return $step;
                }
            }

            return null;
        };

        $headCard = $makeCardFromStep($findStep('reviewer_head'), 'หัวหน้างาน');
        if ($headCard !== null) {
            $cards[] = $headCard;
        }

        if (count($additionalSteps) > 0) {
            foreach ($additionalSteps as $step) {
                if (! is_array($step)) {
                    continue;
                }
                $staffId = trim((string) ($step['staff_id'] ?? ''));
                $actor = $actorByStaffId[$staffId] ?? [];
                $isApproved = strtolower(trim((string) ($step['status'] ?? ''))) === 'approved';
                $sigUri = '';
                if ($isApproved && $staffId !== '') {
                    $sigUri = $this->resolveMemoPdfSignerDataUri(
                        $staffId,
                        $signatureMap,
                        $bearerHeaderToken,
                        $rawBearerToken
                    );
                    if ($sigUri === '') {
                        $sigUri = trim((string) ($actor['signature_base64'] ?? $actor['signature_url'] ?? $signatureMap[$staffId] ?? ''));
                    }
                }
                if ($sigUri !== '' && str_starts_with($sigUri, 'data:image/')) {
                    $embedded = $this->persistSignatureDataUriForMpdf($sigUri);
                    if ($embedded !== '') {
                        $sigUri = $embedded;
                    }
                }
                $showSig = $sigUri !== '' && ! str_starts_with($sigUri, 'data:image/svg+xml');
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
                $role = $customStatus !== '' ? $customStatus : 'ผู้ตรวจสอบ/อนุมัติ เพิ่มเติม';
                $cards[] = [
                    'show_sig' => $showSig,
                    'sig_uri' => $showSig ? $sigUri : '',
                    'status_line' => '',
                    'name' => $name,
                    'position' => $position,
                    'role' => $role,
                ];
            }
        }

        $financeOfficerCard = $makeCardFromStep($findStep('finance_officer'), 'ผู้ตรวจสอบ');
        if ($financeOfficerCard !== null) {
            $hangingSigners = [];
            $financeManagerHang = $makeCardFromStep($findStep('finance_manager'), 'หัวหน้างานการเงิน');
            if ($financeManagerHang !== null) {
                $hangingSigners[] = $financeManagerHang;
            }
            $facultySecretaryHang = $makeCardFromStep($findStep('faculty_secretary'), 'เลขาคณะ');
            if ($facultySecretaryHang !== null) {
                $hangingSigners[] = $facultySecretaryHang;
            }
            $financeOfficerCard['hanging_signers'] = $hangingSigners;
            $cards[] = $financeOfficerCard;
        }

        // ผู้อนุมัติ = ลำดับสุดท้ายของรองคณบดี/คณบดี
        $executiveCodes = ['vice_dean', 'dean'];
        $lastExecutiveStep = collect($approvalSteps)
            ->filter(function ($step) use ($executiveCodes) {
                if (! is_array($step)) {
                    return false;
                }

                return in_array(trim((string) ($step['step_code'] ?? '')), $executiveCodes, true);
            })
            ->sortBy(fn ($step) => (int) ($step['step_order'] ?? 0))
            ->last();
        if (! is_array($lastExecutiveStep)) {
            // fallback: ขั้นตอนที่อนุมัติแล้วลำดับท้ายสุดที่ไม่ใช่หัวหน้า/การเงิน/เลขา
            $lastExecutiveStep = collect($approvalSteps)
                ->filter(function ($step) {
                    if (! is_array($step)) {
                        return false;
                    }
                    $code = trim((string) ($step['step_code'] ?? ''));
                    if (in_array($code, [
                        'reviewer_head',
                        'additional_reviewer',
                        'finance_officer',
                        'finance_manager',
                        'faculty_secretary',
                    ], true)) {
                        return false;
                    }

                    return strtolower(trim((string) ($step['status'] ?? ''))) === 'approved';
                })
                ->sortBy(fn ($step) => (int) ($step['step_order'] ?? 0))
                ->last();
        }
        $approverCard = $makeCardFromStep(
            is_array($lastExecutiveStep) ? $lastExecutiveStep : null,
            'ผู้อนุมัติ',
            true
        );
        if ($approverCard !== null) {
            $approverStepCode = is_array($lastExecutiveStep)
                ? trim((string) ($lastExecutiveStep['step_code'] ?? ''))
                : '';
            $approverStepName = is_array($lastExecutiveStep)
                ? trim((string) ($lastExecutiveStep['step_name'] ?? ''))
                : '';
            $approverPosition = trim((string) ($approverCard['position'] ?? ''));
            $isViceDean = $approverStepCode === 'vice_dean'
                || str_contains($approverStepName, 'รองคณบดี')
                || str_contains($approverPosition, 'รองคณบดี');
            if ($isViceDean) {
                $actingSuffix = 'ปฏิบัติรายการแทน คณบดีคณะเภสัชศาสตร์';
                if ($approverPosition === '' || $approverPosition === '-') {
                    $approverCard['position'] = 'รองคณบดี '.$actingSuffix;
                } elseif (! str_contains($approverPosition, $actingSuffix)) {
                    $approverCard['position'] = rtrim($approverPosition).' '.$actingSuffix;
                }
            }
            $cards[] = $approverCard;
        }

        // ถ้าไม่มี approval steps ที่เกี่ยวข้อง ใช้ process_actors แบบเดิม
        if ($cards === [] && count($additionalSteps) === 0) {
            foreach ($actors as $actor) {
                if (! is_array($actor)) {
                    continue;
                }
                $staffId = trim((string) ($actor['staff_id'] ?? ''));
                $sigUri = trim((string) ($actor['signature_base64'] ?? $actor['signature_url'] ?? ''));
                if ($sigUri === '' && $staffId !== '') {
                    $sigUri = $this->resolveMemoPdfSignerDataUri(
                        $staffId,
                        $signatureMap,
                        $bearerHeaderToken,
                        $rawBearerToken
                    );
                }
                if ($sigUri !== '' && str_starts_with($sigUri, 'data:image/')) {
                    $embedded = $this->persistSignatureDataUriForMpdf($sigUri);
                    if ($embedded !== '') {
                        $sigUri = $embedded;
                    }
                }
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
                $cards[] = [
                    'show_sig' => $showSig,
                    'sig_uri' => $showSig ? $sigUri : '',
                    'status_line' => '',
                    'name' => $name,
                    'position' => $position,
                    'role' => $role,
                ];
            }
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

        $staffIds = EsignAttachedPdf::resolveGeneralSignerStaffIds($row, $uploaderStaffId);
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
            } elseif ($fromMap !== '' && ! str_starts_with($fromMap, 'data:image/svg')) {
                $resolved = $this->resolveSignatureAsDataUri($fromMap, $bearerHeaderToken, $rawBearerToken);
                if (str_starts_with($resolved, 'data:image/') && ! str_starts_with($resolved, 'data:image/svg')) {
                    $uri = $resolved;
                }
            }
            if ($uri === '') {
                $uri = $this->fetchStaffSignatureBinaryDataUri($normalizedStaffId, $bearerHeaderToken, $rawBearerToken);
            }
            if ($uri === '' && in_array($role, [EsignAttachedPdf::ROLE_SECRETARY, EsignAttachedPdf::ROLE_VICE_DEAN], true)) {
                Cache::forget('withdraw:signature:data-uri:'.$normalizedStaffId);
                $uri = $this->fetchStaffSignatureBinaryDataUri($normalizedStaffId, $bearerHeaderToken, $rawBearerToken);
            }
            if ($uri !== '') {
                $uris[$role] = $uri;
            }
        }

        $requesterCaption = [];
        if (isset($staffIds[EsignAttachedPdf::ROLE_REQUESTER])) {
            $requesterCaption = EsignAttachedPdf::fetchStaffCaption(
                trim((string) ($staffIds[EsignAttachedPdf::ROLE_REQUESTER] ?? '')),
                EsignAttachedPdf::resolveGeneralLastApproverStepCode($row)
            );
            if (trim((string) ($requesterCaption['name'] ?? '')) === '') {
                $requesterStaffId = trim((string) ($staffIds[EsignAttachedPdf::ROLE_REQUESTER] ?? ''));
                if ($requesterStaffId !== '') {
                    $names = $this->resolveStaffNamesByIds([$requesterStaffId]);
                    $requesterCaption['name'] = trim((string) ($names[$requesterStaffId] ?? ''));
                }
            }
            $requesterCaption['approved_date'] = EsignAttachedPdf::resolveGeneralLastApproverApprovedDate($row);
        }

        return EsignAttachedPdf::stamp($absolutePath, $uris, $requesterCaption);
    }

    /**
     * Prefer a temp file for mPDF <img src>; fall back to data URI if the cache dir is not writable.
     */
    private function persistSignatureDataUriForMpdf(string $dataUri): string
    {
        $dataUri = trim($dataUri);
        if ($dataUri === '') {
            return '';
        }

        if (str_starts_with($dataUri, 'file://')) {
            $localPath = substr($dataUri, 7);
            if ($localPath !== '' && is_file($localPath)) {
                return 'file://'.str_replace('\\', '/', realpath($localPath) ?: $localPath);
            }

            return '';
        }

        if (! preg_match('#^data:image/(jpeg|jpg|png|gif|webp);base64,([A-Za-z0-9+/=\r\n]+)$#i', $dataUri, $matches)) {
            return '';
        }

        $extension = strtolower((string) ($matches[1] ?? 'png'));
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $binary = base64_decode((string) ($matches[2] ?? ''), true);
        if ($binary === false || $binary === '') {
            return '';
        }

        $directory = storage_path('framework/cache/mpdf/signatures');
        try {
            if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
                return $dataUri;
            }
            if (! is_writable($directory)) {
                return $dataUri;
            }

            $filePath = $directory.'/'.substr(md5($dataUri), 0, 20).'.'.$extension;
            if (! is_file($filePath) || filesize($filePath) < 50) {
                if (@file_put_contents($filePath, $binary) === false) {
                    return $dataUri;
                }
            }

            $realPath = realpath($filePath);
            if ($realPath !== false && is_readable($realPath)) {
                return 'file://'.str_replace('\\', '/', $realPath);
            }
        } catch (\Throwable $e) {
            return $dataUri;
        }

        return $dataUri;
    }

    /**
     * Resolve signature for memo-finance PDF (data URI preferred, then binary fetch).
     */
    private function resolveMemoPdfSignerDataUri(
        string $staffId,
        array $signatureMap,
        string $bearerHeaderToken = '',
        string $rawBearerToken = ''
    ): string {
        $normalizedStaffId = trim($staffId);
        if ($normalizedStaffId === '') {
            return '';
        }

        $fromMap = trim((string) ($signatureMap[$normalizedStaffId] ?? ''));
        if ($fromMap !== '' && str_starts_with($fromMap, 'data:image/') && ! str_starts_with($fromMap, 'data:image/svg+xml')) {
            return $fromMap;
        }

        $dataUri = $this->fetchStaffSignatureBinaryDataUri($normalizedStaffId, $bearerHeaderToken, $rawBearerToken);
        if ($dataUri !== '') {
            return $dataUri;
        }

        if ($bearerHeaderToken === '' && $rawBearerToken === '') {
            [$bearerHeaderToken, $rawBearerToken] = $this->resolveStaffApiTokens();
        }

        try {
            $candidateIds = $this->resolveStaffIdentityCandidates($normalizedStaffId, $bearerHeaderToken, $rawBearerToken);
            foreach ($candidateIds as $candidateId) {
                if (trim((string) $candidateId) === '' || trim((string) $candidateId) === $normalizedStaffId) {
                    continue;
                }
                $dataUri = $this->fetchStaffSignatureBinaryDataUri((string) $candidateId, $bearerHeaderToken, $rawBearerToken);
                if ($dataUri !== '') {
                    return $dataUri;
                }
            }
        } catch (\Throwable $e) {
            return '';
        }

        return '';
    }

    /**
     * Fetch signature image as embedded data URI for mPDF (same resolution path as apiSignatureProxy).
     */
    private function fetchStaffSignatureBinaryDataUri(string $staffId, string $bearerHeaderToken = '', string $rawBearerToken = ''): string
    {
        $normalizedStaffId = trim($staffId);
        if ($normalizedStaffId === '') {
            return '';
        }

        $cached = trim((string) Cache::get('withdraw:signature:data-uri:'.$normalizedStaffId, ''));
        if (str_starts_with($cached, 'data:image/') && ! str_starts_with($cached, 'data:image/svg')) {
            return $cached;
        }

        if ($bearerHeaderToken === '' && $rawBearerToken === '') {
            [$bearerHeaderToken, $rawBearerToken] = $this->resolveStaffApiTokens();
        }

        try {
            $candidateIds = $this->resolveStaffIdentityCandidates($normalizedStaffId, $bearerHeaderToken, $rawBearerToken);
            $rawUrl = $this->normalizeStaffSignatureUrl(
                $this->fetchStaffSignatureRawUrlByCandidateIds($candidateIds, $bearerHeaderToken, $rawBearerToken)
            );
            if ($rawUrl === '' || ! filter_var($rawUrl, FILTER_VALIDATE_URL)) {
                return '';
            }

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

            $response = $http->get($rawUrl);
            if (! $response->successful()) {
                return '';
            }

            $contentType = strtolower(trim((string) $response->header('Content-Type', '')));
            $contentType = explode(';', $contentType)[0] ?: '';
            if ($contentType === '' || ! str_starts_with($contentType, 'image/') || str_contains($contentType, 'svg')) {
                return '';
            }

            $binary = $response->body();
            if ($binary === '') {
                return '';
            }

            $dataUri = 'data:'.$contentType.';base64,'.base64_encode($binary);
            Cache::put('withdraw:signature:data-uri:'.$normalizedStaffId, $dataUri, now()->addMinutes(10));

            return $dataUri;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * @param array<int, string> $candidateIds
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

                if ($depCode === '' || $depName === '') {
                    continue;
                }

                $nameMap[$depCode] = $depName;
                $metaMap[$depCode] = [
                    'departmentName' => $depName,
                    'jobName' => '',
                    'unitName' => '',
                    'label' => $depName,
                ];

                if ($jobCode !== '' && $jobName !== '') {
                    $label = $depName.' > '.$jobName;
                    $nameMap[$jobCode] = $label;
                    $metaMap[$jobCode] = [
                        'departmentName' => $depName,
                        'jobName' => $jobName,
                        'unitName' => '',
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
        bool $jobOnly = false,
        bool $unitOnly = false
    ): string {
        $departmentCode = trim($departmentCode);
        $mappedMeta = isset($metaMap[$departmentCode]) && is_array($metaMap[$departmentCode])
            ? $metaMap[$departmentCode]
            : [];
        $mappedLabel = trim((string) ($nameMap[$departmentCode] ?? ''));
        $mappedParts = array_values(array_filter(array_map('trim', explode('>', $mappedLabel))));

        $departmentName = trim((string) ($rowFieldOverrides['department_name'] ?? $mappedMeta['departmentName'] ?? ($mappedParts[0] ?? '')));
        $unitName = trim((string) ($rowFieldOverrides['unit_name'] ?? $mappedMeta['unitName'] ?? ($mappedParts[2] ?? '')));
        $jobName = trim((string) ($rowFieldOverrides['job_name'] ?? $mappedMeta['jobName'] ?? ($mappedParts[1] ?? '')));
        $jobOrDepartmentName = $jobName !== '' ? $jobName : $departmentName;

        if ($unitOnly) {
            if ($unitName === '' && $jobName !== '') {
                $units = [];
                foreach ($metaMap as $meta) {
                    if (! is_array($meta)) {
                        continue;
                    }
                    if (trim((string) ($meta['jobName'] ?? '')) !== $jobName) {
                        continue;
                    }
                    $candidate = trim((string) ($meta['unitName'] ?? ''));
                    if ($candidate !== '') {
                        $units[$candidate] = $candidate;
                    }
                }
                $unitList = array_values($units);
                if (count($unitList) === 1) {
                    $unitName = $unitList[0];
                }
            }

            if ($unitName !== '' && $jobName !== '') {
                return $unitName.'  '.$jobName;
            }
            if ($unitName !== '') {
                return $unitName;
            }
            if ($jobName !== '') {
                return $jobName;
            }

            return $mappedLabel !== '' ? $mappedLabel : ($departmentCode !== '' ? $departmentCode : '-');
        }

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
     * สร้าง payload รายการเบิกจ่ายโครงการสำหรับ API ภายนอก (ระบบโครงการ)
     *
     * @return array<string, mixed>
     */
    public function buildProjectExternalWithdrawPayload(object $row, string $expectedProjectCode): array
    {
        $generalId = (int) ($row->withdraw_id ?? 0);
        $projectCode = trim((string) ($row->withdraw_project_code ?? ''));
        $applicantStaffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
        $applicantNameMap = $this->resolveStaffNamesByIds(array_values(array_filter([$applicantStaffId])));
        $documentMeta = $this->resolveWithdrawGeneralDocumentMeta($row);
        $date = trim((string) ($documentMeta['document_date'] ?? ''));
        if ($date === '' && ! empty($row->withdraw_date_create)) {
            $date = (string) $row->withdraw_date_create;
        }

        $partitionedFiles = EsignAttachedPdf::partitionFinanceEsignUploadedFiles(
            $this->appendProjectRelatedUploadedFiles(
                $this->loadGeneralUploadedFiles($generalId),
                $row
            )
        );

        $signedBase = [
            'withdraw_id' => $generalId,
            'project_code' => $expectedProjectCode !== '' ? $expectedProjectCode : $projectCode,
        ];

        $uploadedFiles = collect($partitionedFiles['uploaded_files'])->map(function (array $file) use ($signedBase, $generalId) {
            $fileId = (int) ($file['file_id'] ?? 0);
            $type = trim((string) ($file['type'] ?? ''));
            if ($fileId <= 0 && $type === 'project_approve_memo') {
                return [
                    'file_id' => 0,
                    'type' => $type,
                    'name' => (string) ($file['name'] ?? ''),
                    'mime' => (string) ($file['mime'] ?? 'application/pdf'),
                    'url' => ProjectWithdrawExternalAccess::signedUrl('withdraw.project.external.approve-memo-pdf', $signedBase),
                ];
            }
            if ($fileId <= 0 && $type === 'project_print') {
                return [
                    'file_id' => 0,
                    'type' => $type,
                    'name' => (string) ($file['name'] ?? ''),
                    'mime' => (string) ($file['mime'] ?? 'application/pdf'),
                    'url' => ProjectWithdrawExternalAccess::signedUrl('withdraw.project.external.print-pdf', $signedBase),
                ];
            }
            if ($fileId <= 0) {
                return null;
            }

            return [
                'file_id' => $fileId,
                'type' => (string) ($file['type'] ?? ''),
                'name' => (string) ($file['name'] ?? ''),
                'mime' => (string) ($file['mime'] ?? ''),
                'url' => ProjectWithdrawExternalAccess::signedUrl('withdraw.project.external.file', array_merge($signedBase, [
                    'id' => $generalId,
                    'file_id' => $fileId,
                ])),
            ];
        })->filter()->values()->all();

        $financeEsignFiles = collect($partitionedFiles['finance_esign_files'])->map(function (array $file) use ($signedBase, $generalId) {
            $fileId = (int) ($file['file_id'] ?? 0);
            if ($fileId <= 0) {
                return null;
            }

            return [
                'file_id' => $fileId,
                'type' => (string) ($file['type'] ?? ''),
                'name' => (string) ($file['name'] ?? ''),
                'mime' => (string) ($file['mime'] ?? ''),
                'url' => ProjectWithdrawExternalAccess::signedUrl('withdraw.project.external.file', array_merge($signedBase, [
                    'id' => $generalId,
                    'file_id' => $fileId,
                ])),
            ];
        })->filter()->values()->all();

        $procurementRows = [];
        if (Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_TABLE) && $generalId > 0) {
            $procurementRecords = DB::table(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)
                ->where('withdraw_id', $generalId)
                ->orderByDesc('withdraw_project_procurement_date_modify')
                ->orderByDesc('withdraw_project_procurement_id')
                ->get();

            foreach ($procurementRecords as $procurementRow) {
                $procurementId = (int) ($procurementRow->withdraw_project_procurement_id ?? 0);
                if ($procurementId <= 0) {
                    continue;
                }
                $payload = json_decode((string) ($procurementRow->withdraw_project_procurement_payload ?? ''), true);
                $payload = is_array($payload) ? $payload : [];
                $type = trim((string) ($procurementRow->withdraw_project_procurement_type ?? ($payload['type'] ?? 'w119')));
                $attachments = [];
                if (Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)) {
                    $attachmentRows = DB::table(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)
                        ->where('withdraw_project_procurement_id', $procurementId)
                        ->orderBy('withdraw_project_procurement_file_row')
                        ->orderBy('withdraw_project_procurement_file_id')
                        ->get();
                    foreach ($attachmentRows as $attachmentRow) {
                        $attachmentId = (int) ($attachmentRow->withdraw_project_procurement_file_id ?? 0);
                        if ($attachmentId <= 0) {
                            continue;
                        }
                        $attachments[] = [
                            'file_id' => $attachmentId,
                            'name' => trim((string) ($attachmentRow->withdraw_project_procurement_file_name ?? '')),
                            'url' => ProjectWithdrawExternalAccess::signedUrl('withdraw.project.external.procurement-file', array_merge($signedBase, [
                                'id' => $attachmentId,
                                'file_id' => $attachmentId,
                            ])),
                        ];
                    }
                }

                $procurementRows[] = [
                    'id' => $procurementId,
                    'type' => $type,
                    'type_label' => $type === 'eight' ? 'เอกสารจัดซื้อจัดจ้าง (8 ข้อ)' : 'เอกสารจัดซื้อจัดจ้าง (ว.119)',
                    'subject' => trim((string) ($payload['subject'] ?? '')),
                    'document_number' => trim((string) ($payload['document_number_display'] ?? $payload['document_number'] ?? '')),
                    'document_date' => trim((string) ($payload['document_date'] ?? '')),
                    'pdf_url' => ProjectWithdrawExternalAccess::signedUrl('withdraw.project.external.procurement-pdf', array_merge($signedBase, [
                        'id' => $procurementId,
                        'procurement_id' => $procurementId,
                    ])),
                    'attachments' => $attachments,
                ];
            }
        }

        $approveMemoMeta = $this->buildProjectApproveMemoPdfMeta($row);
        $printPdfMeta = $this->buildProjectPrintPdfMeta($row);
        $rawStatus = trim((string) ($row->withdraw_status ?? 'saved'));

        return [
            'id' => $generalId,
            'detail_url' => url('/withdraw/project').'?'.http_build_query(['id' => $generalId]),
            'project_code' => $projectCode,
            'project_id' => Schema::hasColumn('withdraw', 'withdraw_project_id')
                ? trim((string) ($row->withdraw_project_id ?? ''))
                : '',
            'project_name' => $this->resolveWithdrawProjectName($row),
            'project_activity_id' => Schema::hasColumn('withdraw', 'withdraw_project_activity_id')
                ? trim((string) ($row->withdraw_project_activity_id ?? ''))
                : '',
            'project_activity_name' => Schema::hasColumn('withdraw', 'withdraw_project_activity_name')
                ? trim((string) ($row->withdraw_project_activity_name ?? ''))
                : '',
            'project_activity_detail' => $this->resolveWithdrawProjectActivityDetail($row),
            'subject' => trim((string) ($row->withdraw_subject ?? '')),
            'code' => $this->resolveWithdrawGeneralDisplayDocumentCode($row),
            'document_number_display' => $this->resolveWithdrawGeneralDisplayDocumentCode($row),
            'document_date' => (string) ($documentMeta['document_date'] ?? ''),
            'date' => $this->formatThaiDate($date) !== '' ? $this->formatThaiDate($date) : '-',
            'raw_status' => $rawStatus,
            'status' => $this->mapWithdrawStatusLabel($rawStatus),
            'amount' => (float) ($row->withdraw_total_amount ?? 0),
            'applicant_staff_id' => $applicantStaffId,
            'applicant_name' => (string) ($applicantNameMap[$applicantStaffId] ?? ''),
            'items' => $this->loadGeneralItems($generalId),
            'approval_steps' => $this->loadGeneralApprovalSteps($generalId),
            'pdfs' => [
                'memo_pdf' => [
                    'name' => 'บันทึกข้อความขอเบิกจ่าย.pdf',
                    'url' => ProjectWithdrawExternalAccess::signedUrl('withdraw.project.external.memo-pdf', $signedBase),
                ],
                'approve_memo_pdf' => $approveMemoMeta !== null ? [
                    'name' => (string) ($approveMemoMeta['name'] ?? 'บันทึกอนุมัติโครงการ.pdf'),
                    'url' => ProjectWithdrawExternalAccess::signedUrl('withdraw.project.external.approve-memo-pdf', $signedBase),
                ] : null,
                'print_pdf' => $printPdfMeta !== null ? [
                    'name' => (string) ($printPdfMeta['name'] ?? 'รายละเอียดโครงการ.pdf'),
                    'url' => ProjectWithdrawExternalAccess::signedUrl('withdraw.project.external.print-pdf', $signedBase),
                ] : null,
                'uploaded_files' => $uploadedFiles,
                'finance_esign_files' => $financeEsignFiles,
                'procurement' => $procurementRows,
            ],
        ];
    }
}
