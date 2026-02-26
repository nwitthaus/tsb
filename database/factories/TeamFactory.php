<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->unique()->words(2, true),
            'table_number' => fake()->numberBetween(1, 30),
            'sort_order' => 0,
        ];
    }

    public function nameOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'table_number' => null,
        ]);
    }

    public function tableOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
        ]);
    }
}
