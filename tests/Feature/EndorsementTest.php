<?php

namespace Tests\Feature;

use App\Mail\EndorsementReceived;
use App\Models\Cv;
use App\Models\Endorsement;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EndorsementTest extends TestCase
{
    use RefreshDatabase;

    private const TEXT = 'Jordan led our final year project with real care and calm.';

    private function form(array $overrides = []): array
    {
        return $overrides + ['relationship' => 'studied_together', 'context' => 'Final year project, 2026', 'body' => self::TEXT];
    }

    private function endorsement(Cv $cv, string $status = 'pending', ?User $endorser = null): Endorsement
    {
        $endorsement = new Endorsement(['relationship' => 'worked_together', 'body' => self::TEXT]);
        $endorsement->cv()->associate($cv);
        $endorsement->endorser()->associate($endorser ?? User::factory()->create());
        $endorsement->status = $status;
        $endorsement->approved_at = $status === 'approved' ? now() : null;
        $endorsement->save();

        return $endorsement;
    }

    public function test_a_signed_in_user_endorses_a_cv_and_the_owner_is_emailed(): void
    {
        Mail::fake();
        $cv = Cv::factory()->create();
        $endorser = User::factory()->create();

        $this->actingAs($endorser)->post(route('cv.endorsements.store', $cv), $this->form())
            ->assertRedirect(route('cv.show', $cv).'#endorse')->assertSessionHasNoErrors();

        $endorsement = $cv->endorsements()->sole();
        $this->assertSame('pending', $endorsement->status);
        $this->assertSame($endorser->id, $endorsement->endorser_id);
        $this->assertSame('Final year project, 2026', $endorsement->context);
        Mail::assertSent(EndorsementReceived::class, fn ($mail) => $mail->hasTo($cv->user->email));
        $this->assertStringContainsString(self::TEXT, (new EndorsementReceived($endorsement))->render());
    }

    public function test_the_endorsement_form_is_validated(): void
    {
        $cv = Cv::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('cv.endorsements.store', $cv), [
            'relationship' => 'best_friend', 'context' => str_repeat('a', 121), 'body' => 'Too short',
        ])->assertRedirect(route('cv.show', $cv).'#endorse')
            ->assertSessionHasErrorsIn('endorsement', ['relationship', 'context', 'body']);
        $this->actingAs(User::factory()->create())->post(route('cv.endorsements.store', $cv), $this->form(['body' => str_repeat('a', Endorsement::MAX_LENGTH + 1)]))
            ->assertSessionHasErrorsIn('endorsement', ['body']);
        $this->assertSame(0, Endorsement::count());
    }

    public function test_guests_owners_and_unverified_accounts_cannot_endorse(): void
    {
        $cv = Cv::factory()->create();

        $this->post(route('cv.endorsements.store', $cv), $this->form())->assertRedirect(route('login'));
        $this->actingAs($cv->user)->post(route('cv.endorsements.store', $cv), $this->form())->assertForbidden();
        $this->actingAs(User::factory()->unverified()->create())->post(route('cv.endorsements.store', $cv), $this->form())
            ->assertRedirect(route('verification.notice'));
        $this->assertSame(0, Endorsement::count());
    }

    public function test_a_suspended_account_cannot_endorse_and_its_endorsements_stop_showing(): void
    {
        $cv = Cv::factory()->create();
        $suspended = User::factory()->suspended()->create();
        $this->actingAs($suspended)->post(route('cv.endorsements.store', $cv), $this->form())->assertRedirect(route('login'));
        $this->assertSame(0, Endorsement::count());

        $endorser = User::factory()->create();
        $this->endorsement($cv, 'approved', $endorser);
        $this->get(route('cv.show', $cv))->assertSee(self::TEXT);
        $endorser->forceFill(['suspended_at' => now()])->save();
        $this->get(route('cv.show', $cv))->assertDontSee(self::TEXT);
    }

    public function test_private_and_hidden_cvs_cannot_be_endorsed(): void
    {
        $private = Cv::factory()->visibility('private')->create();
        $hidden = Cv::factory()->create();
        $hidden->forceFill(['hidden_at' => now()])->save();

        $this->actingAs(User::factory()->create())->post(route('cv.endorsements.store', $private), $this->form())->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->post(route('cv.endorsements.store', $private), $this->form())->assertNotFound();
        $this->actingAs(User::factory()->create())->post(route('cv.endorsements.store', $hidden), $this->form())->assertNotFound();
        $this->assertSame(0, Endorsement::count());
    }

    public function test_one_endorsement_per_endorser_per_cv(): void
    {
        $cv = Cv::factory()->create();
        $endorser = User::factory()->create();
        $this->actingAs($endorser)->post(route('cv.endorsements.store', $cv), $this->form())->assertSessionHasNoErrors();

        $this->actingAs($endorser)->post(route('cv.endorsements.store', $cv), $this->form(['body' => 'A second go at the same CV, written again.']))
            ->assertSessionHasErrorsIn('endorsement', ['body']);
        $this->assertSame(1, Endorsement::count());
        $this->assertSame(self::TEXT, Endorsement::sole()->body);
        // the same person may still endorse a different cv
        $this->actingAs($endorser)->post(route('cv.endorsements.store', Cv::factory()->create()), $this->form())->assertSessionHasNoErrors();
        $this->assertSame(2, Endorsement::count());
    }

    public function test_the_owner_approves_hides_and_deletes(): void
    {
        $cv = Cv::factory()->create();
        $endorsement = $this->endorsement($cv);

        $this->get(route('cv.show', $cv))->assertDontSee(self::TEXT);
        $this->actingAs($cv->user)->get(route('cvs.edit', [$cv, 'endorsements']))->assertOk()
            ->assertSee('<h2 id="endorsements-title"', false)->assertSee('Waiting for approval')->assertSee(self::TEXT)
            ->assertSee('waiting for approval</span>', false);
        $this->actingAs($cv->user)->get(route('dashboard'))->assertSee('1 endorsement is waiting for your approval.');

        $this->actingAs($cv->user)->put(route('cvs.endorsements.decide', [$cv, $endorsement]), ['status' => 'approved'])
            ->assertRedirect(route('cvs.edit', [$cv, 'endorsements']));
        $this->assertSame('approved', $endorsement->fresh()->status);
        $this->assertNotNull($endorsement->fresh()->approved_at);
        auth()->logout();
        $this->get(route('cv.show', $cv))->assertSee('<h2 id="endorsements-title"', false)->assertSee(self::TEXT);

        $this->actingAs($cv->user)->put(route('cvs.endorsements.decide', [$cv, $endorsement]), ['status' => 'hidden']);
        $this->assertSame('hidden', $endorsement->fresh()->status);
        auth()->logout();
        $this->get(route('cv.show', $cv))->assertDontSee(self::TEXT);

        $this->actingAs($cv->user)->put(route('cvs.endorsements.decide', [$cv, $endorsement]), ['status' => 'pending'])->assertSessionHasErrors('status');
        $this->actingAs($cv->user)->delete(route('cv.endorsements.destroy', [$cv, $endorsement]))->assertRedirect(route('cvs.edit', [$cv, 'endorsements']));
        $this->assertNull($endorsement->fresh());
    }

    public function test_only_the_owner_decides_and_endorsements_stay_under_their_own_cv(): void
    {
        $cv = Cv::factory()->create();
        $endorsement = $this->endorsement($cv);
        $other = Cv::factory()->create();

        $this->actingAs($endorsement->endorser)->put(route('cvs.endorsements.decide', [$cv, $endorsement]), ['status' => 'approved'])->assertForbidden();
        $this->actingAs(User::factory()->create())->delete(route('cv.endorsements.destroy', [$cv, $endorsement]))->assertForbidden();
        // the owner of another cv cannot reach this endorsement through their own address
        $this->actingAs($other->user)->put(route('cvs.endorsements.decide', [$other, $endorsement]), ['status' => 'approved'])->assertNotFound();
        $this->assertSame('pending', $endorsement->fresh()->status);
    }

    public function test_the_endorser_edits_and_withdraws_their_own(): void
    {
        $cv = Cv::factory()->create();
        $endorsement = $this->endorsement($cv, 'approved');
        $endorser = $endorsement->endorser;

        $this->actingAs($endorser)->get(route('cv.show', $cv))->assertOk()
            ->assertSee('Your endorsement')->assertSee('Shown on the CV')->assertSee('Withdraw endorsement');

        // new wording needs approving again before it shows
        $this->actingAs($endorser)->put(route('cv.endorsements.update', [$cv, $endorsement]), $this->form(['body' => 'Updated wording that the owner has not read yet.']))
            ->assertSessionHasNoErrors();
        $this->assertSame('pending', $endorsement->fresh()->status);
        $this->assertNull($endorsement->fresh()->approved_at);
        $this->assertSame('Updated wording that the owner has not read yet.', $endorsement->fresh()->body);
        auth()->logout();
        $this->get(route('cv.show', $cv))->assertDontSee('Updated wording');

        $this->actingAs(User::factory()->create())->put(route('cv.endorsements.update', [$cv, $endorsement]), $this->form())->assertForbidden();
        $this->actingAs($endorser)->delete(route('cv.endorsements.destroy', [$cv, $endorsement]))->assertRedirect(route('cv.show', $cv).'#endorse');
        $this->assertNull($endorsement->fresh());
    }

    public function test_approved_endorsements_follow_the_cv_visibility(): void
    {
        $stranger = User::factory()->create();
        foreach (['public' => true, 'unlisted' => true, 'private' => false] as $visibility => $othersSee) {
            $cv = Cv::factory()->visibility($visibility)->create();
            // an approved endorsement on a cv made private later stays out of sight with it
            $this->endorsement($cv, 'approved');

            $this->actingAs($cv->user)->get(route('cv.show', $cv))->assertOk()->assertSee(self::TEXT);
            auth()->logout();
            $guest = $this->get(route('cv.show', $cv));
            $othersSee ? $guest->assertOk()->assertSee(self::TEXT) : $guest->assertNotFound();
            $other = $this->actingAs($stranger)->get(route('cv.show', $cv));
            $othersSee ? $other->assertOk()->assertSee(self::TEXT) : $other->assertNotFound();
            auth()->logout();
        }

        $hidden = Cv::factory()->create();
        $this->endorsement($hidden, 'approved');
        $hidden->forceFill(['hidden_at' => now()])->save();
        $this->get(route('cv.show', $hidden))->assertNotFound();
    }

    public function test_pending_endorsements_never_show_to_anyone_but_their_writer(): void
    {
        $cv = Cv::factory()->create();
        $endorsement = $this->endorsement($cv);

        $this->get(route('cv.show', $cv))->assertDontSee(self::TEXT);
        $this->actingAs(User::factory()->create())->get(route('cv.show', $cv))->assertDontSee(self::TEXT);
        $this->actingAs($cv->user)->get(route('cv.show', $cv))->assertDontSee(self::TEXT)
            ->assertSee('1 endorsement is waiting for your approval.');
        $this->actingAs($endorsement->endorser)->get(route('cv.show', $cv))->assertSee(self::TEXT)->assertSee('Waiting for approval');
    }

    public function test_the_cv_page_offers_the_form_with_labels_and_headings(): void
    {
        $cv = Cv::factory()->create();

        $this->get(route('cv.show', $cv))->assertOk()->assertSee('<h2 id="endorse-title"', false)->assertSee(route('login'), false);
        $this->actingAs(User::factory()->create())->get(route('cv.show', $cv))->assertOk()
            ->assertSee('Endorse '.$cv->user->firstName())
            ->assertSee('<label for="f-relationship"', false)
            ->assertSee('<label for="f-body"', false)
            ->assertSee('<label for="f-context"', false)
            ->assertSee('action="'.route('cv.endorsements.store', $cv).'"', false);
        // the owner gets no form for their own cv
        $this->actingAs($cv->user)->get(route('cv.show', $cv))->assertDontSee('<label for="f-relationship"', false);
    }

    public function test_an_endorsement_is_reported_and_hidden_by_a_moderator(): void
    {
        $admin = User::factory()->admin()->create();
        $cv = Cv::factory()->create();
        $endorsement = $this->endorsement($cv, 'approved');

        $this->post(route('cv.endorsements.report', [$cv, $endorsement]), ['reason' => 'offensive', 'details' => 'Rude'])
            ->assertRedirect(route('cv.show', $cv).'#endorsements');
        $report = Report::sole();
        $this->assertSame($endorsement->id, $report->endorsement_id);
        $this->assertSame($cv->id, $report->cv_id);
        // a report about the cv itself stays separate from one about its endorsement
        $this->post(route('cv.report', $cv), ['reason' => 'spam']);
        $this->assertSame(2, Report::count());

        $this->actingAs($admin)->get(route('admin.index'))->assertOk()->assertSee('Reported endorsement by')->assertSee('Hide endorsement');
        $this->actingAs($admin)->post(route('admin.endorsements.hide', $endorsement))->assertRedirect();
        $this->assertNotNull($endorsement->fresh()->hidden_at);
        $this->assertNotNull($report->fresh()->resolved_at);
        auth()->logout();
        $this->get(route('cv.show', $cv))->assertDontSee(self::TEXT);

        // the owner approving again cannot undo a moderator's decision
        $this->actingAs($cv->user)->put(route('cvs.endorsements.decide', [$cv, $endorsement]), ['status' => 'approved']);
        auth()->logout();
        $this->get(route('cv.show', $cv))->assertDontSee(self::TEXT);

        // the writer cannot withdraw it to clear the decision and start again
        $this->actingAs($endorsement->endorser)->delete(route('cv.endorsements.destroy', [$cv, $endorsement]))->assertForbidden();
        $this->assertNotNull($endorsement->fresh());

        $this->actingAs($admin)->post(route('admin.endorsements.restore', $endorsement));
        $this->assertNull($endorsement->fresh()->hidden_at);
        $this->actingAs($admin)->post(route('admin.endorsements.hide', $endorsement));
        $this->actingAs(User::factory()->create())->post(route('admin.endorsements.restore', $endorsement))->assertForbidden();
    }

    public function test_a_pending_endorsement_can_only_be_reported_by_the_owner(): void
    {
        $cv = Cv::factory()->create();
        $endorsement = $this->endorsement($cv);

        $this->post(route('cv.endorsements.report', [$cv, $endorsement]), ['reason' => 'spam'])->assertNotFound();
        $this->actingAs($cv->user)->post(route('cv.endorsements.report', [$cv, $endorsement]), ['reason' => 'spam'])
            ->assertRedirect(route('cvs.edit', [$cv, 'endorsements']));
        $this->assertSame($endorsement->id, Report::sole()->endorsement_id);
    }

    public function test_the_security_check_is_required_when_switched_on(): void
    {
        config(['vitafolio.turnstile.site_key' => 'site', 'vitafolio.turnstile.secret' => 'secret']);
        $cv = Cv::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('cv.endorsements.store', $cv), $this->form())
            ->assertSessionHasErrorsIn('endorsement', ['cf-turnstile-response']);
        $this->assertSame(0, Endorsement::count());
    }

    public function test_endorsements_are_rate_limited(): void
    {
        $endorser = User::factory()->create();
        $cvs = Cv::factory()->count(11)->create();
        foreach ($cvs->take(10) as $cv) {
            $this->actingAs($endorser)->post(route('cv.endorsements.store', $cv), $this->form())->assertSessionHasNoErrors();
        }
        $this->actingAs($endorser)->post(route('cv.endorsements.store', $cvs->last()), $this->form())->assertStatus(429);
    }

    public function test_both_parties_get_endorsements_in_their_data_export(): void
    {
        $cv = Cv::factory()->create();
        $endorsement = $this->endorsement($cv, 'approved');

        $owner = $this->actingAs($cv->user)->get(route('account.export'))->assertOk()->json();
        $this->assertSame(self::TEXT, $owner['cvs'][0]['endorsements_received'][0]['text']);
        $this->assertSame($endorsement->endorser->name, $owner['cvs'][0]['endorsements_received'][0]['from']);
        $this->assertSame('approved', $owner['cvs'][0]['endorsements_received'][0]['status']);

        $writer = $this->actingAs($endorsement->endorser)->get(route('account.export'))->assertOk()->json();
        $this->assertSame(self::TEXT, $writer['endorsements_written'][0]['text']);
        $this->assertSame(route('cv.show', $cv), $writer['endorsements_written'][0]['cv_address']);
        $this->assertSame('Worked together', $writer['endorsements_written'][0]['relationship']);
    }

    public function test_deleting_either_account_deletes_the_endorsement(): void
    {
        $cv = Cv::factory()->create();
        $first = $this->endorsement($cv);
        $first->endorser->delete();
        $this->assertNull($first->fresh());

        $second = $this->endorsement($cv, 'approved');
        Report::forceCreate(['cv_id' => $cv->id, 'endorsement_id' => $second->id, 'reason' => 'spam', 'reporter_hash' => str_repeat('a', 64)]);
        $cv->user->delete();
        $this->assertNull($second->fresh());
        $this->assertSame(0, Report::count());
        $this->assertNotNull($second->endorser->fresh());
    }

    public function test_deleting_the_account_through_settings_removes_endorsements_written(): void
    {
        $cv = Cv::factory()->create();
        $endorsement = $this->endorsement($cv, 'approved');

        $this->actingAs($endorsement->endorser)->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('account.destroy'), ['confirm' => 'DELETE'])->assertRedirect();
        $this->assertSame(0, Endorsement::count());
        $this->get(route('cv.show', $cv))->assertOk()->assertDontSee(self::TEXT);
    }

    public function test_the_privacy_policy_and_help_describe_endorsements(): void
    {
        $this->get(route('privacy'))->assertOk()->assertSee('Endorsements');
        $this->get(route('help.topic', 'sharing'))->assertOk()->assertSee('Endorsements');
    }

    public function test_endorsements_work_with_routes_cached(): void
    {
        $this->artisan('route:cache')->assertSuccessful();
        // a fresh app starts with an empty in-memory database, so it is migrated again
        $this->refreshApplication();
        // the fresh app also forgets the base test's switches, so pages render without a build again
        $this->withoutVite();
        $this->artisan('migrate');
        try {
            $cv = Cv::factory()->create();
            $endorser = User::factory()->create();
            $this->actingAs($endorser)->post(route('cv.endorsements.store', $cv), $this->form())->assertSessionHasNoErrors();
            $endorsement = Endorsement::sole();
            $this->actingAs($endorser)->put(route('cv.endorsements.update', [$cv, $endorsement]), $this->form())->assertSessionHasNoErrors();
            $this->actingAs($cv->user)->get(route('cvs.edit', [$cv, 'endorsements']))->assertOk()->assertSee(self::TEXT);
            $this->actingAs($cv->user)->put(route('cvs.endorsements.decide', [$cv, $endorsement]), ['status' => 'approved'])
                ->assertRedirect(route('cvs.edit', [$cv, 'endorsements']));
            auth()->logout();
            $this->get(route('cv.show', $cv))->assertOk()->assertSee(self::TEXT);
            $this->post(route('cv.endorsements.report', [$cv, $endorsement]), ['reason' => 'spam'])->assertRedirect();
            $this->actingAs($endorser)->delete(route('cv.endorsements.destroy', [$cv, $endorsement]))->assertRedirect();
            $this->assertSame(0, Endorsement::count());
        } finally {
            $this->artisan('route:clear');
        }
    }
}
