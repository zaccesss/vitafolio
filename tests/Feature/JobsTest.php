<?php

namespace Tests\Feature;

use App\Models\JobListing;
use App\Models\User;
use App\Support\Jobs\JobFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
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

        $this->assertSame(1, JobListing::where('source', 'adzuna')->where('title', 'Software Engineering Internship')->count());
        $this->assertDatabaseMissing('job_listings', ['title' => 'Senior Architect']);
        $intern = JobListing::where('title', 'Software Engineering Internship')->first();
        $this->assertSame('internship', $intern->kind);
        $this->assertSame('Summer internship', $intern->description);
        $grad = JobListing::where('source', 'reed')->where('title', 'Graduate Data Analyst')->where('kind', 'graduate')->first();
        $this->assertNotNull($grad->closes_at);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'reed.co.uk') && $request->hasHeader('Authorization'));
    }

    public function test_a_role_in_many_cities_stays_within_the_location_column(): void
    {
        config(['services.adzuna.app_id' => 'id', 'services.adzuna.app_key' => 'key']);
        $cities = array_map(fn ($n) => "Townshire {$n}", range(1, 30));
        $ad = fn ($n) => ['id' => "c{$n}", 'title' => 'Graduate Surveyor', 'company' => ['display_name' => 'Gamma'], 'location' => ['display_name' => $cities[$n]], 'redirect_url' => "https://www.adzuna.co.uk/jobs/land/ad/c{$n}"];
        Http::fake(['api.adzuna.com/*' => Http::response(['results' => array_map($ad, array_keys($cities))])]);

        $this->artisan('vitafolio:fetch-jobs')->assertSuccessful();

        $location = (string) JobListing::where('title', 'Graduate Surveyor')->value('location');
        $this->assertLessThanOrEqual(160, mb_strlen($location));
        $this->assertStringEndsWith('...', $location);
    }

    public function test_one_role_posted_for_several_cities_is_one_listing(): void
    {
        config(['services.adzuna.app_id' => 'id', 'services.adzuna.app_key' => 'key']);
        $ad = fn ($id, $city) => ['id' => $id, 'title' => 'Aquatic Ecologist Graduate', 'company' => ['display_name' => 'APEM'], 'location' => ['display_name' => $city], 'redirect_url' => "https://www.adzuna.co.uk/jobs/land/ad/{$id}"];
        Http::fake(['api.adzuna.com/*' => Http::response(['results' => [$ad('x1', 'Leeds'), $ad('x2', 'Nottingham'), $ad('x3', 'Leeds')]])]);

        $this->artisan('vitafolio:fetch-jobs')->assertSuccessful();

        $this->assertSame(1, JobListing::where('title', 'Aquatic Ecologist Graduate')->count());
        $this->assertSame('Leeds; Nottingham', JobListing::where('title', 'Aquatic Ecologist Graduate')->value('location'));
        $this->assertSame('safran', JobFetcher::employerKey('SAFRAN UK Ltd'));
        $this->assertSame(JobFetcher::employerKey('Safran'), JobFetcher::employerKey('SAFRAN UK'));
    }

    public function test_a_full_fetch_reads_and_saves_in_a_handful_of_queries(): void
    {
        config(['services.adzuna.app_id' => 'id', 'services.adzuna.app_key' => 'key']);
        $ad = fn ($n) => ['id' => "q{$n}", 'title' => "Graduate Engineer {$n}", 'company' => ['display_name' => "Firm {$n}"], 'location' => ['display_name' => 'Leeds'], 'redirect_url' => "https://www.adzuna.co.uk/jobs/land/ad/q{$n}"];
        Http::fake(['api.adzuna.com/*' => Http::response(['results' => array_map($ad, range(1, 50))])]);
        DB::enableQueryLog();

        $this->artisan('vitafolio:fetch-jobs')->assertSuccessful();

        // dozens of searches and a thousand results, saved without a query per listing
        $this->assertLessThan(20, count(DB::getQueryLog()));
        $this->assertSame(50, JobListing::where('source', 'adzuna')->count());
    }

    public function test_a_refused_adzuna_search_is_retried_and_a_single_failure_raises_no_alert(): void
    {
        config(['services.adzuna.app_id' => 'id', 'services.adzuna.app_key' => 'key']);
        Http::fake(['api.adzuna.com/*' => Http::sequence()
            ->push('rate limited', 429)
            ->whenEmpty(Http::response(['results' => [['id' => 'r1', 'title' => 'Graduate Accountant', 'company' => ['display_name' => 'Firm'], 'location' => ['display_name' => 'Leeds'], 'redirect_url' => 'https://www.adzuna.co.uk/jobs/land/ad/r1']]]))]);
        Exceptions::fake();

        $this->artisan('vitafolio:fetch-jobs')->assertSuccessful();

        Exceptions::assertNothingReported();
        $this->assertDatabaseHas('job_listings', ['title' => 'Graduate Accountant']);
    }

    public function test_a_failing_board_never_stops_the_other(): void
    {
        config(['services.adzuna.app_id' => 'id', 'services.adzuna.app_key' => 'key', 'services.reed.key' => 'key']);
        Http::fake([
            'api.adzuna.com/*' => Http::response('down', 500),
            'www.reed.co.uk/*' => Http::response(['results' => [['jobId' => 5, 'jobTitle' => 'Graduate Engineer', 'jobUrl' => 'https://www.reed.co.uk/jobs/5']]]),
        ]);

        $this->artisan('vitafolio:fetch-jobs')->assertSuccessful();
        $this->assertDatabaseHas('job_listings', ['source' => 'reed', 'title' => 'Graduate Engineer']);
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

    public function test_the_field_filter_and_the_search_box_narrow_the_list(): void
    {
        JobListing::factory()->create(['title' => 'Trainee Solicitor 2027', 'company' => 'Clifford Chance', 'sector' => 'law', 'description' => 'Python skills welcome']);
        JobListing::factory()->create(['title' => 'Graduate Nurse', 'company' => 'Leeds Hospitals', 'sector' => 'health']);

        $this->get(route('jobs', ['sector' => 'law']))->assertSee('Trainee Solicitor 2027')->assertDontSee('Graduate Nurse');
        $this->get(route('jobs', ['q' => 'clifford']))->assertSee('Trainee Solicitor 2027')->assertDontSee('Graduate Nurse');
        // the search box matches titles and employers, never an advert's text
        $this->get(route('jobs', ['q' => 'python']))->assertDontSee('Trainee Solicitor 2027');
        $this->get(route('jobs', ['sector' => 'nonsense']))->assertSee('Graduate Nurse')->assertSee('Trainee Solicitor 2027');
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
