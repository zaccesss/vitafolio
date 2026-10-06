<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $cvs = $user->cvs()->with(['tags', 'document'])
            ->withCount(['endorsements as pending_endorsements' => fn ($q) => $q->where('status', 'pending')->whereNull('hidden_at')])->get();
        $recentViews = DB::table('cv_views')->whereIn('cv_id', $cvs->pluck('id'))->whereIn('kind', ['view', 'qr'])
            ->where('viewed_on', '>', now()->subDays(30)->toDateString())->count();

        return view('dashboard', [
            'cvs' => $cvs,
            'recentViews' => $recentViews,
            'canCreate' => $cvs->count() < (int) config('vitafolio.max_cvs_per_user'),
        ]);
    }
}
