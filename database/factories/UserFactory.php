<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** hashed once and reused, because bcrypt is deliberately slow */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('a-long-pass-123'),
            'remember_token' => Str::random(10),
            'handle' => 'user-'.Str::lower(Str::random(10)),
            'profile_visibility' => 'public',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->forceFill(['role' => 'admin'])->save());
    }

    public function suspended(): static
    {
        return $this->afterCreating(fn (User $user) => $user->forceFill(['suspended_at' => now()])->save());
    }
}
