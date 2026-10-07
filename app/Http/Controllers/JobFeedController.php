<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use App\Support\Jobs\RoleType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Receives listings taken straight from employers' own hiring systems. The sender's labels are not
 * trusted: every listing is classified again from its title, checked against the recruitment cycle
 * and must link to an https address. Anything that fails is counted and left out.
 */
class JobFeedController extends Controller
{
    private const MAX = 1000;

    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) config('vitafolio.jobs_feed_token');
        abort_if($token === '', 404);
        abort_unless(hash_equals($token, (string) $request->bearerToken()), 403);

        $data = $request->validate([
            'jobs' => ['present', 'array', 'max:'.self::MAX],
            'jobs.*.title' => ['required', 'string', 'max:300'],
            'jobs.*.company' => ['required', 'string', 'max:160'],
            'jobs.*.url' => ['required', 'string', 'max:500'],
            'jobs.*.location' => ['nullable', 'string', 'max:160'],
            'jobs.*.board' => ['nullable', 'string', 'max:40'],
            'jobs.*.deadline' => ['nullable', 'date'],
            'jobs.*.opened' => ['nullable', 'date'],
            'jobs.*.description' => ['nullable', 'string', 'max:50000'],
        ]);

        $now = now();
        $stored = 0;
        $skipped = ['not a student role' => 0, 'outside the cycle' => 0, 'closed' => 0, 'bad link' => 0];
        foreach ($data['jobs'] as $job) {
            $title = trim(preg_replace('/\s+/', ' ', strip_tags($job['title'])));
            $deadline = filled($job['deadline'] ?? null) ? Carbon::parse($job['deadline'])->endOfDay() : null;
            $kind = RoleType::classify($title);
            $reason = match (true) {
                ! $this->safeUrl($job['url']) => 'bad link',
                $kind === null => 'not a student role',
                ! RoleType::inCycle($title, $now) => 'outside the cycle',
                $deadline !== null && $deadline->isPast() => 'closed',
                default => null,
            };
            if ($reason !== null) {
                $skipped[$reason]++;

                continue;
            }

            JobListing::updateOrCreate(['source' => 'employer', 'external_id' => sha1($job['url'])], [
                'board' => $this->text($job['board'] ?? null, 40),
                'kind' => $kind,
                'title' => Str::limit($title, 200, '...'),
                'company' => $this->text($job['company'], 160),
                'location' => $this->text($job['location'] ?? null, 160),
                'url' => $job['url'],
                // kept for "check my CV against this job" only; the listing never shows the employer's text
                'description' => $this->text(html_entity_decode((string) ($job['description'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 10000),
                'posted_at' => filled($job['opened'] ?? null) ? Carbon::parse($job['opened']) : null,
                'closes_at' => $deadline,
                'last_seen_at' => $now,
            ]);
            $stored++;
        }

        return response()->json(['ok' => true, 'stored' => $stored, 'skipped' => $skipped]);
    }

    private function safeUrl(string $url): bool
    {
        return str_starts_with($url, 'https://') && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function text(?string $value, int $limit): ?string
    {
        $value = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)));

        return $value === '' ? null : Str::limit($value, $limit, '...');
    }
}
