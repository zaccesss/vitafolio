<?php

namespace Tests\Feature;

use App\Mail\TicketUpdate;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_a_visitor_opens_a_ticket_and_reaches_it_only_with_the_private_link(): void
    {
        Mail::fake();
        $this->admin();

        $this->post(route('support.store'), [
            'name' => 'Sam', 'email' => 'sam@example.com', 'category' => 'account',
            'subject' => 'Cannot sign in', 'body' => 'The **code** never arrives.',
        ])->assertRedirect(route('support.sent'));

        $ticket = SupportTicket::sole();
        $this->assertSame('VF-'.(1000 + $ticket->id), $ticket->reference());
        Mail::assertSent(TicketUpdate::class, fn ($m) => $m->event === 'opened' && $m->hasTo('sam@example.com') && str_contains((string) $m->link, 'token='));
        Mail::assertSent(TicketUpdate::class, fn ($m) => $m->event === 'new');

        // without the link the ticket does not exist for a stranger
        $this->get(route('support.show', $ticket))->assertNotFound();
        $this->get(route('support.show', ['ticket' => $ticket, 'token' => 'wrong']))->assertNotFound();
        $sent = Mail::sent(TicketUpdate::class, fn ($m) => $m->event === 'opened')->first();
        $this->get($sent->link)->assertOk()->assertSee('Cannot sign in')->assertSee('<strong>code</strong>', false);
    }

    public function test_markdown_never_runs_script(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('support.store'), [
            'category' => 'bug', 'subject' => 'Odd page', 'body' => '<script>alert(1)</script> [x](javascript:alert(2)) and more text',
        ]);
        $ticket = SupportTicket::sole();

        $this->actingAs($user)->get(route('support.show', $ticket))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('javascript:alert(2)', false);
    }

    public function test_staff_reply_sets_waiting_and_the_person_replying_reopens_it(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $admin = $this->admin();
        $this->actingAs($user)->post(route('support.store'), ['category' => 'cv', 'subject' => 'PDF looks wrong', 'body' => 'The PDF cuts off my projects.']);
        $ticket = SupportTicket::sole();

        $this->actingAs($admin)->post(route('support.reply', $ticket), ['body' => 'Thanks, we are looking into it.'])->assertRedirect();
        $this->assertSame('waiting', $ticket->fresh()->status);
        Mail::assertSent(TicketUpdate::class, fn ($m) => $m->event === 'reply' && $m->hasTo($user->email));

        $this->actingAs($user)->post(route('support.reply', $ticket), ['body' => 'Still happening.']);
        $this->assertSame('open', $ticket->fresh()->status);

        $this->actingAs($admin)->post(route('support.reply', $ticket), ['body' => 'Fixed now.', 'resolve' => 1]);
        $this->assertSame('resolved', $ticket->fresh()->status);
        $this->assertSame([false, true, false, true], $ticket->messages()->pluck('from_staff')->all());
    }

    public function test_other_people_cannot_see_a_ticket_and_admins_get_a_queue(): void
    {
        $ticket = SupportTicket::factory()->for(User::factory())->create();
        $this->actingAs(User::factory()->create())->get(route('support.show', $ticket))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('admin.support'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.support'))->assertOk()->assertSee($ticket->subject);
    }

    public function test_messages_are_capped_and_a_closed_ticket_takes_no_replies(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('support.store'), ['category' => 'other', 'subject' => 'Long one', 'body' => str_repeat('a', 5001)])
            ->assertSessionHasErrors('body');
        $ticket = SupportTicket::factory()->for($user)->create(['status' => 'closed']);
        $this->actingAs($user)->post(route('support.reply', $ticket), ['body' => 'Hello again'])->assertForbidden();
    }

    public function test_tidy_closes_quiet_resolved_tickets_and_deletes_year_old_closed_ones(): void
    {
        $quiet = SupportTicket::factory()->create(['status' => 'resolved', 'last_activity_at' => now()->subDays(15)]);
        $old = SupportTicket::factory()->create(['status' => 'closed', 'last_activity_at' => now()->subYear()->subDay()]);
        $live = SupportTicket::factory()->create(['status' => 'open']);

        $this->artisan('vitafolio:tidy')->assertSuccessful();

        $this->assertSame('closed', $quiet->fresh()->status);
        $this->assertModelMissing($old);
        $this->assertModelExists($live);
    }

    public function test_deleting_an_account_deletes_its_tickets(): void
    {
        $user = User::factory()->create();
        SupportTicket::factory()->for($user)->create();
        $user->delete();
        $this->assertSame(0, SupportTicket::count());
    }
}
