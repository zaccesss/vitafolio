<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** small site files built from config, so each deployment carries its own name, address and contact */
class SiteController extends Controller
{
    public function manifest(): JsonResponse
    {
        return response()->json([
            'name' => config('app.name'),
            'short_name' => config('app.name'),
            'description' => 'Build, store and share CVs.',
            'id' => '/',
            'start_url' => '/dashboard?source=app',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#f7f6f3',
            'theme_color' => '#14213d',
            'lang' => 'en-GB',
            'icons' => [
                ['src' => asset('icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => asset('icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => asset('icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => 'My CVs', 'url' => '/dashboard'],
                ['name' => 'Profile', 'url' => '/profile'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=86400']);
    }

    public function robots(): Response
    {
        // signed-in areas carry noindex too; this just saves crawlers the trip
        $lines = [];
        // crawlers that collect training data are kept off public cvs; search engines are not
        foreach (['GPTBot', 'CCBot', 'Google-Extended', 'Bytespider', 'Applebot-Extended', 'meta-externalagent'] as $bot) {
            array_push($lines, 'User-agent: '.$bot, 'Disallow: /', '');
        }
        array_push($lines, 'User-agent: *', 'Disallow: /dashboard', 'Disallow: /cvs/', 'Disallow: /settings', 'Disallow: /admin', 'Disallow: /analytics', '', 'Sitemap: '.route('sitemap'));

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8', 'Cache-Control' => 'public, max-age=86400']);
    }

    /** rfc 9116; the expiry rolls forward on its own so the file never goes stale */
    public function securityTxt(): Response
    {
        $contact = config('vitafolio.security_contact');
        abort_unless(filled($contact), 404);

        $lines = [
            'Contact: mailto:'.$contact,
            'Expires: '.now()->addYear()->startOfMonth()->utc()->format('Y-m-d\TH:i:s\Z'),
            'Preferred-Languages: en',
            'Canonical: '.route('security.txt'),
        ];
        if (filled(config('vitafolio.source_url'))) {
            $lines[] = 'Policy: '.rtrim((string) config('vitafolio.source_url'), '/').'/security/policy';
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8', 'Cache-Control' => 'public, max-age=86400']);
    }

    /** indexnow checks this file to prove the key belongs to the site */
    public function indexNowKey(): Response
    {
        $key = config('vitafolio.indexnow_key');
        abort_unless(filled($key), 404);

        return response($key, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function offline(): View
    {
        return view('pages.offline');
    }
}
