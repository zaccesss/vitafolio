<?php

namespace Tests\Feature;

use App\Mail\ReportReceived;
use App\Models\Cv;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BackgroundJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cron_needs_its_token(): void
    {
        $this->post(route('cron'))->assertNotFound();

        config(['vitafolio.cron_token' => 'a-long-shared-secret']);
        $this->post(route('cron'))->assertForbidden();
        $this->withToken('wrong')->post(route('cron'))->assertForbidden();

        $user = User::factory()->create();
        DB::table('handle_history')->insert(['user_id' => $user->id, 'handle' => 'old-one', 'released_at' => now()->subDay()]);
        $this->withToken('a-long-shared-secret')->post(route('cron'))->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseCount('handle_history', 0);
    }

    public function test_search_engines_hear_about_public_changes_only(): void
    {
        config(['vitafolio.indexnow_key' => 'abcd1234abcd1234']);
        $this->app['env'] = 'production';
        Http::fake();

        $private = Cv::factory()->visibility('private')->create();
        Http::assertNothingSent();

        $public = Cv::factory()->create();
        Http::assertSent(fn ($request) => $request->url() === 'https://api.indexnow.org/indexnow'
            && in_array(route('cv.show', $public), $request['urlList'], true));

        $this->get(route('indexnow.key'))->assertOk()->assertSee('abcd1234abcd1234');
    }

    public function test_admins_are_emailed_about_reports(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $cv = Cv::factory()->create();

        $this->post(route('cv.report', $cv), ['reason' => 'spam'])->assertRedirect();
        Mail::assertSent(ReportReceived::class, fn ($mail) => $mail->hasTo($admin->email));

        // the same visitor reporting again does not send a second email
        $this->post(route('cv.report', $cv), ['reason' => 'spam']);
        Mail::assertSent(ReportReceived::class, 1);
    }
}
