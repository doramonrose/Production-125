<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UsageLogRecordContext
{
    /** @var list<string> */
    private const PATH_ID_ROUTES = [
        'withdraw.general.api.detail',
        'withdraw.general.api.file',
        'withdraw.general.memo-pdf',
        'withdraw.general.memo-finance-pdf',
        'withdraw.project.memo-pdf',
        'withdraw.project.memo-finance-pdf',
        'withdraw.salary.api.detail',
        'withdraw.salary.api.file',
        'withdraw.salary.memo-pdf',
        'withdraw.travel.memo-pdf',
        'withdraw.salary.api.one-planning-preview',
        'withdraw.salary.api.one-planning-send',
    ];

    /** @var list<string> */
    private const BODY_ID_ROUTES = [
        'withdraw.general.api.save-draft',
        'withdraw.salary.api.save-draft',
        'withdraw.general.api.delete',
        'withdraw.salary.api.delete',
        'withdraw.general.api.proceed',
        'withdraw.salary.api.proceed',
        'withdraw.salary.api.resend-notification',
        'withdraw.project.api.procurement.list',
        'withdraw.project.api.procurement.save',
        'withdraw.project.api.procurement.pdf',
        'withdraw.project.api.procurement-pdf',
        'withdraw.project.api.procurement.show',
        'withdraw.project.api.procurement.winners',
    ];

    /**
     * @param  list<int>  $ids
     * @return array<int, array{type_label: string, line: string}>
     */
    public function load(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === [] || ! \Illuminate\Support\Facades\Schema::hasTable('withdraw')) {
            return [];
        }

        $columns = ['withdraw_id', 'withdraw_type', 'withdraw_subject'];
        foreach (['withdraw_sub_type', 'withdraw_project_name', 'withdraw_project_code', 'withdraw_project_activity_name'] as $column) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('withdraw', $column)) {
                $columns[] = $column;
            }
        }

        $rows = \Illuminate\Support\Facades\DB::table('withdraw')
            ->whereIn('withdraw_id', $ids)
            ->get($columns);
        $documents = $this->documentsFor($ids);
        $resolved = [];
        foreach ($rows as $row) {
            $id = (int) ($row->withdraw_id ?? 0);
            if ($id <= 0) {
                continue;
            }
            $resolved[$id] = $this->formatRow($row, $documents[$id] ?? []);
        }

        return $resolved;
    }

    /**
     * @return array{id: int, action: string, type_label: string, line: string, scope: string}
     */
    public function fromRequest(Request $request, ?Response $response = null): array
    {
        $routeName = (string) ($request->route()?->getName() ?? '');
        $payload = $request->input();
        $payload = is_array($payload) ? $payload : [];
        $query = $request->query();
        $id = $this->idFromParts($routeName, $request->path(), $payload, $query);
        if ($id <= 0) {
            $id = $this->idFromSaveResponse($routeName, $response);
        }

        return $this->assemble($id, $payload, $query);
    }

    /**
     * @return list<string>|null
     */
    public function listedItems(?Response $response, string $routeName): ?array
    {
        if (! in_array($routeName, ['withdraw.general.api.list', 'withdraw.salary.api.list'], true) || $response === null) {
            return null;
        }
        $contentType = strtolower((string) $response->headers->get('content-type', ''));
        if (! str_contains($contentType, 'json')) {
            return null;
        }
        $decoded = json_decode((string) $response->getContent(), true);
        $data = is_array($decoded) ? ($decoded['data'] ?? null) : null;
        if (! is_array($data)) {
            return null;
        }

        $lines = [];
        foreach ($data as $item) {
            if (! is_array($item)) {
                continue;
            }
            $id = (int) ($item['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            if (count($lines) >= 40) {
                $lines[] = 'และรายการอื่นที่แสดงในหน้านั้นอีก';
                break;
            }
            $parts = ['รายการ #'.$id];
            $type = $this->clean((string) ($item['type'] ?? ''));
            if ($type !== '') {
                $parts[] = $type;
            }
            $code = $this->clean((string) ($item['code'] ?? ''));
            if ($code !== '' && $code !== '-') {
                $parts[] = 'เลขที่ '.$code;
            }
            $project = $this->clean((string) ($item['project_name'] ?? ''));
            if ($project !== '') {
                $parts[] = 'โครงการ '.$project;
            }
            $subject = $this->clean((string) ($item['subject'] ?? ''));
            if ($subject !== '' && $subject !== '-') {
                $parts[] = 'เรื่อง '.$subject;
            }
            $lines[] = implode(' · ', $parts);
        }

        return $lines;
    }

    /**
     * @return array{id: int, action: string, type_label: string, line: string, scope: string}
     */
    public function inspectLog(object $log): array
    {
        $payload = $this->decodeJson($log->request_payload ?? null);
        $query = $this->decodeJson($log->query_params ?? null);
        $id = $this->idFromParts((string) ($log->route_name ?? ''), (string) ($log->request_path ?? ''), $payload, $query);

        return [
            'id' => $id,
            'action' => trim((string) ($payload['action'] ?? '')),
            'type_label' => $this->typeLabel(
                (string) ($payload['withdraw_type'] ?? ''),
                (string) ($payload['salary_request_type'] ?? '')
            ),
            'line' => $this->lineFromInput($id, $payload, $query),
            'scope' => $this->scopeLabel((string) ($query['scope'] ?? '')),
        ];
    }

    /**
     * @return array{id: int, action: string, type_label: string, line: string, scope: string}
     */
    public function fromLog(object $log): array
    {
        $payload = $this->decodeJson($log->request_payload ?? null);
        $query = $this->decodeJson($log->query_params ?? null);

        return $this->assemble(
            $this->idFromParts((string) ($log->route_name ?? ''), (string) ($log->request_path ?? ''), $payload, $query),
            $payload,
            $query
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     * @return array{id: int, action: string, type_label: string, line: string, scope: string}
     */
    private function assemble(int $id, array $payload, array $query): array
    {
        $loaded = $id > 0 ? ($this->load([$id])[$id] ?? null) : null;
        $typeLabel = (string) ($loaded['type_label'] ?? $this->typeLabel(
            (string) ($payload['withdraw_type'] ?? ''),
            (string) ($payload['salary_request_type'] ?? '')
        ));
        $line = (string) ($loaded['line'] ?? '');
        if ($line === '') {
            $line = $this->lineFromInput($id, $payload, $query);
        }

        return [
            'id' => $id,
            'action' => trim((string) ($payload['action'] ?? '')),
            'type_label' => $typeLabel,
            'line' => $line,
            'scope' => $this->scopeLabel((string) ($query['scope'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     */
    private function idFromParts(string $routeName, string $path, array $payload, array $query): int
    {
        $withdrawId = (int) ($query['withdraw_id'] ?? $payload['withdraw_id'] ?? 0);
        if ($withdrawId > 0) {
            return $withdrawId;
        }

        if (in_array($routeName, self::PATH_ID_ROUTES, true) && preg_match('#/(\d+)(?:/|$)#', $path, $matches) === 1) {
            return (int) $matches[1];
        }

        if (in_array($routeName, self::BODY_ID_ROUTES, true)) {
            return max(0, (int) ($payload['id'] ?? 0));
        }

        return 0;
    }

    private function idFromSaveResponse(string $routeName, ?Response $response): int
    {
        if (! in_array($routeName, ['withdraw.general.api.save-draft', 'withdraw.salary.api.save-draft'], true) || $response === null) {
            return 0;
        }
        $contentType = strtolower((string) $response->headers->get('content-type', ''));
        if (! str_contains($contentType, 'json')) {
            return 0;
        }
        $decoded = json_decode((string) $response->getContent(), true);

        return is_array($decoded) ? max(0, (int) data_get($decoded, 'data.id', 0)) : 0;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, list<object>>
     */
    private function documentsFor(array $ids): array
    {
        if ($ids === [] || ! \Illuminate\Support\Facades\Schema::hasTable('withdraw_document')) {
            return [];
        }

        $grouped = [];
        $rows = \Illuminate\Support\Facades\DB::table('withdraw_document')
            ->whereIn('withdraw_id', $ids)
            ->get(['withdraw_id', 'withdraw_document_type', 'document_number', 'running_no']);
        foreach ($rows as $row) {
            $grouped[(int) $row->withdraw_id][] = $row;
        }

        return $grouped;
    }

    /**
     * @param  list<object>  $documents
     * @return array{type_label: string, line: string}
     */
    private function formatRow(object $row, array $documents): array
    {
        $type = trim((string) ($row->withdraw_type ?? ''));
        $subType = trim((string) ($row->withdraw_sub_type ?? ''));
        $typeLabel = $this->typeLabel($type, $subType);
        $parts = ['รายการ #'.(int) $row->withdraw_id];

        $document = $this->pickDocument($type, $documents);
        $documentNo = '';
        if ($document !== null) {
            $base = trim((string) ($document->document_number ?? ''));
            $runningNo = (int) ($document->running_no ?? 0);
            $documentNo = $base !== '' && $runningNo > 0 ? $base.$runningNo : $base;
        }
        if ($documentNo !== '') {
            $parts[] = 'เลขที่ '.$documentNo;
        }

        if ($type === WithdrawSchema::TYPE_PROJECT) {
            $projectName = $this->clean((string) ($row->withdraw_project_name ?? ''));
            $projectCode = $this->clean((string) ($row->withdraw_project_code ?? ''));
            if ($projectName !== '') {
                $parts[] = 'โครงการ '.$projectName.($projectCode !== '' ? ' ('.$projectCode.')' : '');
            } elseif ($projectCode !== '') {
                $parts[] = 'รหัสโครงการ '.$projectCode;
            }
            $activity = $this->clean((string) ($row->withdraw_project_activity_name ?? ''));
            if ($activity !== '' && $activity !== $projectName) {
                $parts[] = 'กิจกรรม '.$activity;
            }
        }

        $subject = $this->clean((string) ($row->withdraw_subject ?? ''));
        if ($subject !== '') {
            $parts[] = 'เรื่อง '.$subject;
        }

        return [
            'type_label' => $typeLabel,
            'line' => implode(' · ', $parts),
        ];
    }

    /**
     * @param  list<object>  $documents
     */
    private function pickDocument(string $type, array $documents): ?object
    {
        foreach ($documents as $document) {
            if (trim((string) ($document->withdraw_document_type ?? '')) === $type) {
                return $document;
            }
        }
        foreach ($documents as $document) {
            if (trim((string) ($document->document_number ?? '')) !== '') {
                return $document;
            }
        }

        return $documents[0] ?? null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     */
    private function lineFromInput(int $id, array $payload, array $query): string
    {
        $parts = [];
        if ($id > 0) {
            $parts[] = 'รายการ #'.$id;
        }
        $projectName = $this->clean((string) ($payload['project_name'] ?? $query['project_name'] ?? ''));
        $projectCode = $this->clean((string) ($payload['project_code'] ?? $query['project_code'] ?? ''));
        $projectId = $this->clean((string) ($payload['project_id'] ?? $query['project_id'] ?? ''));
        if ($projectName !== '') {
            $parts[] = 'โครงการ '.$projectName.($projectCode !== '' ? ' ('.$projectCode.')' : '');
        } elseif ($projectCode !== '') {
            $parts[] = 'รหัสโครงการ '.$projectCode;
        } elseif ($projectId !== '') {
            $parts[] = 'รหัสโครงการ '.$projectId;
        }
        $subject = $this->clean((string) ($payload['subject'] ?? ''));
        if ($subject !== '') {
            $parts[] = 'เรื่อง '.$subject;
        }
        $documentNo = $this->clean((string) ($payload['document_number'] ?? ''));
        if ($documentNo !== '') {
            $parts[] = 'เลขที่ '.$documentNo;
        }

        return implode(' · ', $parts);
    }

    private function typeLabel(string $withdrawType, string $subType): string
    {
        $withdrawType = trim($withdrawType);
        if ($withdrawType === WithdrawSchema::TYPE_PROJECT) {
            return 'เบิกจ่ายเงินโครงการ';
        }
        if ($withdrawType === WithdrawSchema::TYPE_SALARY) {
            return match ($this->salarySubType($subType)) {
                'salary_university_staff' => 'เบิกจ่ายเงินเดือนพนักงานมหาวิทยาลัย',
                'position_allowance' => 'เบิกจ่ายเงินประจำตำแหน่ง',
                'pts' => 'เบิกจ่ายเงิน พ.ต.ส.',
                'travel_leave' => 'เบิกจ่ายในการเดินทางไปปฏิบัติงาน',
                'salary_civil_servant' => trim($subType) === '' ? 'เบิกจ่ายเงินเดือน/ค่าจ้าง' : 'เบิกจ่ายเงินเดือนข้าราชการ',
                default => 'เบิกจ่ายเงินเดือน/ค่าจ้าง',
            };
        }
        if ($withdrawType === WithdrawSchema::TYPE_GENERAL) {
            return 'เบิกจ่ายทั่วไป';
        }

        return '';
    }

    private function salarySubType(string $subType): string
    {
        $subType = strtolower(trim($subType));

        return $subType === 'salary' ? 'salary_civil_servant' : $subType;
    }

    private function scopeLabel(string $scope): string
    {
        return match (trim($scope)) {
            'reviewer-pending' => 'เฉพาะรายการที่รออนุมัติ',
            'document-review' => 'เฉพาะรายการที่รอตรวจสอบเอกสาร',
            default => '',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(mixed $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function clean(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        if ($value === '' || $value === '..........') {
            return '';
        }

        return mb_strlen($value) > 180 ? mb_substr($value, 0, 180).'…' : $value;
    }
}
