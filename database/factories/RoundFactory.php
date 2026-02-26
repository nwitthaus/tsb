<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Round>
 */
class RoundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'sort_order' => 1,
        ];
    }
}
