<?php

namespace App\Http\Controllers;

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

        $jobs = JobListing::query()->open()
            ->when($kind !== '', fn ($query) => $query->where('kind', $kind))
            ->when($sector !== '', fn ($query) => $query->where('sector', $sector))
            // the search box looks for a job title or employer only, so an advert's small print never matches
            ->when($q !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('title', 'like', "%{$q}%")->orWhere('company', 'like', "%{$q}%")))
            ->when($where !== '', fn ($query) => $query->where('location', 'like', "%{$where}%"))
            ->orderByDesc('posted_at')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('jobs.index', compact('jobs', 'kind', 'sector', 'q', 'where'));
    }
}
