<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
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
        // microsoft sign-in comes from the socialite providers package rather than socialite itself
        Event::listen(fn (SocialiteWasCalled $event) => $event->extendSocialite('microsoft', MicrosoftProvider::class));

        // tls ends at the host's proxy, so generated links are forced to https in production
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
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
        RateLimiter::for('pdf', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(10)->by((string) $request->user()?->id));
    }
}
