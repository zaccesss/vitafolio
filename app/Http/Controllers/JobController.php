<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobListing;
use App\Support\Jobs\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(Request $request): View
    {
        $kind = array_key_exists((string) $request->query('kind'), JobListing::KINDS) ? (string) $request->query('kind') : '';
        $sector = array_key_exists((string) $request->query('sector'), Sector::ALL) ? (string) $request->query('sector') : '';
        $q = trim(mb_substr((string) $request->query('q'), 0, 100));
        $where = trim(mb_substr((string) $request->query('where'), 0, 100));

        // every filter except the kind of role, so each tab can show how many jobs it holds
        $filtered = JobListing::query()->open()
            ->when($sector !== '', fn ($query) => $query->where('sector', $sector))
            // the search box looks for a job title or employer only, so an advert's small print never matches
            ->when($q !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('title', 'like', "%{$q}%")->orWhere('company', 'like', "%{$q}%")))
            ->when($where !== '', fn ($query) => $query->where('location', 'like', "%{$where}%"));
        // listings change once a night, so the tab counts can be ten minutes old. the cache holds plain
        // arrays because it refuses to unserialise objects
        $counts = collect(Cache::remember(
            'jobs:counts:'.md5($sector."\n".$q."\n".$where),
            now()->addMinutes(10),
            fn () => (clone $filtered)->selectRaw('kind, count(*) as total')->groupBy('kind')->pluck('total', 'kind')->map(fn ($total) => (int) $total)->all(),
        ));

        // only what a card shows. an employer's advert can run to many thousands of characters, so the
        // description comes back cut to the two-line preview, which is also enough to tell whether there is one
        $jobs = (clone $filtered)
            ->select(['id', 'source', 'kind', 'sector', 'title', 'company', 'location', 'salary_min', 'salary_max', 'posted_at', 'closes_at'])
            ->selectRaw('substr(description, 1, 400) as description')
            ->when($kind !== '', fn ($query) => $query->where('kind', $kind))
            ->orderByDesc('posted_at')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        // which listings on this page the person has already saved, so the button says so
        $saved = $request->user()
            ? Application::where('user_id', $request->user()->id)->whereIn('job_listing_id', $jobs->pluck('id'))->pluck('job_listing_id')->all()
            : [];

        return view('jobs.index', compact('jobs', 'counts', 'kind', 'sector', 'q', 'where', 'saved'));
    }
}
