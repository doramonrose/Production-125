<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuAccess
{
    public function handle(Request $request, Closure $next, string $menuKey): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessMenu($menuKey)) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงเมนูนี้');
        }

        return $next($request);
    }
}
