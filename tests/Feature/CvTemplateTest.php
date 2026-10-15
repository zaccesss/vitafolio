<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\User;
use App\Support\CvTemplates;
use App\Support\Tags;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function detailsPage(Cv $cv, ?string $template = null)
    {
        $url = route('cvs.edit', [$cv, 'details']).($template ? '?template='.$template : '');

        return $this->actingAs($cv->user)->get($url);
    }

    public function test_the_details_tab_offers_a_template_for_each_sector(): void
    {
        $cv = Cv::factory()->create();

        $page = $this->detailsPage($cv)->assertOk()->assertSee('Start from a template');
        foreach (CvTemplates::KINDS as $kind => $label) {
            $page->assertSee($label)->assertSee('?template='.$kind, false);
        }
        $this->assertCount(10, CvTemplates::KINDS);
    }

    public function test_choosing_a_template_fills_the_form_without_saving_it(): void
    {
        $cv = Cv::factory()->create(['profile' => null, 'experience' => null, 'education' => null]);

        $this->detailsPage($cv, 'finance')->assertOk()
            ->assertSee('[university]')
            ->assertSee('Financial analysis')
            ->assertSee('Nothing changes until you press Save.')
            ->assertDontSee('Saving will replace the content you already have.');

        $fresh = $cv->fresh();
        $this->assertNull($fresh->profile);
        $this->assertNull($fresh->experience);
        $this->assertSame(0, $fresh->tags()->count());
    }

    public function test_existing_content_gets_a_warning_and_a_way_back(): void
    {
        $cv = Cv::factory()->create(['profile' => 'My own profile']);

        $this->detailsPage($cv, 'law')->assertOk()
            ->assertSee('Saving will replace the content you already have.')
            ->assertSee('Keep my current content');
        $this->detailsPage($cv)->assertOk()->assertSee('My own profile');
    }

    public function test_an_unknown_template_is_ignored(): void
    {
        $cv = Cv::factory()->create(['profile' => 'My own profile']);

        $this->detailsPage($cv, 'not-a-template')->assertOk()
            ->assertSee('My own profile')
            ->assertDontSee('Nothing changes until you press Save.');
    }

    public function test_nobody_else_can_open_a_template_on_someone_elses_cv(): void
    {
        $cv = Cv::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('cvs.edit', [$cv, 'details']).'?template=technology')
            ->assertForbidden();
    }

    public function test_every_template_is_placeholders_within_the_field_limits(): void
    {
        $limits = ['headline' => 120, 'key_language' => 60, 'skills' => 1500, 'profile' => 3000, 'experience' => 5000, 'education' => 5000];

        foreach (array_keys(CvTemplates::KINDS) as $kind) {
            $fields = CvTemplates::fields($kind);
            $this->assertSame(array_keys($limits), array_keys($fields));
            foreach ($limits as $field => $max) {
                $this->assertLessThanOrEqual($max, mb_strlen($fields[$field]), "$kind $field is over its limit");
            }
            foreach (['headline', 'key_language', 'profile', 'experience', 'education'] as $field) {
                $this->assertMatchesRegularExpression('/\[[^\]]+\]/', $fields[$field], "$kind $field has nothing to replace");
            }
            // skills become searchable tags, so they are real words with no brackets
            $this->assertStringNotContainsString('[', $fields['skills']);
            $this->assertNotEmpty(Tags::parse($fields['skills']));
        }
    }

    public function test_a_saved_template_goes_through_the_normal_save(): void
    {
        $cv = Cv::factory()->create();
        $fields = CvTemplates::fields('healthcare');

        $this->actingAs($cv->user)->put(route('cvs.details', $cv), $fields)->assertRedirect();

        $fresh = $cv->fresh();
        $this->assertSame($fields['profile'], $fresh->profile);
        $this->assertTrue($fresh->tags()->where('name', 'Patient care')->exists());
    }
}
