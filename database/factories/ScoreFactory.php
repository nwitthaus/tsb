<?php

namespace Database\Factories;

use App\Models\Round;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Score>
 */
class ScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'round_id' => Round::factory(),
            'value' => fake()->randomFloat(1, 0, 10),
        ];
    }
}
