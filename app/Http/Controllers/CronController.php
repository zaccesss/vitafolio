<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

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

        Artisan::call('vitafolio:tidy');

        return response()->json(['ok' => true, 'output' => trim(Artisan::output())]);
    }
}
