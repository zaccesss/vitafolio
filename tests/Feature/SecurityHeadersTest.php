<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_carries_a_strict_policy(): void
    {
        $response = $this->get(route('home'))->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
        $this->assertStringNotContainsString('wasm-unsafe-eval', $csp);
        $this->assertStringNotContainsString('blob:', $csp);
    }

    public function test_only_the_latex_page_may_run_webassembly(): void
    {
        $cv = Cv::factory()->create();
        $csp = $this->actingAs($cv->user)->get(route('cvs.latex', $cv))->assertOk()->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("'wasm-unsafe-eval'", $csp);
    }

    public function test_only_the_profile_page_may_preview_local_images(): void
    {
        $csp = $this->actingAs(User::factory()->create())->get(route('profile.edit'))->assertOk()->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression('/img-src [^;]*blob:/', $csp);
    }
}
