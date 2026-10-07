<?php

namespace Tests\Feature;

use App\Listeners\SendSecurityNotice;
use App\Mail\SecurityNotice;
use App\Models\User;
use App\Support\Locales;
use App\Support\TranslationKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocalisationTest extends TestCase
{
    use RefreshDatabase;

    public static function languages(): array
    {
        return collect(Locales::ALL)->map(fn ($language, $code) => [$code])->all();
    }

    public function test_english_is_used_when_the_browser_asks_for_nothing_supported(): void
    {
        $this->get(route('features'), ['Accept-Language' => 'de-DE,de;q=0.9'])->assertOk()
            ->assertSee('<html lang="en-GB" dir="ltr"', false);
    }

    #[DataProvider('languages')]
    public function test_the_browser_language_is_used_on_a_first_visit(string $code): void
    {
        $header = str_replace('_', '-', $code);
        $response = $this->get(route('features'), ['Accept-Language' => $header])->assertOk();

        $response->assertSee('<html lang="'.Locales::html($code).'" dir="'.Locales::dir($code).'"', false);
        $response->assertSee(__('Everything a CV needs. Nothing it does not.', [], $code));
        $response->assertHeader('Vary');
        $this->assertStringContainsString('Accept-Language', (string) $response->headers->get('Vary'));
    }

    public function test_arabic_and_urdu_pages_read_right_to_left(): void
    {
        foreach (['ar', 'ur'] as $code) {
            $this->get(route('home'), ['Accept-Language' => $code])->assertOk()
                ->assertSee('<html lang="'.$code.'" dir="rtl"', false);
        }
        foreach (['en', 'es', 'fr', 'pt-BR', 'zh-CN'] as $code) {
            $this->get(route('home'), ['Accept-Language' => $code])->assertOk()->assertSee('dir="ltr"', false)->assertDontSee('dir="rtl" data-theme', false);
        }
    }

    public function test_error_pages_follow_the_browser_language(): void
    {
        $this->get('/no-such-page', ['Accept-Language' => 'ar'])->assertNotFound()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('Page not found', [], 'ar'));
    }

    public function test_every_language_is_named_in_its_own_script_in_the_menu(): void
    {
        $response = $this->get(route('home'))->assertOk();
        foreach (Locales::ALL as $code => $language) {
            $response->assertSee('name="locale" value="'.$code.'" lang="'.$language['html'].'"', false);
            $response->assertSee($language['name']);
        }
    }

    public function test_a_guest_choice_is_kept_in_a_cookie(): void
    {
        $this->from(route('features'))->post(route('locale'), ['locale' => 'fr'])
            ->assertRedirect(route('features'))
            ->assertCookie(Locales::COOKIE, 'fr');

        // the cookie wins over what the browser asks for
        $this->withCookie(Locales::COOKIE, 'fr')->get(route('features'), ['Accept-Language' => 'es'])
            ->assertSee('<html lang="fr" dir="ltr"', false);
    }

    public function test_an_unknown_language_is_refused(): void
    {
        $this->post(route('locale'), ['locale' => 'xx'])->assertSessionHasErrors('locale');
        $this->post(route('locale'), ['locale' => '../../en'])->assertSessionHasErrors('locale');
    }

    public function test_the_menu_never_sends_people_to_another_site(): void
    {
        $this->from('https://evil.example/phish')->post(route('locale'), ['locale' => 'es'])->assertRedirect(route('home'));
    }

    public function test_a_signed_in_choice_is_saved_on_the_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('locale'), ['locale' => 'ur'])->assertRedirect();
        $this->assertSame('ur', $user->fresh()->locale);

        // a new device with no cookie still gets the saved language
        $this->actingAs($user->fresh())->get(route('dashboard'), ['Accept-Language' => 'en-GB'])
            ->assertSee('<html lang="ur" dir="rtl"', false);
    }

    public function test_a_new_account_keeps_the_language_it_was_made_in(): void
    {
        // the breached-password check calls out over the network, so it answers "not found" here
        Http::fake();
        $this->post(route('register'), [
            'name' => 'Ana Lima', 'email' => 'ana@example.com', 'password' => 'a-long-pass-123', 'password_confirmation' => 'a-long-pass-123', 'terms' => '1',
        ], ['Accept-Language' => 'pt-BR'])->assertSessionHasNoErrors();
        $user = User::where('email', 'ana@example.com')->firstOrFail();
        // the first cv starts in the same language
        $this->assertSame('pt_BR', $user->cvs()->firstOrFail()->language);

        $this->assertSame('pt_BR', User::where('email', 'ana@example.com')->value('locale'));
    }

    public function test_security_emails_arrive_in_the_owner_language(): void
    {
        Mail::fake();
        $user = User::factory()->create(['locale' => 'es']);

        SendSecurityNotice::send($user, 'Password changed', 'Your password was changed from your settings. Every other device has been signed out.');

        Mail::assertSent(SecurityNotice::class, function (SecurityNotice $mail) use ($user) {
            $mail->assertSeeInText(__('If this was you, there is nothing to do.', [], 'es'));

            return $mail->hasTo($user->email) && $mail->locale === 'es';
        });
    }

    public function test_legal_pages_say_the_english_version_applies(): void
    {
        $this->get(route('privacy'), ['Accept-Language' => 'fr'])->assertOk()
            ->assertSee(__('This page is only available in English. The English version is the one that applies.', [], 'fr'))
            ->assertSee('lang="en-GB" dir="ltr"', false);
        $this->get(route('privacy'), ['Accept-Language' => 'en'])->assertOk()
            ->assertDontSee('This page is only available in English.');
    }

    public function test_the_scripts_get_their_words_in_the_page_language(): void
    {
        $this->get(route('home'), ['Accept-Language' => 'zh-CN'])->assertOk()
            ->assertSee('<script type="application/json" id="i18n-strings">', false)
            ->assertSee(json_encode(__('Copy link', [], 'zh_CN'), JSON_UNESCAPED_UNICODE), false);
    }

    public function test_the_english_source_file_matches_the_code(): void
    {
        $english = json_decode((string) file_get_contents(lang_path('en.json')), true);
        $missing = array_diff(TranslationKeys::all(), array_keys($english));
        $this->assertSame([], array_values($missing), 'run php artisan vitafolio:translations to add new strings to lang/en.json');
        foreach ($english as $key => $value) {
            $this->assertSame($key, $value, 'english values match their keys');
        }
    }

    public function test_every_script_string_is_sent_to_the_browser(): void
    {
        $this->assertSame([], array_values(array_diff(TranslationKeys::fromScripts(), Locales::SCRIPT_STRINGS)));
    }

    #[DataProvider('languages')]
    public function test_every_key_is_translated(string $code): void
    {
        if ($code === Locales::DEFAULT) {
            $this->assertFileExists(lang_path('en.json'));

            return;
        }
        $english = json_decode((string) file_get_contents(lang_path('en.json')), true);
        $path = lang_path($code.'.json');
        $this->assertFileExists($path);
        $translated = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($translated, $code.'.json is valid json');

        $this->assertSame([], array_values(array_diff(array_keys($english), array_keys($translated))), "keys missing from $code.json");
        $this->assertSame([], array_values(array_diff(array_keys($translated), array_keys($english))), "keys in $code.json that english no longer has");

        foreach ($english as $key => $_) {
            $value = $translated[$key];
            $this->assertIsString($value);
            $this->assertNotSame('', trim($value), "an empty translation in $code.json for: $key");
            preg_match_all('/:[a-z_]+/', $key, $wanted);
            preg_match_all('/:[a-z_]+/', $value, $found);
            $this->assertEqualsCanonicalizing(array_values(array_unique($wanted[0])), array_values(array_unique($found[0])), "placeholders differ in $code.json for: $key");
            preg_match_all('#</?[a-z]+\b[^>]*>#', $key, $tagsWanted);
            preg_match_all('#</?[a-z]+\b[^>]*>#', $value, $tagsFound);
            $this->assertEqualsCanonicalizing($tagsWanted[0], $tagsFound[0], "markup differs in $code.json for: $key");
            $this->assertSame(substr_count($key, '|'), substr_count($value, '|'), "plural forms differ in $code.json for: $key");
        }

        foreach (glob(lang_path('en/*.php')) as $file) {
            $group = basename($file);
            $this->assertFileExists(lang_path("$code/$group"));
            $this->assertSame(
                $this->keyPaths(require $file),
                $this->keyPaths(require lang_path("$code/$group")),
                "lang/$code/$group has the same keys as english",
            );
        }
    }

    public function test_the_language_menu_works_with_routes_cached(): void
    {
        $this->artisan('route:cache')->assertSuccessful();
        // a fresh app starts with an empty in-memory database, so it is migrated again
        $this->refreshApplication();
        // the fresh app also forgets the base test's switches, so pages render without a build again
        $this->withoutVite();
        $this->artisan('migrate');
        try {
            $user = User::factory()->create();
            $this->actingAs($user)->post(route('locale'), ['locale' => 'ar'])->assertRedirect();
            $this->assertSame('ar', $user->fresh()->locale);
            $this->get(route('dashboard'))->assertOk()->assertSee('<html lang="ar" dir="rtl"', false);
        } finally {
            $this->artisan('route:clear');
        }
    }

    /** every key path in a nested lang array, sorted, so two files can be compared */
    private function keyPaths(array $lines, string $prefix = ''): array
    {
        $paths = [];
        foreach ($lines as $key => $value) {
            $paths[] = $prefix.$key;
            if (is_array($value)) {
                array_push($paths, ...$this->keyPaths($value, $prefix.$key.'.'));
            }
        }
        sort($paths);

        return $paths;
    }
}
