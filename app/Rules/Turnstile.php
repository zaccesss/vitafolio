<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/** cloudflare turnstile check; skipped when no keys are configured, such as in local development */
class Turnstile implements ValidationRule
{
    // implicit so the check still runs when the field is left out of the request entirely; otherwise Laravel skips
    // the rule for a missing field and the form could be posted without any challenge
    public bool $implicit = true;

    public static function enabled(): bool
    {
        return filled(config('vitafolio.turnstile.site_key')) && filled(config('vitafolio.turnstile.secret'));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::enabled()) {
            return;
        }
        $passed = is_string($value) && $value !== '' && rescue(fn () => Http::asForm()->timeout(5)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('vitafolio.turnstile.secret'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ])->json('success') === true, false);

        if (! $passed) {
            $fail(__('Please complete the security check and try again.'));
        }
    }
}
