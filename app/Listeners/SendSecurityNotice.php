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
            $event instanceof Login => $this->login($user),
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
    private function login(User $user): void
    {
        $request = request();
        $agent = (string) $request->userAgent();
        $known = DB::table('sessions')->where('user_id', $user->id)
            ->where('user_agent', $agent)->where('id', '<>', $request->session()->getId())->exists();
        Audit::log('login', ['user' => $user->id, 'new_device' => ! $known]);
        if (! $known) {
            self::send($user, 'New sign-in', 'Your account was signed in from a browser it has not used before: :agent.', ['agent' => $agent ?: 'unknown browser']);
        }
    }
}
