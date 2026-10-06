<?php

namespace App\Providers;

use App\Listeners\SendSecurityNotice;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Passkeys\Events\PasskeyDeleted;
use Laravel\Passkeys\Events\PasskeyRegistered;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // every sign-in change is emailed to the account holder and written to the audit log
        foreach ([
            PasswordReset::class, PasswordUpdatedViaController::class, TwoFactorAuthenticationConfirmed::class,
            TwoFactorAuthenticationDisabled::class, RecoveryCodesGenerated::class, PasskeyRegistered::class,
            PasskeyDeleted::class, Login::class, Logout::class, Failed::class, Lockout::class, TwoFactorAuthenticationFailed::class,
        ] as $event) {
            Event::listen($event, SendSecurityNotice::class);
        }

        // the health check fails when the database cannot answer, not only when php boots
        Event::listen(DiagnosingHealth::class, fn () => DB::select('select 1'));

        // microsoft sign-in comes from the socialite providers package rather than socialite itself
        Event::listen(fn (SocialiteWasCalled $event) => $event->extendSocialite('microsoft', MicrosoftProvider::class));

        // tls ends at the host's proxy, so generated links are forced to https in production
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
            // every generated link uses the configured address, never the host a request claimed
            URL::forceRootUrl((string) config('app.url'));
        }

        // bcrypt ignores everything past 72 bytes. uncompromised() checks the password
        // against known breaches using k-anonymity, so only a hash prefix leaves the server
        Password::defaults(fn () => Password::min(10)->max(72)->letters()->numbers()->uncompromised());

        Gate::define('admin', fn (User $user) => $user->isAdmin());

        RateLimiter::for('reports', fn (Request $request) => Limit::perHour(5)->by($request->ip()));
        RateLimiter::for('messages', fn (Request $request) => [
            Limit::perHour(5)->by('ip:'.$request->ip()),
            Limit::perDay(20)->by('ip-day:'.$request->ip()),
        ]);
        // endorsements come from signed-in accounts, so the limit follows the account as well as the address
        RateLimiter::for('endorsements', fn (Request $request) => [
            Limit::perHour(10)->by('user:'.$request->user()?->id),
            Limit::perHour(20)->by('ip:'.$request->ip()),
        ]);
        RateLimiter::for('pdf', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(10)->by((string) $request->user()?->id));
    }
}
