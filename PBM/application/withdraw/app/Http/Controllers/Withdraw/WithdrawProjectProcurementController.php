<?php

namespace App\Http\Controllers\Withdraw;

use App\Http\Controllers\Controller;
use App\Support\EsignAttachedPdf;
use App\Support\StaffApiName;
use App\Support\ThaiBahtText;
use App\Support\WithdrawSchema;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class WithdrawProjectProcurementController extends Controller
{
    public function pdf(Request $request): Response
    {
        $type = trim((string) $request->input('type', ''));
        if (! in_array($type, ['w119', 'eight'], true)) {
            abort(422, 'กรุณาเลือกประเภทเอกสารจัดซื้อจัดจ้าง');
        }

        $data = $type === 'w119'
            ? $this->normalizeW119($request)
            : $this->normalizeEight($request);

        $withdrawId = (int) $request->input('withdraw_id', 0);
        if ($type === 'eight') {
            $projectAmount = $this->resolveWithdrawProjectAmount($withdrawId);
            if ($projectAmount > 0) {
                $data['allocated_budget'] = $projectAmount;
                $data['allocated_budget_display'] = ThaiBahtText::formatAmount($projectAmount);
                $data['allocated_budget_text'] = ThaiBahtText::fromAmount($projectAmount);
            }
        }
        $procurementId = (int) $request->input(
            'withdraw_project_procurement_id',
            $request->input('id', 0)
        );
        $creatorDepartment = $this->resolveWithdrawCreatorDepartment($withdrawId);
        if ($creatorDepartment['name'] !== '') {
            $data['department'] = $creatorDepartment['name'];
        }
        if ($creatorDepartment['phone'] !== '') {
            $data['phone'] = $creatorDepartment['phone'];
        }
        $data['id'] = $procurementId;
        $data['withdraw_project_procurement_id'] = $procurementId;
        $data['running_no'] = (int) $request->input('running_no', $data['running_no'] ?? 0);
        if ($this->text($request, 'document_number_display') !== '') {
            $data['document_number_display'] = $this->text($request, 'document_number_display');
        }
        if ($type === 'eight' && $withdrawId > 0) {
            try {
                app(WithdrawGeneralController::class)->ensureIssuedProjectProcurementNumbers($withdrawId);
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::warning('eight procurement second document number was not issued', [
                    'withdraw_id' => $withdrawId,
                    'procurement_id' => $procurementId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
        $documentMeta = $this->resolveWithdrawDocumentMeta($withdrawId, $data);
        $documentNumber = $this->formatWithdrawDocumentNumberDisplay($documentMeta);
        if ($documentNumber === '' && trim((string) ($data['document_number_display'] ?? '')) !== '') {
            $documentNumber = trim((string) $data['document_number_display']);
        }
        if ($documentNumber !== '') {
            $data['document_number'] = $documentNumber;
        }
        if ($type === 'eight' && $procurementId > 0) {
            $considerNumber = $this->issuedProcurementNumberDisplay(
                $withdrawId,
                'project_procurement:'.$procurementId.':consider'
            );
            if ($considerNumber !== '') {
                $data['consider_number'] = $considerNumber;
            }
            $referenceNumber = trim((string) ($data['consider_ref_number'] ?? ''));
            $numberBase = trim((string) ($documentMeta['document_number'] ?? ''));
            if ($documentNumber !== '' && ($referenceNumber === '' || $referenceNumber === $numberBase)) {
                $data['consider_ref_number'] = $documentNumber;
            }
        }
        $documentDate = $this->formatWithdrawDocumentDateDisplay($documentMeta);
        if ($documentDate !== '') {
            $data['document_date'] = $documentDate;
            $data['document_date_raw'] = trim((string) ($documentMeta['document_date'] ?? ''));
        }
        $requesterPosition = $this->resolveRequesterPosition($withdrawId);
        if ($requesterPosition !== '' && $this->isEmptyOrPlaceholder((string) ($data['requester_position'] ?? ''))) {
            $data['requester_position'] = $requesterPosition;
        }
        $financeManager = $this->resolveFinanceManagerDisplay($withdrawId);
        if ($financeManager['name'] !== '') {
            $data['finance_name'] = $financeManager['name'];
        }
        if ($financeManager['position'] !== '' && $this->isEmptyOrPlaceholder((string) ($data['finance_position'] ?? ''))) {
            $data['finance_position'] = $financeManager['position'];
        }
        $approvalDecision = $this->resolveWithdrawApprovalDecision($withdrawId);
        $data['approval_decision'] = $approvalDecision['decision'];
        $data['approval_reason'] = $approvalDecision['reason'];
        $viceDean = $this->resolveStepApproverDisplay($withdrawId, 'vice_dean');
        $payloadApproverPosition = trim((string) ($data['approver_position'] ?? ''));
        if ($viceDean['name'] !== '') {
            $data['approver_name'] = $viceDean['name'];
        }
        if ($payloadApproverPosition !== '') {
            $data['approver_position_lines'] = \App\Support\EsignAttachedPdf::normalizeRequesterPositionLines(
                $payloadApproverPosition,
                'vice_dean'
            );
        } elseif ($viceDean['position_lines'] !== []) {
            $data['approver_position_lines'] = $viceDean['position_lines'];
        }
        $secretary = $this->resolveStepApproverDisplay($withdrawId, 'faculty_secretary');
        if ($secretary['name'] !== '') {
            $data['secretary_name'] = $secretary['name'];
        }
        if ($secretary['position_lines'] !== []) {
            $data['secretary_position_lines'] = $secretary['position_lines'];
        }
        if ($type === 'w119') {
            $data = $this->enrichW119Opening($data, $request, $creatorDepartment);
        }
        $data['requester_signature'] = $this->persistSignatureDataUriForMpdf(
            $this->resolveStaffSignatureDataUri($this->getWithdrawApplicantStaffId($withdrawId))
        );
        $data['reviewer_head_signature'] = $this->persistSignatureDataUriForMpdf(
            $this->resolveStaffSignatureDataUri($this->resolveApprovedStepActorStaffId($withdrawId, 'reviewer_head'))
        );
        $data['finance_signature'] = $this->persistSignatureDataUriForMpdf(
            $this->resolveStaffSignatureDataUri($this->resolveApprovedStepActorStaffId($withdrawId, 'finance_manager'))
        );
        $data['secretary_signature'] = $this->persistSignatureDataUriForMpdf(
            $this->resolveStaffSignatureDataUri($this->resolveApprovedStepActorStaffId($withdrawId, 'faculty_secretary'))
        );
        $data['approver_signature'] = $this->persistSignatureDataUriForMpdf(
            $this->resolveStaffSignatureDataUri($this->resolveApprovedStepActorStaffId($withdrawId, 'vice_dean'))
        );
        $data['spec_signer_signature'] = $this->persistSignatureDataUriForMpdf(
            $this->resolveStaffSignatureDataUri(trim((string) $request->input('inspector_staff_id', '')))
        );

        $view = $type === 'w119'
            ? 'withdraw.project.pdf.w119'
            : 'withdraw.project.pdf.eight';

        $html = view($view, [
            'd' => $data,
            'krut' => $this->krutMeta(),
        ])->render();

        $filename = $type === 'w119'
            ? 'เอกสารจัดซื้อจัดจ้าง-ว119.pdf'
            : 'เอกสารจัดซื้อจัดจ้าง-8ข้อ.pdf';

        return $this->renderPdf($html, $filename);
    }

    public function list(Request $request): JsonResponse
    {
        if (! Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางข้อมูล'], 500);
        }

        $withdrawId = (int) $request->query('withdraw_id', $request->input('withdraw_id', 0));
        if ($withdrawId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรายการเบิกจ่าย'], 422);
        }
        if (! $this->withdrawExists($withdrawId)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรายการเบิกจ่าย'], 404);
        }

        $rows = DB::table(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)
            ->where('withdraw_id', $withdrawId)
            ->orderByDesc('withdraw_project_procurement_date_modify')
            ->orderByDesc('withdraw_project_procurement_id')
            ->get();

        $canEdit = $this->canEditProcurementDocuments($withdrawId);
        $data = [];
        foreach ($rows as $row) {
            $item = $this->presentRow($row, false);
            $item['can_edit'] = $canEdit;
            $data[] = $item;
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'withdraw_id' => $withdrawId,
                'withdraw_status' => $this->withdrawStatus($withdrawId),
                'can_edit' => $canEdit,
            ],
        ]);
    }

    public function winners(): JsonResponse
    {
        if (! Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $rows = DB::table(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)
            ->orderByDesc('withdraw_project_procurement_date_modify')
            ->orderByDesc('withdraw_project_procurement_id')
            ->limit(800)
            ->get(['withdraw_project_procurement_payload']);

        $names = [];
        $seen = [];
        foreach ($rows as $row) {
            foreach ($this->winnerNamesFromPayload($this->decodePayload($row->withdraw_project_procurement_payload ?? null)) as $name) {
                $key = mb_strtolower($name);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $names[] = $name;
            }
        }
        natcasesort($names);

        return response()->json([
            'status' => 'success',
            'data' => array_values($names),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $row = $this->findRow($id);
        if ($row === null) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบเอกสารจัดซื้อจัดจ้าง'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->presentRow($row, true),
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        if (! Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางข้อมูล'], 500);
        }

        $data = $this->requestPayload($request);
        $withdrawId = (int) ($data['withdraw_id'] ?? $request->input('withdraw_id', 0));
        $id = (int) ($data['id'] ?? $request->input('id', 0));
        $itemId = (int) ($data['withdraw_item_id'] ?? $request->input('withdraw_item_id', 0));
        $type = trim((string) ($data['type'] ?? $request->input('type', '')));
        if (! in_array($type, ['w119', 'eight'], true)) {
            return response()->json(['status' => 'error', 'message' => 'กรุณาเลือกประเภทเอกสารจัดซื้อจัดจ้าง'], 422);
        }
        if ($withdrawId <= 0 || ! $this->withdrawExists($withdrawId)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบรายการเบิกจ่าย'], 422);
        }
        if (! $this->canEditProcurementDocuments($withdrawId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถแก้ไขหรือเพิ่มเอกสารจัดซื้อจัดจ้างได้ เนื่องจากรายการนี้อยู่ในขั้นตอนดำเนินการแล้ว (แก้ไขได้เฉพาะรายการที่ถูกส่งกลับมาให้แก้ไข)',
            ], 422);
        }

        $limit = $this->itemAmountLimit($withdrawId, $itemId);
        if ($type === 'w119' && $limit > 10000) {
            return response()->json([
                'status' => 'error',
                'message' => 'รายการเบิกจ่ายเกิน 10,000 บาท กรุณาใช้เอกสารจัดซื้อจัดจ้าง (8 ข้อ)',
            ], 422);
        }
        if ($limit > 0) {
            $sum = $this->detailRowsTotal($data['detail_rows'] ?? []);
            if ($sum - $limit > 0.009) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'จำนวนเงินรวมในรายละเอียดประกอบรายงานเกินวงเงินของรายการเบิกจ่าย ('.number_format($limit, 2).' บาท)',
                ], 422);
            }
        }

        $payload = $data;
        unset($payload['id'], $payload['withdraw_id'], $payload['_token']);
        $payload['type'] = $type;
        $payload['withdraw_item_id'] = $itemId > 0 ? $itemId : null;
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return response()->json(['status' => 'error', 'message' => 'ไม่สามารถบันทึกข้อมูลได้'], 422);
        }

        $now = now();
        $staffId = trim((string) ($request->session()->get('staff_id') ?? $this->currentActorId() ?? ''));

        if ($id > 0) {
            $existing = $this->findRow($id);
            if ($existing === null || (int) ($existing->withdraw_id ?? 0) !== $withdrawId) {
                return response()->json(['status' => 'error', 'message' => 'ไม่พบเอกสารจัดซื้อจัดจ้าง'], 404);
            }
            $update = [
                'withdraw_project_procurement_type' => $type,
                'withdraw_project_procurement_payload' => $encoded,
                'withdraw_project_procurement_date_modify' => $now,
                'staff_modify' => $staffId !== '' ? $staffId : null,
            ];
            if (Schema::hasColumn(WithdrawSchema::PROJECT_PROCUREMENT_TABLE, 'withdraw_item_id')) {
                $update['withdraw_item_id'] = $itemId > 0 ? $itemId : null;
            }
            DB::table(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)
                ->where('withdraw_project_procurement_id', $id)
                ->update($update);
        } else {
            $insert = [
                'withdraw_id' => $withdrawId,
                'withdraw_project_procurement_type' => $type,
                'withdraw_project_procurement_payload' => $encoded,
                'withdraw_project_procurement_date_create' => $now,
                'withdraw_project_procurement_date_modify' => $now,
                'staff_create' => $staffId !== '' ? $staffId : null,
                'staff_modify' => $staffId !== '' ? $staffId : null,
            ];
            if (Schema::hasColumn(WithdrawSchema::PROJECT_PROCUREMENT_TABLE, 'withdraw_item_id')) {
                $insert['withdraw_item_id'] = $itemId > 0 ? $itemId : null;
            }
            $id = (int) DB::table(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)
                ->insertGetId($insert, 'withdraw_project_procurement_id');
        }

        $this->syncDetailFiles($request, $id, is_array($payload['detail_rows'] ?? null) ? $payload['detail_rows'] : [], $staffId);

        $row = $this->findRow($id);

        return response()->json([
            'status' => 'success',
            'message' => 'บันทึกเอกสารจัดซื้อจัดจ้างแล้ว',
            'data' => $row ? $this->presentRow($row, true) : ['id' => $id],
        ]);
    }

    public function file(int $id)
    {
        if (! Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)) {
            abort(404, 'ไม่พบไฟล์');
        }
        $row = DB::table(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)
            ->where('withdraw_project_procurement_file_id', $id)
            ->first();
        if ($row === null) {
            abort(404, 'ไม่พบไฟล์');
        }
        $path = trim((string) ($row->withdraw_project_procurement_file_path ?? ''));
        $name = trim((string) ($row->withdraw_project_procurement_file_name ?? 'document'));
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            abort(404, 'ไม่พบไฟล์');
        }
        $absolutePath = Storage::disk('public')->path($path);
        $disposition = request()->boolean('download') ? 'attachment' : 'inline';

        return response()->file($absolutePath, [
            'Content-Disposition' => $disposition.'; filename="'.$name.'"',
        ]);
    }

    public function pdfById(int $id): Response
    {
        $row = $this->findRow($id);
        if ($row === null) {
            abort(404, 'ไม่พบเอกสารจัดซื้อจัดจ้าง');
        }
        $payload = $this->decodePayload($row->withdraw_project_procurement_payload ?? null);
        $payload['type'] = (string) ($row->withdraw_project_procurement_type ?? ($payload['type'] ?? ''));
        $payload['withdraw_id'] = (int) ($row->withdraw_id ?? 0);
        $payload['id'] = (int) ($row->withdraw_project_procurement_id ?? $id);
        $payload['withdraw_project_procurement_id'] = (int) ($row->withdraw_project_procurement_id ?? $id);
        $request = Request::create('/', 'POST', $payload);

        return $this->pdf($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentRow(object $row, bool $includePayload): array
    {
        $payload = $this->decodePayload($row->withdraw_project_procurement_payload ?? null);
        $type = trim((string) ($row->withdraw_project_procurement_type ?? ($payload['type'] ?? 'w119')));
        $itemId = 0;
        if (isset($row->withdraw_item_id)) {
            $itemId = (int) $row->withdraw_item_id;
        }
        if ($itemId <= 0) {
            $itemId = (int) ($payload['withdraw_item_id'] ?? 0);
        }
        $itemName = trim((string) ($payload['procurement'] ?? ''));
        if ($itemId > 0 && Schema::hasTable('withdraw_item')) {
            $itemRow = DB::table('withdraw_item')
                ->where('withdraw_item_id', $itemId)
                ->first(['withdraw_item_name']);
            $fromItem = trim((string) ($itemRow->withdraw_item_name ?? ''));
            if ($fromItem !== '') {
                $itemName = $fromItem;
            }
        }
        $item = [
            'id' => (int) ($row->withdraw_project_procurement_id ?? 0),
            'withdraw_id' => (int) ($row->withdraw_id ?? 0),
            'withdraw_item_id' => $itemId,
            'item_name' => $itemName,
            'type' => $type,
            'type_label' => $type === 'eight' ? 'เอกสารจัดซื้อจัดจ้าง (8 ข้อ)' : 'เอกสารจัดซื้อจัดจ้าง (ว.119)',
            'subject' => trim((string) ($payload['subject'] ?? '')),
            'procurement' => trim((string) ($payload['procurement'] ?? '')),
            'document_date' => trim((string) ($payload['document_date'] ?? '')),
            'document_number' => trim((string) ($payload['document_number'] ?? '')),
            'document_number_display' => $this->procurementDocumentNumberDisplay($payload),
            'running_no' => (int) ($payload['running_no'] ?? 0),
            'date_create' => (string) ($row->withdraw_project_procurement_date_create ?? ''),
            'date_modify' => (string) ($row->withdraw_project_procurement_date_modify ?? ''),
            'date_modify_display' => $this->listDate((string) ($row->withdraw_project_procurement_date_modify ?? $row->withdraw_project_procurement_date_create ?? '')),
        ];
        if ($includePayload) {
            $payload['detail_rows'] = $this->attachFilesToDetailRows(
                (int) ($row->withdraw_project_procurement_id ?? 0),
                is_array($payload['detail_rows'] ?? null) ? $payload['detail_rows'] : []
            );
            $item['payload'] = $payload;
        }

        return $item;
    }

    private function findRow(int $id): ?object
    {
        if ($id <= 0 || ! Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)) {
            return null;
        }

        return DB::table(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)
            ->where('withdraw_project_procurement_id', $id)
            ->first();
    }

    private function withdrawExists(int $withdrawId): bool
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw')) {
            return false;
        }

        return DB::table('withdraw')->where('withdraw_id', $withdrawId)->exists();
    }

    private function withdrawStatus(int $withdrawId): string
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw') || ! Schema::hasColumn('withdraw', 'withdraw_status')) {
            return '';
        }

        return strtolower(trim((string) (DB::table('withdraw')->where('withdraw_id', $withdrawId)->value('withdraw_status') ?? '')));
    }

    private function canEditProcurementDocuments(int $withdrawId): bool
    {
        $status = $this->withdrawStatus($withdrawId);
        if ($status === '') {
            return true;
        }

        return in_array($status, ['saved', 'draft', 'pending_revision'], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(Request $request): array
    {
        $raw = $request->input('payload');
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        if (is_array($raw)) {
            return $raw;
        }

        return $request->except(['_token', 'payload']);
    }

    /**
     * @param  mixed  $rows
     */
    private function detailRowsTotal(mixed $rows): float
    {
        if (! is_array($rows)) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $raw = str_replace([',', ' '], '', (string) ($row['agreed_price'] ?? $row['amount'] ?? 0));
            if (is_numeric($raw)) {
                $sum += (float) $raw;
            }
        }

        return $sum;
    }

    private function itemAmountLimit(int $withdrawId, int $itemId): float
    {
        if ($itemId > 0 && Schema::hasTable('withdraw_item')) {
            $row = DB::table('withdraw_item')
                ->where('withdraw_id', $withdrawId)
                ->where('withdraw_item_id', $itemId)
                ->first(['withdraw_item_amount']);
            $amount = (float) ($row->withdraw_item_amount ?? 0);
            if ($amount > 0) {
                return $amount;
            }
        }
        if (Schema::hasTable('withdraw')) {
            $row = DB::table('withdraw')->where('withdraw_id', $withdrawId)->first(['withdraw_total_amount']);

            return (float) ($row->withdraw_total_amount ?? 0);
        }

        return 0.0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $detailRows
     */
    private function syncDetailFiles(Request $request, int $procurementId, array $detailRows, string $staffId): void
    {
        if ($procurementId <= 0 || ! Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)) {
            return;
        }

        $keepIds = [];
        foreach ($detailRows as $index => $row) {
            $files = is_array($row['files'] ?? null) ? $row['files'] : [];
            foreach ($files as $file) {
                $fileId = (int) (is_array($file) ? ($file['id'] ?? 0) : 0);
                if ($fileId > 0) {
                    $keepIds[$fileId] = $index;
                }
            }
        }

        $existing = DB::table(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)
            ->where('withdraw_project_procurement_id', $procurementId)
            ->get();
        foreach ($existing as $fileRow) {
            $fileId = (int) ($fileRow->withdraw_project_procurement_file_id ?? 0);
            if ($fileId > 0 && isset($keepIds[$fileId])) {
                DB::table(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)
                    ->where('withdraw_project_procurement_file_id', $fileId)
                    ->update([
                        'withdraw_project_procurement_file_row' => (int) $keepIds[$fileId],
                        'withdraw_project_procurement_file_date_modify' => now(),
                        'staff_modify' => $staffId !== '' ? $staffId : null,
                    ]);
                continue;
            }
            $path = trim((string) ($fileRow->withdraw_project_procurement_file_path ?? ''));
            if ($path !== '' && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            DB::table(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)
                ->where('withdraw_project_procurement_file_id', $fileId)
                ->delete();
        }

        $uploaded = $request->allFiles();
        $groups = $uploaded['detail_files'] ?? [];
        if (! is_array($groups)) {
            return;
        }
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif'];
        $now = now();
        foreach ($groups as $index => $files) {
            $rowIndex = (int) $index;
            if (! is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                if (! $file instanceof \Illuminate\Http\UploadedFile || ! $file->isValid()) {
                    continue;
                }
                $ext = strtolower((string) $file->getClientOriginalExtension());
                if (! in_array($ext, $allowed, true)) {
                    continue;
                }
                if ($file->getSize() > 10 * 1024 * 1024) {
                    continue;
                }
                $path = $file->store('withdraw/procurement/'.$procurementId, 'public');
                if (! is_string($path) || $path === '') {
                    continue;
                }
                DB::table(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)->insert([
                    'withdraw_project_procurement_id' => $procurementId,
                    'withdraw_project_procurement_file_row' => $rowIndex,
                    'withdraw_project_procurement_file_path' => $path,
                    'withdraw_project_procurement_file_name' => $file->getClientOriginalName(),
                    'withdraw_project_procurement_file_size' => (int) $file->getSize(),
                    'withdraw_project_procurement_file_mime' => (string) $file->getMimeType(),
                    'withdraw_project_procurement_file_date_create' => $now,
                    'withdraw_project_procurement_file_date_modify' => $now,
                    'staff_create' => $staffId !== '' ? $staffId : null,
                    'staff_modify' => $staffId !== '' ? $staffId : null,
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $detailRows
     * @return array<int, array<string, mixed>>
     */
    private function attachFilesToDetailRows(int $procurementId, array $detailRows): array
    {
        if ($procurementId <= 0 || ! Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)) {
            return $detailRows;
        }
        $files = DB::table(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)
            ->where('withdraw_project_procurement_id', $procurementId)
            ->orderBy('withdraw_project_procurement_file_row')
            ->orderBy('withdraw_project_procurement_file_id')
            ->get();
        $byRow = [];
        foreach ($files as $file) {
            $rowIndex = (int) ($file->withdraw_project_procurement_file_row ?? 0);
            $fileId = (int) ($file->withdraw_project_procurement_file_id ?? 0);
            $byRow[$rowIndex][] = [
                'id' => $fileId,
                'name' => trim((string) ($file->withdraw_project_procurement_file_name ?? '')),
                'url' => url('/withdraw/api/project/procurement/file/'.$fileId),
            ];
        }
        if ($detailRows === []) {
            $detailRows = [[]];
        }
        foreach ($detailRows as $index => $row) {
            if (! is_array($row)) {
                $row = [];
            }
            $row['files'] = $byRow[$index] ?? [];
            $detailRows[$index] = $row;
        }

        return $detailRows;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    private function winnerNamesFromPayload(array $payload): array
    {
        $names = [];
        $push = static function ($value) use (&$names): void {
            $name = trim((string) $value);
            if ($name !== '') {
                $names[] = $name;
            }
        };
        $push($payload['winner_name'] ?? '');
        foreach (['winners', 'consider_rows', 'detail_rows'] as $key) {
            $rows = is_array($payload[$key] ?? null) ? $payload[$key] : [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $push($row['winner'] ?? '');
                $push($row['name'] ?? '');
                $push($row['company'] ?? '');
            }
        }

        return $names;
    }

    private function listDate(string $value): string
    {
        $input = trim($value);
        if ($input === '') {
            return '';
        }
        try {
            $date = Carbon::parse($input);
        } catch (\Throwable) {
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
        $month = $longMonths[(int) $date->month] ?? '';
        if ($month === '') {
            return $input;
        }

        return ((int) $date->day).' '.$month.' '.((int) $date->year + 543);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeW119(Request $request): array
    {
        $amount = $this->toAmount($request->input('amount'));
        $rows = $this->rows($request->input('detail_rows', []), ['company', 'document_type', 'document_date', 'description', 'amount']);
        if ($rows === []) {
            $rows = [[
                'company' => '',
                'document_type' => 'ใบสำคัญรับเงิน',
                'document_date' => '',
                'description' => '',
                'amount' => $amount,
            ]];
        }
        $rowTotal = collect($rows)->sum(static fn ($row) => (float) ($row['amount'] ?? 0));
        $winners = $this->rows($request->input('winners', []), ['name', 'amount']);
        if ($winners === []) {
            $winners = collect($rows)->map(static fn ($row) => [
                'name' => (string) ($row['company'] ?? ''),
                'amount' => (float) ($row['amount'] ?? 0),
            ])->all();
        }
        $winnerTotal = collect($winners)->sum(static fn ($row) => (float) ($row['amount'] ?? 0));
        $useAmount = $rowTotal > 0 ? $rowTotal : $amount;

        return [
            'department' => $this->text($request, 'department'),
            'phone' => $this->text($request, 'phone'),
            'document_number' => $this->text($request, 'document_number'),
            'document_date' => $this->thaiDate($this->text($request, 'document_date')),
            'document_date_raw' => $this->text($request, 'document_date'),
            'subject' => $this->text($request, 'subject', 'รายงานขอความเห็นชอบการจัดซื้อจัดจ้าง'),
            'to' => $this->text($request, 'to', 'คณบดีคณะเภสัชศาสตร์'),
            'procurement' => $this->text($request, 'procurement'),
            'purpose' => $this->text($request, 'purpose'),
            'attachment_count' => $this->text($request, 'attachment_count', (string) max(count($rows), 1)),
            'budget_source' => $this->text($request, 'budget_source'),
            'amount' => $useAmount,
            'amount_text' => ThaiBahtText::fromAmount($useAmount),
            'amount_display' => ThaiBahtText::formatAmount($useAmount),
            'closing' => $this->text($request, 'closing', 'จึงเรียนมาเพื่อโปรดพิจารณาให้ความเห็นชอบ และให้ถือรายงานนี้เป็นหลักฐานการตรวจรับพัสดุโดยอนุโลม'),
            'requester_name' => $this->text($request, 'requester_name'),
            'requester_position' => $this->text($request, 'requester_position'),
            'finance_name' => $this->text($request, 'finance_name', 'นางเรืองรอง คำปา'),
            'finance_position' => $this->text($request, 'finance_position', 'หัวหน้างานการเงิน การคลังและพัสดุ'),
            'approver_name' => $this->text($request, 'approver_name', 'ผู้ช่วยศาสตราจารย์ ดร.ภญ.ดรุณี หงษ์วิเศษ'),
            'approver_position' => $this->text($request, 'approver_position', 'รองคณบดี ปฏิบัติการแทน คณบดีคณะเภสัชศาสตร์'),
            'detail_rows' => array_map(function (array $row) {
                $amount = (float) ($row['amount'] ?? 0);

                return [
                    'company' => (string) ($row['company'] ?? ''),
                    'document_type' => (string) ($row['document_type'] ?? ''),
                    'document_date' => $this->thaiDate((string) ($row['document_date'] ?? '')),
                    'description' => (string) ($row['description'] ?? ''),
                    'amount' => $amount,
                    'amount_display' => ThaiBahtText::formatAmount($amount),
                ];
            }, $rows),
            'winners' => array_map(function (array $row) {
                $amount = (float) ($row['amount'] ?? 0);

                return [
                    'name' => (string) ($row['name'] ?? ''),
                    'amount' => $amount,
                    'amount_display' => ThaiBahtText::formatAmount($amount),
                    'amount_text' => ThaiBahtText::fromAmount($amount),
                ];
            }, $winners),
            'winner_total' => $winnerTotal > 0 ? $winnerTotal : $useAmount,
            'winner_total_display' => ThaiBahtText::formatAmount($winnerTotal > 0 ? $winnerTotal : $useAmount),
            'winner_total_text' => ThaiBahtText::fromAmount($winnerTotal > 0 ? $winnerTotal : $useAmount),
            'announce_subject' => $this->text($request, 'announce_subject', 'รายชื่อผู้ชนะการคัดเลือกจัดซื้อจัดจ้าง โดยวิธีเฉพาะเจาะจง'),
            'announce_date' => $this->announcementDate($this->text($request, 'announce_date', $this->text($request, 'document_date'))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeEight(Request $request): array
    {
        $detailRows = $this->rows($request->input('detail_rows', []), [
            'company', 'document_type', 'document_date', 'description', 'amount',
            'item', 'quantity', 'winner', 'proposed_price', 'agreed_price',
        ]);
        $detailTotal = $this->detailRowsTotal($detailRows);
        $amount = $detailTotal > 0 ? $detailTotal : $this->toAmount($request->input('amount'));
        $median = $this->toAmount($request->input('median_price', $amount));
        $budgetAmount = $this->toAmount($request->input('budget_amount', $amount));
        $allocated = $this->toAmount($request->input('allocated_budget', $amount));
        $purchaseItems = $this->rows($request->input('purchase_items', []), ['name', 'quantity']);
        if ($purchaseItems === [] && $detailRows !== []) {
            foreach ($detailRows as $row) {
                $name = trim((string) ($row['item'] ?? ''));
                if ($name === '') {
                    $name = trim(implode(' — ', array_filter([
                        trim((string) ($row['company'] ?? '')),
                        trim((string) ($row['description'] ?? '')),
                    ], static fn ($value) => $value !== '')));
                }
                if ($name === '') {
                    continue;
                }
                $qty = trim((string) ($row['quantity'] ?? ''));
                $purchaseItems[] = ['name' => $name, 'quantity' => $qty !== '' ? $qty : '1'];
            }
        }
        $specItems = $this->rows($request->input('spec_items', []), ['name', 'detail']);
        $priceItems = $this->rows($request->input('price_items', []), ['name']);
        $considerRows = $this->rows($request->input('consider_rows', []), ['item', 'winner', 'proposed_price', 'agreed_price']);
        $tor = str_replace(["\r\n", "\r"], "\n", (string) $request->input('tor', ''));
        if (trim($tor) === '') {
            $tor = '';
        }
        $inspector = $this->text($request, 'inspector', $this->text($request, 'spec_signer_name'));
        if ($considerRows === [] && $detailRows !== []) {
            foreach ($detailRows as $row) {
                $considerRows[] = [
                    'item' => (string) ($row['item'] ?? $row['description'] ?? ''),
                    'winner' => (string) ($row['winner'] ?? $row['company'] ?? ''),
                    'proposed_price' => $row['proposed_price'] ?? 0,
                    'agreed_price' => $row['agreed_price'] ?? $row['amount'] ?? 0,
                ];
            }
        }
        if ($considerRows === []) {
            $considerRows = [[
                'item' => $this->text($request, 'procurement'),
                'winner' => $this->text($request, 'winner_name'),
                'proposed_price' => $amount,
                'agreed_price' => $amount,
            ]];
        }
        $days = (int) $request->input('timeline_days', 0);
        if ($days <= 0) {
            if (preg_match('/(\d+)/', (string) $request->input('timeline', ''), $match)) {
                $days = (int) $match[1];
            }
        }
        if ($days <= 0) {
            $days = 15;
        }
        $timeline = $this->text($request, 'timeline', 'ภายใน '.$days.' วัน');
        $proposedTotal = collect($considerRows)->sum(static fn ($row) => (float) ($row['proposed_price'] ?? 0));
        $agreedTotal = collect($considerRows)->sum(static fn ($row) => (float) ($row['agreed_price'] ?? 0));

        return [
            'department' => $this->text($request, 'department'),
            'phone' => $this->text($request, 'phone'),
            'document_number' => $this->text($request, 'document_number'),
            'document_date' => $this->thaiDate($this->text($request, 'document_date')),
            'subject' => $this->text($request, 'subject', 'รายงานขอความเห็นชอบการจัดซื้อหรือจัดจ้างโดยวิธีเฉพาะเจาะจง'),
            'to' => $this->text($request, 'to', 'คณบดีคณะเภสัชศาสตร์'),
            'procurement' => $this->text($request, 'procurement'),
            'budget_source' => $this->text($request, 'budget_source'),
            'amount' => $amount,
            'amount_text' => ThaiBahtText::fromAmount($amount),
            'amount_display' => ThaiBahtText::formatAmount($amount),
            'reason' => $this->text($request, 'reason'),
            'scope' => $this->text($request, 'scope', 'เอกสารแนบ'),
            'tor' => $tor,
            'median_price' => $median,
            'median_price_display' => ThaiBahtText::formatAmount($median),
            'median_price_text' => ThaiBahtText::fromAmount($median),
            'budget_amount' => $budgetAmount,
            'budget_amount_display' => ThaiBahtText::formatAmount($budgetAmount),
            'budget_amount_text' => ThaiBahtText::fromAmount($budgetAmount),
            'timeline' => $timeline,
            'method' => $this->text(
                $request,
                'method',
                'จัดซื้อจัดจ้างโดยวิธีเฉพาะเจาะจง ตามมาตรา 56 (2) แห่งพระราชบัญญัติการจัดซื้อจัดจ้างและการบริหารพัสดุภาครัฐ พ.ศ. 2560 และข้อ 78 แห่งระเบียบกระทรวงการคลังว่าด้วยการจัดซื้อจัดจ้างและการบริหารพัสดุภาครัฐ พ.ศ. 2560'
            ),
            'criteria' => $this->text($request, 'criteria', 'ใช้เกณฑ์ราคา'),
            'inspector' => $inspector,
            'purchase_items' => $purchaseItems !== [] ? $purchaseItems : [['name' => $this->text($request, 'procurement'), 'quantity' => '1']],
            'requester_name' => $this->text($request, 'requester_name'),
            'requester_position' => $this->text($request, 'requester_position'),
            'officer_name' => $this->text($request, 'officer_name', 'นางเรืองรอง คำปา'),
            'officer_position' => $this->text($request, 'officer_position', 'หัวหน้าเจ้าหน้าที่'),
            'approver_name' => $this->text($request, 'approver_name', 'ผู้ช่วยศาสตราจารย์ ดร.ภญ.ดรุณี หงษ์วิเศษ'),
            'approver_position' => $this->text($request, 'approver_position', 'รองคณบดี ปฏิบัติการแทน คณบดีคณะเภสัชศาสตร์'),
            'spec_title' => $this->text($request, 'spec_title', 'คุณลักษณะเฉพาะ/TOR จ้างซื้อวัสดุ'),
            'spec_items' => $specItems !== [] ? $specItems : [['name' => $this->text($request, 'procurement'), 'detail' => '']],
            'spec_signer_name' => $this->text($request, 'spec_signer_name', $inspector),
            'spec_signer_position' => $this->text($request, 'spec_signer_position', 'ผู้กำหนดคุณลักษณะเฉพาะ/TOR'),
            'bk_project_name' => $this->text($request, 'bk_project_name'),
            'bk_owner' => $this->text($request, 'bk_owner', $this->text($request, 'department')),
            'allocated_budget' => $allocated,
            'allocated_budget_display' => ThaiBahtText::formatAmount($allocated),
            'allocated_budget_text' => ThaiBahtText::fromAmount($allocated),
            'price_date' => $this->thaiDate($this->text($request, 'price_date', $this->text($request, 'document_date'))),
            'price_items' => $priceItems !== [] ? $priceItems : [['name' => $this->text($request, 'procurement')]],
            'price_source' => $this->text($request, 'price_source', 'market'),
            'price_source_detail' => $this->text($request, 'price_source_detail'),
            'price_officer' => $this->text($request, 'price_officer', $this->text($request, 'inspector')),
            'consider_number' => $this->text($request, 'consider_number'),
            'consider_date' => $this->thaiDate($this->text($request, 'consider_date')),
            'consider_ref_number' => $this->text($request, 'consider_ref_number', $this->text($request, 'document_number')),
            'consider_ref_date' => $this->thaiDate($this->text($request, 'consider_ref_date', $this->text($request, 'document_date'))),
            'consider_rows' => array_map(function (array $row) {
                $proposed = (float) ($row['proposed_price'] ?? 0);
                $agreed = (float) ($row['agreed_price'] ?? 0);

                return [
                    'item' => (string) ($row['item'] ?? ''),
                    'winner' => (string) ($row['winner'] ?? ''),
                    'proposed_price' => $proposed,
                    'agreed_price' => $agreed,
                    'proposed_display' => ThaiBahtText::formatAmount($proposed),
                    'agreed_display' => ThaiBahtText::formatAmount($agreed),
                ];
            }, $considerRows),
            'proposed_total_display' => ThaiBahtText::formatAmount($proposedTotal > 0 ? $proposedTotal : $amount),
            'agreed_total_display' => ThaiBahtText::formatAmount($agreedTotal > 0 ? $agreedTotal : $amount),
            'agreed_total_text' => ThaiBahtText::fromAmount($agreedTotal > 0 ? $agreedTotal : $amount),
            'winner_name' => $this->text($request, 'winner_name'),
            'announce_subject' => $this->text($request, 'announce_subject', 'รายชื่อผู้ชนะการคัดเลือกจัดซื้อ/จัดจ้าง โดยวิธีเฉพาะเจาะจง'),
            'announce_date' => $this->announcementDate($this->text($request, 'announce_date', $this->text($request, 'consider_date'))),
        ];
    }

    /**
     * @param  mixed  $raw
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    private function rows(mixed $raw, array $keys): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $normalized = [];
            $hasValue = false;
            foreach ($keys as $key) {
                if (in_array($key, ['amount', 'proposed_price', 'agreed_price'], true)) {
                    $normalized[$key] = $this->toAmount($row[$key] ?? 0);
                    if ($normalized[$key] > 0) {
                        $hasValue = true;
                    }
                    continue;
                }
                $normalized[$key] = trim((string) ($row[$key] ?? ''));
                if ($normalized[$key] !== '') {
                    $hasValue = true;
                }
            }
            if ($hasValue) {
                $out[] = $normalized;
            }
        }

        return $out;
    }

    private function text(Request $request, string $key, string $default = ''): string
    {
        $value = trim((string) $request->input($key, ''));

        return $value !== '' ? $value : $default;
    }

    private function toAmount(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? (float) $value : 0.0;
        }
        $raw = trim((string) $value);
        $raw = str_replace([',', ' '], '', $raw);
        $raw = str_replace('.-', '', $raw);
        if ($raw === '' || ! is_numeric($raw)) {
            return 0.0;
        }

        return (float) $raw;
    }

    private function thaiDate(string $value): string
    {
        $date = $this->parseDate($value);
        if ($date === null) {
            return trim($value);
        }
        $months = [1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'];

        return ((int) $date->day).' '.($months[(int) $date->month] ?? '').' '.((int) $date->year + 543);
    }

    /**
     * @return array{day:string,month:string,year:string}
     */
    private function announcementDate(string $value): array
    {
        $date = $this->parseDate($value) ?? now();
        $months = [1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'];

        return [
            'day' => (string) ((int) $date->day),
            'month' => (string) ($months[(int) $date->month] ?? ''),
            'year' => (string) ((int) $date->year + 543),
        ];
    }

    private function parseDate(string $value): ?Carbon
    {
        $input = trim($value);
        if ($input === '') {
            return null;
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $input);
                if ($date !== false) {
                    if ((int) $date->year >= 2400) {
                        $date->subYears(543);
                    }

                    return $date;
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    /**
     * Resolve the creator's unit then job for PDF header "ส่วนงาน".
     *
     * @return array{name:string,phone:string,unit:string,job:string}
     */
    private function resolveWithdrawCreatorDepartment(int $withdrawId): array
    {
        $empty = ['name' => '', 'phone' => '', 'unit' => '', 'job' => ''];
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw')) {
            return $empty;
        }

        $row = DB::table('withdraw')->where('withdraw_id', $withdrawId)->first();
        if ($row === null) {
            return $empty;
        }

        $type = trim((string) ($row->withdraw_type ?? ''));
        if ($type === '') {
            $type = WithdrawSchema::TYPE_PROJECT;
        }

        $departmentCode = '';
        if (Schema::hasTable('withdraw_document')) {
            $documentRow = DB::table('withdraw_document')
                ->where('withdraw_document_type', $type)
                ->where('withdraw_id', $withdrawId)
                ->first(['department_code']);
            $departmentCode = trim((string) ($documentRow->department_code ?? ''));
        }

        $maps = $this->loadDepartmentApiReferenceMaps();
        $staffIds = array_values(array_filter([
            trim((string) ($row->withdraw_applicant_staff_id ?? '')),
            trim((string) ($row->staff_create ?? '')),
        ]));
        $staffUnit = $this->resolveStaffUnitDepartment($staffIds, $maps['name'], $maps['meta']);
        $fromCode = $departmentCode !== ''
            ? $this->resolveUnitDepartmentDisplay($departmentCode, $maps['name'], $maps['meta'])
            : ['name' => '', 'phone' => '', 'code' => '', 'unit' => '', 'job' => ''];

        $chosen = $fromCode['name'] !== '' ? $fromCode : $staffUnit;
        $name = (string) ($chosen['name'] ?? '');
        $unit = trim((string) ($chosen['unit'] ?? ''));
        $job = trim((string) ($chosen['job'] ?? ''));
        if ($unit === '' && $job === '' && $name !== '') {
            $split = $this->splitUnitAndJobDisplay($name);
            $unit = $split['unit'];
            $job = $split['job'];
        }
        $phone = trim((string) ($chosen['phone'] ?? ''));
        if ($phone === '' && $departmentCode !== '') {
            $phone = $this->resolveDepartmentPhone($departmentCode, $maps['meta']);
        }

        return [
            'name' => $name,
            'phone' => $phone,
            'unit' => $unit,
            'job' => $job,
        ];
    }

    /**
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
     * @param  array<int, string>  $staffIds
     * @param  array<string, string>  $nameMap
     * @param  array<string, array<string, string>>  $metaMap
     * @return array{name:string,phone:string,code:string,unit:string,job:string}
     */
    private function resolveStaffUnitDepartment(array $staffIds, array $nameMap, array $metaMap): array
    {
        $empty = ['name' => '', 'phone' => '', 'code' => '', 'unit' => '', 'job' => ''];
        foreach ($staffIds as $staffId) {
            $staffId = trim((string) $staffId);
            if ($staffId === '') {
                continue;
            }
            $staff = $this->fetchStaffRow($staffId);
            if (! is_array($staff) || $staff === []) {
                continue;
            }

            $unitCode = trim((string) ($staff['unit_code'] ?? ''));
            $agencyName = trim((string) (
                $staff['agency_name']
                ?? $staff['unit_name']
                ?? $staff['name_agency']
                ?? $staff['name_unit']
                ?? ''
            ));
            $jobName = trim((string) ($staff['job_name'] ?? $staff['name_job'] ?? ''));
            $fromCode = $unitCode !== ''
                ? $this->resolveUnitDepartmentDisplay($unitCode, $nameMap, $metaMap)
                : $empty;
            $unit = trim((string) ($fromCode['unit'] ?? ''));
            $job = trim((string) ($fromCode['job'] ?? ''));
            if ($unit === '') {
                $unit = $agencyName;
            }
            if ($job === '') {
                $job = $jobName;
            }
            $name = $fromCode['name'] !== ''
                ? $fromCode['name']
                : $this->formatUnitAndJobDisplay($unit, $job);
            $phone = $fromCode['phone'] !== '' ? $fromCode['phone'] : '';
            if ($name !== '') {
                return [
                    'name' => $name,
                    'phone' => $phone,
                    'code' => $unitCode,
                    'unit' => $unit,
                    'job' => $job,
                ];
            }
        }

        return $empty;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchStaffRow(string $staffId): ?array
    {
        $id = trim($staffId);
        if ($id === '') {
            return null;
        }

        $tokenRefreshUrl = trim((string) config('services.staff_rx_api.token_refresh_url', ''));
        $checkStaffUrl = trim((string) config('services.staff_rx_api.check_staff_url', ''));
        $listStaffUrl = trim((string) config('services.staff_rx_api.list_staff_url', ''));
        if ($tokenRefreshUrl === '' || ($checkStaffUrl === '' && $listStaffUrl === '')) {
            return null;
        }

        try {
            $refresh = Http::acceptJson()->timeout(10)->get($tokenRefreshUrl);
            if (! $refresh->successful()) {
                return null;
            }
            $bearerHeaderToken = trim((string) $refresh->json('token', ''));
            $rawBearerToken = trim((string) $refresh->json('raw_token', ''));
            if ($bearerHeaderToken === '' && $rawBearerToken === '') {
                return null;
            }

            $http = Http::acceptJson()->timeout(10);
            if ($rawBearerToken !== '') {
                $http = $http->withToken($rawBearerToken);
            } elseif (str_starts_with($bearerHeaderToken, 'Bearer ')) {
                $http = $http->withHeaders(['Authorization' => $bearerHeaderToken]);
            } else {
                $http = $http->withToken($bearerHeaderToken);
            }

            if ($checkStaffUrl !== '') {
                foreach ([['staffID' => $id], ['staff_id' => $id], ['id' => $id]] as $query) {
                    $response = $http->get($checkStaffUrl, $query);
                    if (! $response->successful()) {
                        continue;
                    }
                    $payload = $response->json();
                    $candidate = is_array($payload['data'] ?? null) ? $payload['data'] : (is_array($payload) ? $payload : []);
                    $candidate = $this->unwrapStaffRow($candidate);
                    if (is_array($candidate) && $candidate !== []) {
                        return $candidate;
                    }
                }
            }

            if ($listStaffUrl !== '') {
                $response = $http->timeout(20)->get($listStaffUrl);
                if ($response->successful()) {
                    $payload = $response->json();
                    $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];
                    foreach ($rows as $row) {
                        if (! is_array($row)) {
                            continue;
                        }
                        $candidateIds = [
                            trim((string) ($row['id'] ?? '')),
                            trim((string) ($row['code'] ?? '')),
                            trim((string) ($row['staff_id'] ?? '')),
                            trim((string) ($row['user_id'] ?? '')),
                        ];
                        if (in_array($id, $candidateIds, true)) {
                            return $this->unwrapStaffRow($row) ?? $row;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private function getWithdrawApplicantStaffId(int $withdrawId): string
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw')) {
            return '';
        }

        $selects = [];
        if (Schema::hasColumn('withdraw', 'withdraw_applicant_staff_id')) {
            $selects[] = 'withdraw_applicant_staff_id';
        }
        if (Schema::hasColumn('withdraw', 'staff_create')) {
            $selects[] = 'staff_create';
        }
        if ($selects === []) {
            return '';
        }

        $row = DB::table('withdraw')->where('withdraw_id', $withdrawId)->first($selects);
        if ($row === null) {
            return '';
        }
        $staffId = trim((string) ($row->withdraw_applicant_staff_id ?? ''));
        if ($staffId === '') {
            $staffId = trim((string) ($row->staff_create ?? ''));
        }

        return $staffId;
    }

    private function isEmptyOrPlaceholder(string $value): bool
    {
        $trimmed = trim($value);

        return $trimmed === '' || preg_match('/^\.+$/u', $trimmed) === 1;
    }

    private function resolveRequesterPosition(int $withdrawId): string
    {
        $staffId = $this->getWithdrawApplicantStaffId($withdrawId);
        if ($staffId === '') {
            return '';
        }

        $staff = $this->unwrapStaffRow($this->fetchStaffRow($staffId));
        $position = is_array($staff) ? $this->extractStaffPosition($staff) : '';
        if ($position !== '') {
            return $position;
        }

        return $this->resolveStaffPositionFromList($staffId, is_array($staff) ? $staff : []);
    }

    /**
     * @return array{name:string,position:string}
     */
    private function resolveFinanceManagerDisplay(int $withdrawId): array
    {
        $empty = ['name' => '', 'position' => ''];
        $staffId = $this->resolveFinanceManagerStaffId($withdrawId);
        if ($staffId === '') {
            return $empty;
        }

        $staff = $this->unwrapStaffRow($this->fetchStaffRow($staffId));
        if (! is_array($staff)) {
            return $empty;
        }

        return [
            'name' => StaffApiName::composeDisplayName($staff),
            'position' => $this->extractStaffPosition($staff),
        ];
    }

    private function resolveFinanceManagerStaffId(int $withdrawId): string
    {
        if ($withdrawId > 0 && Schema::hasTable('withdraw_approval_step') && Schema::hasColumn('withdraw_approval_step', 'withdraw_approval_staff_id')) {
            $fromStep = trim((string) (DB::table('withdraw_approval_step')
                ->where('withdraw_id', $withdrawId)
                ->where('withdraw_approval_step_code', 'finance_manager')
                ->value('withdraw_approval_staff_id') ?? ''));
            if ($fromStep !== '') {
                return $fromStep;
            }
        }

        if (! Schema::hasTable('withdraw_set_approver')) {
            return '';
        }

        $query = DB::table('withdraw_set_approver')
            ->where('withdraw_set_approver_step_code', 'finance_manager');
        if (Schema::hasColumn('withdraw_set_approver', 'withdraw_set_approver_status')) {
            $query->where('withdraw_set_approver_status', 1);
        }

        return trim((string) ($query->value('withdraw_set_approver_staff_id') ?? ''));
    }

    /**
     * @return array{decision:string,reason:string}
     */
    private function resolveWithdrawApprovalDecision(int $withdrawId): array
    {
        $empty = ['decision' => '', 'reason' => ''];
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw') || ! Schema::hasColumn('withdraw', 'withdraw_status')) {
            return $empty;
        }

        $status = strtolower(trim((string) (DB::table('withdraw')->where('withdraw_id', $withdrawId)->value('withdraw_status') ?? '')));
        if ($status === 'approved') {
            return ['decision' => 'approved', 'reason' => ''];
        }

        $reason = '';
        $rejected = false;
        if (Schema::hasTable('withdraw_approval_step')) {
            $query = DB::table('withdraw_approval_step')
                ->where('withdraw_id', $withdrawId)
                ->where('withdraw_approval_status', 'rejected')
                ->whereIn('withdraw_approval_step_code', ['faculty_secretary', 'vice_dean', 'dean'])
                ->orderByDesc('withdraw_approval_step_id');
            $selects = ['withdraw_approval_step_code'];
            if (Schema::hasColumn('withdraw_approval_step', 'withdraw_approval_comment')) {
                $selects[] = 'withdraw_approval_comment';
            }
            $rejectedStep = $query->first($selects);
            if ($rejectedStep !== null) {
                $rejected = true;
                $reason = trim((string) ($rejectedStep->withdraw_approval_comment ?? ''));
            }
        }

        if ($status === 'cancelled') {
            $rejected = true;
        }

        if (! $rejected) {
            return $empty;
        }

        if ($reason === '' && Schema::hasTable('withdraw_action_logs')) {
            $logQuery = DB::table('withdraw_action_logs')->where('withdraw_id', $withdrawId);
            if (Schema::hasColumn('withdraw_action_logs', 'action_code')) {
                $logQuery->whereIn('action_code', ['cancel_proceed', 'cancelled', 'reject', 'request_revision']);
            }
            if (Schema::hasColumn('withdraw_action_logs', 'action_at')) {
                $logQuery->orderByDesc('action_at');
            }
            if (Schema::hasColumn('withdraw_action_logs', 'id')) {
                $logQuery->orderByDesc('id');
            }
            $reason = trim((string) ($logQuery->value('comment') ?? ''));
        }

        return ['decision' => 'rejected', 'reason' => $reason];
    }

    /**
     * @return array{name:string,position_lines:array<int, string>}
     */
    private function resolveStepApproverDisplay(int $withdrawId, string $stepCode): array
    {
        $stepCode = trim($stepCode);
        if ($stepCode === '') {
            return ['name' => '', 'position_lines' => []];
        }

        $staffId = $this->resolveStepApproverStaffId($withdrawId, $stepCode);
        $name = '';
        $staff = null;
        if ($staffId !== '') {
            $staff = $this->unwrapStaffRow($this->fetchStaffRow($staffId));
            if (is_array($staff)) {
                $name = StaffApiName::composeDisplayName($staff);
            }
        }

        $positionLines = [];
        if (is_array($staff)) {
            $position = $this->extractStaffPosition($staff);
            if ($position !== '') {
                $positionLines = [$position];
            }
        }
        if ($positionLines === []) {
            $stepName = $this->resolveApprovalStepName($withdrawId, $stepCode);
            if ($stepName !== '') {
                $positionLines = [$stepName];
            }
        }
        if ($positionLines === [] && ! in_array($stepCode, ['vice_dean', 'dean', 'faculty_secretary'], true)) {
            $positionLines = EsignAttachedPdf::normalizeRequesterPositionLines('', $stepCode);
        }

        return [
            'name' => $name,
            'position_lines' => $positionLines,
        ];
    }

    private function resolveApprovalStepName(int $withdrawId, string $stepCode): string
    {
        $stepCode = trim($stepCode);
        if ($withdrawId <= 0 || $stepCode === '' || ! Schema::hasTable('withdraw_approval_step')) {
            return '';
        }
        if (! Schema::hasColumn('withdraw_approval_step', 'withdraw_approval_step_name')) {
            return '';
        }

        return trim((string) (DB::table('withdraw_approval_step')
            ->where('withdraw_id', $withdrawId)
            ->where('withdraw_approval_step_code', $stepCode)
            ->value('withdraw_approval_step_name') ?? ''));
    }

    /**
     * @return array{name:string,position_lines:array<int, string>}
     */
    private function resolveActualApproverDisplay(int $withdrawId): array
    {
        $approver = $this->resolveActualApprover($withdrawId);
        $staffId = $approver['staff_id'];
        $stepCode = $approver['step_code'] !== '' ? $approver['step_code'] : 'vice_dean';

        $name = '';
        if ($staffId !== '') {
            $staff = $this->unwrapStaffRow($this->fetchStaffRow($staffId));
            if (is_array($staff)) {
                $name = StaffApiName::composeDisplayName($staff);
            }
        }

        return [
            'name' => $name,
            'position_lines' => EsignAttachedPdf::normalizeRequesterPositionLines('', $stepCode),
        ];
    }

    /**
     * @return array{staff_id:string,step_code:string}
     */
    private function resolveActualApprover(int $withdrawId): array
    {
        $empty = ['staff_id' => '', 'step_code' => ''];
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_approval_step')) {
            return $empty;
        }

        $selects = ['withdraw_approval_step_code', 'withdraw_approval_staff_id'];
        if (Schema::hasColumn('withdraw_approval_step', 'staff_modify')) {
            $selects[] = 'staff_modify';
        }

        $query = DB::table('withdraw_approval_step')
            ->where('withdraw_id', $withdrawId)
            ->whereIn('withdraw_approval_step_code', ['faculty_secretary', 'vice_dean', 'dean']);
        if (Schema::hasColumn('withdraw_approval_step', 'withdraw_approval_status')) {
            $query->where('withdraw_approval_status', 'approved');
        }
        if (Schema::hasColumn('withdraw_approval_step', 'withdraw_approval_step_order')) {
            $query->orderByDesc('withdraw_approval_step_order');
        }
        $query->orderByDesc('withdraw_approval_step_id');

        $row = $query->first($selects);
        if ($row === null) {
            return $this->resolveLastExecutiveApprover($withdrawId);
        }

        $actorId = Schema::hasColumn('withdraw_approval_step', 'staff_modify')
            ? trim((string) ($row->staff_modify ?? ''))
            : '';
        $assignedId = trim((string) ($row->withdraw_approval_staff_id ?? ''));

        return [
            'staff_id' => $actorId !== '' ? $actorId : $assignedId,
            'step_code' => trim((string) ($row->withdraw_approval_step_code ?? '')),
        ];
    }

    /**
     * @return array{staff_id:string,step_code:string}
     */
    private function resolveLastExecutiveApprover(int $withdrawId): array
    {
        $empty = ['staff_id' => '', 'step_code' => ''];
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_approval_step')) {
            return $empty;
        }

        $query = DB::table('withdraw_approval_step')
            ->where('withdraw_id', $withdrawId)
            ->whereIn('withdraw_approval_step_code', ['faculty_secretary', 'vice_dean', 'dean']);
        if (Schema::hasColumn('withdraw_approval_step', 'withdraw_approval_step_order')) {
            $query->orderBy('withdraw_approval_step_order');
        }
        $rows = $query->get([
            'withdraw_approval_step_code',
            'withdraw_approval_staff_id',
        ]);
        $last = $empty;
        foreach ($rows as $row) {
            $staffId = trim((string) ($row->withdraw_approval_staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }
            $last = [
                'staff_id' => $staffId,
                'step_code' => trim((string) ($row->withdraw_approval_step_code ?? '')),
            ];
        }

        return $last;
    }

    private function resolveStepApproverStaffId(int $withdrawId, string $stepCode): string
    {
        $stepCode = trim($stepCode);
        if ($stepCode === '') {
            return '';
        }

        if ($withdrawId > 0 && Schema::hasTable('withdraw_approval_step') && Schema::hasColumn('withdraw_approval_step', 'withdraw_approval_staff_id')) {
            $fromStep = trim((string) (DB::table('withdraw_approval_step')
                ->where('withdraw_id', $withdrawId)
                ->where('withdraw_approval_step_code', $stepCode)
                ->value('withdraw_approval_staff_id') ?? ''));
            if ($fromStep !== '') {
                return $fromStep;
            }
        }

        if (! Schema::hasTable('withdraw_set_approver')) {
            return '';
        }

        $query = DB::table('withdraw_set_approver')
            ->where('withdraw_set_approver_step_code', $stepCode);
        if (Schema::hasColumn('withdraw_set_approver', 'withdraw_set_approver_status')) {
            $query->where('withdraw_set_approver_status', 1);
        }

        return trim((string) ($query->value('withdraw_set_approver_staff_id') ?? ''));
    }

    /**
     * @param  array<string, mixed>  $staff
     */
    private function extractStaffPosition(array $staff): string
    {
        $candidates = [
            $staff['name_position'] ?? '',
            $staff['position'] ?? '',
            $staff['position_name'] ?? '',
            $staff['academic_position'] ?? '',
            $staff['name_academic_position'] ?? '',
            $staff['job_position'] ?? '',
            data_get($staff, 'data.name_position'),
            data_get($staff, 'data.position'),
            data_get($staff, 'data.position_name'),
        ];
        foreach ($candidates as $candidate) {
            $position = trim((string) $candidate);
            if ($position !== '') {
                return $position;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $staff
     */
    private function resolveStaffPositionFromList(string $staffId, array $staff): string
    {
        $wanted = array_values(array_unique(array_filter([
            trim($staffId),
            trim((string) ($staff['id'] ?? '')),
            trim((string) ($staff['staff_id'] ?? '')),
            trim((string) ($staff['code'] ?? '')),
            trim((string) ($staff['user_id'] ?? '')),
        ], static fn ($id) => $id !== '')));
        if ($wanted === []) {
            return '';
        }

        $tokenRefreshUrl = trim((string) config('services.staff_rx_api.token_refresh_url', ''));
        $listStaffUrl = trim((string) config('services.staff_rx_api.list_staff_url', ''));
        if ($tokenRefreshUrl === '' || $listStaffUrl === '') {
            return '';
        }

        try {
            $refresh = Http::acceptJson()->timeout(10)->get($tokenRefreshUrl);
            if (! $refresh->successful()) {
                return '';
            }
            $bearerHeaderToken = trim((string) $refresh->json('token', ''));
            $rawBearerToken = trim((string) $refresh->json('raw_token', ''));
            if ($bearerHeaderToken === '' && $rawBearerToken === '') {
                return '';
            }

            $http = Http::acceptJson()->timeout(20);
            if ($rawBearerToken !== '') {
                $http = $http->withToken($rawBearerToken);
            } elseif (str_starts_with($bearerHeaderToken, 'Bearer ')) {
                $http = $http->withHeaders(['Authorization' => $bearerHeaderToken]);
            } else {
                $http = $http->withToken($bearerHeaderToken);
            }

            $response = $http->get($listStaffUrl);
            if (! $response->successful()) {
                return '';
            }
            $payload = $response->json();
            $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $candidateIds = array_values(array_filter([
                    trim((string) ($row['id'] ?? '')),
                    trim((string) ($row['staff_id'] ?? '')),
                    trim((string) ($row['code'] ?? '')),
                    trim((string) ($row['user_id'] ?? '')),
                ], static fn ($id) => $id !== ''));
                if (array_intersect($wanted, $candidateIds) === []) {
                    continue;
                }
                $position = $this->extractStaffPosition($row);
                if ($position !== '') {
                    return $position;
                }
            }
        } catch (\Throwable) {
            return '';
        }

        return '';
    }

    private function resolveApprovedStepActorStaffId(int $withdrawId, string $stepCode): string
    {
        $stepCode = trim($stepCode);
        if ($withdrawId <= 0 || $stepCode === '' || ! Schema::hasTable('withdraw_approval_step')) {
            return '';
        }

        $selects = ['withdraw_approval_status', 'withdraw_approval_staff_id'];
        if (Schema::hasColumn('withdraw_approval_step', 'staff_modify')) {
            $selects[] = 'staff_modify';
        }

        $row = DB::table('withdraw_approval_step')
            ->where('withdraw_id', $withdrawId)
            ->where('withdraw_approval_step_code', $stepCode)
            ->first($selects);
        if ($row === null || strtolower(trim((string) ($row->withdraw_approval_status ?? ''))) !== 'approved') {
            return '';
        }

        $actorId = Schema::hasColumn('withdraw_approval_step', 'staff_modify')
            ? trim((string) ($row->staff_modify ?? ''))
            : '';

        return $actorId !== '' ? $actorId : trim((string) ($row->withdraw_approval_staff_id ?? ''));
    }

    private function resolveApprovedExecutiveActorStaffId(int $withdrawId): string
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw_approval_step')) {
            return '';
        }

        $selects = ['withdraw_approval_staff_id'];
        if (Schema::hasColumn('withdraw_approval_step', 'staff_modify')) {
            $selects[] = 'staff_modify';
        }

        $query = DB::table('withdraw_approval_step')
            ->where('withdraw_id', $withdrawId)
            ->whereIn('withdraw_approval_step_code', ['faculty_secretary', 'vice_dean', 'dean']);
        if (Schema::hasColumn('withdraw_approval_step', 'withdraw_approval_status')) {
            $query->where('withdraw_approval_status', 'approved');
        }
        if (Schema::hasColumn('withdraw_approval_step', 'withdraw_approval_step_order')) {
            $query->orderByDesc('withdraw_approval_step_order');
        }
        $query->orderByDesc('withdraw_approval_step_id');

        $row = $query->first($selects);
        if ($row === null) {
            return '';
        }

        $actorId = Schema::hasColumn('withdraw_approval_step', 'staff_modify')
            ? trim((string) ($row->staff_modify ?? ''))
            : '';

        return $actorId !== '' ? $actorId : trim((string) ($row->withdraw_approval_staff_id ?? ''));
    }

    private function resolveRequesterSignatureDataUri(int $withdrawId): string
    {
        return $this->resolveStaffSignatureDataUri($this->getWithdrawApplicantStaffId($withdrawId));
    }

    private function resolveStaffSignatureDataUri(string $staffId): string
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return '';
        }

        $cached = $this->getCachedSignatureDataUri($staffId);
        if (str_starts_with($cached, 'data:image/') && ! str_starts_with($cached, 'data:image/svg')) {
            return $cached;
        }

        $tokenRefreshUrl = trim((string) config('services.staff_rx_api.token_refresh_url', ''));
        $bearerHeaderToken = '';
        $rawBearerToken = '';
        if ($tokenRefreshUrl !== '') {
            try {
                $refresh = Http::acceptJson()->timeout(10)->get($tokenRefreshUrl);
                if ($refresh->successful()) {
                    $bearerHeaderToken = trim((string) $refresh->json('token', ''));
                    $rawBearerToken = trim((string) $refresh->json('raw_token', ''));
                }
            } catch (\Throwable) {
            }
        }

        $candidateIds = [$staffId];
        $staff = $this->unwrapStaffRow($this->fetchStaffRow($staffId));
        if (is_array($staff)) {
            foreach (['code', 'staff_id', 'id', 'user_id'] as $key) {
                $candidate = trim((string) ($staff[$key] ?? ''));
                if ($candidate !== '') {
                    $candidateIds[] = $candidate;
                }
            }
            $fromStaff = $this->extractStaffSignatureUrl($staff);
            if (str_starts_with($fromStaff, 'data:image/') && ! str_starts_with($fromStaff, 'data:image/svg')) {
                $this->putCachedSignatureDataUri($staffId, $fromStaff);

                return $fromStaff;
            }
            if ($fromStaff !== '' && $this->looksLikeSignatureUrl($fromStaff)) {
                $fromStaffUri = $this->downloadSignatureAsDataUri($fromStaff, $bearerHeaderToken, $rawBearerToken);
                if ($fromStaffUri !== '') {
                    $this->putCachedSignatureDataUri($staffId, $fromStaffUri);

                    return $fromStaffUri;
                }
            }
        }

        $rawUrl = $this->fetchStaffSignatureRawUrl(
            array_values(array_unique(array_filter($candidateIds))),
            $bearerHeaderToken,
            $rawBearerToken
        );
        if ($rawUrl === '') {
            return '';
        }
        if (str_starts_with($rawUrl, 'data:image/')) {
            return str_starts_with($rawUrl, 'data:image/svg') ? '' : $rawUrl;
        }

        $dataUri = $this->downloadSignatureAsDataUri($rawUrl, $bearerHeaderToken, $rawBearerToken);
        if ($dataUri !== '') {
            $this->putCachedSignatureDataUri($staffId, $dataUri);
        }

        return $dataUri;
    }

    private function getCachedSignatureDataUri(string $staffId): string
    {
        try {
            return trim((string) Cache::get('withdraw:signature:data-uri:'.$staffId, ''));
        } catch (\Throwable) {
            return '';
        }
    }

    private function putCachedSignatureDataUri(string $staffId, string $dataUri): void
    {
        try {
            Cache::put('withdraw:signature:data-uri:'.$staffId, $dataUri, now()->addMinutes(10));
        } catch (\Throwable) {
        }
    }

    /**
     * @param  mixed  $staff
     * @return array<string, mixed>|null
     */
    private function unwrapStaffRow(mixed $staff): ?array
    {
        if (! is_array($staff) || $staff === []) {
            return null;
        }
        if (array_is_list($staff) && isset($staff[0]) && is_array($staff[0])) {
            return $staff[0];
        }

        return $staff;
    }

    /**
     * @param  array<string, mixed>  $staff
     */
    private function extractStaffSignatureUrl(array $staff): string
    {
        $candidates = [
            $staff['sign_url'] ?? '',
            $staff['signature_url'] ?? '',
            $staff['signature'] ?? '',
            data_get($staff, 'data.sign_url'),
            data_get($staff, 'data.signature_url'),
            data_get($staff, 'data.signature'),
            data_get($staff, 'data.0.sign_url'),
            data_get($staff, 'data.0.signature_url'),
            data_get($staff, 'data.0.signature'),
            data_get($staff, '0.sign_url'),
            data_get($staff, '0.signature_url'),
            data_get($staff, '0.signature'),
        ];
        foreach ($candidates as $candidate) {
            $url = $this->normalizeStaffSignatureUrl((string) $candidate);
            if ($url !== '') {
                return $url;
            }
        }

        return '';
    }

    /**
     * Signature API payloads often put the image at data.url / url.
     *
     * @param  array<string, mixed>  $payload
     */
    private function extractSignatureUrlFromPayload(array $payload): string
    {
        if (is_string($payload['data'] ?? null)) {
            $fromString = $this->normalizeStaffSignatureUrl((string) $payload['data']);
            if ($fromString !== '') {
                return $fromString;
            }
        }

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

        foreach ($candidates as $candidate) {
            $url = $this->normalizeStaffSignatureUrl((string) $candidate);
            if ($url !== '') {
                return $url;
            }
        }

        return '';
    }

    private function looksLikeSignatureUrl(string $url): bool
    {
        $normalized = strtolower(trim($url));
        if ($normalized === '') {
            return false;
        }
        if (str_starts_with($normalized, 'data:image/')) {
            return ! str_starts_with($normalized, 'data:image/svg');
        }

        return str_contains($normalized, 'signatureproxy')
            || str_contains($normalized, 'staffsignature')
            || str_contains($normalized, '/sign');
    }

    /**
     * @param  array<int, string>  $candidateIds
     */
    private function fetchStaffSignatureRawUrl(array $candidateIds, string $bearerHeaderToken, string $rawBearerToken): string
    {
        $signatureApi = trim((string) config('services.staff_rx_api.signature_api_base', 'https://api.pharmacy.cmu.ac.th/api/staffSignature?staffID='));
        $signatureEndpoint = 'https://api.pharmacy.cmu.ac.th/api/staffSignature';
        if ($signatureApi !== '' && str_contains($signatureApi, '?')) {
            $signatureEndpoint = (string) (parse_url($signatureApi, PHP_URL_SCHEME) ?? 'https')
                .'://'.(string) (parse_url($signatureApi, PHP_URL_HOST) ?? 'api.pharmacy.cmu.ac.th')
                .(string) (parse_url($signatureApi, PHP_URL_PATH) ?? '/api/staffSignature');
        }

        foreach ($candidateIds as $candidateId) {
            $id = trim((string) $candidateId);
            if ($id === '') {
                continue;
            }
            foreach ([['staffID' => $id], ['staff_id' => $id], ['id' => $id], ['code' => $id]] as $query) {
                try {
                    $http = Http::acceptJson()->timeout(15);
                    if ($rawBearerToken !== '') {
                        $http = $http->withToken($rawBearerToken);
                    } elseif (str_starts_with($bearerHeaderToken, 'Bearer ')) {
                        $http = $http->withHeaders(['Authorization' => $bearerHeaderToken]);
                    } elseif ($bearerHeaderToken !== '') {
                        $http = $http->withToken($bearerHeaderToken);
                    }

                    $response = $http->get($signatureEndpoint, $query);
                    if (! $response->successful()) {
                        continue;
                    }

                    $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type', ''))[0]));
                    $body = (string) $response->body();
                    $fromBinary = $this->signatureBinaryToDataUri($contentType, $body);
                    if ($fromBinary !== '') {
                        return $fromBinary;
                    }

                    $payload = $response->json();
                    $resolved = $this->extractSignatureUrlFromPayload(is_array($payload) ? $payload : []);
                    if ($resolved !== '') {
                        return $resolved;
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return '';
    }

    private function normalizeStaffSignatureUrl(string $url): string
    {
        $normalized = trim($url);
        if ($normalized === '') {
            return '';
        }
        $normalized = preg_replace('#/signatureProxy\.php\?#i', '/api/signatureProxy?', $normalized) ?? $normalized;
        $normalized = preg_replace('#^http://#i', 'https://', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function downloadSignatureAsDataUri(string $url, string $bearerHeaderToken = '', string $rawBearerToken = ''): string
    {
        $url = $this->normalizeStaffSignatureUrl($url);
        if ($url === '') {
            return '';
        }

        $candidates = [];
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $candidates[] = $url;
        } else {
            $apiBase = 'https://api.pharmacy.cmu.ac.th';
            $normalizedPath = ltrim($url, '/');
            if ($normalizedPath !== '') {
                $candidates[] = $apiBase.'/'.$normalizedPath;
                $candidates[] = $apiBase.'/'.str_replace('%2F', '/', rawurlencode($normalizedPath));
                $candidates[] = $apiBase.'/'.str_replace(' ', '%20', $normalizedPath);
            }
        }

        foreach ($candidates as $candidateUrl) {
            $clients = [];
            $authHttp = Http::timeout(20);
            if ($rawBearerToken !== '') {
                $authHttp = $authHttp->withToken($rawBearerToken);
            } elseif ($bearerHeaderToken !== '') {
                if (str_starts_with($bearerHeaderToken, 'Bearer ')) {
                    $authHttp = $authHttp->withHeaders(['Authorization' => $bearerHeaderToken]);
                } else {
                    $authHttp = $authHttp->withToken($bearerHeaderToken);
                }
            }
            $clients[] = $authHttp;
            $clients[] = Http::timeout(20);

            foreach ($clients as $httpClient) {
                try {
                    $response = $httpClient->get($candidateUrl);
                    if (! $response->successful()) {
                        continue;
                    }
                    $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type', ''))[0]));
                    $body = (string) $response->body();
                    if ($body === '') {
                        continue;
                    }
                    if (str_contains($contentType, 'application/json')) {
                        $payload = $response->json();
                        $nestedUrl = $this->extractSignatureUrlFromPayload(is_array($payload) ? $payload : []);
                        if ($nestedUrl !== '' && $nestedUrl !== $candidateUrl) {
                            $nested = $this->downloadSignatureAsDataUri($nestedUrl, $bearerHeaderToken, $rawBearerToken);
                            if ($nested !== '') {
                                return $nested;
                            }
                        }
                        continue;
                    }
                    $dataUri = $this->signatureBinaryToDataUri($contentType, $body);
                    if ($dataUri !== '') {
                        return $dataUri;
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return '';
    }

    private function signatureBinaryToDataUri(string $contentType, string $body): string
    {
        if ($body === '') {
            return '';
        }

        $mime = $contentType;
        if ($mime === '' || $mime === 'application/octet-stream' || $mime === 'binary/octet-stream') {
            $mime = $this->detectImageMimeFromBinary($body);
        }
        if ($mime === '' || ! str_starts_with($mime, 'image/') || str_contains($mime, 'svg')) {
            $sniffed = $this->detectImageMimeFromBinary($body);
            if ($sniffed === '') {
                return '';
            }
            $mime = $sniffed;
        }

        return 'data:'.$mime.';base64,'.base64_encode($body);
    }

    private function detectImageMimeFromBinary(string $body): string
    {
        if (str_starts_with($body, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }
        if (str_starts_with($body, "\xff\xd8\xff")) {
            return 'image/jpeg';
        }
        if (str_starts_with($body, 'GIF87a') || str_starts_with($body, 'GIF89a')) {
            return 'image/gif';
        }
        if (str_starts_with($body, 'RIFF') && str_contains(substr($body, 0, 16), 'WEBP')) {
            return 'image/webp';
        }

        return '';
    }

    /**
     * @param  array<string, string>  $nameMap
     * @param  array<string, array<string, string>>  $metaMap
     * @return array{name:string,phone:string,code:string,unit:string,job:string}
     */
    private function resolveUnitDepartmentDisplay(string $departmentCode, array $nameMap, array $metaMap): array
    {
        $departmentCode = trim($departmentCode);
        $empty = ['name' => '', 'phone' => '', 'code' => $departmentCode, 'unit' => '', 'job' => ''];
        if ($departmentCode === '') {
            return $empty;
        }

        $mappedMeta = isset($metaMap[$departmentCode]) && is_array($metaMap[$departmentCode])
            ? $metaMap[$departmentCode]
            : [];
        $mappedLabel = trim((string) ($nameMap[$departmentCode] ?? ''));
        $mappedParts = array_values(array_filter(array_map('trim', explode('>', $mappedLabel))));

        $unitName = trim((string) ($mappedMeta['unitName'] ?? ($mappedParts[2] ?? '')));
        $jobName = trim((string) ($mappedMeta['jobName'] ?? ($mappedParts[1] ?? '')));
        $phone = $this->resolveDepartmentPhone($departmentCode, $metaMap);

        if ($unitName === '' && $jobName !== '') {
            $units = [];
            foreach ($metaMap as $code => $meta) {
                if (! is_array($meta)) {
                    continue;
                }
                if (trim((string) ($meta['jobName'] ?? '')) !== $jobName) {
                    continue;
                }
                $candidate = trim((string) ($meta['unitName'] ?? ''));
                if ($candidate === '') {
                    continue;
                }
                $units[$candidate] = [
                    'name' => $this->formatUnitAndJobDisplay($candidate, $jobName),
                    'phone' => $this->resolveDepartmentPhone((string) $code, $metaMap),
                    'code' => (string) $code,
                    'unit' => $candidate,
                    'job' => $jobName,
                ];
            }
            $unitList = array_values($units);
            if (count($unitList) === 1) {
                return $unitList[0];
            }
        }

        if ($unitName !== '') {
            return [
                'name' => $this->formatUnitAndJobDisplay($unitName, $jobName),
                'phone' => $phone,
                'code' => $departmentCode,
                'unit' => $unitName,
                'job' => $jobName,
            ];
        }

        return $empty;
    }

    private function formatUnitAndJobDisplay(string $unitName, string $jobName): string
    {
        $unitName = trim($unitName);
        $jobName = trim($jobName);
        if ($unitName !== '' && $jobName !== '') {
            return $unitName.'  '.$jobName;
        }

        return $unitName !== '' ? $unitName : $jobName;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{name?:string,phone?:string,unit?:string,job?:string}  $creatorDepartment
     * @return array<string, mixed>
     */
    private function enrichW119Opening(array $data, Request $request, array $creatorDepartment): array
    {
        $rows = is_array($data['detail_rows'] ?? null) ? $data['detail_rows'] : [];
        $unit = trim((string) ($creatorDepartment['unit'] ?? ($data['department_unit'] ?? '')));
        $job = trim((string) ($creatorDepartment['job'] ?? ($data['department_job'] ?? '')));
        if ($unit === '' && $job === '') {
            $split = $this->splitUnitAndJobDisplay((string) ($data['department'] ?? ''));
            $unit = $split['unit'];
            $job = $split['job'];
        }

        $withdrawId = (int) $request->input('withdraw_id', $data['withdraw_id'] ?? 0);
        $purpose = $this->resolveWithdrawProjectActivityDetail($withdrawId);
        if ($purpose === '') {
            $purpose = trim((string) ($data['purpose'] ?? ''));
            $purpose = trim((string) preg_replace('/^เพื่อ\s*/u', '', $purpose));
            if ($this->isProjectTitleAsPurpose($purpose, $withdrawId)) {
                $purpose = '';
            }
            // ไม่ใช้ชื่อกิจกรรมแทน — ต้องเป็นรายละเอียดการจัดกิจกรรมจากระบบโครงการ
            $activityName = $this->resolveWithdrawProjectActivityName($withdrawId);
            if ($activityName !== '' && ($purpose === $activityName || str_contains($purpose, $activityName))) {
                $purpose = '';
            }
        }
        $itemCount = count($rows);
        if ($itemCount <= 0) {
            $itemCount = 1;
        }
        $attachCount = $this->countProcurementAttachments($request, $rows);
        if ($attachCount <= 0) {
            $attachCount = $itemCount;
        }
        $budgetName = $this->resolveWithdrawBudgetName(
            (int) $request->input('withdraw_id', 0),
            (int) $request->input('withdraw_item_id', $data['withdraw_item_id'] ?? 0)
        );

        $data['department_unit'] = $unit;
        $data['department_job'] = $job;
        $data['item_count'] = $itemCount;
        $data['receipt_documents'] = $this->resolveReceiptDocumentLabel($rows);
        $data['attachment_count'] = (string) $attachCount;
        $data['purpose'] = $purpose;
        if ($budgetName !== '') {
            $data['budget_source'] = $budgetName;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function procurementDocumentNumberDisplay(array $payload): string
    {
        $display = trim((string) ($payload['document_number_display'] ?? ''));
        if ($display !== '') {
            return $display;
        }
        $documentNumber = trim((string) ($payload['document_number'] ?? ''));
        $runningNo = (int) ($payload['running_no'] ?? 0);
        if ($documentNumber !== '' && $runningNo > 0) {
            return $documentNumber.$runningNo;
        }

        return $documentNumber;
    }

    /**
     * @param  array{document_number?:string,running_no?:int}  $meta
     */
    private function formatWithdrawDocumentNumberDisplay(array $meta): string
    {
        $documentNumber = trim((string) ($meta['document_number'] ?? ''));
        $runningNo = (int) ($meta['running_no'] ?? 0);
        if ($documentNumber !== '' && $runningNo > 0) {
            return $documentNumber.$runningNo;
        }

        return $documentNumber;
    }

    private function issuedProcurementNumberDisplay(int $withdrawId, string $documentType): string
    {
        if ($withdrawId <= 0 || $documentType === '' || ! Schema::hasTable('withdraw_document')) {
            return '';
        }

        $row = DB::table('withdraw_document')
            ->where('withdraw_id', $withdrawId)
            ->where('withdraw_document_type', $documentType)
            ->first(['document_number', 'running_no']);
        if ($row === null) {
            return '';
        }

        return $this->formatWithdrawDocumentNumberDisplay([
            'document_number' => (string) ($row->document_number ?? ''),
            'running_no' => (int) ($row->running_no ?? 0),
        ]);
    }

    /**
     * @param  array{document_date?:string}  $meta
     */
    private function formatWithdrawDocumentDateDisplay(array $meta): string
    {
        $raw = trim((string) ($meta['document_date'] ?? ''));
        if ($raw === '') {
            return '';
        }

        return $this->thaiDate($raw);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{document_number:string,running_no:int,document_date:string}
     */
    private function resolveWithdrawDocumentMeta(int $withdrawId, array $payload = []): array
    {
        $empty = ['document_number' => '', 'running_no' => 0, 'document_date' => ''];
        $procurementId = (int) ($payload['id'] ?? $payload['withdraw_project_procurement_id'] ?? 0);

        if ($procurementId > 0 && Schema::hasTable('withdraw_document')) {
            $procurementDoc = DB::table('withdraw_document')
                ->where('withdraw_document_type', 'project_procurement:'.$procurementId)
                ->where('withdraw_id', $withdrawId)
                ->first(['document_number', 'running_no', 'document_date']);
            if ($procurementDoc !== null) {
                $number = trim((string) ($procurementDoc->document_number ?? ''));
                $running = (int) ($procurementDoc->running_no ?? 0);
                $date = trim((string) ($procurementDoc->document_date ?? ''));
                if ($number !== '' || $running > 0 || $date !== '') {
                    return [
                        'document_number' => $number !== '' ? $number : trim((string) ($payload['document_number'] ?? '')),
                        'running_no' => $running > 0 ? $running : (int) ($payload['running_no'] ?? 0),
                        'document_date' => $date !== '' ? $date : trim((string) ($payload['document_date'] ?? '')),
                    ];
                }
            }
        }

        $fromPayloadDisplay = $this->procurementDocumentNumberDisplay($payload);
        $fromPayloadRunning = (int) ($payload['running_no'] ?? 0);
        $fromPayloadNumber = trim((string) ($payload['document_number'] ?? ''));
        $fromPayloadDate = trim((string) ($payload['document_date'] ?? ''));
        if ($fromPayloadRunning > 0 || ($fromPayloadDisplay !== '' && $fromPayloadDisplay !== $fromPayloadNumber)) {
            return [
                'document_number' => $fromPayloadNumber,
                'running_no' => $fromPayloadRunning,
                'document_date' => $fromPayloadDate,
            ];
        }

        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw') || ! Schema::hasTable('withdraw_document')) {
            return [
                'document_number' => $fromPayloadNumber,
                'running_no' => $fromPayloadRunning,
                'document_date' => $fromPayloadDate,
            ];
        }

        $type = trim((string) (DB::table('withdraw')->where('withdraw_id', $withdrawId)->value('withdraw_type') ?? ''));
        if ($type === '') {
            $type = WithdrawSchema::TYPE_PROJECT;
        }

        $selects = ['document_number', 'running_no'];
        if (Schema::hasColumn('withdraw_document', 'document_date')) {
            $selects[] = 'document_date';
        }

        $row = DB::table('withdraw_document')
            ->where('withdraw_document_type', $type)
            ->where('withdraw_id', $withdrawId)
            ->first($selects);
        if ($row === null && $type !== WithdrawSchema::TYPE_PROJECT) {
            $row = DB::table('withdraw_document')
                ->where('withdraw_document_type', WithdrawSchema::TYPE_PROJECT)
                ->where('withdraw_id', $withdrawId)
                ->first($selects);
        }
        if ($row === null) {
            return [
                'document_number' => $fromPayloadNumber,
                'running_no' => $fromPayloadRunning,
                'document_date' => $fromPayloadDate,
            ];
        }

        return [
            'document_number' => trim((string) ($row->document_number ?? '')) ?: $fromPayloadNumber,
            'running_no' => (int) ($row->running_no ?? 0) ?: $fromPayloadRunning,
            'document_date' => trim((string) ($row->document_date ?? '')) ?: $fromPayloadDate,
        ];
    }

    /**
     * @return array{unit:string,job:string}
     */
    private function splitUnitAndJobDisplay(string $display): array
    {
        $display = trim($display);
        if ($display === '') {
            return ['unit' => '', 'job' => ''];
        }
        if (preg_match('/^(.*?)\s{2,}(.+)$/u', $display, $matches) === 1) {
            return ['unit' => trim((string) $matches[1]), 'job' => trim((string) $matches[2])];
        }
        if (preg_match('/^(.*?)\s+(งาน\S.*)$/u', $display, $matches) === 1) {
            return ['unit' => trim((string) $matches[1]), 'job' => trim((string) $matches[2])];
        }
        if (str_starts_with($display, 'งาน')) {
            return ['unit' => '', 'job' => $display];
        }

        return ['unit' => $display, 'job' => ''];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function resolveReceiptDocumentLabel(array $rows): string
    {
        $hasReceipt = false;
        $hasVoucher = false;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $text = trim((string) ($row['document_type'] ?? ''));
            if ($text === '') {
                continue;
            }
            if (mb_strpos($text, 'ใบเสร็จ') !== false) {
                $hasReceipt = true;
            }
            if (mb_strpos($text, 'ใบสำคัญ') !== false) {
                $hasVoucher = true;
            }
        }
        if ($hasVoucher && $hasReceipt) {
            return 'ใบสำคัญรับเงิน/ใบเสร็จ';
        }
        if ($hasReceipt) {
            return 'ใบเสร็จ';
        }
        if ($hasVoucher) {
            return 'ใบสำคัญรับเงิน';
        }

        return 'ใบสำคัญรับเงิน';
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function countProcurementAttachments(Request $request, array $rows): int
    {
        $procurementId = (int) $request->input('id', $request->input('withdraw_project_procurement_id', 0));
        $count = 0;
        if ($procurementId > 0 && Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)) {
            $count += (int) DB::table(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)
                ->where('withdraw_project_procurement_id', $procurementId)
                ->count();
        }
        if ($count <= 0) {
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $files = is_array($row['files'] ?? null) ? $row['files'] : [];
                $count += count($files);
            }
        }
        $uploads = $request->file('detail_files');
        if (is_array($uploads)) {
            foreach ($uploads as $rowFiles) {
                if (is_array($rowFiles)) {
                    $count += count($rowFiles);
                } elseif ($rowFiles !== null) {
                    $count += 1;
                }
            }
        }

        return $count;
    }

    private function resolveWithdrawProjectActivityDetail(int $withdrawId): string
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw')) {
            return '';
        }

        $columns = ['withdraw_paragraph_2'];
        if (Schema::hasColumn('withdraw', 'withdraw_project_activity_detail')) {
            $columns[] = 'withdraw_project_activity_detail';
        }
        if (Schema::hasColumn('withdraw', 'withdraw_project_activity_name')) {
            $columns[] = 'withdraw_project_activity_name';
        }
        $row = DB::table('withdraw')->where('withdraw_id', $withdrawId)->first($columns);
        if (! $row) {
            return '';
        }

        $storedDetail = trim((string) ($row->withdraw_project_activity_detail ?? ''));
        if ($storedDetail !== '') {
            return $storedDetail;
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

            // ป้าย "กิจกรรม" ต้องอยู่ต้นบรรทัดเท่านั้น (กัน match คำว่า กิจกรรม กลางประโยค)
            $activityLabeled = $this->extractLineAnchoredMemoValue(
                $paragraph2,
                'กิจกรรม',
                ['รายละเอียดการดำเนินการ', 'รายละเอียดการจัดกิจกรรม', 'รายละเอียดกิจกรรม', 'รายละเอียดค่าใช้จ่ายทั้งสิ้น']
            );
            if ($activityLabeled !== '') {
                return $activityLabeled;
            }
        }

        // ไม่ fallback เป็นชื่อกิจกรรม/ชื่อโครงการ — ต้องใช้รายละเอียดการจัดกิจกรรมจาก API เท่านั้น
        return '';
    }

    private function resolveWithdrawProjectAmount(int $withdrawId): float
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw')) {
            return 0.0;
        }

        $columns = [];
        foreach (['withdraw_project_amount', 'withdraw_project_id', 'withdraw_project_code'] as $column) {
            if (Schema::hasColumn('withdraw', $column)) {
                $columns[] = $column;
            }
        }
        if ($columns === []) {
            return 0.0;
        }

        $row = DB::table('withdraw')->where('withdraw_id', $withdrawId)->first($columns);
        if ($row === null) {
            return 0.0;
        }

        $stored = isset($row->withdraw_project_amount) ? (float) $row->withdraw_project_amount : 0.0;
        if ($stored > 0) {
            return $stored;
        }

        return \App\Support\ProjectWithdrawHandoff::resolveProjectAmount(
            trim((string) ($row->withdraw_project_id ?? '')),
            trim((string) ($row->withdraw_project_code ?? ''))
        );
    }

    private function resolveWithdrawProjectActivityName(int $withdrawId, ?object $row = null): string
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw')) {
            return '';
        }
        if ($row === null) {
            if (! Schema::hasColumn('withdraw', 'withdraw_project_activity_name')) {
                return '';
            }
            $row = DB::table('withdraw')
                ->where('withdraw_id', $withdrawId)
                ->first(['withdraw_project_activity_name']);
        }

        return trim((string) ($row->withdraw_project_activity_name ?? ''));
    }

    private function isProjectTitleAsPurpose(string $purpose, int $withdrawId): bool
    {
        $purpose = trim($purpose);
        if ($purpose === '') {
            return false;
        }
        if (str_contains($purpose, 'รหัสโครงการ')) {
            return true;
        }
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw')) {
            return false;
        }

        $columns = [];
        if (Schema::hasColumn('withdraw', 'withdraw_project_name')) {
            $columns[] = 'withdraw_project_name';
        }
        if (Schema::hasColumn('withdraw', 'withdraw_project_code')) {
            $columns[] = 'withdraw_project_code';
        }
        if ($columns === []) {
            return false;
        }
        $row = DB::table('withdraw')->where('withdraw_id', $withdrawId)->first($columns);
        if (! $row) {
            return false;
        }
        $projectName = trim((string) ($row->withdraw_project_name ?? ''));
        $projectCode = trim((string) ($row->withdraw_project_code ?? ''));
        if ($projectName !== '' && (str_contains($purpose, $projectName) || $purpose === $projectName)) {
            return true;
        }
        if ($projectCode !== '' && str_contains($purpose, $projectCode)) {
            return true;
        }

        return false;
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
        if ($startLine === $label) {
            // value on following lines
        } elseif (str_starts_with($startLine, $label)) {
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

    private function extractLabeledMemoValue(string $text, string $label, string $nextLabel = ''): string
    {
        // ใช้เฉพาะป้ายที่ขึ้นต้นบรรทัด เพื่อไม่ให้ match คำกลางประโยค
        return $this->extractLineAnchoredMemoValue(
            $text,
            $label,
            $nextLabel !== '' ? [$nextLabel] : []
        );
    }

    private function resolveWithdrawBudgetName(int $withdrawId, int $withdrawItemId = 0): string
    {
        if ($withdrawId <= 0 || ! Schema::hasTable('withdraw')) {
            return '';
        }

        $budgetManageId = 0;
        if (
            $withdrawItemId > 0
            && Schema::hasTable('withdraw_item')
            && Schema::hasColumn('withdraw_item', 'withdraw_item_budget_manage_id')
        ) {
            $budgetManageId = (int) (DB::table('withdraw_item')
                ->where('withdraw_id', $withdrawId)
                ->where('withdraw_item_id', $withdrawItemId)
                ->value('withdraw_item_budget_manage_id') ?? 0);
        }
        if ($budgetManageId <= 0 && Schema::hasColumn('withdraw', 'withdraw_budget_manage_id')) {
            $budgetManageId = (int) (DB::table('withdraw')
                ->where('withdraw_id', $withdrawId)
                ->value('withdraw_budget_manage_id') ?? 0);
        }
        if (
            $budgetManageId <= 0
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

            $row = DB::connection('budget')
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
            $fullName = $this->formatBudgetFullName($row);
            if ($fullName !== '') {
                return $fullName;
            }

            return trim((string) ($row?->budget_manage_code ?? ''));
        } catch (\Throwable) {
            return '';
        }
    }

    private function formatBudgetFullName(?object $row): string
    {
        if ($row === null) {
            return '';
        }

        $activityName = trim((string) ($row->budget_activity_name ?? ''));
        $payDetail = trim((string) ($row->budget_category_pay_detail ?? ''));
        $parts = [
            trim(trim((string) ($row->budget_source_name ?? '')).'ปี '.trim((string) ($row->budget_manage_year ?? ''))),
            trim((string) ($row->budget_plan_name ?? '')),
            trim(trim((string) ($row->budget_event_name ?? '')).($activityName !== '' ? '-'.$activityName : '')),
            trim((string) ($row->budget_fund_name ?? '')),
            trim((string) ($row->budget_category_budget_name ?? '')),
            trim(trim((string) ($row->budget_category_pay_name ?? '')).($payDetail !== '' ? '-'.$payDetail : '')),
        ];

        return trim(implode(' ', array_values(array_filter($parts, static fn ($part) => $part !== ''))));
    }

    /**
     * @param  array<string, array<string, string>>  $metaMap
     */
    private function resolveDepartmentPhone(string $departmentCode, array $metaMap): string
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

    /**
     * @return array{url:?string,height:float,width:float}
     */
    private function krutMeta(): array
    {
        $path = public_path('images/krut.jpg');
        $url = file_exists($path) && is_readable($path) ? 'file://'.str_replace('\\', '/', (string) realpath($path)) : null;
        $height = 15.0;
        $width = 15.0;
        if ($path && file_exists($path)) {
            $size = @getimagesize($path);
            if (is_array($size) && (int) ($size[0] ?? 0) > 0 && (int) ($size[1] ?? 0) > 0) {
                $width = round($height * ((float) $size[0] / (float) $size[1]), 2);
            }
        }

        return ['url' => $url, 'height' => $height, 'width' => $width];
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
        } catch (\Throwable) {
            return $dataUri;
        }

        return $dataUri;
    }

    private function renderPdf(string $html, string $filename): Response
    {
        $tempDir = storage_path('framework/cache/mpdf');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }
        $fontStoragePath = storage_path('fonts');
        if (! File::exists($fontStoragePath)) {
            File::makeDirectory($fontStoragePath, 0775, true);
        }

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFontConfig = (new FontVariables())->getDefaults();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDir,
            'margin_left' => 20,
            'margin_right' => 18,
            'margin_top' => 12,
            'margin_bottom' => 14,
            'margin_footer' => 2,
            'fontDir' => array_merge($defaultConfig['fontDir'], [
                storage_path('fonts'),
                resource_path('fonts'),
            ]),
            'fontdata' => ($defaultFontConfig['fontdata'] ?? []) + [
                'thsarabunnew' => [
                    'R' => 'THSarabunNew.ttf',
                    'B' => 'THSarabunNew-Bold.ttf',
                ],
                'sarabun' => [
                    'R' => 'THSarabunNew.ttf',
                    'B' => 'THSarabunNew-Bold.ttf',
                ],
            ],
            'default_font' => 'thsarabunnew',
            'shrink_tables_to_fit' => 0,
            'setAutoBottomMargin' => false,
        ]);
        $mpdf->keep_table_proportions = true;
        $mpdf->WriteHTML($html);
        $safe = trim((string) preg_replace('/[\\\\\/:\*\?"<>\|\x00-\x1F]+/u', '-', $filename), ' .-');
        if ($safe === '') {
            $safe = 'procurement.pdf';
        }

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$safe.'"',
        ]);
    }
}
