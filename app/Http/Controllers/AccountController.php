<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\User;
use App\Support\JsonResume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings.account', ['user' => $request->user()]);
    }

    /** for accounts made through social sign-in, which have never had a password of their own */
    public function setPassword(Request $request): RedirectResponse
    {
        abort_if($request->user()->has_password, 404);
        $request->validateWithBag('setPassword', ['password' => ['required', 'string', Password::default(), 'confirmed']]);
        $request->user()->forceFill(['password' => Hash::make($request->input('password')), 'has_password' => true])->save();

        return redirect()->route('settings.security')->with('status', __('Password set. You can now sign in with your email address and this password too.'));
    }

    /** lands back on the passkeys section once the password has been confirmed or after one was added */
    public function passkeys(Request $request): RedirectResponse
    {
        $redirect = redirect()->route('settings.passkeys');

        return $request->boolean('added') ? $redirect->with('status', 'passkey-registered') : $redirect;
    }

    /** ends every other session; the password is checked so a borrowed device cannot do it */
    public function endOtherSessions(Request $request): RedirectResponse
    {
        $request->validateWithBag('sessions', ['current_password' => ['required', 'current_password']]);
        Auth::logoutOtherDevices($request->input('current_password'));
        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '<>', $request->session()->getId())
            ->delete();

        return redirect()->route('settings.sessions')->with('status', 'other-sessions-ended');
    }

    /** the signed-in devices page, with every active session and which one is this browser */
    public function sessions(Request $request): View
    {
        return view('settings.sessions', ['user' => $request->user(), 'sessions' => self::sessionsFor($request->user()), 'current' => $request->session()->getId()]);
    }

    /** signs out one device; the current one is left alone, it has its own sign out */
    public function endSession(Request $request, string $id): RedirectResponse
    {
        abort_if($id === $request->session()->getId(), 404);
        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', $id)->delete();

        return redirect()->route('settings.sessions')->with('status', __('That device has been signed out.'));
    }

    /** active sessions with the browser description made readable, newest first */
    public static function sessionsFor(User $user): Collection
    {
        return DB::table('sessions')->where('user_id', $user->id)->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(function ($row) {
                $ua = (string) $row->user_agent;
                $browser = collect(['Edg/' => 'Edge', 'OPR/' => 'Opera', 'Firefox/' => 'Firefox', 'Chrome/' => 'Chrome', 'Safari/' => 'Safari'])
                    ->first(fn ($name, $needle) => str_contains($ua, $needle));
                $os = collect(['iPhone' => 'iPhone', 'iPad' => 'iPad', 'Android' => 'Android', 'Mac OS' => 'macOS', 'Windows' => 'Windows', 'Linux' => 'Linux'])
                    ->first(fn ($name, $needle) => str_contains($ua, $needle));
                $row->device = match (true) {
                    $browser && $os => __(':browser on :system', ['browser' => $browser, 'system' => $os]),
                    (bool) $browser => __(':browser on an unknown device', ['browser' => $browser]),
                    (bool) $os => __('A browser on :system', ['system' => $os]),
                    default => __('A browser on an unknown device'),
                };
                $row->last_active = Carbon::createFromTimestamp($row->last_activity);

                return $row;
            });
    }

    /** everything stored about the account, as a download, for the right of access */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user()->load(['cvs.tags', 'cvs.document', 'cvs.projects', 'cvs.endorsements.endorser', 'socialAccounts', 'passkeys', 'endorsementsWritten.cv']);

        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'account' => $user->only(['name', 'email', 'email_verified_at', 'created_at']),
            'profile' => $user->only(['handle', 'pronouns', 'headline', 'bio', 'location', 'university', 'availability', 'links', 'profile_visibility']),
            'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
            'photo' => $user->hasAvatar() ? ['type' => $user->avatar_type, 'version' => $user->avatar_version] : null,
            'connected_accounts' => $user->socialAccounts->map(fn ($a) => $a->only(['provider', 'email', 'created_at'])),
            'passkeys' => $user->passkeys->map(fn ($p) => $p->only(['name', 'created_at', 'last_used_at'])),
            'previous_handles' => DB::table('handle_history')->where('user_id', $user->id)->get(['handle', 'released_at']),
            'signed_in_devices' => self::sessionsFor($user)->map(fn ($s) => ['device' => $s->device, 'ip_address' => $s->ip_address, 'last_active' => $s->last_active]),
            'reports_made' => [],
            // what this account wrote about other people's cvs; the other person's own details stay out
            'support_tickets' => SupportTicket::where('user_id', $user->id)->with('messages')->get()->map(fn ($t) => [
                'reference' => $t->reference(), 'subject' => $t->subject, 'category' => $t->category, 'status' => $t->status,
                'opened' => $t->created_at?->toIso8601String(),
                'messages' => $t->messages->map(fn ($m) => ['from' => $m->from_staff ? 'support' : 'you', 'sent' => $m->created_at?->toIso8601String(), 'text' => $m->body])->all(),
            ])->all(),
            'endorsements_written' => $user->endorsementsWritten->map(fn ($e) => ['cv_address' => $e->cv ? route('cv.show', $e->cv) : null] + $e->exportFields()),
            'cvs' => $user->cvs->map(fn ($cv) => [
                'resume' => JsonResume::export($cv->setRelation('user', $user)),
                'settings' => $cv->only(['title', 'slug', 'visibility', 'show_email', 'theme', 'accent', 'font', 'section_order', 'view_count', 'created_at', 'updated_at']),
                'latex_source' => $cv->latex_source,
                'cover_letter' => $cv->hasCoverLetter() ? $cv->only(['letter_to', 'cover_letter']) : null,
                'uploaded_file' => $cv->document?->only(['filename', 'mime', 'size']),
                'endorsements_received' => $cv->endorsements->map(fn ($e) => ['from' => $e->endorser->name] + $e->exportFields()),
            ]),
        ], 200, ['Content-Disposition' => 'attachment; filename="vitafolio-my-data.json"'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** removes the account, every cv, file, picture, view record and endorsement written or received through the cascades */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['confirm' => ['required', 'in:DELETE']], ['confirm.in' => __('Type DELETE in capitals to confirm.')]);

        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', __('Your account and all your CVs have been permanently deleted.'));
    }
}
