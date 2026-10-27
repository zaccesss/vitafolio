<?php

use App\Http\Controllers\DatabaseHealthController;
use App\Http\Middleware\EnsureAccountIsUsable;
use App\Http\Middleware\RedirectToCanonicalHost;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\StatelessForCrawlers;
use App\Http\Middleware\ThrottleAuthForms;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // the database check sits outside the web group, so polling it never starts a session
        then: fn () => Route::get('/up/db', DatabaseHealthController::class)->name('up.db'),
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // tls ends at the host's proxy, so its forwarded headers say whether the visit was https
        // and which address it came from. only those two are trusted: a forwarded host header
        // could otherwise become the host used in password reset links
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT);
        $middleware->prepend(RedirectToCanonicalHost::class);
        $middleware->append(ThrottleAuthForms::class);
        // decides which session store a request gets, so it has to run before the session starts
        $middleware->prependToGroup('web', StatelessForCrawlers::class);
        $middleware->prependToPriorityList(StartSession::class, StatelessForCrawlers::class);
        $middleware->appendToGroup('web', EnsureAccountIsUsable::class);
        $middleware->appendToGroup('web', SetLocale::class);
        $middleware->append(SecurityHeaders::class);
        // the nightly tidy-up is called by a scheduler with a bearer token, not from a page with a form
        $middleware->validateCsrfTokens(except: ['cron', 'jobs/feed']);
        // while the database is unreachable the site shows its maintenance page. /up is let through by the
        // framework; the database check still reports and /cron can bring the site back once it answers
        $middleware->preventRequestsDuringMaintenance(except: ['up/db', 'cron']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // errors go to sentry when SENTRY_LARAVEL_DSN is set; without it this does nothing
        Integration::handles($exceptions);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
