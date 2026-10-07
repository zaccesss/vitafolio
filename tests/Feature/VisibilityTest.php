<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_private_cv_is_only_seen_by_its_owner_and_admins(): void
    {
        $cv = Cv::factory()->visibility('private')->create();

        $this->get(route('cv.show', $cv))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('cv.show', $cv))->assertNotFound();
        $this->actingAs($cv->user)->get(route('cv.show', $cv))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('cv.show', $cv))->assertOk();
    }

    public function test_an_unlisted_cv_opens_by_link_but_is_kept_out_of_search(): void
    {
        $cv = Cv::factory()->visibility('unlisted')->create();

        $this->get(route('cv.show', $cv))->assertOk()->assertSee('<meta name="robots" content="noindex">', false);
        $this->get(route('home'))->assertDontSee($cv->user->name);
        $this->get(route('sitemap'))->assertDontSee($cv->slug);
    }

    public function test_a_public_cv_is_listed_and_indexable(): void
    {
        $cv = Cv::factory()->create();

        $this->get(route('cv.show', $cv))->assertOk()->assertDontSee('content="noindex"', false);
        $this->get(route('home'))->assertSee($cv->user->name);
        $this->get(route('sitemap'))->assertSee($cv->slug);
    }

    public function test_hidden_cvs_and_suspended_accounts_disappear(): void
    {
        $hidden = Cv::factory()->create();
        $hidden->forceFill(['hidden_at' => now()])->save();
        $suspended = Cv::factory()->for(User::factory()->suspended())->create();

        $this->get(route('cv.show', $hidden))->assertNotFound();
        $this->get(route('cv.show', $suspended))->assertNotFound();
        $this->get(route('profile.show', $suspended->user->handle))->assertNotFound();
        $this->get(route('cv.og', $suspended))->assertNotFound();
    }

    public function test_a_private_profile_is_hidden_and_takes_its_cvs_out_of_the_directory(): void
    {
        $user = User::factory()->create(['profile_visibility' => 'private']);
        $cv = Cv::factory()->for($user)->create();

        $this->get(route('profile.show', $user->handle))->assertNotFound();
        $this->get(route('home'))->assertDontSee($user->name);
        $this->get(route('cv.show', $cv))->assertOk()->assertSee('content="noindex"', false);
    }

    public function test_the_dashboard_shows_each_visibility_as_an_icon_with_its_word(): void
    {
        $owner = User::factory()->create();
        Cv::factory()->for($owner)->visibility('private')->create(['title' => 'Private one']);
        Cv::factory()->for($owner)->visibility('unlisted')->create(['title' => 'Unlisted one']);

        $page = $this->actingAs($owner)->get(route('dashboard'))->assertOk();
        $page->assertSee('Private')->assertSee('Unlisted');
        $page->assertDontSee('<span class="badge shrink-0">', false);
        $page->assertSee('aria-hidden="true" focusable="false"', false);
    }
}
