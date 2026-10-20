<?php

namespace App\Http\Controllers\Withdraw;

use App\Http\Controllers\Controller;
use App\Support\EsignAttachedPdf;
use App\Support\TravelWithdrawHandoff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class WithdrawTravelController extends Controller
{
    public function file(Request $request)
    {
        $source = TravelWithdrawHandoff::canonicalLeavesAssetUrl((string) $request->query('src', ''));
        if ($source === null) {
            abort(404);
        }

        try {
            $upstream = (new \GuzzleHttp\Client([
                'http_errors' => false,
                'allow_redirects' => false,
                'timeout' => 60,
                'connect_timeout' => 10,
            ]))->request('GET', $source, ['stream' => true]);
        } catch (\Throwable $e) {
            report($e);
            abort(502, 'ไม่สามารถดึงไฟล์แนบได้');
        }

        $status = (int) $upstream->getStatusCode();
        $contentType = strtolower(trim(explode(';', $upstream->getHeaderLine('Content-Type'))[0] ?? ''));
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if ($status < 200 || $status >= 300 || ! in_array($contentType, $allowedTypes, true)) {
            abort(404);
        }

        $filename = rawurldecode((string) (basename((string) parse_url($source, PHP_URL_PATH)) ?: 'file'));
        $filename = str_replace(['"', '\\', "\r", "\n"], '', $filename);
        $body = $upstream->getBody();

        return response()->stream(function () use ($body): void {
            while (! $body->eof()) {
                echo $body->read(8192);
                if (function_exists('flush')) {
                    flush();
                }
            }
        }, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function form(Request $request)
    {
        $kind = trim((string) $request->query('kind', ''));
        $leavesCode = trim((string) $request->query('leaves_code', ''));
        $allowed = ['leaves-report', 'leaves-report-money', 'leaves-report-receipt', 'leaves-report-register'];
        if (! in_array($kind, $allowed, true) || ! preg_match('/^[0-9A-Za-z_-]{1,32}$/', $leavesCode)) {
            abort(404);
        }

        $timestamp = time();
        $signature = TravelWithdrawHandoff::leavesFormSignature($kind, $leavesCode, 'on', $timestamp);
        if ($signature === '') {
            abort(500, 'ยังไม่ได้ตั้งค่าการเชื่อมต่อระบบขออนุมัติไปปฏิบัติงาน');
        }

        $base = rtrim((string) config('services.leaves_mission.base_url'), '/');
        try {
            $upstream = (new \GuzzleHttp\Client([
                'http_errors' => false,
                'allow_redirects' => false,
                'timeout' => 120,
                'connect_timeout' => 10,
            ]))->request('GET', $base.'/export/internal/'.$kind, [
                'query' => [
                    'leaves_code' => $leavesCode,
                    'sign' => 'on',
                    'ts' => $timestamp,
                    'sig' => $signature,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            abort(502, 'ไม่สามารถดึงแบบฟอร์มจากระบบขออนุมัติไปปฏิบัติงานได้');
        }

        $status = (int) $upstream->getStatusCode();
        $body = (string) $upstream->getBody();
        $contentType = strtolower(trim(explode(';', $upstream->getHeaderLine('Content-Type'))[0] ?? ''));
        if ($status < 200 || $status >= 300 || ($contentType !== 'application/pdf' && ! str_starts_with($body, '%PDF'))) {
            abort(502, 'ไม่สามารถสร้างแบบฟอร์มได้');
        }

        $filename = $kind.'-'.$leavesCode.'.pdf';

        return response($body, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=60',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function accept(Request $request): JsonResponse
    {
        $encoded = trim((string) $request->input('payload', ''));
        $raw = $encoded !== '' ? TravelWithdrawHandoff::decodePayload($encoded) : $request->all();
        if (! is_array($raw)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูลจากระบบขออนุมัติเดินทาง'], 422);
        }

        $signature = trim((string) ($request->input('sig') ?? $request->header('X-PBM-Signature', '')));
        $signedBody = $encoded !== '' ? $encoded : json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (! TravelWithdrawHandoff::hasValidSignature((string) $signedBody, $signature)) {
            return response()->json(['status' => 'error', 'message' => 'ลายเซ็นข้อมูลจากระบบขออนุมัติเดินทางไม่ถูกต้อง'], 403);
        }

        $items = is_array($raw['items'] ?? null) ? $raw['items'] : [];
        if ($items === []) {
            return response()->json(['status' => 'error', 'message' => 'ไม่มีรายการค่าใช้จ่ายให้เบิกจ่าย'], 422);
        }

        $token = TravelWithdrawHandoff::store($raw);

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
                'redirect' => TravelWithdrawHandoff::formUrl($token),
            ],
        ]);
    }

    public function index(Request $request)
    {
        return view('withdraw.travel.index', [
            'title' => 'เบิกจ่ายในการเดินทางไปปฏิบัติงาน',
            'handoffToken' => trim((string) $request->query('handoff', '')),
            'staffId' => trim((string) ($request->session()->get('staff_id') ?? '')),
        ]);
    }

    public function pending(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => ['items' => TravelWithdrawHandoff::listPending()],
        ]);
    }

    public function cancel(string $token): JsonResponse
    {
        if (! TravelWithdrawHandoff::cancel($token)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรายการที่รอทำ หรือยกเลิกไปแล้ว'], 404);
        }

        return response()->json(['status' => 'success', 'message' => 'ยกเลิกทำรายการแล้ว']);
    }

    public function show(string $token): JsonResponse
    {
        $payload = TravelWithdrawHandoff::peek($token);
        if ($payload === null) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรายการ หรือทำรายการนี้ไปแล้ว'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $payload]);
    }

    public function destroy(Request $request): JsonResponse
    {
        if ((string) $request->session()->get('pbm.system_role', '') !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลระบบเท่านั้นที่ลบรายการนี้ได้'], 403);
        }
        if (! Schema::hasTable('withdraw')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางข้อมูล'], 500);
        }

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'], 422);
        }

        $columns = ['withdraw_id', 'withdraw_status'];
        if (Schema::hasColumn('withdraw', 'withdraw_sub_type')) {
            $columns[] = 'withdraw_sub_type';
        }
        $existing = DB::table('withdraw')
            ->where('withdraw_id', $id)
            ->where('withdraw_type', 'salary')
            ->first($columns);
        if (! $existing || trim((string) ($existing->withdraw_sub_type ?? '')) !== 'travel_leave') {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรายการเบิกจ่ายในการเดินทางไปปฏิบัติงาน'], 404);
        }

        $currentStatus = trim((string) ($existing->withdraw_status ?? ''));
        $currentStaffId = trim((string) ($request->session()->get('staff_id') ?? ''));

        try {
            DB::transaction(function () use ($id, $currentStatus, $currentStaffId) {
                if (Schema::hasTable('withdraw_action_logs')) {
                    DB::table('withdraw_action_logs')->insert([
                        'withdraw_id' => $id,
                        'action_code' => 'delete',
                        'action_label' => 'ลบรายการ',
                        'from_status' => $currentStatus !== '' ? $currentStatus : null,
                        'to_status' => 'deleted',
                        'actor_staff_id' => $currentStaffId !== '' ? $currentStaffId : null,
                        'action_at' => now(),
                    ]);
                }

                if (Schema::hasTable('withdraw_file')) {
                    $fileRows = DB::table('withdraw_file')->where('withdraw_id', $id)->get(['withdraw_file_path']);
                    foreach ($fileRows as $fileRow) {
                        $path = trim((string) ($fileRow->withdraw_file_path ?? ''));
                        if ($path !== '' && Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->delete($path);
                        }
                    }
                    DB::table('withdraw_file')->where('withdraw_id', $id)->delete();
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
                if (Schema::hasTable('withdraw_budget')) {
                    DB::table('withdraw_budget')->where('withdraw_id', $id)->delete();
                }

                DB::table('withdraw')
                    ->where('withdraw_id', $id)
                    ->where('withdraw_type', 'salary')
                    ->delete();
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['status' => 'error', 'message' => 'ไม่สามารถลบรายการและข้อมูลที่เกี่ยวข้องได้'], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'ลบรายการเบิกจ่ายเรียบร้อยแล้ว',
        ]);
    }

    public function cancelRecord(Request $request): JsonResponse
    {
        if (! Schema::hasTable('withdraw')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางข้อมูล'], 500);
        }

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'], 422);
        }

        $columns = ['withdraw_id', 'withdraw_status'];
        if (Schema::hasColumn('withdraw', 'withdraw_sub_type')) {
            $columns[] = 'withdraw_sub_type';
        }
        $existing = DB::table('withdraw')
            ->where('withdraw_id', $id)
            ->where('withdraw_type', 'salary')
            ->first($columns);
        if (! $existing || trim((string) ($existing->withdraw_sub_type ?? '')) !== 'travel_leave') {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรายการเบิกจ่ายในการเดินทางไปปฏิบัติงาน'], 404);
        }

        $currentStatus = trim((string) ($existing->withdraw_status ?? ''));
        $allowed = [
            'saved',
            'draft',
            'pending_primary_review',
            'processing',
            'pending_additional_review',
            'pending_finance_review',
            'pending_approval',
            'pending_revision',
        ];
        if (! in_array($currentStatus, $allowed, true)) {
            return response()->json(['status' => 'error', 'message' => 'สถานะรายการนี้ไม่สามารถยกเลิกรายการได้'], 422);
        }

        $currentStaffId = trim((string) ($request->session()->get('staff_id') ?? ''));
        DB::table('withdraw')
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

        if (Schema::hasTable('withdraw_action_logs')) {
            DB::table('withdraw_action_logs')->insert([
                'withdraw_id' => $id,
                'action_code' => 'cancel_proceed',
                'action_label' => 'ยกเลิกรายการ',
                'from_status' => $currentStatus !== '' ? $currentStatus : null,
                'to_status' => 'cancelled',
                'actor_staff_id' => $currentStaffId !== '' ? $currentStaffId : null,
                'action_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'ยกเลิกรายการเบิกจ่ายเรียบร้อยแล้ว',
        ]);
    }

    public function records(Request $request): JsonResponse
    {
        if (! Schema::hasTable('withdraw') || ! Schema::hasColumn('withdraw', 'withdraw_sub_type')) {
            return response()->json(['status' => 'success', 'data' => ['items' => []]]);
        }

        $currentStaffId = trim((string) ($request->session()->get('staff_id') ?? ''));
        $items = app(WithdrawSalaryController::class)
            ->fetchApprovalListRows('travel', $currentStaffId)
            ->values();
        $esignCounts = $this->travelFinanceEsignCounts(
            $items->pluck('id')->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->all()
        );
        $items = $items->map(function (array $row) use ($esignCounts): array {
            $rowId = (int) ($row['id'] ?? 0);
            $row['finance_esign_count'] = (int) ($esignCounts[$rowId] ?? 0);

            return $row;
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    /**
     * @param  array<int, int>  $withdrawIds
     * @return array<int, int>
     */
    private function travelFinanceEsignCounts(array $withdrawIds): array
    {
        $withdrawIds = array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $withdrawIds),
            static fn (int $id): bool => $id > 0
        )));
        if ($withdrawIds === [] || ! Schema::hasTable('withdraw_file')) {
            return [];
        }

        $typeCodes = EsignAttachedPdf::financeEsignTypeCodes();
        if ($typeCodes === []) {
            return [];
        }

        return DB::table('withdraw_file')
            ->whereIn('withdraw_id', $withdrawIds)
            ->whereIn('withdraw_file_type_code', $typeCodes)
            ->selectRaw('withdraw_id, COUNT(*) as total')
            ->groupBy('withdraw_id')
            ->pluck('total', 'withdraw_id')
            ->map(static fn ($total): int => (int) $total)
            ->all();
    }

    public function byLeaves(Request $request): JsonResponse
    {
        $leavesCode = trim((string) $request->query('leaves_code', ''));
        if (! preg_match('/^[A-Za-z0-9_-]{1,32}$/', $leavesCode)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรหัสรายการ'], 422);
        }
        $signature = trim((string) ($request->query('sig') ?? $request->header('X-PBM-Signature', '')));
        if (! TravelWithdrawHandoff::hasValidSignature($leavesCode, $signature)) {
            return response()->json(['status' => 'error', 'message' => 'ลายเซ็นข้อมูลไม่ถูกต้อง'], 403);
        }

        TravelWithdrawHandoff::ensureTable();
        $handoff = DB::table('withdraw_travel_handoff')
            ->where(function ($query) use ($leavesCode) {
                $query->where('leaves_code', $leavesCode)
                    ->orWhere('payload_json', 'like', '%"leaves_code":"'.$leavesCode.'"%');
            })
            ->orderByRaw("case when status = 'consumed' and withdraw_id is not null then 0 else 1 end")
            ->orderByDesc('id')
            ->first();
        if (! $handoff) {
            return response()->json([
                'status' => 'success',
                'data' => ['found' => false],
            ]);
        }

        $withdrawId = (int) ($handoff->withdraw_id ?? 0);
        $withdraw = null;
        if ($withdrawId > 0 && Schema::hasTable('withdraw')) {
            $withdraw = DB::table('withdraw')->where('withdraw_id', $withdrawId)->first();
        }
        if (! $withdraw) {
            $isPending = (string) ($handoff->status ?? '') === TravelWithdrawHandoff::STATUS_PENDING;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'found' => true,
                    'state' => $isPending ? 'queued' : 'cancelled',
                    'subject' => (string) ($handoff->subject ?? ''),
                    'status_label' => $isPending ? 'รอทำรายการในระบบเบิกจ่าย' : 'ยกเลิกทำรายการแล้ว',
                    'amount' => (float) ($handoff->amount ?? 0),
                    'form_url' => $isPending ? TravelWithdrawHandoff::formUrl((string) ($handoff->token ?? '')) : '',
                    'items' => [],
                    'steps' => [],
                ],
            ]);
        }

        $status = (string) ($withdraw->withdraw_status ?? '');
        $moneyForm = $this->travelMoneyFormContext($withdrawId, $withdraw);

        return response()->json([
            'status' => 'success',
            'data' => [
                'found' => true,
                'state' => 'recorded',
                'withdraw_id' => $withdrawId,
                'open_url' => TravelWithdrawHandoff::recordUrl($withdrawId),
                'subject' => (string) ($withdraw->withdraw_subject ?? ''),
                'status' => $status,
                'status_label' => $this->travelStatusLabel($status),
                'amount' => (float) ($withdraw->withdraw_total_amount ?? 0),
                'to' => (string) ($withdraw->withdraw_to ?? ''),
                'created_at' => (string) ($withdraw->withdraw_date_create ?? ''),
                'memo_date' => $moneyForm['memo_date'],
                'payer_staff_id' => $moneyForm['payer_staff_id'],
                'approver_staff_id' => $moneyForm['approver_staff_id'],
                'approver_position' => $moneyForm['approver_position'],
                'endorsements' => $moneyForm['endorsements'],
                'items' => $this->travelWithdrawItems($withdrawId),
                'steps' => $this->travelApprovalSteps($withdrawId),
            ],
        ]);
    }

    /**
     * @return array{memo_date: string, payer_staff_id: string, approver_staff_id: string, approver_position: string, endorsements: list<array{label: string, staff_id: string}>}
     */
    private function travelMoneyFormContext(int $withdrawId, object $withdraw): array
    {
        $documentDate = '';
        if ($withdrawId > 0 && Schema::hasTable('withdraw_document')) {
            $documentDate = trim((string) DB::table('withdraw_document')
                ->where('withdraw_id', $withdrawId)
                ->where('withdraw_document_type', 'salary')
                ->value('document_date'));
        }

        $memoDate = $documentDate;
        $approverStaffId = '';
        $approverPosition = '';
        $endorsements = [];
        if ($withdrawId > 0 && Schema::hasTable('withdraw_approval_step')) {
            $steps = DB::table('withdraw_approval_step')
                ->where('withdraw_id', $withdrawId)
                ->orderBy('withdraw_approval_step_order')
                ->orderBy('withdraw_approval_step_id')
                ->get();
            $deanApprovedAt = '';
            foreach ($steps as $step) {
                $code = trim((string) ($step->withdraw_approval_step_code ?? ''));
                $status = strtolower(trim((string) ($step->withdraw_approval_status ?? '')));
                if (in_array($code, ['dean', 'vice_dean'], true) && $status === 'approved') {
                    $deanApprovedAt = trim((string) ($step->withdraw_approval_approved_at ?? ''));
                }
            }
            if ($deanApprovedAt !== '') {
                $memoDate = substr($deanApprovedAt, 0, 10);
            }

            $lastApproved = $steps
                ->filter(static fn ($step): bool => strtolower(trim((string) ($step->withdraw_approval_status ?? ''))) === 'approved')
                ->sortBy([
                    ['withdraw_approval_step_order', 'asc'],
                    ['withdraw_approval_step_id', 'asc'],
                ])
                ->last();
            if ($lastApproved) {
                $approverStaffId = trim((string) ($lastApproved->withdraw_approval_staff_id ?? ''));
                $approverPosition = trim((string) ($lastApproved->withdraw_approval_step_name ?? ''));
            }

            $endorsementLabels = [
                'finance_manager' => 'หัวหน้างานการเงิน',
                'faculty_secretary' => 'เลขาคณะ',
            ];
            foreach ($endorsementLabels as $code => $fallback) {
                $matched = $steps->first(static fn ($step): bool => trim((string) ($step->withdraw_approval_step_code ?? '')) === $code);
                if (! $matched) {
                    continue;
                }
                $staffId = trim((string) ($matched->withdraw_approval_staff_id ?? ''));
                $status = strtolower(trim((string) ($matched->withdraw_approval_status ?? '')));
                if ($staffId === '' || $status !== 'approved') {
                    continue;
                }
                $label = trim((string) ($matched->withdraw_approval_step_name ?? ''));
                $endorsements[] = [
                    'label' => $label !== '' ? $label : $fallback,
                    'staff_id' => $staffId,
                ];
            }
        }

        return [
            'memo_date' => $memoDate,
            'payer_staff_id' => trim((string) ($withdraw->withdraw_applicant_staff_id ?? '')),
            'approver_staff_id' => $approverStaffId,
            'approver_position' => $approverPosition,
            'endorsements' => $endorsements,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function travelWithdrawItems(int $withdrawId): array
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_item')) {
            return [];
        }
        $rows = DB::table('withdraw_item')->where('withdraw_id', $withdrawId)->orderBy('withdraw_item_id')->get();
        $budgetIds = $rows->map(fn ($row) => (int) ($row->withdraw_item_budget_manage_id ?? 0))->filter(fn ($id) => $id > 0)->unique()->values()->all();
        $budgetNames = $this->travelBudgetNames($budgetIds);
        $categoryNames = $this->travelCategoryNames($rows->map(fn ($row) => trim((string) ($row->withdraw_item_category ?? '')))->filter()->unique()->values()->all());

        return $rows->map(function ($row) use ($budgetNames, $categoryNames) {
            $budgetId = (int) ($row->withdraw_item_budget_manage_id ?? 0);
            $category = trim((string) ($row->withdraw_item_category ?? ''));

            return [
                'name' => trim((string) ($row->withdraw_item_name ?? '')),
                'category' => $categoryNames[$category] ?? $category,
                'budget' => $budgetNames[$budgetId] ?? trim((string) ($row->withdraw_item_budget_manage_code ?? '')),
                'amount' => (float) ($row->withdraw_item_amount ?? 0),
            ];
        })->values()->all();
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private function travelBudgetNames(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        try {
            if (! Schema::connection('budget')->hasTable('budget_manage')) {
                return [];
            }

            return DB::connection('budget')->table('budget_manage as bm')
                ->leftJoin('budget_set_source as bs', 'bm.budget_source_code', '=', 'bs.budget_source_code')
                ->leftJoin('budget_set_plan as bp', 'bm.budget_plan_code', '=', 'bp.budget_plan_code')
                ->leftJoin('budget_set_event as be', 'bm.budget_event_code', '=', 'be.budget_event_code')
                ->leftJoin('budget_set_activity as ba', 'bm.budget_activity_code', '=', 'ba.budget_activity_code')
                ->leftJoin('budget_set_fund as bf', 'bm.budget_fund_code', '=', 'bf.budget_fund_code')
                ->leftJoin('budget_set_category_budget as bcb', 'bm.budget_category_budget_code', '=', 'bcb.budget_category_budget_code')
                ->leftJoin('budget_set_category_pay as bcp', 'bm.budget_category_pay_code', '=', 'bcp.budget_category_pay_code')
                ->whereIn('bm.budget_manage_id', $ids)
                ->get([
                    'bm.budget_manage_id',
                    'bm.budget_manage_code',
                    'bs.budget_source_name',
                    'bm.budget_manage_year',
                    'bp.budget_plan_name',
                    'be.budget_event_name',
                    'ba.budget_activity_name',
                    'bf.budget_fund_name',
                    'bcb.budget_category_budget_name',
                    'bcp.budget_category_pay_name',
                    'bm.budget_category_pay_detail',
                ])
                ->mapWithKeys(function ($row) {
                    $activityName = trim((string) ($row->budget_activity_name ?? ''));
                    $payDetail = trim((string) ($row->budget_category_pay_detail ?? ''));
                    $path = trim((string) ($row->budget_source_name ?? '')).'ปี '.trim((string) ($row->budget_manage_year ?? ''))
                        .'/'.trim((string) ($row->budget_plan_name ?? ''))
                        .'/'.trim((string) ($row->budget_event_name ?? ''))
                        .($activityName !== '' ? '-'.$activityName : '')
                        .'/'.trim((string) ($row->budget_fund_name ?? ''))
                        .'/'.trim((string) ($row->budget_category_budget_name ?? ''))
                        .'/'.trim((string) ($row->budget_category_pay_name ?? ''))
                        .($payDetail !== '' ? '-'.$payDetail : '');
                    $path = trim(preg_replace('/\s+/', ' ', str_replace('/', ' ', $path)) ?? '');
                    $label = trim(implode(' · ', array_filter([
                        trim((string) ($row->budget_manage_code ?? '')),
                        $path,
                    ])));

                    return [(int) $row->budget_manage_id => $label];
                })
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  list<string>  $codes
     * @return array<string, string>
     */
    private function travelCategoryNames(array $codes): array
    {
        $codes = array_values(array_filter($codes, static fn (string $code) => $code !== ''));
        if ($codes === []) {
            return [];
        }
        try {
            if (! Schema::connection('budget')->hasTable('budget_set_pay_list')) {
                return [];
            }

            return DB::connection('budget')->table('budget_set_pay_list')
                ->whereIn('budget_pay_code', $codes)
                ->pluck('budget_pay_name', 'budget_pay_code')
                ->map(fn ($name) => trim((string) $name))
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, string>>
     */
    private function travelApprovalSteps(int $withdrawId): array
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_approval_step')) {
            return [];
        }
        $labels = [
            'pending' => 'รออนุมัติ',
            'approved' => 'อนุมัติแล้ว',
            'rejected' => 'ส่งกลับ',
        ];
        $rows = DB::table('withdraw_approval_step')
            ->where('withdraw_id', $withdrawId)
            ->orderBy('withdraw_approval_step_order')
            ->orderBy('withdraw_approval_step_id')
            ->get();
        $staffIds = $rows->map(fn ($row) => trim((string) ($row->withdraw_approval_staff_id ?? '')))->filter()->unique()->values()->all();
        $names = $this->travelStaffNames($staffIds);

        return $rows->map(function ($row) use ($labels, $names) {
            $status = trim((string) ($row->withdraw_approval_status ?? 'pending'));
            $staffId = trim((string) ($row->withdraw_approval_staff_id ?? ''));

            return [
                'name' => trim((string) ($row->withdraw_approval_step_name ?? '')),
                'status_label' => $labels[$status] ?? $status,
                'staff_name' => $names[$staffId] ?? '',
                'approved_at' => trim((string) ($row->withdraw_approval_approved_at ?? '')),
            ];
        })->values()->all();
    }

    /**
     * @param  list<string>  $staffIds
     * @return array<string, string>
     */
    private function travelStaffNames(array $staffIds): array
    {
        if ($staffIds === []) {
            return [];
        }
        try {
            if (! Schema::hasTable('users')) {
                return [];
            }
            $names = [];
            $rows = DB::table('users')
                ->whereIn('user_id', $staffIds)
                ->orWhereIn('staff_code', $staffIds)
                ->get(['user_id', 'staff_code', 'name', 'name_th']);
            foreach ($rows as $row) {
                $name = trim((string) ($row->name_th ?? ''));
                if ($name === '') {
                    $name = trim((string) ($row->name ?? ''));
                }
                if ($name === '') {
                    continue;
                }
                foreach (['user_id', 'staff_code'] as $column) {
                    $key = trim((string) ($row->{$column} ?? ''));
                    if ($key !== '') {
                        $names[$key] = $name;
                    }
                }
            }

            return $names;
        } catch (\Throwable) {
            return [];
        }
    }

    private function travelStatusLabel(string $status): string
    {
        return [
            'saved' => 'ร่าง',
            'draft' => 'ร่าง',
            'cancelled' => 'ยกเลิกรายการ',
            'pending_primary_review' => 'รอตรวจสอบ',
            'pending_finance_review' => 'รอการเงินตรวจสอบเอกสาร',
            'pending_revision' => 'รอแก้ไข',
            'pending_approval' => 'รออนุมัติ',
            'approved' => 'อนุมัติแล้ว',
        ][$status] ?? ($status !== '' ? $status : '-');
    }
}
