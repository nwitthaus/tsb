<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Round;
use App\Models\Score;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'name' => 'Tuesday Trivia at Joe\'s',
            'slug' => 'tuesday-trivia',
        ]);

        $teams = collect([
            ['name' => 'Quizly Bears', 'table_number' => 3, 'sort_order' => 1],
            ['name' => 'Brain Stormers', 'table_number' => 7, 'sort_order' => 2],
            ['name' => null, 'table_number' => 12, 'sort_order' => 3],
        ])->map(fn ($data) => Team::factory()->create([...$data, 'event_id' => $event->id]));

        $rounds = collect([1, 2, 3])->map(
            fn ($order) => Round::factory()->create(['event_id' => $event->id, 'sort_order' => $order])
        );

        $sampleScores = [
            [7.5, 8.0, null],
            [6.0, 9.0, null],
            [5.0, null, null],
        ];

        $teams->each(function ($team, $teamIndex) use ($rounds, $sampleScores) {
            $rounds->each(function ($round, $roundIndex) use ($team, $teamIndex, $sampleScores) {
                $value = $sampleScores[$teamIndex][$roundIndex];
                if ($value !== null) {
                    Score::factory()->create([
                        'team_id' => $team->id,
                        'round_id' => $round->id,
                        'value' => $value,
                    ]);
                }
            });
        });
    }
}
