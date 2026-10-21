<?php

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        return ['source_id' => Source::factory(), 'source_event_id' => fake()->uuid(), 'payload_hash' => hash('sha256', fake()->uuid()), 'event_type' => 'auth.login_failed', 'severity' => 3, 'count' => 1, 'occurred_at' => now(), 'received_at' => now()];
    }
}
