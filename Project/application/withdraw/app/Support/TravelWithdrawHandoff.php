<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TravelWithdrawHandoff
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONSUMED = 'consumed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function store(array $payload): string
    {
        self::ensureTable();

        $leavesCode = trim((string) ($payload['leaves_code'] ?? ''));
        $existing = null;
        if ($leavesCode !== '') {
            $existing = DB::table('withdraw_travel_handoff')
                ->where('leaves_code', $leavesCode)
                ->where('status', self::STATUS_PENDING)
                ->orderByDesc('id')
                ->first();
        }

        $token = $existing ? (string) $existing->token : Str::lower(Str::random(40));
        $amount = (float) ($payload['amount'] ?? 0);
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $row = [
            'token' => $token,
            'status' => self::STATUS_PENDING,
            'leaves_code' => $leavesCode,
            'leaves_code_group' => trim((string) ($payload['leaves_code_group'] ?? '')),
            'subject' => trim((string) ($payload['subject'] ?? '')),
            'applicant_staff_id' => trim((string) ($payload['finance_staff_id'] ?? '')),
            'item_count' => count($items),
            'amount' => $amount,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'expires_at' => now()->addDays(90),
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('withdraw_travel_handoff')->where('id', $existing->id)->update($row);
        } else {
            $row['created_at'] = now();
            DB::table('withdraw_travel_handoff')->insert($row);
        }

        return $token;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function peek(string $token): ?array
    {
        self::ensureTable();
        $token = trim($token);
        if ($token === '' || ! preg_match('/^[a-z0-9]{20,64}$/', $token)) {
            return null;
        }

        $row = DB::table('withdraw_travel_handoff')->where('token', $token)->first();
        if (! $row || trim((string) ($row->status ?? '')) !== self::STATUS_PENDING) {
            return null;
        }

        $payload = json_decode((string) ($row->payload_json ?? ''), true);

        return is_array($payload) ? $payload + ['token' => $token] : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listPending(int $limit = 30): array
    {
        self::ensureTable();
        $limit = max(1, min(50, $limit));

        return DB::table('withdraw_travel_handoff')
            ->where('status', self::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(static function ($row): array {
                $payload = json_decode((string) ($row->payload_json ?? ''), true);

                return [
                    'token' => (string) ($row->token ?? ''),
                    'leaves_code' => (string) ($row->leaves_code ?? ''),
                    'subject' => (string) ($row->subject ?? ''),
                    'amount' => (float) ($row->amount ?? 0),
                    'item_count' => (int) ($row->item_count ?? 0),
                    'created_at' => (string) ($row->created_at ?? ''),
                    'traveler' => is_array($payload) ? (string) ($payload['traveler_name'] ?? '') : '',
                    'place' => is_array($payload) ? (string) ($payload['place'] ?? '') : '',
                ];
            })
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function travelerNamesForWithdraw(int $withdrawId): array
    {
        $payload = self::payloadForWithdraw($withdrawId);
        if ($payload === null) {
            return [];
        }

        $names = [];
        $people = $payload['report_view']['people'] ?? null;
        if (is_array($people)) {
            foreach ($people as $person) {
                if (! is_array($person)) {
                    continue;
                }
                $name = trim((string) ($person['name'] ?? ''));
                if ($name !== '') {
                    $names[$name] = $name;
                }
            }
        }
        if ($names === []) {
            $travelerName = trim((string) ($payload['traveler_name'] ?? ''));
            if ($travelerName !== '') {
                $names[$travelerName] = $travelerName;
            }
        }

        return array_values($names);
    }

    /**
     * ไฟล์จากระบบขออนุมัติไปปฏิบัติงาน สำหรับเอกสารประกอบการเบิกจ่าย
     *
     * @return list<array{label: string, name: string, url: string, mime: string, embed: bool}>
     */
    public static function supportingFilesForWithdraw(int $withdrawId): array
    {
        $payload = self::payloadForWithdraw($withdrawId);

        return $payload === null ? [] : self::supportingFilesFromPayload($payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function payloadForWithdraw(int $withdrawId): ?array
    {
        if ($withdrawId <= 0) {
            return null;
        }
        self::ensureTable();
        $payloadJson = DB::table('withdraw_travel_handoff')
            ->where('withdraw_id', $withdrawId)
            ->orderByDesc('id')
            ->value('payload_json');
        $payload = json_decode((string) $payloadJson, true);

        return is_array($payload) ? $payload : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{label: string, name: string, url: string, mime: string, embed: bool}>
     */
    private static function supportingFilesFromPayload(array $payload): array
    {
        $files = [];
        $seen = [];
        $push = function (string $label, string $name, string $url, bool $embed = false) use (&$files, &$seen): void {
            $url = trim($url);
            $label = trim($label);
            if ($url === '' || $label === '' || isset($seen[$url])) {
                return;
            }
            $seen[$url] = true;
            $name = trim($name);
            if ($name === '') {
                $name = $label;
            }
            $assetUrl = $embed ? null : self::canonicalLeavesAssetUrl($url);
            $files[] = [
                'label' => $label,
                'name' => $name,
                'url' => $assetUrl !== null
                    ? url('/withdraw/api/travel/file').'?'.http_build_query(['src' => $assetUrl])
                    : $url,
                'mime' => self::mimeFromName($name !== '' ? $name : $url),
                'embed' => $embed || $assetUrl !== null,
            ];
        };

        $people = is_array($payload['report_view']['people'] ?? null) ? $payload['report_view']['people'] : [];
        $people = array_values(array_filter($people, 'is_array'));
        $nameSuffix = count($people) > 1;
        $generated = [
            ['path' => 'leaves-report', 'label' => 'แบบฟอร์มรายงานการเดินทาง'],
            ['path' => 'leaves-report-money', 'label' => 'หลักฐานการจ่ายเงินค่าใช้จ่าย'],
        ];
        foreach ($people as $person) {
            $code = trim((string) ($person['code'] ?? ''));
            $personName = trim((string) ($person['name'] ?? ''));
            $suffix = $nameSuffix && $personName !== '' ? ' ('.$personName.')' : '';
            if ($code !== '') {
                foreach ($generated as $document) {
                    $label = $document['label'].$suffix;
                    $push($label, $label.'.pdf', self::leavesFormPreviewUrl($document['path'], $code), true);
                }
                if (self::personClaimsRegistration($person)) {
                    $label = 'บันทึกขออนุมัติเบิกค่าลงทะเบียน'.$suffix;
                    $push($label, $label.'.pdf', self::leavesFormPreviewUrl('leaves-report-register', $code), true);
                }
            }
            $reportFiles = is_array($person['files'] ?? null) ? $person['files'] : [];
            foreach ($reportFiles as $file) {
                if (! is_array($file)) {
                    continue;
                }
                $label = 'ไฟล์แนบจากรายงานการเดินทาง'.$suffix;
                $push($label, (string) ($file['name'] ?? ''), (string) ($file['url'] ?? ''));
            }
        }

        if ($people === []) {
            $code = trim((string) ($payload['leaves_code'] ?? ''));
            if ($code !== '') {
                foreach ($generated as $document) {
                    $push($document['label'], $document['label'].'.pdf', self::leavesFormPreviewUrl($document['path'], $code), true);
                }
            }
        }

        $headers = is_array($payload['report_view']['headers'] ?? null) ? $payload['report_view']['headers'] : [];
        foreach ($headers as $header) {
            if (! is_array($header)) {
                continue;
            }
            $attachments = is_array($header['attachments'] ?? null) ? $header['attachments'] : [];
            foreach ($attachments as $file) {
                if (! is_array($file)) {
                    continue;
                }
                $group = trim((string) ($file['group'] ?? ''));
                $label = 'ไฟล์แนบจากข้อมูลการขออนุมัติ'.($group !== '' ? ' · '.$group : '');
                $push($label, (string) ($file['name'] ?? ''), (string) ($file['url'] ?? ''));
            }
        }

        return $files;
    }

    /**
     * @param  array<string, mixed>  $person
     */
    private static function personClaimsRegistration(array $person): bool
    {
        $expenses = is_array($person['expenses'] ?? null) ? $person['expenses'] : [];
        foreach ($expenses as $expense) {
            if (! is_array($expense)) {
                continue;
            }
            if (trim((string) ($expense['label'] ?? '')) !== 'ค่าลงทะเบียน') {
                continue;
            }
            if ((float) ($expense['amount'] ?? 0) > 0.00001) {
                return true;
            }
        }

        return false;
    }

    public static function canonicalLeavesAssetUrl(string $url): ?string
    {
        $parts = parse_url(trim($url));
        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            return null;
        }
        $allowedHost = strtolower((string) (parse_url((string) config('services.leaves_mission.base_url'), PHP_URL_HOST) ?: 'mis.pharmacy.cmu.ac.th'));
        if (strtolower((string) ($parts['host'] ?? '')) !== $allowedHost) {
            return null;
        }
        $path = (string) ($parts['path'] ?? '');
        if (str_contains($path, '..') || str_contains($path, '\\')) {
            return null;
        }
        $allowed = false;
        foreach (['/application/assets/file_leaves/', '/application/assets/file_leaves_report/', '/application/assets/file_leaves_form/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $allowed = true;
                break;
            }
        }
        if (! $allowed) {
            return null;
        }

        return 'https://'.$allowedHost.$path;
    }

    private static function leavesFormPreviewUrl(string $kind, string $leavesCode): string
    {
        return url('/withdraw/api/travel/form').'?'.http_build_query([
            'kind' => $kind,
            'leaves_code' => $leavesCode,
        ]);
    }

    public static function leavesFormSignature(string $kind, string $leavesCode, string $signFlag, int $timestamp): string
    {
        return self::signatureFor($kind.'|'.$leavesCode.'|'.$signFlag.'|'.$timestamp);
    }

    private static function mimeFromName(string $name): string
    {
        $extension = strtolower(pathinfo(parse_url($name, PHP_URL_PATH) ?: $name, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
    }

    public static function cancel(string $token): bool
    {
        self::ensureTable();
        $token = trim($token);
        if ($token === '' || ! preg_match('/^[a-z0-9]{20,64}$/', $token)) {
            return false;
        }

        return DB::table('withdraw_travel_handoff')
            ->where('token', $token)
            ->where('status', self::STATUS_PENDING)
            ->update([
                'status' => self::STATUS_CANCELLED,
                'updated_at' => now(),
            ]) > 0;
    }

    public static function consume(string $token, int $withdrawId = 0): void
    {
        self::ensureTable();
        $token = trim($token);
        if ($token === '') {
            return;
        }

        DB::table('withdraw_travel_handoff')
            ->where('token', $token)
            ->where('status', self::STATUS_PENDING)
            ->update([
                'status' => self::STATUS_CONSUMED,
                'withdraw_id' => $withdrawId > 0 ? $withdrawId : null,
                'consumed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public static function formUrl(string $token): string
    {
        return url('/withdraw/travel').'?'.http_build_query(['handoff' => $token]);
    }

    public static function recordUrl(int $withdrawId): string
    {
        return url('/withdraw/travel').'?'.http_build_query(['view_id' => $withdrawId]);
    }

    public static function signatureFor(string $payloadInput): string
    {
        $secret = (string) config('pbm_bridge.secret');
        if ($secret === '') {
            return '';
        }

        return hash_hmac('sha256', $payloadInput, hash('sha256', $secret));
    }

    public static function hasValidSignature(string $payloadInput, string $signatureInput): bool
    {
        $expected = self::signatureFor($payloadInput);
        if ($expected === '' || trim($signatureInput) === '') {
            return false;
        }

        return hash_equals($expected, trim($signatureInput));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function decodePayload(string $payloadInput): ?array
    {
        $payloadInput = trim($payloadInput);
        if ($payloadInput === '') {
            return null;
        }

        $asJson = json_decode($payloadInput, true);
        if (is_array($asJson)) {
            return $asJson;
        }

        $normalized = strtr($payloadInput, '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode($normalized, true);
        if (! is_string($decoded) || $decoded === '') {
            return null;
        }
        $payload = json_decode($decoded, true);

        return is_array($payload) ? $payload : null;
    }

    public static function ensureTable(): void
    {
        if (Schema::hasTable('withdraw_travel_handoff')) {
            return;
        }

        Schema::create('withdraw_travel_handoff', function ($table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->string('leaves_code', 32)->nullable()->index();
            $table->string('leaves_code_group', 32)->nullable();
            $table->string('subject', 500)->nullable();
            $table->string('applicant_staff_id', 64)->nullable();
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('amount', 15, 2)->nullable();
            $table->unsignedBigInteger('withdraw_id')->nullable();
            $table->longText('payload_json');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
}
