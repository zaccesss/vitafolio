<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // tls ends at the host's proxy, so its forwarded headers say whether the visit was
        // https and which address it came from
        $middleware->trustProxies(at: '*');
        $middleware->append(SecurityHeaders::class);
        // the nightly tidy-up is called by a scheduler with a bearer token, not from a page with a form
        $middleware->validateCsrfTokens(except: ['cron']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // errors go to sentry when SENTRY_LARAVEL_DSN is set; without it this does nothing
        Integration::handles($exceptions);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
