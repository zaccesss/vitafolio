<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * the free hosting plan has no scheduled jobs, so a scheduler outside the app calls this once a
 * night with a shared secret. without CRON_TOKEN set the address does not exist at all
 */
class CronController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) config('vitafolio.cron_token');
        abort_if($token === '', 404);
        abort_unless(hash_equals($token, (string) $request->bearerToken()), 403);

        // the job fetch reads two boards over the network and can run for minutes. the call answers at
        // once and the work runs after the response, so the caller never times out and retries into a
        // second run, which would tie up both of the free plan's request threads until the host
        // restarts the site. the lock turns away a retry or a second scheduler while a run is going
        $lock = Cache::lock('vitafolio-cron', 600);
        if (! $lock->get()) {
            return response()->json(['ok' => true, 'running' => true], 202);
        }

        app()->terminating(function () use ($lock): void {
            if (function_exists('frankenphp_finish_request')) {
                frankenphp_finish_request();
            }
            set_time_limit(300);
            try {
                Artisan::call('vitafolio:tidy');
                $output = trim(Artisan::output());
                Artisan::call('vitafolio:fetch-jobs');
                Log::info('nightly tidy-up finished', ['output' => $output."\n".trim(Artisan::output())]);
            } finally {
                $lock->release();
            }
        });

        return response()->json(['ok' => true, 'started' => true], 202);
    }
}
