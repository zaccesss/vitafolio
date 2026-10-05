<?php

namespace App\Http\Controllers;

use App\Support\ViewRecorder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $cvs = $request->user()->cvs()->with(['tags', 'document'])->get();
        // analytics follow the cv picked in the list, defaulting to the most recently edited
        $selected = $cvs->firstWhere('slug', $request->query('cv')) ?? $cvs->first();
        $series = $selected ? ViewRecorder::series($selected) : [];

        return view('dashboard', [
            'cvs' => $cvs,
            'selected' => $selected,
            'series' => $series,
            'recentViews' => array_sum($series),
            'referrers' => $selected ? ViewRecorder::referrers($selected) : [],
            'canCreate' => $cvs->count() < (int) config('vitafolio.max_cvs_per_user'),
        ]);
    }
}
