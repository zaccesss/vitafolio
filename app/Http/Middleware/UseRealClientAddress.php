<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * works out the visitor's real address before anything reads it. the host's proxies pass on an
 * X-Forwarded-For header with whatever the visitor sent at the front of it. Trusting every proxy
 * makes the framework read that first entry, so a made-up header would dodge every rate limit keyed
 * on the address.
 *
 * the host sits behind Cloudflare, which sets CF-Connecting-IP itself and refuses requests that try
 * to send their own. when it is missing, the last entry is the one the nearest proxy added, which a
 * visitor cannot choose
 */
class UseRealClientAddress
{
    public function handle(Request $request, Closure $next): Response
    {
        $address = $this->address($request);

        if ($address === null) {
            $request->headers->remove('X-Forwarded-For');
            $request->server->remove('HTTP_X_FORWARDED_FOR');
        } else {
            $request->headers->set('X-Forwarded-For', $address);
            $request->server->set('HTTP_X_FORWARDED_FOR', $address);
        }

        return $next($request);
    }

    private function address(Request $request): ?string
    {
        $edge = trim((string) $request->headers->get('CF-Connecting-IP'));
        if (filter_var($edge, FILTER_VALIDATE_IP)) {
            return $edge;
        }

        $hops = explode(',', (string) $request->headers->get('X-Forwarded-For'));
        $nearest = trim((string) end($hops));

        return filter_var($nearest, FILTER_VALIDATE_IP) ? $nearest : null;
    }
}
