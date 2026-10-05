<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * tells search engines that take part in indexnow (bing, yandex, seznam, naver and others) when a
 * public page changes or disappears, so updates show up in hours rather than weeks. google does
 * not take part; it reads the sitemap instead. off unless INDEXNOW_KEY is set
 */
class IndexNow
{
    public static function enabled(): bool
    {
        return filled(config('vitafolio.indexnow_key')) && app()->isProduction();
    }

    /** queued until the response has been sent, so a save is never slowed down or broken by it */
    public static function submit(string ...$urls): void
    {
        if (! self::enabled() || $urls === []) {
            return;
        }
        $urls = array_values(array_unique($urls));

        defer(function () use ($urls) {
            try {
                Http::timeout(5)->asJson()->post('https://api.indexnow.org/indexnow', [
                    'host' => parse_url(config('app.url'), PHP_URL_HOST),
                    'key' => config('vitafolio.indexnow_key'),
                    'keyLocation' => route('indexnow.key'),
                    'urlList' => $urls,
                ]);
            } catch (\Throwable $e) {
                // a missed ping only delays indexing; the sitemap still covers it
                Log::info('indexnow submission failed', ['error' => $e->getMessage()]);
            }
        });
    }
}
