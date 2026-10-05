<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * in production every page lives at the configured address only. the host's own address keeps
 * answering, but links, styles and scripts are all built for the configured one, so a visit there
 * is sent across with its path intact. the health check stays reachable on both addresses
 */
class RedirectToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonical = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (config('app.env') !== 'production' || ! $canonical || $request->getHost() === $canonical || $request->is('up')) {
            return $next($request);
        }

        return redirect()->away(rtrim((string) config('app.url'), '/').$request->getRequestUri(), 301);
    }
}
