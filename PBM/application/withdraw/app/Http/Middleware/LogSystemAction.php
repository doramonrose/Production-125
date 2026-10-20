<?php

namespace App\Http\Middleware;

use App\Support\UsageLogDescriber;
use App\Support\UsageLogRecordContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogSystemAction
{
    private const LOG_TABLE = 'system_action_logs';

    /**
     * @var array<int, string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'pbm_sig',
        'pbm_payload',
        '_token',
        'api_key',
        'secret',
    ];

    private static ?bool $hasLogTable = null;

    private static ?bool $hasSummaryColumns = null;

    private static ?bool $hasListedColumn = null;

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->writeLog($request, null, $startedAt, $exception);
            throw $exception;
        }

        $this->writeLog($request, $response, $startedAt);

        return $response;
    }

    private function writeLog(Request $request, ?Response $response, float $startedAt, ?Throwable $exception = null): void
    {
        if (! $this->hasLogTable()) {
            return;
        }

        try {
            $route = $request->route();
            $user = $request->user();
            $routeName = $route ? $route->getName() : null;
            $routeParameters = $route ? $route->parameters() : [];
            $payload = $request->input();
            $files = $this->uploadedFiles($request);
            if ($files !== []) {
                $payload['_files'] = $files;
            }

            $row = [
                'occurred_at' => now(),
                'request_method' => substr($request->method(), 0, 10),
                'request_path' => substr($request->path(), 0, 500),
                'route_name' => $routeName,
                'action_name' => $route ? $route->getActionName() : null,
                'http_status' => $response ? $response->getStatusCode() : 500,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'staff_id' => $this->resolveStaffId($request),
                'user_id' => $this->resolveUserId($user),
                'system_role' => (string) ($request->session()->get('pbm.system_role') ?? ''),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'query_params' => $this->encodePayload($request->query()),
                'request_payload' => $this->encodePayload($payload),
                'exception_message' => $exception ? mb_substr($exception->getMessage(), 0, 65535) : null,
            ];
            if ($this->hasSummaryColumns()) {
                $record = app(UsageLogRecordContext::class)->fromRequest($request, $response);
                $summary = app(UsageLogDescriber::class)->describe(
                    $request->method(),
                    $routeName,
                    $request->path(),
                    $routeParameters,
                    [
                        'action' => $record['action'] !== '' ? $record['action'] : $request->input('action'),
                        'id' => $record['id'] > 0 ? $record['id'] : $request->input('id', $request->input('withdraw_id')),
                        'type_label' => $record['type_label'],
                        'scope' => $record['scope'],
                        'has_record' => $record['line'] !== '',
                    ]
                );
                if ($record['line'] !== '') {
                    $summary .= ' · '.$record['line'];
                }
                $listed = app(UsageLogRecordContext::class)->listedItems($response, (string) $routeName);
                if (is_array($listed) && $listed !== []) {
                    $summary .= ' · '.implode(' | ', array_slice($listed, 0, 8));
                }
                $row['staff_name'] = $this->resolveStaffName($request);
                $row['action_summary'] = mb_substr($summary, 0, 500);
                if (is_array($listed) && $this->hasListedColumn()) {
                    $encoded = json_encode($listed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $row['listed_items'] = is_string($encoded) ? $encoded : null;
                }
            }

            DB::table(self::LOG_TABLE)->insert($row);
        } catch (Throwable $writeException) {
            Log::warning('Failed to write system action log', [
                'message' => $writeException->getMessage(),
            ]);
        }
    }

    private function hasLogTable(): bool
    {
        if (self::$hasLogTable !== null) {
            return self::$hasLogTable;
        }

        try {
            self::$hasLogTable = Schema::hasTable(self::LOG_TABLE);
        } catch (Throwable) {
            self::$hasLogTable = false;
        }

        return self::$hasLogTable;
    }

    private function hasSummaryColumns(): bool
    {
        if (self::$hasSummaryColumns !== null) {
            return self::$hasSummaryColumns;
        }

        try {
            self::$hasSummaryColumns = Schema::hasColumn(self::LOG_TABLE, 'action_summary')
                && Schema::hasColumn(self::LOG_TABLE, 'staff_name');
        } catch (Throwable) {
            self::$hasSummaryColumns = false;
        }

        return self::$hasSummaryColumns;
    }

    private function hasListedColumn(): bool
    {
        if (self::$hasListedColumn !== null) {
            return self::$hasListedColumn;
        }

        try {
            self::$hasListedColumn = Schema::hasColumn(self::LOG_TABLE, 'listed_items');
        } catch (Throwable) {
            self::$hasListedColumn = false;
        }

        return self::$hasListedColumn;
    }

    private function resolveStaffName(Request $request): ?string
    {
        $user = $request->user();
        $name = trim((string) (data_get($user, 'name_th') ?: data_get($user, 'name') ?: ''));

        return $name !== '' ? mb_substr($name, 0, 255) : null;
    }

    /**
     * @return list<array{field: string, name: string, size: int}>
     */
    private function uploadedFiles(Request $request): array
    {
        $files = [];
        foreach ($request->allFiles() as $field => $file) {
            $items = is_array($file) ? $file : [$file];
            foreach ($items as $item) {
                if (! $item instanceof UploadedFile) {
                    continue;
                }
                $files[] = [
                    'field' => (string) $field,
                    'name' => mb_substr($item->getClientOriginalName(), 0, 255),
                    'size' => (int) $item->getSize(),
                ];
            }
        }

        return $files;
    }

    private function resolveStaffId(Request $request): ?string
    {
        $user = $request->user();

        foreach (['staff_id', 'staff_code', 'user_id', 'id'] as $field) {
            $value = data_get($user, $field);
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        $sessionStaff = $request->session()->get('staff_id');

        return $sessionStaff !== null && trim((string) $sessionStaff) !== ''
            ? trim((string) $sessionStaff)
            : null;
    }

    private function resolveUserId(mixed $user): ?string
    {
        if (! $user) {
            return null;
        }

        foreach (['user_id', 'id'] as $field) {
            $value = data_get($user, $field);
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function encodePayload(array $payload): ?string
    {
        if ($payload === []) {
            return null;
        }

        $sanitized = $this->sanitizePayload($payload);

        $encoded = json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (! is_string($encoded) || $encoded === '') {
            return null;
        }

        return mb_strlen($encoded) > 20000 ? mb_substr($encoded, 0, 20000) : $encoded;
    }

    private function sanitizePayload(array $payload): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            $normalizedKey = is_string($key) ? strtolower($key) : (string) $key;
            if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
                $sanitized[$key] = '***';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
                continue;
            }

            if (is_string($value)) {
                $sanitized[$key] = mb_substr($value, 0, 5000);
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
