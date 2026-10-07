<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(Request $request): View
    {
        $kind = array_key_exists((string) $request->query('kind'), JobListing::KINDS) ? (string) $request->query('kind') : '';
        $q = trim(mb_substr((string) $request->query('q'), 0, 100));
        $where = trim(mb_substr((string) $request->query('where'), 0, 100));

        $jobs = JobListing::query()->open()
            ->when($kind !== '', fn ($query) => $query->where('kind', $kind))
            ->when($q !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('title', 'like', "%{$q}%")->orWhere('company', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%")))
            ->when($where !== '', fn ($query) => $query->where('location', 'like', "%{$where}%"))
            ->orderByDesc('posted_at')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('jobs.index', compact('jobs', 'kind', 'q', 'where'));
    }
}
