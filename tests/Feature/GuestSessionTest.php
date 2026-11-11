<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GuestSessionTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 15_0) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.0 Safari/605.1.15';

    private const GOOGLEBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'database']);
        $this->withHeader('User-Agent', self::BROWSER);
    }

    /** every real request starts with nothing in memory, so the session kept from the last one is dropped */
    private function nextRequest(): static
    {
        $this->app['session']->forgetDrivers();

        return $this;
    }

    /** the browser sending back the session cookie it was given */
    private function withSessionCookie(): static
    {
        return $this->nextRequest()->withCookie(config('session.cookie'), (string) DB::table('sessions')->value('id'));
    }

    private function tokenIn(TestResponse $response): string
    {
        $this->assertSame(1, preg_match('/name="_token" value="([^"]+)"/', (string) $response->getContent(), $found));

        return $found[1];
    }

    /** the form token check is skipped while tests run, so these tests pose as a normal environment */
    private function enforceFormTokens(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
    }

    public function test_a_signed_out_visitor_reading_public_pages_writes_no_session_row(): void
    {
        foreach (['home', 'about', 'features', 'help', 'privacy'] as $page) {
            $response = $this->nextRequest()->get(route($page))->assertOk();
            // no token in the page, so nothing posted back later could depend on this session
            $response->assertDontSee('name="_token"', false)->assertDontSee('name="csrf-token"', false);
        }

        $this->assertDatabaseCount('sessions', 0);
    }

    public function test_the_language_menu_still_lists_every_language_for_a_visitor_with_no_session(): void
    {
        $response = $this->get(route('about'))->assertOk();

        $response->assertSee('method="GET" action="'.route('locale.choose').'"', false);
        foreach (Locales::ALL as $code => $language) {
            $response->assertSee('name="locale" value="'.$code.'" lang="'.$language['html'].'"', false);
        }
    }

    public function test_a_visitor_with_no_session_changes_language_in_one_press(): void
    {
        $this->from(route('about'))->get(route('locale.choose', ['locale' => 'fr']))
            ->assertRedirect(route('about'))
            ->assertCookie(Locales::COOKIE, 'fr');
    }

    public function test_an_unknown_language_changes_nothing(): void
    {
        $this->from(route('about'))->get(route('locale.choose', ['locale' => 'xx']))->assertOk()
            ->assertCookieMissing(Locales::COOKIE);
    }

    public function test_a_signed_in_person_is_never_switched_by_a_link(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)->get(route('locale.choose', ['locale' => 'fr']))->assertOk()
            ->assertCookieMissing(Locales::COOKIE);
        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_a_visitor_with_no_session_can_still_use_the_language_page(): void
    {
        $this->enforceFormTokens();

        $page = $this->from(route('about'))->get(route('locale.choose'))->assertOk();
        $page->assertSee('name="from" value="'.route('about').'"', false);
        // the page that prints the token is the one that stores the session
        $this->assertDatabaseCount('sessions', 1);

        $this->withSessionCookie()->post(route('locale'), ['_token' => $this->tokenIn($page), 'locale' => 'fr', 'from' => route('about')])
            ->assertRedirect(route('about'))
            ->assertCookie(Locales::COOKIE, 'fr');
    }

    public function test_the_language_page_never_returns_to_another_site(): void
    {
        $this->from('https://evil.example/phish')->get(route('locale.choose'))->assertOk()
            ->assertSee('name="from" value="'.route('home').'"', false);
        $this->nextRequest()->post(route('locale'), ['locale' => 'es', 'from' => 'https://evil.example/phish'])->assertRedirect(route('home'));
    }

    public function test_a_guest_can_load_the_sign_in_form_and_submit_it(): void
    {
        $this->enforceFormTokens();
        $user = User::factory()->create();

        $form = $this->get(route('login'))->assertOk();
        $token = $this->tokenIn($form);
        $this->assertDatabaseCount('sessions', 1);
        $guestSession = DB::table('sessions')->value('id');

        $credentials = ['email' => $user->email, 'password' => 'a-long-pass-123'];

        // the check is real here: without the token the form is refused
        $this->withSessionCookie()->post(route('login'), $credentials)->assertStatus(419);
        $this->withSessionCookie()->post(route('login'), ['_token' => 'not-the-token'] + $credentials)->assertStatus(419);
        $this->assertGuest();

        $this->withSessionCookie()->post(route('login'), ['_token' => $token] + $credentials)->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // sign-in moves the session to a new id, which is stored against the account
        $this->assertDatabaseCount('sessions', 1);
        $this->assertDatabaseMissing('sessions', ['id' => $guestSession]);
        $this->assertDatabaseHas('sessions', ['user_id' => $user->id]);
    }

    public function test_a_form_posted_without_a_stored_session_is_refused(): void
    {
        $this->enforceFormTokens();
        $user = User::factory()->create();

        // a token from a page that was never stored cannot be replayed
        $this->get(route('about'))->assertOk();
        $token = $this->app['session']->token();
        $this->nextRequest()->post(route('login'), ['_token' => $token, 'email' => $user->email, 'password' => 'a-long-pass-123'])->assertStatus(419);
        $this->assertGuest();
    }

    public function test_a_flash_message_survives_a_guest_redirect(): void
    {
        $this->from(route('about'))->post(route('locale'), ['locale' => 'en'])->assertRedirect(route('about'));
        $this->assertDatabaseCount('sessions', 1);

        $message = __('The language is now :name.', ['name' => Locales::ALL['en']['name']]);
        $this->withSessionCookie()->get(route('about'))->assertOk()->assertSee($message);

        // shown once: the page after that no longer has it
        $this->withSessionCookie()->get(route('about'))->assertOk()->assertDontSee($message);
    }

    public function test_a_guest_sent_to_sign_in_keeps_the_page_they_wanted(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $this->assertDatabaseCount('sessions', 1);
        $this->withSessionCookie()->get(route('login'))->assertOk()->assertSessionHas('url.intended', route('dashboard'));
    }

    public function test_a_signed_in_user_still_has_a_session_row(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('about'))->assertOk()->assertSee('name="_token"', false);

        $this->assertDatabaseCount('sessions', 1);
        $this->assertDatabaseHas('sessions', ['user_id' => $user->id]);
    }

    public function test_a_visitor_with_a_stored_session_keeps_the_one_step_language_menu(): void
    {
        $this->get(route('login'))->assertOk();

        $this->withSessionCookie()->get(route('about'))->assertOk()
            ->assertSee('method="POST" action="'.route('locale').'"', false)
            ->assertSee('name="_token"', false);
        $this->assertDatabaseCount('sessions', 1);
    }

    public function test_crawlers_still_write_nothing(): void
    {
        foreach (['home', 'login', 'contact.show'] as $page) {
            $this->nextRequest()->withHeader('User-Agent', self::GOOGLEBOT)->get(route($page))->assertOk();
        }

        $this->assertDatabaseCount('sessions', 0);
    }
}
