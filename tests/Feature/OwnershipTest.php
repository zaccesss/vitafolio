<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_sign_in(): void
    {
        $cv = Cv::factory()->create();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('cvs.edit', $cv))->assertRedirect(route('login'));
    }

    public function test_nobody_can_change_someone_elses_cv(): void
    {
        $cv = Cv::factory()->create();
        $intruder = User::factory()->create();
        $this->actingAs($intruder);

        $this->get(route('cvs.edit', $cv))->assertForbidden();
        $this->put(route('cvs.details', $cv), ['profile' => 'changed'])->assertForbidden();
        $this->put(route('cvs.settings', $cv), ['visibility' => 'private'])->assertForbidden();
        $this->put(route('cvs.latex.update', $cv), ['source' => '\documentclass{article}'])->assertForbidden();
        $this->post(route('cvs.document', $cv), ['document' => UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4')])->assertForbidden();
        $this->delete(route('cvs.destroy', $cv), ['confirm_title' => $cv->title])->assertForbidden();

        $this->assertSame('A short profile.', $cv->fresh()->profile);
    }

    public function test_the_owner_can_edit_their_cv(): void
    {
        $cv = Cv::factory()->create();
        $this->actingAs($cv->user);

        $this->get(route('cvs.edit', $cv))->assertOk();
        $this->put(route('cvs.details', $cv), ['profile' => 'Updated profile.'])->assertRedirect();
        $this->assertSame('Updated profile.', $cv->fresh()->profile);
    }

    public function test_members_cannot_reach_moderation(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.index'))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.index'))->assertOk();
    }
}
