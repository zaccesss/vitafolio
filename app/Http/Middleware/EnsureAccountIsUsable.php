<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * two account states that sign-in alone cannot police. a suspended account could carry on through
 * a remember-me cookie, so it is signed out on its next request. an unverified account has not
 * proved it owns its address, so until it does it may only verify or sign out: no settings,
 * passkeys, two-factor setup or connected accounts that would survive a verified owner arriving
 */
class EnsureAccountIsUsable
{
    private const UNVERIFIED_MAY = ['verification.notice', 'verification.send', 'verification.verify', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        if ($user->isSuspended()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'This account cannot sign in. Contact us if you think this is a mistake.');
        }

        $route = (string) $request->route()?->getName();
        $sensitive = $request->method() !== 'GET'
            || str_starts_with($route, 'passkey.') || str_starts_with($route, 'two-factor.')
            || str_starts_with($route, 'social.') || str_starts_with($route, 'settings.')
            || in_array($route, ['profile.edit', 'account', 'dashboard', 'analytics'], true);
        if ($sensitive && ! $user->hasVerifiedEmail() && ! in_array($route, self::UNVERIFIED_MAY, true)) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
