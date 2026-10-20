<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
    ];

    private static ?bool $hasLogTable = null;

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

            DB::table(self::LOG_TABLE)->insert([
                'occurred_at' => now(),
                'request_method' => substr($request->method(), 0, 10),
                'request_path' => substr($request->path(), 0, 500),
                'route_name' => $route ? $route->getName() : null,
                'action_name' => $route ? $route->getActionName() : null,
                'http_status' => $response ? $response->getStatusCode() : 500,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'staff_id' => $this->resolveStaffId($request),
                'user_id' => $this->resolveUserId($user),
                'system_role' => (string) ($request->session()->get('pbm.system_role') ?? ''),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'query_params' => $this->encodePayload($request->query()),
                'request_payload' => $this->encodePayload($request->input()),
                'exception_message' => $exception ? mb_substr($exception->getMessage(), 0, 65535) : null,
            ]);
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

        return json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
