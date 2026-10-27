<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
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
        try {
            DB::select('select 1');
        } catch (Throwable) {
            // the 503 is the signal; reporting every failed poll would flood the error tracker
            return response()->json(['status' => 'down'], 503, ['Cache-Control' => 'no-store']);
        }

        return response()->json(['status' => 'up'], 200, ['Cache-Control' => 'no-store']);
    }
}
