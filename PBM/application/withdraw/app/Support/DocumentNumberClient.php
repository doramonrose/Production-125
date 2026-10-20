<?php

namespace App\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Http;

class DocumentNumberClient
{
    /**
     * @return array{document_number: string, running_no: int, full_number: string}
     */
    public function issueForWithdraw(int $withdrawId, string $withdrawType, string $departmentCode): array
    {
        $departmentCode = trim($departmentCode);
        $withdrawType = trim($withdrawType);
        if ($withdrawId <= 0 || $departmentCode === '' || $withdrawType === '') {
            $this->fail('ไม่พบหน่วยงานสำหรับออกเลขที่หนังสือ');
        }

        $safeType = preg_replace('/[^A-Za-z0-9._:-]/', '', $withdrawType) ?? '';
        if ($safeType === '') {
            $safeType = 'memo';
        }

        $issued = $this->request('POST', [], [
            'action' => 'issue',
            'type' => 'org',
            'code' => $departmentCode,
            'subject' => $this->subject($withdrawType, $withdrawId),
            'reference' => $this->reference($withdrawType, $withdrawId),
            'requested_by' => $this->requestedBy(),
        ], $this->idempotencyKey($withdrawId, $safeType));

        return $this->parts($issued);
    }

    /**
     * @return array{document_number: string, running_no: int, full_number: string}
     */
    public function preview(string $departmentCode): array
    {
        $departmentCode = trim($departmentCode);
        if ($departmentCode === '') {
            $this->fail('ไม่พบหน่วยงานสำหรับออกเลขที่หนังสือ');
        }

        $preview = $this->request('GET', [
            'type' => 'org',
            'code' => $departmentCode,
            'preview' => '1',
        ], null, null);

        return $this->parts($preview);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{document_number: string, running_no: int, full_number: string}
     */
    private function parts(array $data): array
    {
        $fullNumber = trim((string) ($data['full_number'] ?? ''));
        $runningNo = (int) ($data['sequence_no'] ?? $data['sequence'] ?? $data['running_no'] ?? 0);
        $base = trim((string) ($data['number_base'] ?? ''));
        if ($base === '' && $fullNumber !== '' && $runningNo > 0) {
            $base = preg_replace('/\d+$/u', '', $fullNumber) ?? '';
        }
        $base = trim($base);
        if ($base === '') {
            $prefix = trim((string) ($data['prefix'] ?? ''));
            $systemCode = trim((string) ($data['system_code'] ?? ''));
            if ($prefix !== '' && $systemCode !== '') {
                $base = $prefix.$systemCode.'.';
            }
        }
        if ($fullNumber === '' || $runningNo <= 0 || $base === '') {
            $this->fail('ระบบกลางไม่ออกเลขที่หนังสือ');
        }

        return [
            'document_number' => $base,
            'running_no' => $runningNo,
            'full_number' => $fullNumber,
        ];
    }

    private function subject(string $withdrawType, int $withdrawId): string
    {
        $subject = '';
        try {
            $subject = trim((string) \Illuminate\Support\Facades\DB::table('withdraw')
                ->where('withdraw_id', $withdrawId)
                ->value('withdraw_subject'));
        } catch (\Throwable) {
            $subject = '';
        }
        if ($subject === '') {
            $subject = 'เบิกจ่ายงบประมาณ';
        }
        $subject .= ' ('.$withdrawId.')';

        return mb_strlen($subject) > 255 ? mb_substr($subject, 0, 255) : $subject;
    }

    private function reference(string $withdrawType, int $withdrawId): string
    {
        $reference = 'withdraw:'.$withdrawId.':'.$withdrawType;

        return mb_strlen($reference) > 128 ? mb_substr($reference, 0, 128) : $reference;
    }

    private function requestedBy(): string
    {
        $staffId = '';
        try {
            $staffId = trim((string) request()->session()->get('staff_id', ''));
        } catch (\Throwable) {
            $staffId = '';
        }

        return $staffId;
    }

    private function idempotencyKey(int $withdrawId, string $safeType): string
    {
        $key = 'withdraw-'.$withdrawId.'-'.$safeType;
        if (strlen($key) < 8) {
            $key = 'withdraw-doc-'.$withdrawId.'-'.$safeType;
        }
        if (strlen($key) > 128) {
            $key = substr($key, 0, 128);
        }

        return $key;
    }

    /**
     * @param  array<string, scalar>  $query
     * @param  array<string, scalar>|null  $body
     * @return array<string, mixed>
     */
    private function request(string $method, array $query, ?array $body, ?string $idempotencyKey): array
    {
        $url = trim((string) config('services.document_number.api_url'));
        $apiKey = trim((string) config('services.document_number.api_key'));
        if ($url === '' || $apiKey === '') {
            $this->fail('ยังไม่ได้ตั้งค่า API ระบบออกเลขที่หนังสือ');
        }

        $headers = [
            'Accept' => 'application/json',
            'X-API-Key' => $apiKey,
        ];
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        try {
            $pending = Http::withHeaders($headers)->timeout(20)->connectTimeout(5);
            $response = $method === 'GET'
                ? $pending->get($url, $query)
                : $pending->asJson()->post($url, $body ?? []);
        } catch (\Throwable) {
            $this->fail('ติดต่อระบบออกเลขที่หนังสือไม่สำเร็จ');
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            $this->fail('ระบบออกเลขที่หนังสือตอบกลับไม่ถูกต้อง');
        }
        if (! $response->successful() || ($payload['success'] ?? false) !== true) {
            $message = trim((string) ($payload['message'] ?? ''));
            $this->fail($message !== '' ? $message : 'ระบบออกเลขที่หนังสือตอบกลับไม่สำเร็จ');
        }

        $data = $payload['data'] ?? null;

        return is_array($data) ? $data : [];
    }

    private function fail(string $message): never
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $message,
        ], 422));
    }
}
