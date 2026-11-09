<?php

namespace App\Listeners;

use App\Mail\SecurityNotice;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Passkeys\Events\PasskeyDeleted;
use Laravel\Passkeys\Events\PasskeyRegistered;

/**
 * every change to how an account signs in is emailed to its owner and written to the audit log.
 * a sign-in from a device the account has not used before is reported the same way
 */
class SendSecurityNotice
{
    /** the cookie that lets a browser be recognised after its sessions have been cleared away */
    public const DEVICE_COOKIE = 'known_device';

    /** 400 days is the longest a browser keeps a cookie */
    private const DEVICE_COOKIE_MINUTES = 400 * 24 * 60;

    /** accounts one browser remembers, so a shared computer cannot grow the cookie without limit */
    private const DEVICE_COOKIE_ACCOUNTS = 5;

    /** a sign-in this soon after sign-up is the new account's owner, who has nothing to be warned about */
    private const NEW_ACCOUNT_MINUTES = 10;

    /** @var array<class-string, array{string, string}> event => [headline, detail] */
    private const NOTICES = [
        PasswordReset::class => ['Password reset', 'Your password was reset using a link sent to your email address. Every other device has been signed out.'],
        PasswordUpdatedViaController::class => ['Password changed', 'Your password was changed from your settings. Every other device has been signed out.'],
        TwoFactorAuthenticationConfirmed::class => ['Two-factor authentication turned on', 'Signing in now needs a code from your authenticator app as well as your password.'],
        TwoFactorAuthenticationDisabled::class => ['Two-factor authentication turned off', 'Signing in no longer needs a code from your authenticator app.'],
        RecoveryCodesGenerated::class => ['New recovery codes', 'New two-factor recovery codes were created. The old ones no longer work.'],
        PasskeyRegistered::class => ['Passkey added', 'A passkey was added to your account. It can sign in without your password.'],
        PasskeyDeleted::class => ['Passkey removed', 'A passkey was removed from your account.'],
    ];

    public function handle(object $event): void
    {
        $user = $event->user ?? null;
        if (! $user instanceof User) {
            if ($event instanceof Failed) {
                Audit::log('login.failed', ['email' => $event->credentials['email'] ?? null]);
            } elseif ($event instanceof Lockout) {
                Audit::log('login.lockout', ['email' => $event->request->input('email')]);
            }

            return;
        }

        if (isset(self::NOTICES[$event::class])) {
            [$headline, $detail] = self::NOTICES[$event::class];
            Audit::log('account.'.strtolower(str_replace(' ', '_', $headline)), ['user' => $user->id]);
            self::send($user, $headline, $detail);

            return;
        }

        match (true) {
            $event instanceof Login => $this->login($user, $event->guard),
            $event instanceof Logout => Audit::log('logout', ['user' => $user->id]),
            $event instanceof TwoFactorAuthenticationFailed => Audit::log('two_factor.failed', ['user' => $user->id]),
            $event instanceof Failed => Audit::log('login.failed', ['user' => $user->id]),
            default => null,
        };
    }

    /** @param  array<string, string>  $replace */
    public static function send(User $user, string $headline, string $detail, array $replace = []): void
    {
        // sent to the account itself, so it arrives in the language the owner chose
        rescue(fn () => Mail::to($user)->send(new SecurityNotice($headline, $detail, $replace)), report: false);
    }

    /** a browser the account has not signed in from before gets a notice; known ones only get logged */
    private function login(User $user, string $guard): void
    {
        $request = request();
        $auth = Auth::guard($guard);

        // the framework fires Login when a remember-me cookie restores an expired session. Nobody
        // signed in: the same browser came back, so a notice here would arrive on an ordinary visit
        if ($auth instanceof SessionGuard && $auth->viaRemember()) {
            Audit::log('login.restored', ['user' => $user->id]);
            $this->rememberDevice($user);

            return;
        }

        $agent = (string) $request->userAgent();
        $known = in_array($user->id, $this->rememberedAccounts(), true)
            || DB::table('sessions')->where('user_id', $user->id)
                ->where('user_agent', $agent)->where('id', '<>', $request->session()->getId())->exists();
        Audit::log('login', ['user' => $user->id, 'new_device' => ! $known]);
        $this->rememberDevice($user);

        if (! $known && ! $user->created_at?->gt(now()->subMinutes(self::NEW_ACCOUNT_MINUTES))) {
            self::send($user, 'New sign-in', 'Your account was signed in from a browser it has not used before: :agent.', ['agent' => $agent ?: 'unknown browser']);
        }
    }

    /** @return list<int> */
    private function rememberedAccounts(): array
    {
        $ids = json_decode((string) request()->cookie(self::DEVICE_COOKIE), true);

        return is_array($ids) ? array_values(array_filter($ids, 'is_int')) : [];
    }

    private function rememberDevice(User $user): void
    {
        $accounts = array_values(array_unique([$user->id, ...$this->rememberedAccounts()]));

        // encrypted by the web middleware like every other cookie, so it cannot be forged to hide a sign-in
        Cookie::queue(Cookie::make(
            self::DEVICE_COOKIE,
            (string) json_encode(array_slice($accounts, 0, self::DEVICE_COOKIE_ACCOUNTS)),
            self::DEVICE_COOKIE_MINUTES,
            secure: config('session.secure'),
            httpOnly: true,
            sameSite: 'lax',
        ));
    }
}
