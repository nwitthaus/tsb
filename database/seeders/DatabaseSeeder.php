<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Round;
use App\Models\Score;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'name' => 'Nick Witthaus',
            'email' => 'nick@witthaus.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $organization = Organization::factory()->create([
            'name' => "Joe's Bar Trivia",
            'slug' => 'joes-bar',
        ]);

        $organization->users()->attach($admin, ['role' => OrganizationRole::Owner->value]);
        $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

        $event = Event::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Tuesday Trivia at Joe\'s',
            'slug' => 'tuesday-trivia',
            'starts_at' => now()->addHours(2),
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
