<?php

namespace App\Http\Controllers\Withdraw;

use App\Http\Controllers\Controller;
use App\Support\ProjectWithdrawExternalAccess;
use App\Support\WithdrawSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WithdrawProjectExternalApiController extends Controller
{
    public function __construct(
        private readonly WithdrawGeneralController $generalController,
        private readonly WithdrawProjectProcurementController $procurementController,
    ) {}

    /**
     * รายการเบิกจ่ายโครงการตามรหัสโครงการ สำหรับระบบโครงการเรียกดู
     *
     * GET /withdraw/api/project/external/withdraws?project_code=XXX&sig=YYY
     * Header: X-PBM-Signature (optional alternative to sig query)
     */
    public function index(Request $request): JsonResponse
    {
        $projectCode = trim((string) $request->query('project_code', $request->input('project_code', '')));
        if ($projectCode === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาระบุรหัสโครงการ (project_code)',
            ], 422);
        }

        if (! ProjectWithdrawExternalAccess::verifyProjectCodeRequest($projectCode, ProjectWithdrawExternalAccess::extractSignature($request))) {
            return response()->json([
                'status' => 'error',
                'message' => 'ลายเซ็นคำขอไม่ถูกต้อง',
            ], 403);
        }

        if (! Schema::hasTable('withdraw')) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบตารางข้อมูล'], 500);
        }

        $rowsQuery = DB::table('withdraw')
            ->where('withdraw_type', WithdrawSchema::TYPE_PROJECT)
            ->orderByDesc('withdraw_id');

        if (Schema::hasColumn('withdraw', 'withdraw_project_code')) {
            $rowsQuery->where('withdraw_project_code', $projectCode);
        } else {
            return response()->json([
                'status' => 'success',
                'meta' => ['project_code' => $projectCode, 'count' => 0],
                'data' => [],
            ]);
        }

        $rows = $rowsQuery->get();
        $data = [];
        foreach ($rows as $row) {
            $data[] = $this->generalController->buildProjectExternalWithdrawPayload($row, $projectCode);
        }

        return response()->json([
            'status' => 'success',
            'meta' => [
                'project_code' => $projectCode,
                'count' => count($data),
            ],
            'data' => $data,
        ]);
    }

    /**
     * รายการที่ยืนยันจัดทำเอกสารเบิกจ่ายแล้ว แต่ยังไม่บันทึกในระบบเบิกจ่าย
     *
     * GET /withdraw/api/project/external/pending-confirms?project_code=XXX&sig=YYY
     * Optional: staff_id
     */
    public function pendingConfirms(Request $request): JsonResponse
    {
        $projectCode = trim((string) $request->query('project_code', $request->input('project_code', '')));
        $staffId = trim((string) $request->query('staff_id', $request->input('staff_id', '')));
        $signaturePayload = $projectCode !== '' ? $projectCode : ($staffId !== '' ? $staffId : 'pending');

        if (! ProjectWithdrawExternalAccess::verifySignature($signaturePayload, ProjectWithdrawExternalAccess::extractSignature($request))) {
            // Allow empty-secret bridge in local/dev the same way as other external endpoints.
            if (ProjectWithdrawExternalAccess::bridgeSecret() !== '') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ลายเซ็นคำขอไม่ถูกต้อง',
                ], 403);
            }
        }

        $items = \App\Support\ProjectWithdrawHandoff::listPendingLocal($staffId !== '' ? $staffId : null);
        if ($projectCode !== '') {
            $items = array_values(array_filter(
                $items,
                static fn (array $row): bool => trim((string) ($row['project_code'] ?? '')) === $projectCode
            ));
        }

        return response()->json([
            'status' => 'success',
            'meta' => [
                'project_code' => $projectCode,
                'staff_id' => $staffId,
                'count' => count($items),
            ],
            'data' => $items,
        ]);
    }

    public function memoPdf(Request $request)
    {
        if (! $this->verifyPdfAccess($request, ['withdraw_id', 'project_code'])) {
            abort(403, 'ลายเซ็นลิงก์ไม่ถูกต้องหรือหมดอายุ');
        }

        $withdrawId = (int) $request->query('withdraw_id', 0);
        if (! $this->withdrawBelongsToProjectCode($withdrawId, trim((string) $request->query('project_code', '')))) {
            abort(404, 'ไม่พบรายการเบิกจ่าย');
        }

        return $this->generalController->memoPdf((string) $withdrawId);
    }

    public function approveMemoPdf(Request $request)
    {
        if (! $this->verifyPdfAccess($request, ['withdraw_id', 'project_code'])) {
            abort(403, 'ลายเซ็นลิงก์ไม่ถูกต้องหรือหมดอายุ');
        }

        $withdrawId = (int) $request->query('withdraw_id', 0);
        $projectCode = trim((string) $request->query('project_code', ''));
        if (! $this->withdrawBelongsToProjectCode($withdrawId, $projectCode)) {
            abort(404, 'ไม่พบรายการเบิกจ่าย');
        }

        return $this->generalController->projectApproveMemoPdf($request);
    }

    public function printPdf(Request $request)
    {
        if (! $this->verifyPdfAccess($request, ['withdraw_id', 'project_code'])) {
            abort(403, 'ลายเซ็นลิงก์ไม่ถูกต้องหรือหมดอายุ');
        }

        $withdrawId = (int) $request->query('withdraw_id', 0);
        $projectCode = trim((string) $request->query('project_code', ''));
        if (! $this->withdrawBelongsToProjectCode($withdrawId, $projectCode)) {
            abort(404, 'ไม่พบรายการเบิกจ่าย');
        }

        return $this->generalController->projectPrintPdf($request);
    }

    public function file(Request $request, string $id)
    {
        if (! $this->verifyPdfAccess($request, ['withdraw_id', 'project_code', 'file_id'])) {
            abort(403, 'ลายเซ็นลิงก์ไม่ถูกต้องหรือหมดอายุ');
        }

        $withdrawId = (int) $request->query('withdraw_id', 0);
        if ((int) $id !== $withdrawId) {
            abort(404, 'ไม่พบรายการเบิกจ่าย');
        }
        if (! $this->withdrawBelongsToProjectCode($withdrawId, trim((string) $request->query('project_code', '')))) {
            abort(404, 'ไม่พบรายการเบิกจ่าย');
        }

        return $this->generalController->apiFile($id, $request);
    }

    public function procurementPdf(Request $request, int $id)
    {
        if (! $this->verifyPdfAccess($request, ['withdraw_id', 'project_code', 'procurement_id'])) {
            abort(403, 'ลายเซ็นลิงก์ไม่ถูกต้องหรือหมดอายุ');
        }

        $withdrawId = (int) $request->query('withdraw_id', 0);
        $procurementId = (int) $request->query('procurement_id', 0);
        if ($procurementId !== $id || ! $this->procurementBelongsToWithdraw($procurementId, $withdrawId)) {
            abort(404, 'ไม่พบเอกสารจัดซื้อจัดจ้าง');
        }
        if (! $this->withdrawBelongsToProjectCode($withdrawId, trim((string) $request->query('project_code', '')))) {
            abort(404, 'ไม่พบรายการเบิกจ่าย');
        }

        return $this->procurementController->pdfById($id);
    }

    public function procurementFile(Request $request, int $id)
    {
        if (! $this->verifyPdfAccess($request, ['withdraw_id', 'project_code', 'file_id'])) {
            abort(403, 'ลายเซ็นลิงก์ไม่ถูกต้องหรือหมดอายุ');
        }

        $withdrawId = (int) $request->query('withdraw_id', 0);
        if (! $this->procurementFileBelongsToWithdraw($id, $withdrawId)) {
            abort(404, 'ไม่พบไฟล์เอกสารจัดซื้อจัดจ้าง');
        }
        if (! $this->withdrawBelongsToProjectCode($withdrawId, trim((string) $request->query('project_code', '')))) {
            abort(404, 'ไม่พบรายการเบิกจ่าย');
        }

        return $this->procurementController->file($id);
    }

    /**
     * @param  list<string>  $signedKeys
     */
    private function verifyPdfAccess(Request $request, array $signedKeys): bool
    {
        return ProjectWithdrawExternalAccess::verifySignedRequest($request, $signedKeys);
    }

    private function withdrawBelongsToProjectCode(int $withdrawId, string $projectCode): bool
    {
        $withdrawId = (int) $withdrawId;
        $projectCode = trim($projectCode);
        if ($withdrawId <= 0 || $projectCode === '' || ! Schema::hasTable('withdraw')) {
            return false;
        }

        return DB::table('withdraw')
            ->where('withdraw_id', $withdrawId)
            ->where('withdraw_type', WithdrawSchema::TYPE_PROJECT)
            ->where('withdraw_project_code', $projectCode)
            ->exists();
    }

    private function procurementBelongsToWithdraw(int $procurementId, int $withdrawId): bool
    {
        if ($procurementId <= 0 || $withdrawId <= 0 || ! Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)) {
            return false;
        }

        return DB::table(WithdrawSchema::PROJECT_PROCUREMENT_TABLE)
            ->where('withdraw_project_procurement_id', $procurementId)
            ->where('withdraw_id', $withdrawId)
            ->exists();
    }

    private function procurementFileBelongsToWithdraw(int $fileId, int $withdrawId): bool
    {
        if ($fileId <= 0 || $withdrawId <= 0 || ! Schema::hasTable(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE)) {
            return false;
        }

        return DB::table(WithdrawSchema::PROJECT_PROCUREMENT_FILE_TABLE.' as f')
            ->join(WithdrawSchema::PROJECT_PROCUREMENT_TABLE.' as p', 'p.withdraw_project_procurement_id', '=', 'f.withdraw_project_procurement_id')
            ->where('f.withdraw_project_procurement_file_id', $fileId)
            ->where('p.withdraw_id', $withdrawId)
            ->exists();
    }
}
