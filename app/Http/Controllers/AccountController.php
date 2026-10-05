<?php

namespace App\Http\Controllers;

use App\Support\JsonResume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account', ['user' => $request->user()]);
    }

    /** for accounts made through social sign-in, which have never had a password of their own */
    public function setPassword(Request $request): RedirectResponse
    {
        abort_if($request->user()->has_password, 404);
        $request->validateWithBag('setPassword', ['password' => ['required', 'string', Password::default(), 'confirmed']]);
        $request->user()->forceFill(['password' => Hash::make($request->input('password')), 'has_password' => true])->save();

        return redirect()->to(route('account').'#password-title')->with('status', 'Password set. You can now sign in with your email address and this password too.');
    }

    /** lands back on the passkeys section once the password has been confirmed or after one was added */
    public function passkeys(Request $request): RedirectResponse
    {
        $redirect = redirect()->to(route('account').'#passkeys');

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

        return redirect()->route('account')->with('status', 'other-sessions-ended');
    }

    /** everything stored about the account, as a download, for the right of access */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user()->load(['cvs.tags', 'cvs.document', 'cvs.projects']);

        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'account' => $user->only(['name', 'email', 'email_verified_at', 'created_at']),
            'profile' => $user->only(['handle', 'pronouns', 'headline', 'bio', 'location', 'university', 'availability', 'links', 'profile_visibility']),
            'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
            'cvs' => $user->cvs->map(fn ($cv) => [
                'resume' => JsonResume::export($cv->setRelation('user', $user)),
                'settings' => $cv->only(['title', 'slug', 'visibility', 'show_email', 'theme', 'accent', 'font', 'section_order', 'view_count', 'created_at', 'updated_at']),
                'latex_source' => $cv->latex_source,
                'uploaded_file' => $cv->document?->only(['filename', 'mime', 'size']),
            ]),
        ], 200, ['Content-Disposition' => 'attachment; filename="vitafolio-my-data.json"'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** removes the account and every cv, file, picture and view record through the cascades */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['confirm' => ['required', 'in:DELETE']], ['confirm.in' => 'Type DELETE in capitals to confirm.']);

        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Your account and all your CVs have been permanently deleted.');
    }
}
