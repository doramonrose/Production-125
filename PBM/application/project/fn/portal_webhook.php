<?php
/**
 * Webhook notifier (staff.changed) สำหรับ Pharmacy CMU Portal Integration Standard
 * ส่งหลัง commit และหลังตอบผู้ใช้แล้ว ด้วย HMAC-SHA256 และ timeout สั้น
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/portal.php';

if (!function_exists('pm_portal_uuid_v4')) {
    function pm_portal_uuid_v4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}

if (!function_exists('pm_portal_notify_staff_changed')) {
    /**
     * @param array<int, string|int> $staffIds
     */
    function pm_portal_notify_staff_changed(array $staffIds, bool $deferAfterResponse = true): void
    {
        $cfg = pm_portal_config();
        $url = trim((string)($cfg['webhook_url'] ?? ''));
        $secret = (string)($cfg['webhook_secret'] ?? '');
        if ($url === '' || strlen($secret) < 32 || stripos($url, 'https://') !== 0) {
            return;
        }

        $clean = [];
        foreach ($staffIds as $sid) {
            $s = trim((string)$sid);
            if ($s !== '' && preg_match('/^[0-9]{1,20}$/', $s)) {
                $clean[$s] = $s;
            }
        }
        $ids = array_values($clean);
        if (empty($ids)) {
            return;
        }

        $send = static function () use ($url, $secret, $ids): void {
            $chunks = array_chunk($ids, 200);
            foreach ($chunks as $chunk) {
                $eventId = pm_portal_uuid_v4();
                $ts = time();
                $occurredAt = (new DateTimeImmutable('@' . $ts))
                    ->setTimezone(new DateTimeZone('Asia/Bangkok'))
                    ->format(DateTimeInterface::RFC3339);

                $payload = json_encode([
                    'event_id' => $eventId,
                    'event' => 'staff.changed',
                    'staff_ids' => $chunk,
                    'occurred_at' => $occurredAt,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                if (!is_string($payload) || $payload === '') {
                    continue;
                }

                $sig = hash_hmac('sha256', $payload, $secret);
                $ch = @curl_init($url);
                if ($ch === false) {
                    continue;
                }
                @curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_CONNECTTIMEOUT => 2,
                    CURLOPT_TIMEOUT => 3,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'X-Webhook-Event-Id: ' . $eventId,
                        'X-Webhook-Timestamp: ' . $ts,
                        'X-Webhook-Signature: sha256=' . $sig,
                    ],
                ]);
                @curl_exec($ch);
                $err = curl_error($ch);
                $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                @curl_close($ch);
                if ($err !== '' || ($code < 200 || $code >= 300)) {
                    error_log(sprintf('[pbm_portal_webhook] event=%s status=%d err=%s', $eventId, $code, $err));
                }
            }
        };

        if ($deferAfterResponse) {
            register_shutdown_function(static function () use ($send): void {
                if (function_exists('fastcgi_finish_request')) {
                    @fastcgi_finish_request();
                }
                $send();
            });
            return;
        }

        $send();
    }
}
