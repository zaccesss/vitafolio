<?php

namespace Tests\Feature;

use App\Models\Cv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaunchPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_faq_expands_and_is_marked_up_for_search_engines(): void
    {
        $this->get(route('help.topic', 'faq'))->assertOk()
            ->assertSee('<details class="faq', false)
            ->assertSee('"@type":"FAQPage"', false);
    }

    public function test_long_pages_carry_breadcrumbs_and_reading_progress(): void
    {
        $this->get(route('privacy'))->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('data-reading', false);
    }

    public function test_the_home_page_describes_the_site_and_its_search(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('"@type":"WebSite"', false)
            ->assertSee('"@type":"SearchAction"', false);
    }

    public function test_every_page_has_the_confirmation_dialog_and_back_to_top(): void
    {
        $this->get(route('features'))->assertOk()
            ->assertSee('id="confirm-dialog"', false)
            ->assertSee('id="back-to-top"', false);
    }

    public function test_the_thank_you_page_is_kept_out_of_search(): void
    {
        $this->get(route('contact.sent'))->assertOk()->assertSee('Message sent')->assertSee('noindex', false);
    }

    public function test_a_tagged_link_is_named_by_its_tag(): void
    {
        $cv = Cv::factory()->create();

        $this->get(route('cv.show', $cv).'?utm_source=Newsletter')->assertOk();
        $this->assertSame('tagged: newsletter', DB::table('cv_views')->where('cv_id', $cv->id)->value('referrer_host'));

        // anything that is not a plain tag is ignored rather than stored
        $other = Cv::factory()->create();
        $this->get(route('cv.show', $other).'?utm_source=%3Cscript%3E')->assertOk();
        $this->assertNull(DB::table('cv_views')->where('cv_id', $other->id)->value('referrer_host'));
    }

    public function test_the_accessibility_statement_lists_shortcuts_for_every_system(): void
    {
        $this->get(route('accessibility'))->assertOk()
            ->assertSee('id="keyboard"', false)
            ->assertSee('Windows and Linux')
            ->assertSee('Cmd+Enter')
            ->assertSee('id="browsers"', false);
    }

    public function test_the_theme_button_has_an_icon_for_each_choice(): void
    {
        $html = $this->get(route('features'))->assertOk()->getContent();
        foreach (['isLight', 'isDark', 'isSystem'] as $choice) {
            $this->assertStringContainsString('x-show="'.$choice.'"', $html);
        }
    }

    public function test_the_host_address_redirects_to_the_site_address_in_production(): void
    {
        config(['app.env' => 'production', 'app.url' => 'https://vitafolio.example.test']);

        $this->get('http://vitafolio.onrender.test/privacy?x=1')
            ->assertStatus(301)->assertRedirect('https://vitafolio.example.test/privacy?x=1');
        $this->get('http://vitafolio.onrender.test/up')->assertOk();
        $this->get('https://vitafolio.example.test/privacy')->assertOk();
    }

    public function test_the_sitemap_lists_help_guides_and_last_modified_dates(): void
    {
        $this->get(route('sitemap'))->assertOk()
            ->assertSee(route('help.topic', 'getting-started'), false)
            ->assertSee(route('copyright'), false)
            ->assertSee('<lastmod>'.config('vitafolio.content_updated').'</lastmod>', false);
    }

    public function test_the_copyright_line_links_to_the_copyright_page(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('href="'.route('copyright').'"', false);
        $this->get(route('copyright'))->assertOk()->assertSee('MIT Licence');
    }

    public function test_the_microsoft_publisher_file_names_the_sign_in_app(): void
    {
        config(['services.microsoft.client_id' => '']);
        $this->get('/.well-known/microsoft-identity-association.json')->assertNotFound();

        config(['services.microsoft.client_id' => 'a573bf4c-0000-0000-0000-000000000000']);
        $this->get('/.well-known/microsoft-identity-association.json')->assertOk()
            ->assertExactJson(['associatedApplications' => [['applicationId' => 'a573bf4c-0000-0000-0000-000000000000']]]);
    }

    public function test_no_redirect_route_swallows_a_form_submission(): void
    {
        // a redirect that answers every method shadows a form route at the same address once routes
        // are cached in production, so every redirect must answer page visits only
        foreach (app('router')->getRoutes() as $route) {
            if (str_contains((string) $route->getActionName(), 'RedirectController')) {
                $this->assertSame(['GET', 'HEAD'], $route->methods(), $route->uri().' should only redirect page visits');
            }
        }
    }

    public function test_the_profile_saves_with_routes_cached(): void
    {
        $this->artisan('route:cache')->assertSuccessful();
        $this->refreshApplication();
        try {
            $user = \App\Models\User::factory()->create();
            $this->actingAs($user)->put(route('profile.update'), [
                'name' => $user->name, 'pronouns' => 'he/him', 'availability' => 'none', 'profile_visibility' => 'private',
            ])->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors();
            $this->assertSame('he/him', $user->fresh()->pronouns);
        } finally {
            $this->artisan('route:clear');
        }
    }
}
