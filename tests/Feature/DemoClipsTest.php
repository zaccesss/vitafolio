<?php

namespace Tests\Feature;

use App\Support\Demos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoClipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_clip_has_its_files_in_both_themes(): void
    {
        foreach (array_keys(Demos::all()) as $clip) {
            foreach (['', '-dark'] as $suffix) {
                foreach (['.webp', '.mp4', '-still.webp'] as $file) {
                    $this->assertFileExists(public_path("demo/{$clip}{$suffix}{$file}"));
                }
            }
        }
    }

    public function test_the_features_page_links_each_clip_to_its_own_page(): void
    {
        $response = $this->get(route('features'))->assertOk();
        foreach (array_keys(Demos::all()) as $clip) {
            $response->assertSee(route('features.demo', $clip), false)
                ->assertSee("demo/{$clip}-dark.webp", false)
                ->assertSee("demo/{$clip}-still.webp", false);
        }
    }

    public function test_a_clip_page_plays_the_video_and_leads_back_to_features(): void
    {
        $this->get(route('features.demo', 'share'))->assertOk()
            ->assertSee('demo/share.mp4', false)
            ->assertSee('demo/share-dark.mp4', false)
            ->assertSee(route('features').'#demo', false)
            ->assertSee(route('features.demo', 'build'), false);
    }

    public function test_an_unknown_clip_is_not_found(): void
    {
        $this->get('/features/demo/nothing')->assertNotFound();
    }

    public function test_signed_out_visitors_are_pointed_at_the_clips_from_the_home_page(): void
    {
        $this->get(route('home'))->assertOk()->assertSee(route('features').'#demo', false);
    }
}
