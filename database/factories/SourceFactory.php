<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SourceFactory extends Factory
{
    public function definition(): array
    {
        return ['id' => fake()->unique()->slug(2), 'name' => fake()->company(), 'token_hash' => hash('sha256', 'test-source-token'), 'enabled' => true];
    }
}
