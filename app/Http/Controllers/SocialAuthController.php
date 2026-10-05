<?php

namespace App\Http\Controllers;

use App\Listeners\SendSecurityNotice;
use App\Models\Cv;
use App\Models\SocialAccount;
use App\Models\User;
use App\Support\Audit;
use App\Support\Images;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser as ProviderUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

/** sign in, sign up and account linking through google, github, microsoft and linkedin */
class SocialAuthController extends Controller
{
    /** providers whose keys are set, for the buttons on the sign-in pages */
    public static function enabled(): array
    {
        return array_filter(config('vitafolio.social'), fn (array $p) => filled(config('services.'.$p['driver'].'.client_id'))
            && filled(config('services.'.$p['driver'].'.client_secret')));
    }

    public function redirect(string $provider): RedirectResponse
    {
        $driver = $this->driver($provider);
        $scopes = $provider === 'microsoft' ? ['openid', 'profile', 'email', 'User.Read'] : [];

        /** @var AbstractProvider $provider every driver in use is an oauth 2 provider */
        $provider = Socialite::driver($driver);

        return $provider->scopes($scopes)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $driver = $this->driver($provider);
        try {
            /** @var ProviderUser $remote oauth 2 drivers return the abstract user with its raw claims */
            $remote = Socialite::driver($driver)->user();
        } catch (\Throwable $e) {
            // a cancelled consent screen or an expired state both land here
            return redirect()->route($request->user() ? 'settings.connected' : 'login')
                ->with('error', 'Signing in with '.config("vitafolio.social.$provider.label").' did not finish. Please try again.');
        }

        $linked = SocialAccount::where('provider', $provider)->where('provider_id', (string) $remote->getId())->first();

        // signed in already: this connects the provider to the current account
        if ($request->user()) {
            return $this->connect($request->user(), $provider, $remote, $linked);
        }

        if ($linked) {
            $this->remember($linked, $remote);

            return $this->signIn($request, $linked->user);
        }

        $email = Str::lower((string) $remote->getEmail());
        if ($email === '') {
            return redirect()->route('login')->with('error', config("vitafolio.social.$provider.label").' did not share an email address, so an account cannot be made from it.');
        }
        $verified = $this->emailVerified($provider, $remote);
        $existing = User::where('email', $email)->first();

        if ($existing) {
            if (! $verified) {
                return redirect()->route('login')->with('error', 'An account with this email already exists. Sign in with your password, then connect '.config("vitafolio.social.$provider.label").' from your account page.');
            }
            if (! $existing->hasVerifiedEmail()) {
                // nobody has proved they own this address yet, so whoever created the account
                // cannot keep a way in: the person the provider just verified is its owner now
                $this->takeOverUnverified($existing);
            }
            $this->link($existing, $provider, $remote);

            return $this->signIn($request, $existing);
        }

        $user = $this->createAccount($provider, $remote, $email, $verified);

        return $this->signIn($request, $user, 'Welcome to '.config('app.name').'. Your first CV is ready to edit.');
    }

    /** strips every sign-in method and session from an unverified account before a verified owner takes it */
    private function takeOverUnverified(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->socialAccounts()->delete();
            $user->passkeys()->delete();
            $user->forceFill([
                'password' => Hash::make(Str::random(64)),
                'has_password' => false,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => Str::random(60),
                'email_verified_at' => now(),
            ])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });
    }

    /** disconnects a provider; the account always keeps at least one way to sign in */
    public function disconnect(Request $request, string $provider): RedirectResponse
    {
        $user = $request->user();
        $others = $user->socialAccounts()->where('provider', '<>', $provider)->exists() || $user->has_password || $user->passkeys()->exists();
        if (! $others) {
            return redirect()->route('settings.connected')->with('error', 'Set a password or add a passkey first, so you can still sign in afterwards.');
        }
        $user->socialAccounts()->where('provider', $provider)->delete();
        $label = config("vitafolio.social.$provider.label");
        Audit::log('account.disconnected', ['user' => $user->id, 'provider' => $provider]);
        SendSecurityNotice::send($user, $label.' disconnected', $label.' can no longer be used to sign in to your account.');

        return redirect()->route('settings.connected')->with('status', $label.' has been disconnected.');
    }

    /** copies the provider's photo once, re-encoded like any upload; it is never loaded from their site */
    public function usePhoto(Request $request, string $provider): RedirectResponse
    {
        $this->driver($provider);
        $account = $request->user()->socialAccounts()->where('provider', $provider)->firstOrFail();
        $host = strtolower((string) parse_url((string) $account->avatar_url, PHP_URL_HOST));
        $allowed = collect(config("vitafolio.social.$provider.photo_hosts"))
            ->contains(fn (string $h) => $host === $h || str_ends_with($host, '.'.$h));
        abort_unless($allowed && str_starts_with((string) $account->avatar_url, 'https://'), 404);

        $back = redirect()->route('settings.connected');
        $response = rescue(fn () => Http::timeout(10)->withOptions(['allow_redirects' => false])->get($account->avatar_url), report: false);
        $bytes = $response?->successful() && strlen($response->body()) <= 5 * 1024 * 1024 ? $response->body() : null;
        $tmp = $bytes ? tempnam(sys_get_temp_dir(), 'photo') : null;
        $jpeg = $tmp && file_put_contents($tmp, $bytes) ? Images::squareJpeg($tmp, 600) : null;
        if ($tmp) {
            @unlink($tmp);
        }
        if ($jpeg === null) {
            return $back->with('error', 'That photo could not be copied. You can upload one on your profile instead.');
        }
        $request->user()->forceFill([
            'avatar' => $jpeg,
            'avatar_type' => 'image/jpeg',
            'avatar_version' => base_convert((string) now()->getTimestampMs(), 10, 36),
        ])->save();

        return $back->with('status', 'Your '.config("vitafolio.social.$provider.label").' photo is now your profile photo.');
    }

    /** the route name maps to a socialite driver; anything unknown or switched off is a 404 */
    private function driver(string $provider): string
    {
        abort_unless(array_key_exists($provider, self::enabled()), 404);

        return config("vitafolio.social.$provider.driver");
    }

    /**
     * google and linkedin say outright whether the address is verified. github only hands over an
     * address it has verified. microsoft gives no such promise, so it is never trusted
     */
    private function emailVerified(string $provider, ProviderUser $remote): bool
    {
        if (! config("vitafolio.social.$provider.trusts_email")) {
            return false;
        }
        $raw = $remote->getRaw();

        return match ($provider) {
            'google', 'linkedin' => filter_var($raw['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'github' => true,
            default => false,
        };
    }

    private function connect(User $user, string $provider, ProviderUser $remote, ?SocialAccount $linked): RedirectResponse
    {
        $back = redirect()->route('settings.connected');
        $label = config("vitafolio.social.$provider.label");
        if ($linked && $linked->user_id !== $user->id) {
            return $back->with('error', 'That '.$label.' account is already connected to a different '.config('app.name').' account.');
        }
        $this->link($user, $provider, $remote);
        Audit::log('account.connected', ['user' => $user->id, 'provider' => $provider]);
        SendSecurityNotice::send($user, $label.' connected', $label.' can now be used to sign in to your account.');

        return $back->with('status', $label.' is now connected. You can use it to sign in.');
    }

    private function link(User $user, string $provider, ProviderUser $remote): void
    {
        $account = SocialAccount::firstOrNew(['user_id' => $user->id, 'provider' => $provider]);
        $account->forceFill(['user_id' => $user->id, 'provider' => $provider, 'provider_id' => (string) $remote->getId()]);
        $this->remember($account, $remote);
    }

    /** refreshes the details shown on the account page each time the provider is used */
    private function remember(SocialAccount $account, ProviderUser $remote): void
    {
        $account->fill(['email' => Str::limit((string) $remote->getEmail(), 254, ''), 'avatar_url' => Str::limit((string) $remote->getAvatar(), 500, '') ?: null])->save();
    }

    private function createAccount(string $provider, ProviderUser $remote, string $email, bool $verified): User
    {
        $name = trim((string) ($remote->getName() ?: $remote->getNickname())) ?: Str::before($email, '@');

        // the same first cv as a normal sign-up, so the editor is ready straight away
        return DB::transaction(function () use ($provider, $remote, $email, $verified, $name) {
            $user = User::create([
                'name' => Str::limit($name, 100, ''),
                'email' => $email,
                // unusable until the owner sets one; has_password tells the account page to offer that
                'password' => Hash::make(Str::random(64)),
                'handle' => User::suggestHandle($name),
            ]);
            $user->forceFill(['has_password' => false, 'email_verified_at' => $verified ? now() : null])->save();
            $user->cvs()->create(['title' => 'My CV', 'slug' => Cv::uniqueSlug($user->name)]);
            $this->link($user, $provider, $remote);

            return $user;
        });
    }

    /** two-factor accounts still answer their challenge, the same as a password sign-in */
    private function signIn(Request $request, User $user, ?string $welcome = null): RedirectResponse
    {
        if ($user->isSuspended()) {
            return redirect()->route('login')->with('error', 'This account cannot sign in. Contact us if you think this is a mistake.');
        }
        if ($user->two_factor_confirmed_at) {
            $request->session()->put(['login.id' => $user->id, 'login.remember' => true]);

            return redirect()->route('two-factor.login');
        }
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('status', $welcome);
    }
}
