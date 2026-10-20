<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: [
            env('PBM_SSO_COOKIE_NAME', 'pbm_sso_active'),
        ]);
        $middleware->append(\App\Http\Middleware\PrependBudgetPrefix::class);
        $middleware->append(\App\Http\Middleware\LogSystemAction::class);
        // Must run after StartSession (prepend runs too early and breaks session() usage).
        $middleware->validateCsrfTokens(except: [
            'withdraw/project/from-project',
            'withdraw/travel/from-leaves',
            'withdraw/api/travel/by-leaves',
            'withdraw/api/project/external/*',
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\HandlePbmInboundAccess::class,
        ]);
        $middleware->alias([
            'menu' => \App\Http\Middleware\EnsureMenuAccess::class,
            'pbm.inbound' => \App\Http\Middleware\HandlePbmInboundAccess::class,
            'pbm.auth' => \App\Http\Middleware\EnsurePbmAuthenticated::class,
            'pbm.role' => \App\Http\Middleware\EnsurePbmRole::class,
        ]);
        $middleware->redirectUsersTo(fn () => '/general');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
