<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkeys;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * register any application services.
     */
    public function register(): void
    {
        // signing out also asks the browser to drop cached pages and site storage, for shared devices
        $this->app->singleton(LogoutResponse::class, fn () => new class implements LogoutResponse
        {
            public function toResponse($request)
            {
                $response = $request->wantsJson() ? new JsonResponse('', 204) : redirect(config('fortify.redirects.logout') ?? '/');

                return $response->withHeaders(['Clear-Site-Data' => '"cache", "storage"']);
            }
        });

        // a reset request answers the same way whether or not the address has an account, so the
        // form cannot be used to find out which addresses are registered
        $this->app->singleton(FailedPasswordResetLinkRequestResponse::class, fn () => new class implements FailedPasswordResetLinkRequestResponse
        {
            public function toResponse($request)
            {
                return $request->wantsJson()
                    ? new JsonResponse(['message' => trans('passwords.sent')], 200)
                    : back()->with('status', trans('passwords.sent'));
            }
        });
    }

    /**
     * bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // suspended accounts are refused at sign-in with the same message as a wrong password
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', Str::lower((string) $request->input('email')))->first();

            return $user && ! $user->isSuspended() && Hash::check((string) $request->input('password'), $user->password)
                ? $user : null;
        });

        // passkey sign-in has its own controller, so suspended accounts are refused here as well
        Passkeys::authorizeLoginUsing(fn (Request $request, PasskeyUser $user) => $user instanceof User && ! $user->isSuspended());

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));

        // limits live in the database cache, so clearing cookies or restarting the server
        // does not reset them; the per-ip limit also slows spraying many accounts at once
        RateLimiter::for('login', function (Request $request) {
            $email = Str::transliterate(Str::lower((string) $request->input(Fortify::username())));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perHour(20)->by('email:'.$email),
                Limit::perHour(60)->by('ip:'.$request->ip()),
            ];
        });

        // each passkey attempt is a signed challenge, so this only stops scripted floods
        RateLimiter::for('passkeys', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
