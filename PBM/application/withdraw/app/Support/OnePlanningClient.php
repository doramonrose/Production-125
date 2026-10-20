<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OnePlanningClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok:bool,http_status:int,error:int|null,message:string,raw:mixed}
     */
    public static function addBudgetUsed(array $payload): array
    {
        $url = trim((string) config('services.one_planning.add_budget_used_url', ''));
        if ($url === '') {
            return [
                'ok' => false,
                'http_status' => 0,
                'error' => 1,
                'message' => 'ยังไม่ได้ตั้งค่า One Planning API URL',
                'raw' => null,
            ];
        }

        $body = array_merge(self::authParams(), [
            'ref_code' => (string) ($payload['ref_code'] ?? config('services.one_planning.ref_code', 'BGAPMC')),
            'ref_id' => (string) ($payload['ref_id'] ?? ''),
            'ref_status_name' => (string) ($payload['ref_status_name'] ?? 'สิ้นสุดการดำเนินการแล้ว'),
            'ref_edit_status' => (string) ($payload['ref_edit_status'] ?? 'Y'),
            'budget_code' => (string) ($payload['budget_code'] ?? ''),
            'budget_departments_id' => (string) ($payload['budget_departments_id'] ?? config('services.one_planning.budget_departments_id', '11')),
            'budget_value' => (string) ($payload['budget_value'] ?? ''),
            'comment' => (string) ($payload['comment'] ?? ''),
        ]);

        try {
            $response = Http::acceptJson()
                ->asForm()
                ->timeout(30)
                ->post($url, $body);

            $json = $response->json();
            $errorCode = is_array($json)
                ? (int) data_get($json, 'status.error', $response->successful() ? 0 : 1)
                : ($response->successful() ? 0 : 1);
            $message = is_array($json)
                ? trim((string) data_get($json, 'status.message', ''))
                : self::summarizeResponseBody((string) $response->body());

            if ($message === '' && ! $response->successful()) {
                $message = 'request failed (HTTP '.$response->status().')';
            }

            return [
                'ok' => $response->successful() && $errorCode === 0,
                'http_status' => $response->status(),
                'error' => $errorCode,
                'message' => $message !== '' ? $message : ($response->successful() ? 'complete' : 'request failed'),
                'raw' => $json ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::warning('OnePlanning add-budget-used failed', [
                'ref_id' => $body['ref_id'] ?? '',
                'budget_code' => $body['budget_code'] ?? '',
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'http_status' => 0,
                'error' => 1,
                'message' => 'ไม่สามารถเชื่อมต่อ One Planning ได้: '.$e->getMessage(),
                'raw' => null,
            ];
        }
    }

    /**
     * @return array{auth_code:string,pass_key:string}
     */
    private static function authParams(): array
    {
        return [
            'auth_code' => (string) config('services.one_planning.auth_code', ''),
            'pass_key' => (string) config('services.one_planning.pass_key', ''),
        ];
    }

    private static function summarizeResponseBody(string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }

        if (str_contains($body, '<html') || str_contains($body, '<!DOCTYPE')) {
            if (preg_match('/An Internal Error Has Occurred/i', $body)) {
                return 'An Internal Error Has Occurred (จาก One Planning)';
            }
            $text = trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $text = preg_replace('/\s+/u', ' ', $text) ?: '';

            return $text !== '' ? mb_substr($text, 0, 180) : 'One Planning ตอบกลับเป็นหน้า HTML error';
        }

        return mb_substr($body, 0, 255);
    }
}
