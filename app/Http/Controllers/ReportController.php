<?php

namespace App\Http\Controllers;

use App\Mail\ReportReceived;
use App\Models\Cv;
use App\Models\Report;
use App\Models\User;
use App\Rules\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function store(Request $request, Cv $cv): RedirectResponse
    {
        abort_unless($cv->isVisibleTo($request->user()), 404);
        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(Report::REASONS))],
            'details' => ['nullable', 'string', 'max:1000'],
            'cf-turnstile-response' => [new Turnstile],
        ]);

        // one open report per visitor per cv; the reporter is stored only as a salted hash
        $reporter = hash('sha256', $request->ip().'|'.config('app.key'));
        $report = $cv->reports()->firstOrCreate(
            ['reporter_hash' => $reporter, 'resolved_at' => null],
            ['reason' => $data['reason'], 'details' => $data['details'] ?? null],
        );

        // sent after the response, so a slow mail service never holds up the visitor
        if ($report->wasRecentlyCreated) {
            $admins = User::where('role', 'admin')->pluck('email');
            defer(fn () => $admins->each(fn (string $email) => rescue(fn () => Mail::to($email)->send(new ReportReceived($report)))));
        }

        return redirect()->route('cv.show', $cv)->with('status', 'Thanks for reporting this. A moderator will review it.');
    }
}
