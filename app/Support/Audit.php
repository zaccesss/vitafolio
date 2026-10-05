<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * one structured log line per security-relevant event, written to the normal log channel (stderr
 * in production, which the host keeps). never pass a password, token, code or session id in here
 */
class Audit
{
    public static function log(string $event, array $context = []): void
    {
        Log::info('audit.'.$event, $context + ['ip' => request()->ip(), 'at' => now()->toIso8601String()]);
    }
}
