<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrependBudgetPrefix
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof RedirectResponse) {
            $targetUrl = $response->getTargetUrl();
            $targetPath = parse_url($targetUrl, PHP_URL_PATH) ?: '';

            if (
                str_starts_with($targetPath, '/withdraw')
                || str_starts_with($targetPath, '/login')
            ) {
                return $response;
            }

            if (str_starts_with($targetUrl, '/')) {
                $response->setTargetUrl('/withdraw'.$targetUrl);
            } elseif ($targetPath !== '' && str_starts_with($targetPath, '/')) {
                $rewrittenPath = '/withdraw'.$targetPath;
                $response->setTargetUrl(str_replace($targetPath, $rewrittenPath, $targetUrl));
            }
        }

        return $response;
    }
}
