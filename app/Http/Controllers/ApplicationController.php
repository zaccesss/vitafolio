<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * A private tracker for the roles someone is applying to: saved from the Jobs page or added by hand
 * for roles found anywhere else. Only the owner ever sees their applications.
 */
class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), Application::STATUSES, true) ? (string) $request->query('status') : '';
        $mine = Application::where('user_id', $request->user()->id);
        $counts = (clone $mine)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $applications = (clone $mine)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            // the nearest deadline first; roles without one follow, newest first
            ->orderByRaw('deadline is null')->orderBy('deadline')->latest()
            ->paginate(25)->withQueryString();

        return view('applications.index', compact('applications', 'counts', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['status'] ??= 'saved';
        $request->user()->hasMany(Application::class)->create($data);

        return redirect()->route('applications.index')->with('status', __('Application added.'));
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 404);
        $data = $this->validated($request, partial: true);
        // moving to Applied for the first time records the day, unless a date was given
        if (($data['status'] ?? null) === 'applied' && ! $application->applied_on && empty($data['applied_on'])) {
            $data['applied_on'] = now()->toDateString();
        }
        $application->update($data);

        return back()->with('status', __('Application updated.'));
    }

    public function destroy(Request $request, Application $application): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 404);
        $application->delete();

        return back()->with('status', __('Application removed.'));
    }

    /** saves a listing from the Jobs page, copying what is needed to keep it after the listing closes */
    public function save(Request $request, JobListing $job): RedirectResponse
    {
        Application::firstOrCreate(
            ['user_id' => $request->user()->id, 'job_listing_id' => $job->id],
            ['title' => $job->title, 'company' => $job->company, 'location' => $job->locationText(), 'url' => $job->url,
                'kind' => $job->kind, 'deadline' => Carbon::make($job->closes_at)?->toDateString(), 'status' => $request->input('status') === 'applied' ? 'applied' : 'saved',
                'applied_on' => $request->input('status') === 'applied' ? now()->toDateString() : null],
        );

        return back()->with('status', __('Saved to your applications.'));
    }

    /**
     * The apply link: counts the click for the listing (a number per day, never who) and sends the
     * person on to the employer or board.
     */
    public function go(JobListing $job): RedirectResponse
    {
        DB::table('job_clicks')->upsert(
            [['job_listing_id' => $job->id, 'clicked_on' => now()->toDateString(), 'clicks' => 1]],
            ['job_listing_id', 'clicked_on'],
            ['clicks' => DB::raw('clicks + 1')],
        );

        return redirect()->away($job->url);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:200'],
            'company' => ['nullable', 'string', 'max:160'],
            'location' => ['nullable', 'string', 'max:160'],
            'url' => ['nullable', 'url:https,http', 'max:500'],
            'status' => ['sometimes', Rule::in(Application::STATUSES)],
            'applied_on' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
