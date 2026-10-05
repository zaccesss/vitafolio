<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_handle_changes_once_a_month_and_the_old_one_redirects(): void
    {
        $user = User::factory()->create(['handle' => 'first-name']);
        $this->actingAs($user);

        $this->put(route('profile.handle'), ['handle' => 'second-name'])->assertSessionHasNoErrors();
        $this->assertSame('second-name', $user->fresh()->handle);
        $this->get('/@first-name')->assertRedirect(route('profile.show', 'second-name'))->assertStatus(301);

        $this->put(route('profile.handle'), ['handle' => 'third-name'])->assertSessionHasErrorsIn('handle', 'handle');
        $this->assertSame('second-name', $user->fresh()->handle);

        $this->travel(31)->days();
        $this->put(route('profile.handle'), ['handle' => 'third-name'])->assertSessionHasNoErrors();
    }

    public function test_an_old_handle_is_held_so_nobody_else_can_take_it(): void
    {
        $user = User::factory()->create(['handle' => 'held-name']);
        $this->actingAs($user)->put(route('profile.handle'), ['handle' => 'new-name']);

        $other = User::factory()->create();
        $this->actingAs($other)->put(route('profile.handle'), ['handle' => 'held-name'])->assertSessionHasErrorsIn('handle', 'handle');
    }

    public function test_reserved_and_badly_formed_handles_are_refused(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['admin', 'Upper-Case', 'double--hyphen', 'ab'] as $handle) {
            $this->put(route('profile.handle'), ['handle' => $handle])->assertSessionHasErrorsIn('handle', 'handle');
        }
    }
}
