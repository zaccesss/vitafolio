<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\Endorsement;
use App\Models\Report;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.index', [
            'reports' => Report::with(['cv.user', 'endorsement.endorser'])->whereNull('resolved_at')->latest()->paginate(20),
            'stats' => [
                'Accounts' => User::count(),
                'CVs' => Cv::count(),
                'Public CVs' => Cv::listed()->count(),
                'Open reports' => Report::whereNull('resolved_at')->count(),
                'Suspended accounts' => User::whereNotNull('suspended_at')->count(),
            ],
            'hidden' => Cv::with('user')->whereNotNull('hidden_at')->latest('hidden_at')->limit(20)->get(),
            'hiddenEndorsements' => Endorsement::with(['cv', 'endorser'])->whereNotNull('hidden_at')->latest('hidden_at')->limit(20)->get(),
        ]);
    }

    public function hide(Cv $cv): RedirectResponse
    {
        $cv->forceFill(['hidden_at' => now()])->save();
        Audit::log('moderation.hide', ['admin' => auth()->id(), 'cv' => $cv->id]);
        $cv->reports()->whereNull('resolved_at')->update(['resolved_at' => now()]);

        return back()->with('status', __('“:title” is now hidden from everyone except its owner.', ['title' => $cv->title]));
    }

    public function restore(Cv $cv): RedirectResponse
    {
        $cv->forceFill(['hidden_at' => null])->save();
        Audit::log('moderation.restore', ['admin' => auth()->id(), 'cv' => $cv->id]);

        return back()->with('status', __('“:title” is visible again.', ['title' => $cv->title]));
    }

    /** hides one endorsement from everyone whatever its owner chose, then closes its reports */
    public function hideEndorsement(Endorsement $endorsement): RedirectResponse
    {
        $endorsement->forceFill(['hidden_at' => now()])->save();
        Audit::log('moderation.hide_endorsement', ['admin' => auth()->id(), 'endorsement' => $endorsement->id]);
        $endorsement->reports()->whereNull('resolved_at')->update(['resolved_at' => now()]);

        return back()->with('status', __('The endorsement by :name is now hidden.', ['name' => $endorsement->endorser->name]));
    }

    public function restoreEndorsement(Endorsement $endorsement): RedirectResponse
    {
        $endorsement->forceFill(['hidden_at' => null])->save();
        Audit::log('moderation.restore_endorsement', ['admin' => auth()->id(), 'endorsement' => $endorsement->id]);

        return back()->with('status', __('The endorsement by :name is no longer hidden. It shows if its CV owner has approved it.', ['name' => $endorsement->endorser->name]));
    }

    public function dismiss(Report $report): RedirectResponse
    {
        $report->forceFill(['resolved_at' => now()])->save();
        Audit::log('moderation.dismiss', ['admin' => auth()->id(), 'report' => $report->id]);

        return back()->with('status', __('Report dismissed.'));
    }

    /** removes a photo that breaks the terms without touching anything else on the account */
    public function removeAvatar(User $user): RedirectResponse
    {
        $user->forceFill(['avatar' => null, 'avatar_type' => null, 'avatar_version' => null])->save();
        Audit::log('moderation.remove_photo', ['admin' => auth()->id(), 'user' => $user->id]);

        return back()->with('status', __('The photo of :name has been removed.', ['name' => $user->name]));
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()) || $user->isAdmin(), 403, __('Admins cannot be suspended here.'));
        $suspending = ! $user->isSuspended();
        // a new remember token means a "keep me signed in" cookie cannot carry a suspended account on
        $user->forceFill(['suspended_at' => $suspending ? now() : null, 'remember_token' => Str::random(60)])->save();
        // sessions live in the database, so a suspension also signs the account out everywhere
        DB::table('sessions')->where('user_id', $user->id)->delete();
        Audit::log($suspending ? 'moderation.suspend' : 'moderation.reinstate', ['admin' => auth()->id(), 'user' => $user->id]);

        return back()->with('status', $user->isSuspended() ? __(':name has been suspended.', ['name' => $user->name]) : __(':name has been reinstated.', ['name' => $user->name]));
    }
}
