<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupportTicket> */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'category' => 'account',
            'subject' => 'I cannot sign in',
            'status' => 'open',
            'token_hash' => hash('sha256', 'visitor-token'),
            'last_activity_at' => now(),
        ];
    }
}
