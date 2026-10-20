<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePbmRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $roles = count($roles) > 0 ? $roles : ['viewer', 'member', 'admin'];
        $roles = array_values(array_filter($roles));

        $effectiveRole = (string) $request->session()->get('pbm.system_role', 'viewer');

        if (! in_array($effectiveRole, $roles, true)) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        return $next($request);
    }
}
