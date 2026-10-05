<?php

namespace Database\Factories;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cv>
 */
class CvFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => 'Software roles',
            'slug' => 'cv-'.Str::lower(Str::random(10)),
            'visibility' => 'public',
            'profile' => 'A short profile.',
        ];
    }

    public function visibility(string $visibility): static
    {
        return $this->state(fn () => ['visibility' => $visibility]);
    }
}
