<?php

namespace Tests\Feature;

use App\Models\JobListing;
use App\Models\User;
use App\Support\Jobs\JobFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class JobFeedTest extends TestCase
{
    use RefreshDatabase;

    private function send(array $jobs, ?string $token = 'feed-token')
    {
        return $this->withToken((string) $token)->postJson(route('jobs.feed'), ['jobs' => $jobs]);
    }

    public function test_the_feed_does_not_exist_without_a_token_and_refuses_a_wrong_one(): void
    {
        $this->postJson(route('jobs.feed'), ['jobs' => []])->assertNotFound();
        config(['vitafolio.jobs_feed_token' => 'feed-token']);
        $this->send([], 'wrong')->assertForbidden();
    }

    public function test_listings_are_checked_again_and_stored_once(): void
    {
        Carbon::setTestNow('2026-10-08 06:00');
        config(['vitafolio.jobs_feed_token' => 'feed-token']);
        $jobs = [
            ['title' => 'Software Engineering Intern (Summer 2027)', 'company' => 'Acme', 'url' => 'https://boards.greenhouse.io/acme/jobs/1', 'board' => 'Greenhouse', 'location' => 'London', 'deadline' => '2026-11-30',
                'description' => '<p>You will build services in <strong>Go</strong> and Kubernetes.</p>'],
            ['title' => 'Industrial Placement 2027', 'company' => 'Beta', 'url' => 'https://beta.wd3.myworkdayjobs.com/en-GB/careers/job/2', 'board' => 'Workday'],
            ['title' => 'Internal Audit Manager', 'company' => 'Acme', 'url' => 'https://boards.greenhouse.io/acme/jobs/3'],
            ['title' => 'Summer Internship 2026', 'company' => 'Acme', 'url' => 'https://boards.greenhouse.io/acme/jobs/4'],
            ['title' => 'Graduate Analyst', 'company' => 'Acme', 'url' => 'https://boards.greenhouse.io/acme/jobs/5', 'deadline' => '2026-10-01'],
            ['title' => 'Graduate Engineer', 'company' => 'Acme', 'url' => 'javascript:alert(1)'],
        ];

        $this->send($jobs)->assertOk()->assertJson(['stored' => 2, 'skipped' => ['not a student role' => 1, 'outside the cycle' => 1, 'closed' => 1, 'bad link' => 1]]);
        $this->send($jobs)->assertOk();

        $this->assertSame(2, JobListing::where('source', 'employer')->count());
        $intern = JobListing::where('url', 'https://boards.greenhouse.io/acme/jobs/1')->first();
        $this->assertSame(['internship', 'Greenhouse'], [$intern->kind, $intern->board]);
        $this->assertSame('placement', JobListing::where('company', 'Beta')->value('kind'));

        $this->actingAs(User::factory()->create())->get(route('jobs'))->assertSee('Software Engineering Intern (Summer 2027)')
            ->assertSee("From the employer's own careers site")
            ->assertDontSee('You will build services')
            ->assertSee(route('check', ['job' => $intern->id]), false)
            ->assertDontSee(route('check', ['job' => JobListing::where('company', 'Beta')->value('id')]), false);
        $this->assertSame('You will build services in Go and Kubernetes.', $intern->description);
    }

    public function test_the_employers_own_listing_replaces_a_boards_copy(): void
    {
        Carbon::setTestNow('2026-10-08 06:00');
        config(['vitafolio.jobs_feed_token' => 'feed-token']);
        $board = JobListing::factory()->create(['source' => 'adzuna', 'title' => 'Software Engineering Intern (2027 Start)', 'company' => 'Acme Ltd',
            'external_id' => JobFetcher::sameRole('Software Engineering Intern (2027 Start)', 'Acme Ltd')]);

        $this->send([['title' => 'Software Engineering Intern (2027 Start)', 'company' => 'Acme', 'url' => 'https://jobs.lever.co/acme/1']])->assertOk();

        $this->assertModelMissing($board);
        $this->assertSame(1, JobListing::where('title', 'Software Engineering Intern (2027 Start)')->count());
        $this->assertTrue(JobFetcher::employerHas('Software Engineering Intern (2027 Start)', 'ACME UK'));
    }

    public function test_tidy_removes_employer_listings_the_feed_stopped_sending(): void
    {
        $seen = JobListing::factory()->create(['source' => 'employer', 'last_seen_at' => now(), 'posted_at' => null]);
        JobListing::factory()->create(['source' => 'employer', 'last_seen_at' => now()->subDays(4)]);

        $this->artisan('vitafolio:tidy')->assertSuccessful();

        $this->assertSame([$seen->id], JobListing::pluck('id')->all());
    }
}
