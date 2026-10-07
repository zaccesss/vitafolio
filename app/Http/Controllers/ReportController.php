<?php

namespace App\Http\Controllers;

use App\Mail\ReportReceived;
use App\Models\Cv;
use App\Models\Endorsement;
use App\Models\Report;
use App\Models\User;
use App\Rules\Turnstile;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function store(Request $request, Cv $cv): RedirectResponse
    {
        abort_unless($cv->isVisibleTo($request->user()), 404);
        $this->record($request, $cv, null);

        return redirect()->route('cv.show', $cv)->with('status', __('Thanks for reporting this. A moderator will review it.'));
    }

    /** one endorsement on a cv; visitors see only shown ones. the owner can also report one still waiting */
    public function endorsement(Request $request, Cv $cv, Endorsement $endorsement): RedirectResponse
    {
        $isOwner = $request->user()?->id === $cv->user_id;
        abort_unless($cv->isVisibleTo($request->user()) && ($isOwner || $endorsement->isShown()), 404);
        $this->record($request, $cv, $endorsement);

        return ($isOwner ? redirect()->route('cvs.edit', [$cv, 'endorsements']) : redirect()->to(route('cv.show', $cv).'#endorsements'))
            ->with('status', __('Thanks for reporting this endorsement. A moderator will review it.'));
    }

    private function record(Request $request, Cv $cv, ?Endorsement $endorsement): void
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(Report::REASONS))],
            'details' => ['nullable', 'string', 'max:1000'],
            'cf-turnstile-response' => [new Turnstile],
        ]);

        // one open report per visitor per cv or endorsement; the reporter is stored only as a salted hash
        $reporter = hash('sha256', $request->ip().'|'.config('app.key'));
        $report = $cv->reports()->firstOrCreate(
            ['reporter_hash' => $reporter, 'resolved_at' => null, 'endorsement_id' => $endorsement?->id],
            ['reason' => $data['reason'], 'details' => $data['details'] ?? null],
        );

        // sent after the response, so a slow mail service never holds up the visitor
        if ($report->wasRecentlyCreated) {
            $admins = User::where('role', 'admin')->pluck('email');
            defer(fn () => $admins->each(fn (string $email) => rescue(fn () => Mail::to($email)->locale(Locales::DEFAULT)->send(new ReportReceived($report)))));
        }
    }
}
