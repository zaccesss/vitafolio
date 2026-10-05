<?php

namespace App\Http\Controllers;

use App\Support\Analytics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $days = (int) $request->query('days', 30);
        $days = in_array($days, Analytics::RANGES, true) ? $days : 30;

        return view('analytics', Analytics::forUser($request->user(), $days));
    }
}
