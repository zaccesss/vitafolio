<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
}
