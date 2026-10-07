<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/** picks the interface language for each page: a saved choice, then the cookie, then the browser */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Locales::forRequest($request);
        app()->setLocale($locale);
        Carbon::setLocale($locale);
        // error pages render outside this middleware, so they read the choice from here
        $request->attributes->set('locale', $locale);

        $response = $next($request);

        // the same address answers in different languages, so shared caches must keep them apart
        $response->headers->set('Vary', trim($response->headers->get('Vary').', Accept-Language', ', '));

        return $response;
    }
}
