<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class HandlePbmInboundAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $payloadInput = $request->query('pbm_payload');
        $signatureInput = $request->query('pbm_sig');

        if (! is_string($payloadInput) || ! is_string($signatureInput)) {
            return $next($request);
        }

        $payload = $this->decodePayload($payloadInput);
        if (! is_array($payload)) {
            return $this->reject('ข้อมูลยืนยันตัวตนไม่ถูกต้อง');
        }

        if (! $this->hasValidSignature($payloadInput, $signatureInput)) {
            return $this->reject('ลายเซ็นข้อมูลไม่ถูกต้อง');
        }

        if (! $this->isFreshPayload($payload)) {
            return $this->reject('ข้อมูลยืนยันตัวตนหมดอายุ');
        }

        if (! $this->isExpectedSystem($payload)) {
            return $this->reject('ระบบปลายทางไม่ตรงกับข้อมูลที่ส่งมา');
        }

        $staffId = trim((string) ($payload['staff_id'] ?? ''));
        if ($staffId === '') {
            return $this->reject('ไม่พบรหัสผู้ใช้งาน');
        }

        $role = $this->normalizeRole((string) ($payload['system_role'] ?? 'viewer'));
        $isPlatformAdmin = (bool) ($payload['is_platform_admin'] ?? false);
        $displayName = trim((string) ($payload['name'] ?? ''));
        $emailFromPayload = trim((string) ($payload['email'] ?? ''));
        $avatarUrlFromPayload = trim((string) ($payload['avatar_url'] ?? ($payload['staff_photo_url'] ?? '')));

        if ($isPlatformAdmin) {
            $role = 'admin';
        }

        $safeEmail = filter_var($emailFromPayload, FILTER_VALIDATE_EMAIL)
            ? strtolower($emailFromPayload)
            : 'pbm-'.$staffId.'@local.pbm';

        $user = User::query()->firstOrCreate(
            ['user_id' => $staffId],
            [
                'name' => $displayName !== '' ? $displayName : 'PBM User '.$staffId,
                'email' => $safeEmail,
                'password' => Str::password(32),
                'staff_code' => $staffId,
                'image_url' => $avatarUrlFromPayload !== '' ? $avatarUrlFromPayload : null,
            ]
        );

        $user->forceFill([
            'name' => $displayName !== '' ? $displayName : $user->name,
            'email' => $user->email ?: $safeEmail,
            'staff_code' => $staffId,
            'image_url' => $avatarUrlFromPayload !== '' ? $avatarUrlFromPayload : $user->image_url,
        ])->save();

        Auth::login($user);

        $request->session()->put('pbm.authenticated', true);
        $request->session()->put('pbm.staff_id', $staffId);
        $request->session()->put('pbm.system_role', $role);
        $request->session()->put('pbm.system_user_type', $role === 'admin' ? 'admin' : 'user');
        $request->session()->put('pbm.is_platform_admin', $isPlatformAdmin);
        $request->session()->put('staff_id', $staffId);
        $request->session()->put('staff_photo_url', $avatarUrlFromPayload !== '' ? $avatarUrlFromPayload : $user->image_url);

        return $this->redirectWithoutBridgeParams($request);
    }

    private function decodePayload(string $payloadInput): ?array
    {
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

    private function hasValidSignature(string $payloadInput, string $signatureInput): bool
    {
        $secret = (string) config('pbm_bridge.secret');
        if ($secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payloadInput, hash('sha256', $secret));

        return hash_equals($expected, $signatureInput);
    }

    private function isFreshPayload(array $payload): bool
    {
        $issuedAt = (int) ($payload['issued_at'] ?? 0);
        if ($issuedAt <= 0) {
            return false;
        }

        $ttl = max(30, (int) config('pbm_bridge.ttl', 300));

        return now()->timestamp - $issuedAt <= $ttl;
    }

    private function isExpectedSystem(array $payload): bool
    {
        $expectedSlug = (string) config('pbm_bridge.system_slug', 'budget');
        $slug = trim((string) ($payload['system_slug'] ?? ''));

        return $slug !== '' && $slug === $expectedSlug;
    }

    private function normalizeRole(string $role): string
    {
        return in_array($role, ['admin', 'member', 'viewer'], true) ? $role : 'viewer';
    }

    private function reject(string $message): RedirectResponse
    {
        return redirect()
            ->route('handoff.denied')
            ->with('error', $message);
    }

    private function redirectWithoutBridgeParams(Request $request): RedirectResponse
    {
        $query = $request->query();
        $returnUrl = trim((string) ($query['pbm_return'] ?? ''));
        unset(
            $query['staff_id'],
            $query['system_user_type'],
            $query['system_role'],
            $query['is_platform_admin'],
            $query['pbm_payload'],
            $query['pbm_sig'],
            $query['pbm_return']
        );

        $validatedReturnUrl = $this->validatedReturnUrl($request, $returnUrl);
        if ($validatedReturnUrl !== '') {
            return redirect()->to($validatedReturnUrl);
        }

        $baseUrl = $request->url();
        $targetUrl = count($query) > 0 ? $baseUrl.'?'.http_build_query($query) : $baseUrl;

        return redirect()->to($targetUrl);
    }

    private function validatedReturnUrl(Request $request, string $url): string
    {
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return '';
        }

        $host = (string) ($parts['host'] ?? '');
        if ($host !== '' && ! hash_equals($host, $request->getHost())) {
            return '';
        }

        $path = (string) ($parts['path'] ?? '');
        if (! str_starts_with($path, '/budget')) {
            return '';
        }

        return $url;
    }
}
