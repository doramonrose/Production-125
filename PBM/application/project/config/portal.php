<?php
/**
 * Config สำหรับ Pharmacy CMU Portal Integration Standard (ระบบบริหารโครงการ / pbm_project)
 * ค่าลับอ่านจาก environment (PORTAL_API_TOKEN, PORTAL_WEBHOOK_SECRET, PORTAL_WEBHOOK_URL)
 * หรือไฟล์ config/portal.local.php เท่านั้น — ห้าม commit secret ลง git
 */
declare(strict_types=1);

if (!function_exists('pm_portal_config')) {
    /**
     * @return array{
     *   system_code: string,
     *   system_name: string,
     *   app_url: string,
     *   api_base_url: string,
     *   portal_web_base: string,
     *   api_token: string,
     *   webhook_url: string,
     *   webhook_secret: string,
     *   actions_enabled: bool,
     *   rate_limit_per_min: int,
     *   snapshot_ttl: int,
     *   feed_cache_ttl: int,
     *   cache_dir: string
     * }
     */
    function pm_portal_config(): array
    {
        static $cfg = null;
        if (is_array($cfg)) {
            return $cfg;
        }

        $local = [];
        $localFile = __DIR__ . '/portal.local.php';
        if (is_file($localFile) && is_readable($localFile)) {
            $loaded = include $localFile;
            if (is_array($loaded)) {
                $local = $loaded;
            }
        }

        $get = static function (string $key, string $default = '') use ($local): string {
            $v = getenv($key);
            if ($v !== false && trim((string)$v) !== '') {
                return trim((string)$v);
            }
            if (array_key_exists($key, $local) && trim((string)$local[$key]) !== '') {
                return trim((string)$local[$key]);
            }
            $lower = strtolower($key);
            foreach ($local as $lk => $lv) {
                if (strtolower((string)$lk) === $lower && is_scalar($lv) && trim((string)$lv) !== '') {
                    return trim((string)$lv);
                }
            }
            return $default;
        };

        $actionsEnv = strtolower($get('PORTAL_ACTIONS_ENABLED', 'true'));
        $actionsEnabled = in_array($actionsEnv, ['1', 'true', 'yes', 'on'], true);

        $appUrl = rtrim($get('PORTAL_APP_URL', 'https://pbm.pharmacy.cmu.ac.th/application/project'), '/');

        $cfg = [
            'system_code' => $get('PORTAL_SYSTEM_CODE', 'pbm_project'),
            'system_name' => $get('PORTAL_SYSTEM_NAME', 'ระบบบริหารโครงการ'),
            'app_url' => $appUrl . '/',
            'api_base_url' => $appUrl . '/api/portal',
            'portal_web_base' => $appUrl . '/portal',
            'api_token' => $get('PORTAL_API_TOKEN', ''),
            'webhook_url' => $get(
                'PORTAL_WEBHOOK_URL',
                'https://mis.pharmacy.cmu.ac.th/rx_portal/api/v1/integrations/pbm_project/webhook'
            ),
            'webhook_secret' => $get('PORTAL_WEBHOOK_SECRET', ''),
            'actions_enabled' => $actionsEnabled,
            'rate_limit_per_min' => max(600, (int)$get('PORTAL_RATE_LIMIT_PER_MIN', '1200')),
            'snapshot_ttl' => 600,
            'feed_cache_ttl' => 15,
            'cache_dir' => $get('PORTAL_CACHE_DIR', sys_get_temp_dir() . '/pbm_portal_cache'),
        ];

        return $cfg;
    }
}
