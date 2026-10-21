<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertFactory extends Factory
{
    public function definition(): array
    {
        return ['event_id' => Event::factory()->state(['severity' => 2]), 'severity' => 2, 'status' => 'open'];
    }
}
