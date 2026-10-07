<?php

namespace Tests\Feature;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JobsTest extends TestCase
{
    use RefreshDatabase;

    private function fakeBoards(): void
    {
        Http::fake([
            'api.adzuna.com/*' => Http::response(['results' => [
                ['id' => 'a1', 'title' => 'Software Engineering Internship', 'company' => ['display_name' => 'Acme'], 'location' => ['display_name' => 'London'],
                    'salary_min' => 24000, 'salary_max' => 24000, 'description' => '<b>Summer</b> internship', 'redirect_url' => 'https://www.adzuna.co.uk/jobs/land/ad/a1', 'created' => now()->subDay()->toIso8601String()],
                ['id' => 'a2', 'title' => 'Senior Architect', 'company' => ['display_name' => 'Acme'], 'location' => ['display_name' => 'London'], 'redirect_url' => 'https://www.adzuna.co.uk/jobs/land/ad/a2'],
            ]]),
            'www.reed.co.uk/*' => Http::response(['results' => [
                ['jobId' => 77, 'jobTitle' => 'Graduate Data Analyst', 'employerName' => 'Beta plc', 'locationName' => 'Birmingham', 'minimumSalary' => 27000, 'maximumSalary' => 30000,
                    'jobDescription' => 'Graduate scheme using SQL', 'jobUrl' => 'https://www.reed.co.uk/jobs/77', 'date' => now()->format('d/m/Y'), 'expirationDate' => now()->addWeek()->format('d/m/Y')],
            ]]),
        ]);
    }

    public function test_nothing_is_fetched_without_credentials(): void
    {
        Http::fake();
        $this->artisan('vitafolio:fetch-jobs')->expectsOutputToContain('No job source is configured.')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_listings_are_fetched_filtered_by_title_and_stored_once(): void
    {
        config(['services.adzuna.app_id' => 'id', 'services.adzuna.app_key' => 'key', 'services.reed.key' => 'key']);
        $this->fakeBoards();

        $this->artisan('vitafolio:fetch-jobs')->assertSuccessful();
        $this->artisan('vitafolio:fetch-jobs')->assertSuccessful();

        $this->assertSame(1, JobListing::where('source', 'adzuna')->where('external_id', 'a1')->count());
        $this->assertDatabaseMissing('job_listings', ['external_id' => 'a2']);
        $intern = JobListing::where('external_id', 'a1')->first();
        $this->assertSame('internship', $intern->kind);
        $this->assertSame('Summer internship', $intern->description);
        $grad = JobListing::where('source', 'reed')->where('external_id', '77')->where('kind', 'graduate')->first();
        $this->assertNotNull($grad->closes_at);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'reed.co.uk') && $request->hasHeader('Authorization'));
    }

    public function test_a_failing_board_never_stops_the_other(): void
    {
        config(['services.adzuna.app_id' => 'id', 'services.adzuna.app_key' => 'key', 'services.reed.key' => 'key']);
        Http::fake([
            'api.adzuna.com/*' => Http::response('down', 500),
            'www.reed.co.uk/*' => Http::response(['results' => [['jobId' => 5, 'jobTitle' => 'Graduate Engineer', 'jobUrl' => 'https://www.reed.co.uk/jobs/5']]]),
        ]);

        $this->artisan('vitafolio:fetch-jobs')->assertSuccessful();
        $this->assertDatabaseHas('job_listings', ['source' => 'reed', 'external_id' => '5']);
    }

    public function test_the_jobs_page_lists_filters_and_attributes_listings(): void
    {
        JobListing::factory()->create(['title' => 'Graduate Analyst', 'kind' => 'graduate', 'location' => 'Leeds']);
        JobListing::factory()->create(['source' => 'adzuna', 'title' => 'Marketing Internship', 'kind' => 'internship', 'url' => 'https://www.adzuna.co.uk/jobs/land/ad/9']);
        JobListing::factory()->create(['title' => 'Closed Graduate Role', 'closes_at' => now()->subDay()]);

        $this->get(route('jobs'))->assertOk()->assertSee('Graduate Analyst')->assertSee('Marketing Internship')
            ->assertDontSee('Closed Graduate Role')->assertSee('href="https://www.adzuna.co.uk"', false);
        $this->get(route('jobs', ['kind' => 'internship']))->assertSee('Marketing Internship')->assertDontSee('Graduate Analyst');
        $this->get(route('jobs', ['where' => 'Leeds']))->assertSee('Graduate Analyst')->assertDontSee('Marketing Internship');
        $this->get(route('jobs', ['q' => 'marketing']))->assertSee('Marketing Internship')->assertDontSee('Graduate Analyst');
    }

    public function test_check_my_cv_against_a_job_fills_in_the_advert(): void
    {
        $job = JobListing::factory()->create(['title' => 'Graduate Analyst', 'description' => 'Must know Tableau and SQL']);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('jobs'))->assertSee(route('check', ['job' => $job->id]), false);
        $this->actingAs($user)->get(route('check', ['job' => $job->id]))->assertOk()
            ->assertSee('Must know Tableau and SQL')->assertSee('Checking against Graduate Analyst');
    }

    public function test_tidy_removes_closed_and_stale_listings(): void
    {
        $open = JobListing::factory()->create();
        JobListing::factory()->create(['closes_at' => now()->subDay()]);
        JobListing::factory()->create(['closes_at' => null, 'posted_at' => now()->subDays(40)]);

        $this->artisan('vitafolio:tidy')->assertSuccessful();

        $this->assertSame([$open->id], JobListing::pluck('id')->all());
    }
}
