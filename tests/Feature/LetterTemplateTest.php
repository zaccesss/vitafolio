<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\User;
use App\Support\LetterTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function letterPage(Cv $cv, ?string $template = null)
    {
        $url = route('cvs.edit', [$cv, 'letter']).($template ? '?template='.$template : '');

        return $this->actingAs($cv->user)->get($url);
    }

    public function test_the_letter_tab_offers_a_template_for_each_kind_of_role(): void
    {
        $cv = Cv::factory()->create();

        $page = $this->letterPage($cv)->assertOk()->assertSee('Start from a template');
        foreach (LetterTemplates::KINDS as $kind => $label) {
            $page->assertSee($label)->assertSee('?template='.$kind, false);
        }
    }

    public function test_choosing_a_template_fills_the_box_with_the_owners_name_without_saving_it(): void
    {
        $cv = Cv::factory()->create(['cover_letter' => null]);

        $this->letterPage($cv, 'placement')->assertOk()
            ->assertSee('placement at [company]')
            ->assertSee('Yours sincerely,')
            ->assertSee($cv->user->name)
            ->assertSee('Nothing changes until you press Save.');

        $this->assertNull($cv->fresh()->cover_letter);
    }

    public function test_an_existing_letter_gets_a_warning_and_a_way_back(): void
    {
        $cv = Cv::factory()->create(['cover_letter' => 'My own letter']);

        $this->letterPage($cv, 'graduate')->assertOk()
            ->assertSee('Saving will replace the letter you already have.')
            ->assertSee('Keep my current letter');
        $this->letterPage($cv)->assertOk()->assertSee('My own letter');
    }

    public function test_an_unknown_template_is_ignored(): void
    {
        $cv = Cv::factory()->create(['cover_letter' => 'My own letter']);

        $this->letterPage($cv, 'not-a-template')->assertOk()
            ->assertSee('My own letter')
            ->assertDontSee('Nothing changes until you press Save.');
    }

    public function test_nobody_else_can_open_a_template_on_someone_elses_cv(): void
    {
        $cv = Cv::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('cvs.edit', [$cv, 'letter']).'?template=internship')
            ->assertForbidden();
    }

    public function test_every_template_keeps_its_placeholders_in_square_brackets(): void
    {
        foreach (array_keys(LetterTemplates::KINDS) as $kind) {
            $body = LetterTemplates::body($kind, 'Sam Taylor');
            $this->assertStringContainsString('[company]', $body);
            $this->assertStringEndsWith('Sam Taylor', $body);
            $this->assertLessThanOrEqual(6000, mb_strlen($body), 'a template must fit the letter limit');
        }
    }

    public function test_the_home_page_says_it_is_free_for_everyone_and_credits_the_maker(): void
    {
        config(['vitafolio.owner.url' => 'https://example.com']);

        $this->get(route('home'))->assertOk()
            ->assertSee('Free for everyone.')
            ->assertDontSee('everyone in between')
            ->assertSee('By <a href="https://example.com"', false)
            ->assertDontSee('Made by');
    }
}
