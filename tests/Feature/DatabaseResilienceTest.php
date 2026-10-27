<?php

namespace Tests\Feature;

use App\Http\Controllers\CronController;
use App\Http\Middleware\StatelessForCrawlers;
use App\Models\JobListing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseResilienceTest extends TestCase
{
    use RefreshDatabase;

    private const GOOGLEBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    private const BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 15_0) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Safari/605.1.15';

    protected function tearDown(): void
    {
        // maintenance mode and the marker are real files, so a failed assertion must not leave them behind
        $this->app->maintenanceMode()->deactivate();
        @unlink(storage_path(CronController::UNREACHABLE_MARKER));
        // the test database is rolled back on the default connection, so it has to be the working one again
        config(['database.default' => $this->workingConnection]);
        parent::tearDown();
    }

    private string $workingConnection = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->workingConnection = (string) config('database.default');
    }

    /** points the app at a database that cannot be opened, the way a refused connection looks to it */
    private function breakTheDatabase(): void
    {
        config([
            'database.connections.unreachable' => ['driver' => 'sqlite', 'database' => storage_path('missing-directory/database.sqlite'), 'prefix' => ''],
            'database.default' => 'unreachable',
        ]);
        DB::purge('unreachable');
    }

    public function test_up_answers_without_a_database(): void
    {
        $this->breakTheDatabase();

        $this->get('/up')->assertOk();
    }

    public function test_up_db_reports_a_working_database_without_a_session(): void
    {
        $response = $this->get('/up/db')->assertOk()->assertExactJson(['status' => 'up']);

        $this->assertEmpty($response->headers->getCookies());
    }

    public function test_up_db_reports_an_unreachable_database(): void
    {
        $this->breakTheDatabase();

        $this->get('/up/db')->assertStatus(503)->assertExactJson(['status' => 'down']);
    }

    public function test_health_checks_answer_during_maintenance(): void
    {
        $this->app->maintenanceMode()->activate(['except' => [], 'retry' => 60, 'status' => 503]);

        $this->get('/')->assertStatus(503)->assertHeader('Retry-After', '60');
        $this->get('/up')->assertOk();
        $this->get('/up/db')->assertOk();
    }

    public function test_cron_lifts_the_start_script_maintenance_once_the_database_answers(): void
    {
        config(['vitafolio.cron_token' => 'a-long-shared-secret']);
        $this->app->maintenanceMode()->activate(['except' => [], 'retry' => 60, 'status' => 503]);
        touch(storage_path(CronController::UNREACHABLE_MARKER));
        // the nightly work itself is covered elsewhere; holding its lock keeps this test to the recovery
        $lock = Cache::lock('vitafolio-cron', 600);
        $lock->get();

        $this->withToken('a-long-shared-secret')->post(route('cron'))->assertAccepted();

        $this->assertFalse($this->app->maintenanceMode()->active());
        $this->assertFileDoesNotExist(storage_path(CronController::UNREACHABLE_MARKER));
        $lock->release();
    }

    public function test_cron_leaves_maintenance_put_up_by_hand(): void
    {
        config(['vitafolio.cron_token' => 'a-long-shared-secret']);
        $this->app->maintenanceMode()->activate(['except' => [], 'retry' => 60, 'status' => 503]);
        $lock = Cache::lock('vitafolio-cron', 600);
        $lock->get();

        $this->withToken('a-long-shared-secret')->post(route('cron'))->assertAccepted();

        $this->assertTrue($this->app->maintenanceMode()->active());
        $lock->release();
    }

    public function test_crawlers_and_site_files_never_write_a_session_row(): void
    {
        config(['session.driver' => 'database']);

        $this->withHeader('User-Agent', self::GOOGLEBOT)->get(route('home'))->assertOk()->assertCookieMissing(config('session.cookie'));
        $this->withHeader('User-Agent', self::BROWSER)->get(route('robots'))->assertOk()->assertCookieMissing(config('session.cookie'));
        $this->assertDatabaseCount('sessions', 0);

        // a person's first visit still gets a stored session, so the signed-in devices list keeps working
        $this->withHeader('User-Agent', self::BROWSER)->get(route('home'))->assertOk()->assertCookie(config('session.cookie'));
        $this->assertDatabaseCount('sessions', 1);
    }

    public function test_crawler_detection_spares_phone_models_with_bot_in_their_name(): void
    {
        $this->assertTrue(StatelessForCrawlers::isCrawler(self::GOOGLEBOT));
        $this->assertTrue(StatelessForCrawlers::isCrawler('Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'));
        $this->assertTrue(StatelessForCrawlers::isCrawler('facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'));
        $this->assertTrue(StatelessForCrawlers::isCrawler(''));
        $this->assertFalse(StatelessForCrawlers::isCrawler(self::BROWSER));
        $this->assertFalse(StatelessForCrawlers::isCrawler('Mozilla/5.0 (Linux; Android 10; CUBOT X30) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0 Mobile Safari/537.36'));
    }

    public function test_robots_keeps_jobs_indexable_but_not_its_filters_or_redirects(): void
    {
        $robots = $this->get(route('robots'))->assertOk()->getContent();

        $this->assertStringContainsString("Disallow: /jobs/*/apply\n", $robots);
        $this->assertStringContainsString("Disallow: /jobs?\n", $robots);
        $this->assertStringNotContainsString("Disallow: /jobs\n", $robots);
    }

    public function test_only_the_first_unfiltered_jobs_page_is_indexed(): void
    {
        $this->get(route('jobs'))->assertOk()->assertDontSee('<meta name="robots"', false);
        $this->get(route('jobs', ['kind' => 'internship']))->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->get(route('jobs', ['page' => 2]))->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_the_apply_redirect_counts_the_click(): void
    {
        $job = JobListing::factory()->create(['url' => 'https://example.com/apply']);

        $this->get(route('jobs.go', $job))->assertRedirect('https://example.com/apply');
        $this->assertDatabaseHas('job_clicks', ['job_listing_id' => $job->id, 'clicks' => 1]);
        $this->get('/jobs/not-a-number/apply')->assertNotFound();
    }
}
