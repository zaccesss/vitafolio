<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobListing;
use App\Support\Jobs\Sector;
use Illuminate\Http\Request;
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
        $counts = (clone $filtered)->selectRaw('kind, count(*) as total')->groupBy('kind')->pluck('total', 'kind');

        $jobs = (clone $filtered)
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
