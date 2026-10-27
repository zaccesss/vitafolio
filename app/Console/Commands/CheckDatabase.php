<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

#[Signature('vitafolio:db-check {--timeout=5 : Seconds to wait for the connection}')]
#[Description('Exit 0 when the database answers a trivial query, 1 when it cannot be reached')]
class CheckDatabase extends Command
{
    public function handle(): int
    {
        $name = (string) config('database.default');
        $key = 'database.connections.'.$name.'.options';
        // a short timeout, so a database that never answers cannot hold up the container start
        config([$key => [PDO::ATTR_TIMEOUT => max(1, (int) $this->option('timeout'))] + (array) config($key, [])]);
        DB::purge($name);

        try {
            DB::connection($name)->select('select 1');
        } catch (Throwable $e) {
            $this->error('The database cannot be reached: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('The database answered.');

        return self::SUCCESS;
    }
}
