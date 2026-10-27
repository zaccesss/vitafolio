<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * crawlers, link previews and machine fetches of site files never come back with a session cookie,
 * so a database session would be written for every one of them and never read again. for those
 * requests the session lives in memory only. real visitors keep database sessions, because the
 * signed-in devices list, ending other sessions and the security emails all read the sessions table
 */
class StatelessForCrawlers
{
    /** addresses fetched by machines rather than read by people */
    public const STATELESS_PATHS = ['robots.txt', 'sitemap.xml', 'cv/*/og.png', 'cv/*/qr.svg', 'up', 'up/db'];

    /**
     * matched against the user agent. it looks for a crawler's version slash or its info link rather than
     * a bare "bot", since some phone models carry those letters in their names
     */
    private const CRAWLER = '~bot/|crawl|spider|slurp|bytespider|facebookexternalhit|whatsapp/|lighthouse|uptime|monitor|\+https?://|^curl/|^wget/|python-requests|go-http-client~i';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isStateless($request)) {
            return $next($request);
        }

        $driver = config('session.driver');
        config(['session.driver' => 'array']);

        try {
            $response = $next($request);
        } finally {
            // put the setting back so nothing later in the same process inherits it
            config(['session.driver' => $driver]);
        }

        // a cookie for a session that was never stored would only start a database session next time
        $response->headers->removeCookie((string) config('session.cookie'), config('session.path'), config('session.domain'));

        return $response;
    }

    public static function isStateless(Request $request): bool
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }
        if ($request->cookies->has((string) config('session.cookie'))) {
            return false;
        }

        return $request->is(...self::STATELESS_PATHS) || self::isCrawler((string) $request->userAgent());
    }

    public static function isCrawler(string $userAgent): bool
    {
        // every browser sends a user agent, so an empty one is a script
        return trim($userAgent) === '' || preg_match(self::CRAWLER, $userAgent) === 1;
    }
}
