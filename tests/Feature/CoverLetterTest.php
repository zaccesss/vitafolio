<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;
use ZipArchive;

class CoverLetterTest extends TestCase
{
    use RefreshDatabase;

    private function letterCv(string $visibility = 'public'): Cv
    {
        return Cv::factory()->visibility($visibility)->create([
            'letter_to' => 'Hiring team, Graduate engineer at Acme',
            'cover_letter' => "Dear hiring team,\n\nThank you for reading & considering it.",
        ]);
    }

    public function test_the_owner_saves_a_cover_letter_with_the_cv(): void
    {
        $cv = Cv::factory()->create();

        $this->actingAs($cv->user)->put(route('cvs.letter', $cv), [
            'letter_to' => 'Hiring team at Acme',
            'cover_letter' => 'Dear hiring team',
        ])->assertRedirect(route('cvs.edit', [$cv, 'letter']))->assertSessionHasNoErrors();

        $this->assertSame('Dear hiring team', $cv->fresh()->cover_letter);
        $this->assertSame('Hiring team at Acme', $cv->fresh()->letter_to);
        $this->actingAs($cv->user)->get(route('cvs.edit', [$cv, 'letter']))->assertOk()
            ->assertSee('id="f-cover_letter"', false)->assertSee('Dear hiring team');
    }

    public function test_a_letter_that_is_too_long_is_refused(): void
    {
        $cv = Cv::factory()->create();

        $this->actingAs($cv->user)->put(route('cvs.letter', $cv), [
            'letter_to' => str_repeat('a', 161),
            'cover_letter' => str_repeat('a', 6001),
        ])->assertSessionHasErrors(['letter_to', 'cover_letter']);
        $this->assertNull($cv->fresh()->cover_letter);
    }

    public function test_only_the_owner_can_change_a_letter(): void
    {
        $cv = Cv::factory()->create();

        $this->actingAs(User::factory()->create())->put(route('cvs.letter', $cv), ['cover_letter' => 'Not mine'])->assertForbidden();
        $this->assertNull($cv->fresh()->cover_letter);
    }

    public function test_the_letter_follows_the_cv_visibility(): void
    {
        // the download limit allows six a minute from one address, fewer than this test makes
        $this->withoutMiddleware(ThrottleRequests::class);
        $stranger = User::factory()->create();
        foreach (['public' => true, 'unlisted' => true, 'private' => false] as $visibility => $guestsSee) {
            $cv = $this->letterCv($visibility);
            foreach (['cv.letter', 'cv.letter.pdf', 'cv.letter.word'] as $route) {
                $this->actingAs($cv->user)->get(route($route, $cv))->assertOk();
                auth()->logout();
                $guest = $this->get(route($route, $cv));
                $guestsSee ? $guest->assertOk() : $guest->assertNotFound();
                $other = $this->actingAs($stranger)->get(route($route, $cv));
                $guestsSee ? $other->assertOk() : $other->assertNotFound();
                auth()->logout();
            }
        }
    }

    public function test_a_letter_on_a_hidden_cv_stays_hidden(): void
    {
        $cv = $this->letterCv();
        $cv->forceFill(['hidden_at' => now()])->save();

        $this->get(route('cv.letter', $cv))->assertNotFound();
        $this->get(route('cv.letter.pdf', $cv))->assertNotFound();
    }

    public function test_an_empty_letter_is_hidden_everywhere(): void
    {
        $cv = Cv::factory()->create(['cover_letter' => null]);

        $this->actingAs($cv->user)->get(route('cv.letter', $cv))->assertNotFound();
        $this->actingAs($cv->user)->get(route('cv.letter.pdf', $cv))->assertNotFound();
        $this->actingAs($cv->user)->get(route('cv.letter.word', $cv))->assertNotFound();
        $this->get(route('cv.show', $cv))->assertOk()->assertDontSee('Cover letter');
    }

    public function test_the_cv_page_links_to_its_letter(): void
    {
        $cv = $this->letterCv();

        $this->get(route('cv.show', $cv))->assertOk()
            ->assertSee('aria-label="CV and cover letter"', false)
            ->assertSee(route('cv.letter', $cv), false);
        $this->get(route('cv.letter', $cv))->assertOk()
            ->assertSee('<h1 id="cv-name"', false)
            ->assertSee('<h2 id="sec-letter"', false)
            ->assertSee('Hiring team, Graduate engineer at Acme')
            ->assertSee('Thank you for reading &amp; considering it.', false);
    }

    public function test_the_letter_downloads_as_a_pdf(): void
    {
        $cv = $this->letterCv();

        $response = $this->get(route('cv.letter.pdf', $cv))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
        $this->assertStringContainsString('_Cover_letter.pdf', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_the_letter_downloads_as_a_valid_word_document(): void
    {
        $cv = $this->letterCv();

        $response = $this->get(route('cv.letter.word', $cv))->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertStringContainsString('_Cover_letter.docx', (string) $response->headers->get('Content-Disposition'));

        $path = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($path, $response->getContent());
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $document = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($path);

        $this->assertNotFalse(simplexml_load_string($document));
        $this->assertStringContainsString('Thank you for reading &amp; considering it.', $document);
        $this->assertStringContainsString('Hiring team, Graduate engineer at Acme', $document);
        $this->assertStringContainsString($cv->user->name, $document);
    }

    public function test_the_letter_saves_and_opens_with_routes_cached(): void
    {
        $this->artisan('route:cache')->assertSuccessful();
        // a fresh app starts with an empty in-memory database, so it is migrated again
        $this->refreshApplication();
        $this->artisan('migrate');
        try {
            $cv = Cv::factory()->create();
            $this->actingAs($cv->user)->put(route('cvs.letter', $cv), ['cover_letter' => 'Dear hiring team'])
                ->assertRedirect(route('cvs.edit', [$cv, 'letter']))->assertSessionHasNoErrors();
            $this->get(route('cv.letter', $cv))->assertOk()->assertSee('Dear hiring team');
            $this->get(route('cv.letter.word', $cv))->assertOk();
        } finally {
            $this->artisan('route:clear');
        }
    }
}
