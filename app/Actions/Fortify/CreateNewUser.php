<?php

namespace App\Actions\Fortify;

use App\Models\Cv;
use App\Models\User;
use App\Rules\Turnstile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:254', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'terms' => ['accepted'],
            'cf-turnstile-response' => [new Turnstile],
        ], [
            'terms.accepted' => 'Please agree to the terms and privacy policy to create an account.',
        ])->validate();

        // a first empty cv is created with the account, so the editor is ready straight away
        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => trim($input['name']),
                'email' => Str::lower(trim($input['email'])),
                'password' => Hash::make($input['password']),
                'handle' => User::suggestHandle($input['name']),
            ]);
            $user->cvs()->create(['title' => 'My CV', 'slug' => Cv::uniqueSlug($user->name), 'visibility' => 'private']);

            return $user;
        });
    }
}
