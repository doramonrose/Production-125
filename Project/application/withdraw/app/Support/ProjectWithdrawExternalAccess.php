<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * การเข้าถึง API รายการเบิกจ่ายโครงการจากระบบภายนอก (ระบบโครงการ)
 *
 * ใช้ PBM_BRIDGE_SECRET ร่วมกับ handoff — ลายเซ็นแบบเดียวกับ ProjectWithdrawHandoff
 */
class ProjectWithdrawExternalAccess
{
    public const PDF_URL_TTL_SECONDS = 3600;

    public static function bridgeSecret(): string
    {
        return (string) config('pbm_bridge.secret');
    }

    public static function signatureFor(string $payloadInput): string
    {
        $secret = self::bridgeSecret();
        if ($secret === '') {
            return '';
        }

        return hash_hmac('sha256', $payloadInput, hash('sha256', $secret));
    }

    public static function verifySignature(string $payloadInput, string $signatureInput): bool
    {
        $expected = self::signatureFor($payloadInput);
        if ($expected === '') {
            return self::bridgeSecret() === '';
        }
        if (trim($signatureInput) === '') {
            return false;
        }

        return hash_equals($expected, trim($signatureInput));
    }

    public static function extractSignature(Request $request): string
    {
        return trim((string) (
            $request->query('sig')
            ?? $request->input('sig')
            ?? $request->header('X-PBM-Signature', '')
        ));
    }

    public static function verifyProjectCodeRequest(string $projectCode, string $signatureInput): bool
    {
        $projectCode = trim($projectCode);
        if ($projectCode === '') {
            return false;
        }

        return self::verifySignature($projectCode, $signatureInput);
    }

    /**
     * @param  array<string, scalar|null>  $params
     */
    public static function signedUrl(string $routeName, array $params, ?int $ttlSeconds = null): string
    {
        $ttlSeconds = $ttlSeconds ?? self::PDF_URL_TTL_SECONDS;
        $routeParams = [];
        $signParams = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $text = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
            $signParams[(string) $key] = $text;
        }

        // Route path `{id}` may duplicate withdraw/procurement/file id — sign only business keys.
        if (isset($signParams['id'], $signParams['withdraw_id']) && $signParams['id'] === $signParams['withdraw_id']) {
            $routeParams['id'] = $signParams['id'];
            unset($signParams['id']);
        } elseif (isset($signParams['id'], $signParams['procurement_id']) && $signParams['id'] === $signParams['procurement_id']) {
            $routeParams['id'] = $signParams['id'];
            unset($signParams['id']);
        } elseif (isset($signParams['id'], $signParams['file_id']) && $signParams['id'] === $signParams['file_id']) {
            $routeParams['id'] = $signParams['id'];
            unset($signParams['id']);
        }

        $signParams['expires'] = (string) (time() + max(60, $ttlSeconds));
        ksort($signParams);
        $payload = http_build_query($signParams);
        $signature = self::signatureFor($payload);

        return route($routeName, array_merge($routeParams, $signParams, ['sig' => $signature]));
    }

    /**
     * @param  list<string>  $signedKeys
     */
    public static function verifySignedRequest(Request $request, array $signedKeys): bool
    {
        $params = [];
        foreach ($signedKeys as $key) {
            $value = $request->query($key);
            if ($value === null || $value === '') {
                return false;
            }
            $params[$key] = (string) $value;
        }

        $expires = (int) $request->query('expires', 0);
        if ($expires <= 0 || time() > $expires) {
            return false;
        }
        $params['expires'] = (string) $expires;

        $sig = self::extractSignature($request);
        ksort($params);

        return self::verifySignature(http_build_query($params), $sig);
    }
}
