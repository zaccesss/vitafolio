<?php

namespace App\Console\Commands;

use App\Support\Jobs\JobFetcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('vitafolio:fetch-jobs')]
#[Description('Fetch internships, placements, graduate roles and student jobs from Adzuna and Reed')]
class FetchJobs extends Command
{
    public function handle(JobFetcher $fetcher): int
    {
        $stored = $fetcher->fetch();
        if ($stored === []) {
            $this->line('No job source is configured.');

            return self::SUCCESS;
        }
        foreach ($stored as $source => $count) {
            $this->line(str_pad($source, 10).$count);
        }

        return self::SUCCESS;
    }
}
