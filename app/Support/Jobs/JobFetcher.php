<?php

namespace App\Support\Jobs;

use App\Models\JobListing;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Pulls UK roles for students and graduates from the free Adzuna and Reed job APIs. Only what a
 * listing needs is kept. Every listing links back to the board it came from. A source with no
 * credentials is skipped; one that fails never stops the other.
 */
class JobFetcher
{
    /** search terms per kind of role; each result's kind is then read from its own title, never assumed from the search */
    public const SEARCHES = [
        'internship' => ['internship', 'summer intern', 'summer internship 2027', 'vacation scheme'],
        'placement' => ['industrial placement', 'placement year', 'year in industry', 'sandwich placement'],
        'insight' => ['spring week', 'insight programme', 'insight day'],
        // one search per field as well, since a plain "graduate" search is dominated by a few fields
        'graduate' => ['graduate scheme', 'graduate', 'graduate engineer', 'graduate analyst', 'graduate accountant',
            'trainee solicitor', 'training contract', 'graduate nurse', 'graduate teacher', 'graduate marketing',
            'graduate scientist', 'graduate software'],
        'apprenticeship' => ['apprenticeship', 'degree apprenticeship', 'level 3 apprenticeship'],
        'part-time' => ['part time student', 'student job'],
    ];

    private const PER_SEARCH = 50;

    /** pages read per search; each page is one request against the board's daily allowance */
    private const PAGES = 3;

    /** @return array<string, int> listings stored per source */
    public function fetch(): array
    {
        $stored = [];
        $this->loadKnown();
        foreach (['adzuna', 'reed'] as $source) {
            if (! $this->configured($source)) {
                continue;
            }
            $stored[$source] = 0;
            if ($source === 'adzuna') {
                foreach ($this->adzunaAll() as $row) {
                    $this->store($source, $row);
                    $stored[$source]++;
                }

                continue;
            }
            foreach (self::SEARCHES as $kind => $terms) {
                foreach ($terms as $term) {
                    try {
                        $rows = $this->reed($term, $kind);
                    } catch (Throwable $e) {
                        report($e);

                        continue;
                    }
                    foreach ($rows as $row) {
                        $this->store($source, $row);
                        $stored[$source]++;
                    }
                }
            }
        }

        $this->flush();

        return $stored;
    }

    /** stored board listings' locations by source and key, read once per fetch */
    private array $known = [];

    /** roles the employer lists itself, by title and cleaned employer name */
    private array $employerRoles = [];

    /** listings waiting to be saved, by source and key */
    private array $buffer = [];

    /**
     * Everything store() needs to decide, read in two queries. A lookup per listing against the
     * remote database took longer than the minute the nightly request may run.
     */
    private function loadKnown(): void
    {
        $this->known = JobListing::whereIn('source', ['adzuna', 'reed'])->get(['source', 'external_id', 'location'])
            ->mapWithKeys(fn ($l) => ["{$l->source}|{$l->external_id}" => $l->location])->all();
        $this->employerRoles = JobListing::where('source', 'employer')->get(['title', 'company'])
            ->mapWithKeys(fn ($l) => [mb_strtolower(trim($l->title)).'|'.self::employerKey((string) $l->company) => true])->all();
        $this->buffer = [];
    }

    /**
     * Boards post one advert per city for the same role, so a listing is keyed on its title and
     * employer and each city is added to the one listing rather than shown as another card.
     *
     * @param  array<string, mixed>  $row
     */
    private function store(string $source, array $row): void
    {
        $row['external_id'] = self::sameRole($row['title'], (string) $row['company']);
        // the employer's own listing links straight to the job with the full advert, so it wins
        if (isset($this->employerRoles[mb_strtolower(trim($row['title'])).'|'.self::employerKey((string) $row['company'])])) {
            return;
        }
        $key = "{$source}|{$row['external_id']}";
        $existing = $this->buffer[$key]['location'] ?? $this->known[$key] ?? null;
        if ($existing) {
            $places = array_map('mb_strtolower', array_map('trim', explode(';', $existing)));
            $row['location'] = filled($row['location']) && ! in_array(mb_strtolower(trim($row['location'])), $places, true)
                ? self::fit($existing.'; '.$row['location'], 160)
                : $existing;
        }
        $this->buffer[$key] = ['source' => $source] + $row;
    }

    /** saves the buffered listings in batches, inserting new ones and refreshing known ones */
    private function flush(): void
    {
        $now = now();
        $columns = ['source', 'external_id', 'kind', 'sector', 'title', 'company', 'location', 'salary_min', 'salary_max',
            'description', 'url', 'posted_at', 'closes_at'];
        $rows = array_map(function (array $row) use ($columns, $now) {
            $out = [];
            foreach ($columns as $c) {
                $value = $row[$c] ?? null;
                $out[$c] = $value instanceof \DateTimeInterface ? Carbon::instance($value)->toDateTimeString() : $value;
            }

            return $out + ['created_at' => $now, 'updated_at' => $now];
        }, array_values($this->buffer));
        foreach (array_chunk($rows, 200) as $chunk) {
            JobListing::upsert($chunk, ['source', 'external_id'], array_diff(array_keys($chunk[0]), ['source', 'external_id', 'created_at']));
        }
        $this->buffer = [];
    }

    public function configured(string $source): bool
    {
        return $source === 'adzuna'
            ? filled(config('services.adzuna.app_id')) && filled(config('services.adzuna.app_key'))
            : filled(config('services.reed.key'));
    }

    /** requests sent to Adzuna at once; each search's next page is asked for only when this one was full */
    private const PARALLEL = 4;

    /**
     * Every Adzuna search, read page by page in parallel batches. One after another, the 30 or so
     * searches took longer than the minute a web request may run.
     *
     * @return list<array<string, mixed>>
     */
    private function adzunaAll(): array
    {
        $pending = [];
        foreach (self::SEARCHES as $kind => $terms) {
            foreach ($terms as $term) {
                $pending[] = [$term, $kind, 1];
            }
        }
        $rows = [];
        $sent = 0;
        $failed = [];
        while ($pending !== []) {
            $batch = array_splice($pending, 0, self::PARALLEL);
            $responses = Http::pool(fn ($pool) => array_map(
                fn ($search) => $pool->timeout(20)->get("https://api.adzuna.com/v1/api/jobs/gb/search/{$search[2]}", $this->adzunaQuery($search[0], $search[1])),
                $batch,
            ));
            foreach ($batch as $i => [$term, $kind, $page]) {
                $sent++;
                $response = $responses[$i];
                // a refused or dropped request is usually a short rate limit, so it is tried again after a pause
                for ($try = 1; $this->adzunaFailed($response) && $try <= 2; $try++) {
                    usleep(1_500_000 * $try);
                    $response = rescue(fn () => Http::timeout(20)->get("https://api.adzuna.com/v1/api/jobs/gb/search/{$page}", $this->adzunaQuery($term, $kind)), null, false);
                }
                if ($this->adzunaFailed($response)) {
                    $failed[] = "{$term} p{$page} (".($response instanceof Response ? $response->status() : 'no response').')';

                    continue;
                }
                $found = $this->adzunaRows($response->json('results', []), $kind);
                $rows = array_merge($rows, $found);
                if (count($response->json('results', [])) === self::PER_SEARCH && $page < self::PAGES) {
                    $pending[] = [$term, $kind, $page + 1];
                }
            }
        }

        // a lost search or two only means fewer listings tonight; an outage is when many fail
        if (count($failed) > $sent / 4) {
            report(new \RuntimeException('Adzuna searches failed: '.count($failed).' of '.$sent.', for example '.implode(', ', array_slice($failed, 0, 5))));
        }

        return $rows;
    }

    private function adzunaFailed(mixed $response): bool
    {
        return ! $response instanceof Response || $response->failed();
    }

    /** @return array<string, mixed> */
    private function adzunaQuery(string $term, string $kind): array
    {
        return [
            'app_id' => config('services.adzuna.app_id'),
            'app_key' => config('services.adzuna.app_key'),
            'what' => $term,
            'max_days_old' => 30,
            'results_per_page' => self::PER_SEARCH,
            'sort_by' => 'date',
            'content-type' => 'application/json',
            ...($kind === 'part-time' ? ['part_time' => 1] : []),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $results
     * @return list<array<string, mixed>>
     */
    private function adzunaRows(array $results, string $kind): array
    {
        $rows = [];
        foreach ($results as $job) {
            $type = $this->kindOf((string) ($job['title'] ?? ''), $kind);
            if ($type === null || blank($job['redirect_url'] ?? null)) {
                continue;
            }
            $rows[] = [
                'external_id' => (string) $job['id'],
                'kind' => $type,
                'sector' => Sector::fromAdzuna($job['category']['tag'] ?? null, (string) $job['title'], $job['company']['display_name'] ?? null),
                'title' => $this->clean($job['title'], 200),
                'company' => $this->clean($job['company']['display_name'] ?? null, 160),
                'location' => $this->clean($job['location']['display_name'] ?? null, 160),
                'salary_min' => $this->money($job['salary_min'] ?? null),
                'salary_max' => $this->money($job['salary_max'] ?? null),
                'description' => $this->clean($job['description'] ?? null, 2000),
                'url' => (string) $job['redirect_url'],
                'posted_at' => isset($job['created']) ? Carbon::parse($job['created']) : null,
                'closes_at' => null,
            ];
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private function reed(string $term, string $kind): array
    {
        $params = ['keywords' => $term, 'resultsToTake' => self::PER_SEARCH];
        if ($kind === 'part-time') {
            $params['partTime'] = 'true';
        }
        $response = Http::timeout(20)->retry(2, 500)
            ->withBasicAuth((string) config('services.reed.key'), '')
            ->get('https://www.reed.co.uk/api/1.0/search', $params)->throw();

        $rows = [];
        foreach ($response->json('results', []) as $job) {
            $type = $this->kindOf((string) ($job['jobTitle'] ?? ''), $kind);
            if ($type === null || blank($job['jobUrl'] ?? null)) {
                continue;
            }
            $rows[] = [
                'external_id' => (string) $job['jobId'],
                'kind' => $type,
                'sector' => Sector::guess((string) $job['jobTitle'], $job['employerName'] ?? null),
                'title' => $this->clean($job['jobTitle'], 200),
                'company' => $this->clean($job['employerName'] ?? null, 160),
                'location' => $this->clean($job['locationName'] ?? null, 160),
                'salary_min' => $this->money($job['minimumSalary'] ?? null),
                'salary_max' => $this->money($job['maximumSalary'] ?? null),
                'description' => $this->clean($job['jobDescription'] ?? null, 2000),
                'url' => (string) $job['jobUrl'],
                'posted_at' => $this->ukDate($job['date'] ?? null),
                'closes_at' => $this->ukDate($job['expirationDate'] ?? null),
            ];
        }

        return $rows;
    }

    private function kindOf(string $title, string $searched): ?string
    {
        return RoleType::inCycle($title) ? RoleType::classify($title, partTime: $searched === 'part-time') : null;
    }

    private function clean(?string $text, int $limit): ?string
    {
        if (blank($text)) {
            return null;
        }
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return self::fit($text, $limit);
    }

    /**
     * Shortens text to at most $limit characters including the trailing dots. Str::limit adds the
     * dots after the limit, which ran three characters past the column and failed the nightly save.
     */
    private static function fit(string $text, int $limit): string
    {
        return mb_strlen($text) <= $limit ? $text : rtrim(mb_substr($text, 0, $limit - 3)).'...';
    }

    /** one role at one employer, whichever board or city it came through */
    public static function sameRole(string $title, string $company): string
    {
        return sha1(mb_strtolower(trim($title)).'|'.self::employerKey($company));
    }

    public static function employerHas(string $title, string $company): bool
    {
        $key = self::employerKey($company);

        return JobListing::where('source', 'employer')->where('title', $title)->pluck('company')
            ->contains(fn ($name) => self::employerKey((string) $name) === $key);
    }

    /** "Safran" and "SAFRAN UK Ltd" are one employer */
    public static function employerKey(string $company): string
    {
        $name = preg_replace('/[^a-z0-9 ]+/', ' ', mb_strtolower($company));
        $name = preg_replace('/\b(uk|u k|ltd|limited|plc|llp|group|holdings|inc|co|company|the)\b/', ' ', $name);

        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /** a figure this small is a daily or hourly rate, which the boards do not label, so it is left out */
    private function money(mixed $value): ?int
    {
        return is_numeric($value) && $value >= 5000 ? (int) round((float) $value) : null;
    }

    /** reed writes dates as dd/mm/yyyy */
    private function ukDate(?string $value): ?Carbon
    {
        return $value && preg_match('#^\d{2}/\d{2}/\d{4}$#', $value) ? Carbon::createFromFormat('d/m/Y', $value)->startOfDay() : null;
    }
}
