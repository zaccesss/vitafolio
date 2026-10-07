<?php

namespace App\Support\Jobs;

use App\Models\JobListing;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pulls UK roles for students and graduates from the free Adzuna and Reed job APIs. Only what a
 * listing needs is kept. Every listing links back to the board it came from. A source with no
 * credentials is skipped; one that fails never stops the other.
 */
class JobFetcher
{
    /** search terms per kind of role; a result is kept only when its title confirms the kind */
    public const SEARCHES = [
        'internship' => ['internship', 'summer intern'],
        'placement' => ['industrial placement', 'placement year'],
        'graduate' => ['graduate scheme', 'graduate'],
        'part-time' => ['part time student'],
    ];

    private const TITLE_WORDS = [
        'internship' => ['intern', 'internship', 'insight'],
        'placement' => ['placement', 'industrial', 'sandwich', 'year in industry', '12 month', '12-month'],
        'graduate' => ['graduate', 'grad ', 'entry level', 'entry-level', 'junior', 'trainee'],
        'part-time' => ['part time', 'part-time', 'student', 'weekend', 'casual'],
    ];

    private const PER_SEARCH = 50;

    /** @return array<string, int> listings stored per source */
    public function fetch(): array
    {
        $stored = [];
        foreach (['adzuna', 'reed'] as $source) {
            if (! $this->configured($source)) {
                continue;
            }
            $stored[$source] = 0;
            foreach (self::SEARCHES as $kind => $terms) {
                foreach ($terms as $term) {
                    try {
                        $rows = $source === 'adzuna' ? $this->adzuna($term, $kind) : $this->reed($term, $kind);
                    } catch (Throwable $e) {
                        report($e);

                        continue;
                    }
                    foreach ($rows as $row) {
                        JobListing::updateOrCreate(['source' => $source, 'external_id' => $row['external_id']], $row);
                        $stored[$source]++;
                    }
                }
            }
        }

        return $stored;
    }

    public function configured(string $source): bool
    {
        return $source === 'adzuna'
            ? filled(config('services.adzuna.app_id')) && filled(config('services.adzuna.app_key'))
            : filled(config('services.reed.key'));
    }

    /** @return list<array<string, mixed>> */
    private function adzuna(string $term, string $kind): array
    {
        $response = Http::timeout(20)->retry(2, 500)->get('https://api.adzuna.com/v1/api/jobs/gb/search/1', [
            'app_id' => config('services.adzuna.app_id'),
            'app_key' => config('services.adzuna.app_key'),
            'what' => $term,
            'max_days_old' => 30,
            'results_per_page' => self::PER_SEARCH,
            'sort_by' => 'date',
            'content-type' => 'application/json',
        ])->throw();

        $rows = [];
        foreach ($response->json('results', []) as $job) {
            if (! $this->titleMatches((string) ($job['title'] ?? ''), $kind) || blank($job['redirect_url'] ?? null)) {
                continue;
            }
            $rows[] = [
                'external_id' => (string) $job['id'],
                'kind' => $kind,
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
            if (! $this->titleMatches((string) ($job['jobTitle'] ?? ''), $kind) || blank($job['jobUrl'] ?? null)) {
                continue;
            }
            $rows[] = [
                'external_id' => (string) $job['jobId'],
                'kind' => $kind,
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

    private function titleMatches(string $title, string $kind): bool
    {
        return Str::contains(mb_strtolower($title), self::TITLE_WORDS[$kind]);
    }

    private function clean(?string $text, int $limit): ?string
    {
        if (blank($text)) {
            return null;
        }
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return Str::limit($text, $limit, '...');
    }

    private function money(mixed $value): ?int
    {
        return is_numeric($value) && $value > 0 ? (int) round((float) $value) : null;
    }

    /** reed writes dates as dd/mm/yyyy */
    private function ukDate(?string $value): ?Carbon
    {
        return $value && preg_match('#^\d{2}/\d{2}/\d{4}$#', $value) ? Carbon::createFromFormat('d/m/Y', $value)->startOfDay() : null;
    }
}
