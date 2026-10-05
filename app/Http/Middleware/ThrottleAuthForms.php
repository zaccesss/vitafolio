<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * a per-address limit on the forms that create accounts or send sign-in emails. the package
 * that provides them registers its own routes, so the limit is applied here rather than per route
 */
class ThrottleAuthForms
{
    private const PATHS = ['register', 'forgot-password', 'email/verification-notification'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() === 'POST' && in_array(trim($request->path(), '/'), self::PATHS, true)) {
            $key = 'auth-forms:'.$request->ip();
            if (RateLimiter::tooManyAttempts($key, 20)) {
                return response()->view('errors.429', ['exception' => null], 429);
            }
            RateLimiter::hit($key, 3600);
        }

        return $next($request);
    }
}
