<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApplicationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_is_private_to_signed_in_people(): void
    {
        $this->get(route('applications.index'))->assertRedirect(route('login'));
    }

    public function test_saving_a_job_keeps_a_copy_that_survives_the_listing_closing(): void
    {
        $user = User::factory()->create();
        $job = JobListing::factory()->create(['title' => 'Graduate Analyst', 'company' => 'Acme', 'closes_at' => now()->addWeek()]);

        $this->actingAs($user)->post(route('jobs.save', $job))->assertRedirect();
        $this->actingAs($user)->post(route('jobs.save', $job));
        $this->assertSame(1, Application::count());

        $this->actingAs($user)->get(route('jobs'))->assertSee(route('applications.index'), false);
        $job->delete();

        $saved = Application::sole();
        $this->assertNull($saved->job_listing_id);
        $this->assertSame(['Graduate Analyst', 'Acme', 'saved'], [$saved->title, $saved->company, $saved->status]);
        $this->actingAs($user)->get(route('applications.index'))->assertOk()->assertSee('Graduate Analyst');
    }

    public function test_adding_and_moving_an_application_records_the_applied_date(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('applications.store'), ['title' => 'Placement Year', 'company' => 'Beta', 'url' => 'https://beta.example/jobs/1'])
            ->assertRedirect(route('applications.index'));
        $app = Application::sole();

        $this->actingAs($user)->patch(route('applications.update', $app), ['status' => 'applied'])->assertRedirect();
        $this->assertSame('applied', $app->fresh()->status);
        $this->assertSame(now()->toDateString(), $app->fresh()->applied_on->toDateString());

        $this->actingAs($user)->get(route('applications.index', ['status' => 'applied']))->assertSee('Placement Year');
        $this->actingAs($user)->get(route('applications.index', ['status' => 'offer']))->assertDontSee('Placement Year');
    }

    public function test_nobody_else_can_change_or_remove_an_application(): void
    {
        $app = Application::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($other)->patch(route('applications.update', $app), ['status' => 'offer'])->assertNotFound();
        $this->actingAs($other)->delete(route('applications.destroy', $app))->assertNotFound();
        $this->actingAs($app->user)->delete(route('applications.destroy', $app))->assertRedirect();
        $this->assertModelMissing($app);
    }

    public function test_the_apply_link_counts_clicks_per_day_and_sends_people_on(): void
    {
        $job = JobListing::factory()->create(['url' => 'https://boards.greenhouse.io/acme/jobs/1']);

        $this->get(route('jobs.go', $job))->assertRedirect('https://boards.greenhouse.io/acme/jobs/1');
        $this->get(route('jobs.go', $job));

        $this->assertSame(2, (int) DB::table('job_clicks')->where('job_listing_id', $job->id)->value('clicks'));
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.index'))->assertSee($job->title);
    }

    public function test_deleting_an_account_deletes_its_applications(): void
    {
        $app = Application::factory()->create();
        $app->user->delete();
        $this->assertSame(0, Application::count());
    }
}
