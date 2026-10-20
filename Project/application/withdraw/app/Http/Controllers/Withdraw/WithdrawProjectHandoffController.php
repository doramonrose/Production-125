<?php

namespace App\Http\Controllers\Withdraw;

use App\Http\Controllers\Controller;
use App\Support\ProjectWithdrawHandoff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WithdrawProjectHandoffController extends Controller
{
    public function accept(Request $request): RedirectResponse|JsonResponse
    {
        $raw = $this->extractRawPayload($request);
        if ($raw === null) {
            return $this->reject($request, 'ไม่พบข้อมูลรายการเบิกจ่ายจากระบบโครงการ');
        }

        $signatureInput = trim((string) (
            $request->input('sig')
            ?? $request->query('sig')
            ?? $request->header('X-PBM-Signature', '')
        ));
        if ($signatureInput !== '') {
            $payloadInput = $this->extractEncodedPayload($request);
            if ($payloadInput === '') {
                $payloadInput = ProjectWithdrawHandoff::encodePayload($raw);
            }
            if (! ProjectWithdrawHandoff::hasValidSignature($payloadInput, $signatureInput)) {
                return $this->reject($request, 'ลายเซ็นข้อมูลจากระบบโครงการไม่ถูกต้อง');
            }
        }

        $normalized = ProjectWithdrawHandoff::normalize($raw);
        $rawItemsCount = 0;
        foreach (['items', 'disbursement_items', 'lines', 'items_json'] as $itemsKey) {
            $candidate = $raw[$itemsKey] ?? null;
            if (is_string($candidate)) {
                $decoded = json_decode($candidate, true);
                $candidate = is_array($decoded) ? $decoded : null;
            }
            if (is_array($candidate)) {
                $rawItemsCount = max($rawItemsCount, count(array_filter($candidate, 'is_array')));
            }
        }
        $normalizedItemsCount = count($normalized['items'] ?? []);
        if ($rawItemsCount > 0 && $normalizedItemsCount < $rawItemsCount) {
            \Illuminate\Support\Facades\Log::warning('project.handoff.items_reduced', [
                'raw_items_count' => $rawItemsCount,
                'normalized_items_count' => $normalizedItemsCount,
                'project_code' => $normalized['project_code'] ?? '',
                'keys' => array_keys($raw),
            ]);
        }

        if (($normalized['items'] ?? []) === [] && trim((string) ($normalized['activity_detail'] ?? '')) === '') {
            return $this->reject($request, 'กรุณาส่งรายการเบิกจ่ายหรือรายละเอียดกิจกรรมอย่างน้อย 1 รายการ');
        }

        if ((float) ($normalized['project_amount'] ?? 0) <= 0) {
            \Illuminate\Support\Facades\Log::warning('project.handoff.missing_project_amount', [
                'keys' => array_keys($raw),
                'amount_like' => collect($raw)
                    ->filter(static function ($value, $key) {
                        if (! is_string($key)) {
                            return false;
                        }
                        if (preg_match('/amount|total|budget|sum|ประมาณ|เงิน/iu', $key) === 1) {
                            return true;
                        }

                        return is_numeric($value) || (is_string($value) && preg_match('/^\s*[\d,]+\.?\d*\s*$/', $value) === 1);
                    })
                    ->all(),
                'project_id' => $normalized['project_id'] ?? '',
                'project_code' => $normalized['project_code'] ?? '',
            ]);
        }

        $token = ProjectWithdrawHandoff::store($normalized);
        $target = ProjectWithdrawHandoff::formUrl($token);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'token' => $token,
                    'redirect' => $target,
                ],
            ]);
        }

        return redirect()->to($target);
    }

    public function show(Request $request, string $token): JsonResponse
    {
        $payload = ProjectWithdrawHandoff::peek($token);
        if ($payload === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลจากระบบโครงการ หรือลิงก์หมดอายุ หากมีรายการค้างอยู่ด้านบนหน้านี้ สามารถกดเปิดทำต่อได้ หรือกลับไปยืนยันใหม่จากระบบโครงการ',
            ], 404);
        }

        if ((float) ($payload['project_amount'] ?? 0) <= 0) {
            $payload['project_amount'] = ProjectWithdrawHandoff::resolveProjectAmount(
                (string) ($payload['project_id'] ?? ''),
                (string) ($payload['project_code'] ?? '')
            );
        }

        return response()->json([
            'status' => 'success',
            'data' => $payload,
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $staffId = trim((string) ($request->session()->get('staff_id') ?? auth()->user()?->user_id ?? ''));

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => ProjectWithdrawHandoff::listPending($staffId !== '' ? $staffId : null),
            ],
        ]);
    }

    public function dismiss(Request $request, string $token): JsonResponse
    {
        $token = trim($token);
        // Synthetic cards from project confirmed_lines — dismiss is UI-only.
        if (str_starts_with($token, 'project-pending:')) {
            return response()->json([
                'status' => 'success',
                'message' => 'ลบรายการค้างออกจากรายการแล้ว',
            ]);
        }

        $ok = ProjectWithdrawHandoff::dismiss($token);
        if (! $ok) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่พบรายการค้าง หรือโทเคนไม่ถูกต้อง',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'ลบรายการค้างออกจากรายการแล้ว',
        ]);
    }

    /**
     * สร้าง handoff ใหม่จากรายการที่ยืนยันในระบบโครงการแล้ว เพื่อเปิดฟอร์มทำต่อในระบบนี้
     */
    public function recreate(Request $request): JsonResponse
    {
        $recreate = $request->input('recreate');
        if (! is_array($recreate)) {
            $recreate = [
                'project_id' => $request->input('project_id'),
                'project_code' => $request->input('project_code'),
                'project_name' => $request->input('project_name'),
                'activity_id' => $request->input('activity_id'),
                'activity_name' => $request->input('activity_name'),
                'activity_detail' => $request->input('activity_detail'),
                'report_id' => $request->input('report_id'),
                'line_ids' => $request->input('line_ids', []),
                'lines' => $request->input('lines', []),
            ];
        }

        $token = ProjectWithdrawHandoff::recreateHandoffFromProjectLines($recreate);
        if ($token === null || $token === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถสร้างรายการเบิกจ่ายจากข้อมูลระบบโครงการได้',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'สร้างรายการเบิกจ่ายสำเร็จ',
            'data' => [
                'token' => $token,
                'resume_url' => ProjectWithdrawHandoff::formUrl($token),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractRawPayload(Request $request): ?array
    {
        // Prefer signed/nested payload first so flat form fields cannot clobber full items[].
        foreach (['payload_json', 'payload', 'data', 'json', 'body', 'pbm_project_payload'] as $key) {
            $value = $request->input($key, $request->query($key));
            if (is_array($value) && $this->looksLikeProjectPayload($value)) {
                return $this->withDecodedItems($value);
            }
            if (is_string($value) && trim($value) !== '') {
                $decoded = ProjectWithdrawHandoff::decodePayload($value);
                if (is_array($decoded) && $this->looksLikeProjectPayload($decoded)) {
                    return $this->withDecodedItems($decoded);
                }
            }
        }

        $headerPayload = trim((string) $request->header('X-PBM-Project-Payload', ''));
        if ($headerPayload !== '') {
            $decoded = ProjectWithdrawHandoff::decodePayload($headerPayload);
            if (is_array($decoded) && $this->looksLikeProjectPayload($decoded)) {
                return $this->withDecodedItems($decoded);
            }
        }

        $all = $request->all();
        if (is_array($all) && $this->looksLikeProjectPayload($all)) {
            return $this->withDecodedItems($all);
        }

        $json = $request->json()?->all();
        if (is_array($json) && $this->looksLikeProjectPayload($json)) {
            return $this->withDecodedItems($json);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withDecodedItems(array $payload): array
    {
        if (isset($payload['items']) && is_string($payload['items'])) {
            $decodedItems = json_decode($payload['items'], true);
            if (is_array($decodedItems)) {
                $payload['items'] = $decodedItems;
            }
        }
        if ((! isset($payload['items']) || $payload['items'] === [] || $payload['items'] === '') && isset($payload['items_json'])) {
            $decodedItems = is_string($payload['items_json'])
                ? json_decode($payload['items_json'], true)
                : $payload['items_json'];
            if (is_array($decodedItems)) {
                $payload['items'] = $decodedItems;
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function looksLikeProjectPayload(array $raw): bool
    {
        foreach (['project_id', 'project_code', 'activity_id', 'activity_name', 'activity_detail', 'subject'] as $key) {
            if (trim((string) ($raw[$key] ?? '')) !== '') {
                return true;
            }
        }

        $items = $raw['items'] ?? $raw['items_json'] ?? null;
        if (is_array($items) && $items !== []) {
            return true;
        }

        return is_string($items) && trim($items) !== '' && trim($items) !== '[]';
    }

    private function extractEncodedPayload(Request $request): string
    {
        foreach (['payload', 'payload_json', 'pbm_project_payload'] as $key) {
            $value = trim((string) ($request->input($key, $request->query($key)) ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return trim((string) $request->header('X-PBM-Project-Payload', ''));
    }

    private function reject(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $message,
            ], 422);
        }

        return redirect()
            ->route('handoff.denied')
            ->with('error', $message)
            ->with('error_title', 'ไม่สามารถเปิดฟอร์มเบิกจ่ายได้');
    }
}
