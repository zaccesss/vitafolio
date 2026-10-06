<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PrivateDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_account_starts_with_a_private_cv_and_an_unlisted_profile(): void
    {
        // the breached-password check calls out over the network, so it answers "not found" here
        Http::fake();
        $this->post(route('register'), [
            'name' => 'New Person', 'email' => 'new@example.test',
            'password' => 'a-long-pass-123', 'password_confirmation' => 'a-long-pass-123', 'terms' => '1',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertSame('unlisted', $user->profile_visibility);
        $this->assertSame('private', $user->cvs()->firstOrFail()->visibility);
    }

    public function test_publish_makes_a_cv_public_for_its_owner_only(): void
    {
        $cv = Cv::factory()->visibility('private')->create();

        $this->actingAs(User::factory()->create())->put(route('cvs.publish', $cv))->assertForbidden();
        $this->assertSame('private', $cv->fresh()->visibility);

        $this->actingAs($cv->user)->put(route('cvs.publish', $cv))->assertRedirect();
        $this->assertSame('public', $cv->fresh()->visibility);
    }
}
