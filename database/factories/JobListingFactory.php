<?php

namespace Database\Factories;

use App\Models\JobListing;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JobListing> */
class JobListingFactory extends Factory
{
    protected $model = JobListing::class;

    public function definition(): array
    {
        return [
            'source' => 'reed',
            'external_id' => (string) fake()->unique()->numberBetween(1000, 999999),
            'kind' => 'graduate',
            'sector' => 'software',
            'title' => 'Graduate Software Engineer',
            'company' => 'Acme Ltd',
            'location' => 'Birmingham',
            'salary_min' => 28000,
            'salary_max' => 32000,
            'description' => 'Join our graduate scheme working with Python and SQL.',
            'url' => 'https://www.reed.co.uk/jobs/graduate-software-engineer/1',
            'posted_at' => now()->subDay(),
            'closes_at' => now()->addWeeks(2),
        ];
    }
}
