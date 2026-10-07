<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

// the security check must stop a public form even when the token field is left out of the request altogether
class TurnstileRequiredTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['vitafolio.turnstile.site_key' => 'site', 'vitafolio.turnstile.secret' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
        Mail::fake();
    }

    public function test_the_contact_form_needs_the_security_check(): void
    {
        $this->post(route('contact'), [
            'sender_name' => 'Visitor',
            'sender_email' => 'visitor@example.com',
            'message' => 'Hello there, this is a message.',
        ])->assertSessionHasErrors(['cf-turnstile-response']);

        Mail::assertNothingSent();
    }

    public function test_a_cv_report_needs_the_security_check(): void
    {
        $cv = Cv::factory()->create();

        $this->post(route('cv.report', $cv), ['reason' => 'spam'])
            ->assertSessionHasErrors(['cf-turnstile-response']);

        $this->assertSame(0, Report::count());
    }

    public function test_a_passed_check_still_lets_the_contact_form_through(): void
    {
        $this->post(route('contact'), [
            'sender_name' => 'Visitor',
            'sender_email' => 'visitor@example.com',
            'message' => 'Hello there, this is a message.',
            'cf-turnstile-response' => 'token',
        ])->assertSessionHasNoErrors();
    }
}
