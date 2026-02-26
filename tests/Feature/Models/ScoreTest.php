<?php

use App\Models\Event;
use App\Models\Round;
use App\Models\Score;
use App\Models\Team;

test('score belongs to a team and round', function () {
    $score = Score::factory()->create();

    expect($score->team)->toBeInstanceOf(Team::class)
        ->and($score->round)->toBeInstanceOf(Round::class);
});

test('team_id and round_id combination is unique', function () {
    $event = Event::factory()->create();
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Score::factory()->create(['team_id' => $team->id, 'round_id' => $round->id, 'value' => 5]);

    expect(fn () => Score::factory()->create([
        'team_id' => $team->id,
        'round_id' => $round->id,
        'value' => 8,
    ]))->toThrow(Exception::class);
});

test('deleting a team cascades to scores', function () {
    $score = Score::factory()->create();
    $teamId = $score->team_id;

    $score->team->forceDelete();

    expect(Score::where('team_id', $teamId)->count())->toBe(0);
});

test('deleting a round cascades to scores', function () {
    $score = Score::factory()->create();
    $roundId = $score->round_id;

    $score->round->delete();

    expect(Score::where('round_id', $roundId)->count())->toBe(0);
});
