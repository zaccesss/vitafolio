<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * the framework's password confirmation can only resume a page visit: after confirming it replays the address as
 * a GET, so a delete form behind it never ran. This asks for the password, returns to the page the form is on and
 * lets the visitor submit again, now confirmed
 */
class ConfirmPasswordFirst
{
    public function handle(Request $request, Closure $next, string $returnRoute): Response
    {
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);
        if (time() - $confirmedAt < (int) config('auth.password_timeout', 10800)) {
            return $next($request);
        }

        $request->session()->put('url.intended', route($returnRoute));

        return redirect()->route('password.confirm')
            ->with('status', 'For your security, confirm your password. You will come back to the same page to finish.');
    }
}
