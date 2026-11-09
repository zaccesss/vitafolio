<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * answers at /up/db for monitors that need to know the database is reachable. it sits outside the
 * web middleware group, so a check never starts a session, sets a cookie or writes a row
 */
class DatabaseHealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // anyone can call this address, so a flood of requests must not become a flood of queries
        // against the database's free allowance
        $up = Cache::remember('health:database', 15, function (): int {
            try {
                DB::select('select 1');

                return 1;
            } catch (Throwable) {
                return 0;
            }
        });

        // the 503 is the signal; reporting every failed poll would flood the error tracker
        return response()->json(['status' => $up ? 'up' : 'down'], $up ? 200 : 503, ['Cache-Control' => 'no-store']);
    }
}
