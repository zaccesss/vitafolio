<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['google', 'microsoft'] as $driver) {
            config(["services.$driver.client_id" => 'id', "services.$driver.client_secret" => 'secret']);
        }
    }

    private function returns(string $driver, string $email, bool $verified = true, string $id = '123'): void
    {
        $remote = (new SocialiteUser)->setRaw(['email_verified' => $verified])->map([
            'id' => $id, 'name' => 'Alex Morgan', 'email' => $email, 'avatar' => 'https://lh3.googleusercontent.com/a/photo',
        ]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($remote);
        Socialite::shouldReceive('driver')->with($driver)->andReturn($provider);
    }

    public function test_a_new_google_user_gets_a_verified_account_and_a_first_cv(): void
    {
        $this->returns('google', 'alex@example.test');
        $this->get(route('social.callback', 'google'))->assertRedirect(route('dashboard'));

        $user = User::where('email', 'alex@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertFalse($user->has_password);
        $this->assertSame(1, $user->cvs()->count());
    }

    public function test_a_verified_email_joins_the_existing_account(): void
    {
        $existing = User::factory()->create(['email' => 'alex@example.test']);
        $this->returns('google', 'alex@example.test');

        $this->get(route('social.callback', 'google'))->assertRedirect();
        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::count());
        $this->assertTrue(SocialAccount::where('user_id', $existing->id)->where('provider', 'google')->exists());
    }

    public function test_microsoft_never_takes_over_an_account_by_email(): void
    {
        User::factory()->create(['email' => 'alex@example.test']);
        $this->returns('microsoft', 'alex@example.test');

        $this->get(route('social.callback', 'microsoft'))->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_an_unverified_google_email_does_not_join_an_account(): void
    {
        User::factory()->create(['email' => 'alex@example.test']);
        $this->returns('google', 'alex@example.test', verified: false);

        $this->get(route('social.callback', 'google'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_two_factor_still_applies(): void
    {
        $user = User::factory()->create(['email' => 'alex@example.test']);
        $user->forceFill(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()])->save();
        $this->returns('google', 'alex@example.test');

        $this->get(route('social.callback', 'google'))->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    }

    public function test_a_suspended_account_cannot_sign_in(): void
    {
        $user = User::factory()->suspended()->create(['email' => 'alex@example.test']);
        $this->returns('google', 'alex@example.test');

        $this->get(route('social.callback', 'google'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_the_last_way_to_sign_in_cannot_be_disconnected(): void
    {
        $this->returns('google', 'alex@example.test');
        $this->get(route('social.callback', 'google'));

        $this->delete(route('social.disconnect', 'google'))->assertSessionHas('error');
        $this->assertDatabaseCount('social_accounts', 1);
    }

    public function test_switched_off_providers_do_not_exist(): void
    {
        $this->get(route('social.redirect', 'linkedin'))->assertNotFound();
        $this->get(route('social.redirect', 'not-a-provider'))->assertNotFound();
    }
}
