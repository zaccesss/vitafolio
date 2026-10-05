<?php

namespace Tests\Feature;

use App\Mail\SecurityNotice;
use App\Models\Cv;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AccountSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function googleReturns(string $email): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        $remote = (new SocialiteUser)->setRaw(['email_verified' => true])->map(['id' => '999', 'name' => 'Real Owner', 'email' => $email, 'avatar' => null]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($remote);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_a_verified_sign_in_evicts_whoever_pre_registered_the_address(): void
    {
        // an attacker registers the victim's address first without verifying it, then connects their own github
        $squatter = User::factory()->unverified()->create(['email' => 'victim@example.test', 'password' => Hash::make('attacker-pass-123')]);
        $squatter->forceFill(['two_factor_secret' => encrypt('x'), 'two_factor_confirmed_at' => now()])->save();
        $squatter->socialAccounts()->create(['provider' => 'github', 'provider_id' => 'g1']);

        $this->googleReturns('victim@example.test');
        $this->get(route('social.callback', 'google'))->assertRedirect(route('dashboard'));

        $account = $squatter->fresh();
        $this->assertAuthenticatedAs($account);
        $this->assertTrue($account->hasVerifiedEmail());
        $this->assertFalse($account->has_password);
        $this->assertFalse(Hash::check('attacker-pass-123', $account->password));
        $this->assertNull($account->two_factor_confirmed_at);
        $this->assertSame(['google'], $account->socialAccounts()->pluck('provider')->all());
    }

    public function test_an_unverified_account_cannot_set_up_sign_in_methods(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $this->get(route('settings.passkeys'))->assertRedirect(route('verification.notice'));
        $this->post(route('two-factor.enable'))->assertRedirect(route('verification.notice'));
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->get(route('home'))->assertOk();
    }

    public function test_a_suspended_account_is_signed_out_on_its_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->forceFill(['suspended_at' => now()])->save();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_suspending_rotates_the_remember_token(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $before = $user->remember_token;

        $this->actingAs($admin)->post(route('admin.suspend', $user))->assertRedirect();
        $this->assertNotSame($before, $user->fresh()->remember_token);
    }

    public function test_forgot_password_answers_the_same_for_unknown_addresses(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $known = $this->post(route('password.email'), ['email' => $user->email]);
        $unknown = $this->post(route('password.email'), ['email' => 'nobody@example.test']);

        $this->assertSame(session()->get('status'), $unknown->getSession()->get('status'));
        $known->assertSessionHas('status');
        $unknown->assertSessionHas('status', trans('passwords.sent'));
    }

    public function test_changing_the_email_needs_the_password(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->put(route('user-profile-information.update'), ['name' => $user->name, 'email' => 'new@example.test'])
            ->assertSessionHasErrorsIn('updateProfileInformation', 'current_password');
        $this->assertSame($user->email, $user->fresh()->email);

        $this->put(route('user-profile-information.update'), ['name' => $user->name, 'email' => 'new@example.test', 'current_password' => 'a-long-pass-123'])
            ->assertSessionHasNoErrors();
        $this->assertSame('new@example.test', $user->fresh()->email);
    }

    public function test_a_photo_needs_the_version_from_a_visible_page(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['avatar' => 'x', 'avatar_type' => 'image/jpeg', 'avatar_version' => 'abc'])->save();

        $this->get('/avatar/'.$user->handle)->assertNotFound();
        $this->get('/avatar/'.$user->handle.'?v=wrong')->assertNotFound();
        $this->get('/avatar/'.$user->handle.'?v=abc')->assertOk();
    }

    public function test_an_old_handle_does_not_reveal_a_private_profile(): void
    {
        $user = User::factory()->create(['handle' => 'old-name']);
        $this->actingAs($user)->put(route('profile.handle'), ['handle' => 'secret-name']);
        $user->forceFill(['profile_visibility' => 'private'])->save();
        auth()->logout();

        $this->get('/@old-name')->assertNotFound();
    }

    public function test_a_password_change_ends_other_devices_and_emails_the_owner(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        \DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time(), 'user_agent' => 'Mozilla/5.0 (X11; Linux) Firefox/140', 'ip_address' => '203.0.113.9']);
        $this->actingAs($user);

        $this->put(route('user-password.update'), ['current_password' => 'a-long-pass-123', 'password' => 'another-pass-456', 'password_confirmation' => 'another-pass-456'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
        Mail::assertSent(SecurityNotice::class, fn ($mail) => $mail->hasTo($user->email) && $mail->headline === 'Password changed');
    }

    public function test_the_change_password_address_points_at_security_settings(): void
    {
        $this->get('/.well-known/change-password')->assertRedirect('/settings/security');
    }

    public function test_signed_in_devices_are_listed_and_can_be_ended(): void
    {
        $user = User::factory()->create();
        \DB::table('sessions')->insert(['id' => 'phone-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time(), 'user_agent' => 'Mozilla/5.0 (iPhone) Safari/605', 'ip_address' => '203.0.113.9']);

        $this->actingAs($user)->get(route('settings.sessions'))->assertOk()->assertSee('Safari on iPhone');
        $this->withSession(['auth.password_confirmed_at' => time()])->delete(route('account.sessions.end', 'phone-session'))->assertRedirect(route('settings.sessions'));
        $this->assertDatabaseMissing('sessions', ['id' => 'phone-session']);
    }

    public function test_the_health_check_answers(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_private_assets_are_not_publicly_cacheable(): void
    {
        $cv = Cv::factory()->visibility('private')->create();
        $this->actingAs($cv->user);

        $this->assertStringContainsString('no-store', $this->get(route('cv.qr', $cv))->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', $this->get(route('cv.qr', Cv::factory()->create()))->headers->get('Cache-Control'));
    }
}
