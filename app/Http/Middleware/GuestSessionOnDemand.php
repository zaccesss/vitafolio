<?php

namespace App\Http\Middleware;

use App\Support\LazyDatabaseSessionHandler;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * a signed-out visitor who only reads pages never needs a stored session, yet each one used to get a
 * row in the sessions table. the one thing a reader's session holds that a later request could depend
 * on is the form token: a form posted back is checked against it. so once the page is built this looks
 * for the token in it. when it is not there, the session handler is told it may skip the write. the
 * handler still writes whenever the session holds anything real or the visitor is signed in
 */
class GuestSessionOnDemand
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->hasSession()) {
            return $response;
        }

        $session = $request->session();
        $handler = $session->getHandler();
        if ($handler instanceof LazyDatabaseSessionHandler && ! self::carriesToken($response, (string) $session->token())) {
            $handler->markTokenUnsent();
        }

        return $response;
    }

    /**
     * whether a form token printed in this page can still be checked when the form comes back. views use
     * it to leave the token out of parts shown on every page, such as the language menu, so those parts
     * alone do not make every page view store a session
     */
    public static function tokenWillLast(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $handler = $request->session()->getHandler();

        return ! $handler instanceof LazyDatabaseSessionHandler || $handler->hasRow() || $request->user() !== null;
    }

    private static function carriesToken(Response $response, string $token): bool
    {
        $body = $response->getContent();

        // a streamed body cannot be read back and a missing token cannot be searched for. both count
        // as sent, which keeps the session stored as before
        return $body === false || $token === '' || str_contains($body, $token);
    }
}
