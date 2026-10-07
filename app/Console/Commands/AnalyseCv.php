<?php

namespace App\Console\Commands;

use App\Support\Ats\AtsReport;
use App\Support\Ats\ResumeText;
use App\Support\Ats\UnreadableResume;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cv:analyse {file : A PDF or Word (.docx) CV} {--job= : A text file holding a job advert to match keywords against} {--out=output : Folder for parsed.json and report.md}')]
#[Description('Check a CV the way an applicant tracking system reads it and write parsed.json and report.md')]
class AnalyseCv extends Command
{
    public function handle(): int
    {
        $file = (string) $this->argument('file');
        if (! is_file($file)) {
            $this->error("No file at {$file}.");

            return self::FAILURE;
        }
        $job = $this->option('job');
        if ($job !== null && ! is_file($job)) {
            $this->error("No job advert at {$job}.");

            return self::FAILURE;
        }

        try {
            $report = AtsReport::analyse(ResumeText::fromFile($file), $job !== null ? (string) file_get_contents($job) : null);
        } catch (UnreadableResume $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $out = rtrim((string) $this->option('out'), '/');
        if (! is_dir($out)) {
            mkdir($out, 0755, true);
        }
        file_put_contents("{$out}/parsed.json", json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
        file_put_contents("{$out}/report.md", $report->toMarkdown());

        $this->info("Score: {$report->total()} / 100 ({$report->grade()})");
        $this->line("Wrote {$out}/parsed.json and {$out}/report.md");

        return self::SUCCESS;
    }
}
