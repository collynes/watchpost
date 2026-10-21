<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DigestFactory extends Factory
{
    public function definition(): array
    {
        return ['report_date' => now()->subDay()->toDateString(), 'timezone' => 'Europe/Amsterdam', 'window_start' => now()->subDay()->startOfDay(), 'window_end' => now()->startOfDay(), 'snapshot' => ['sources' => [], 'metrics' => [], 'alerts' => [], 'total_events' => 0]];
    }
}
