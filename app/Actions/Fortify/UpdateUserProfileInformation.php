<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $changingEmail = isset($input['email']) && Str::lower(trim($input['email'])) !== $user->email;
        Validator::make($input, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:254', Rule::unique('users')->ignore($user->id)],
            // a borrowed session cannot move the account to another address without the password
            'current_password' => $changingEmail && $user->has_password ? ['required', 'current_password'] : ['nullable'],
        ], [
            'current_password.required' => 'Enter your password to change your email address.',
        ])->validateWithBag('updateProfileInformation');

        // every account verifies its email, so a new address has to be verified again
        if ($changingEmail) {
            $previous = $user->email;
            $this->updateVerifiedUser($user, $input);
            // the old address hears about the change, so a takeover cannot go unnoticed
            rescue(fn () => Mail::raw(
                'The email address on your '.config('app.name')." account was changed from {$previous} to {$input['email']}.\n\nIf that was not you, reset your password straight away at ".route('password.request').' and contact us.',
                fn ($message) => $message->to($previous)->subject('Your '.config('app.name').' email address was changed')
            ));
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => Str::lower(trim($input['email'])),
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
