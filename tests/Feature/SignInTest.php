<?php

namespace Tests\Feature;

use App\Listeners\SendSecurityNotice;
use App\Mail\SecurityNotice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Tests\TestCase;

class SignInTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_password_signs_in(): void
    {
        $user = User::factory()->create();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'a-long-pass-123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_suspended_account_is_refused_with_the_usual_message(): void
    {
        $user = User::factory()->suspended()->create();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'a-long-pass-123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_suspended_account_cannot_use_a_passkey_either(): void
    {
        $passkey = new Passkey;
        $passkey->setRelation('user', User::factory()->suspended()->create());
        $this->assertFalse(Passkeys::allowsLogin(Request::create('/'), $passkey));

        $passkey->setRelation('user', User::factory()->create());
        $this->assertTrue(Passkeys::allowsLogin(Request::create('/'), $passkey));
    }

    public function test_the_login_page_offers_a_passkey(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('passkeyLogin')->assertSee('autocomplete="username webauthn"', false);
    }

    private function establishedUser(): User
    {
        return User::factory()->create(['created_at' => now()->subDay()]);
    }

    private function signIn(User $user): TestResponse
    {
        return $this->post(route('login'), ['email' => $user->email, 'password' => 'a-long-pass-123']);
    }

    public function test_a_browser_the_account_has_not_used_gets_a_notice(): void
    {
        Mail::fake();
        $user = $this->establishedUser();

        $this->signIn($user)->assertCookie(SendSecurityNotice::DEVICE_COOKIE);

        Mail::assertSent(SecurityNotice::class, 1);
    }

    public function test_a_browser_that_signed_in_before_gets_no_notice_after_its_sessions_are_gone(): void
    {
        Mail::fake();
        $user = $this->establishedUser();
        DB::table('sessions')->delete();

        $this->withCookie(SendSecurityNotice::DEVICE_COOKIE, (string) json_encode([$user->id]));
        $this->signIn($user);

        $this->assertAuthenticatedAs($user);
        Mail::assertNothingSent();
    }

    public function test_another_accounts_device_cookie_does_not_hide_a_new_sign_in(): void
    {
        Mail::fake();
        $user = $this->establishedUser();

        $this->withCookie(SendSecurityNotice::DEVICE_COOKIE, (string) json_encode([$user->id + 1]));
        $this->signIn($user);

        Mail::assertSent(SecurityNotice::class, 1);
    }

    public function test_a_remember_me_cookie_restoring_a_session_sends_no_notice(): void
    {
        Mail::fake();
        $user = $this->establishedUser();
        $user->forceFill(['remember_token' => 'a-remember-token'])->save();
        $recaller = Auth::guard('web')->getRecallerName();

        $this->withCookie($recaller, $user->id.'|a-remember-token|'.$user->getAuthPassword())
            ->get(route('dashboard'))
            ->assertCookie(SendSecurityNotice::DEVICE_COOKIE);

        $this->assertAuthenticatedAs($user);
        Mail::assertNothingSent();
    }

    public function test_a_brand_new_account_gets_no_notice_for_its_first_sign_in(): void
    {
        Mail::fake();

        $this->signIn(User::factory()->create());

        Mail::assertNothingSent();
    }
}
