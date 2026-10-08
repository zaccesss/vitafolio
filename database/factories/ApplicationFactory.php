<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Application> */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => 'Software Engineering Intern',
            'company' => 'Acme',
            'location' => 'London',
            'url' => 'https://example.com/jobs/1',
            'status' => 'saved',
        ];
    }
}
