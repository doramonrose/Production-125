<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePbmAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->hasValidSsoCookie($request)) {
            $this->clearLocalAuthentication($request);

            return $this->redirectToRelogin($request);
        }

        if ($request->user() && $request->session()->get('pbm.authenticated') === true) {
            return $next($request);
        }

        return $this->redirectToRelogin($request);
    }

    private function redirectToRelogin(Request $request): RedirectResponse
    {
        $target = trim((string) config('pbm_bridge.relogin_url', '/login'));

        if ($target === '') {
            $target = '/login';
        }

        $separator = str_contains($target, '?') ? '&' : '?';
        $intended = rawurlencode($request->fullUrl());

        return redirect()->to($target.$separator.'intended='.$intended);
    }

    private function hasValidSsoCookie(Request $request): bool
    {
        $cookieName = trim((string) config('pbm_bridge.sso_cookie_name', 'pbm_sso_active'));
        $expectedValue = (string) config('pbm_bridge.sso_cookie_value', '1');

        if ($cookieName === '') {
            return true;
        }

        return (string) $request->cookie($cookieName, '') === $expectedValue;
    }

    private function clearLocalAuthentication(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
