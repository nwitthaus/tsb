<?php

use App\Models\Event;
use App\Models\Round;
use App\Models\Score;
use App\Models\Team;
use App\Models\User;

test('host can save a score', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $round->id, '7.5');

    expect(Score::first()->value)->toBe('7.5');
});

test('host can update an existing score', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
    Score::factory()->create(['team_id' => $team->id, 'round_id' => $round->id, 'value' => 5]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $round->id, '8');

    expect(Score::first()->value)->toBe('8.0')
        ->and(Score::count())->toBe(1);
});

test('clearing a score deletes it', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
    Score::factory()->create(['team_id' => $team->id, 'round_id' => $round->id, 'value' => 5]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $round->id, '');

    expect(Score::count())->toBe(0);
});

test('score must be non-negative', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $round->id, '-1')
        ->assertHasErrors();
});

test('score cannot exceed 999.9', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $round->id, '1000')
        ->assertHasErrors();
});

test('score must be numeric', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $round->id, 'abc')
        ->assertHasErrors();
});

test('cannot save score for team not in this event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $otherEvent = Event::factory()->create(['user_id' => $user->id, 'ended_at' => now()]);
    $otherTeam = Team::factory()->create(['event_id' => $otherEvent->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $otherTeam->id, $round->id, '5');

    expect(Score::count())->toBe(0);
});

test('cannot save score for round not in this event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $otherEvent = Event::factory()->create(['user_id' => $user->id, 'ended_at' => now()]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $otherRound = Round::factory()->create(['event_id' => $otherEvent->id, 'sort_order' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $otherRound->id, '5');

    expect(Score::count())->toBe(0);
});

test('cannot save score on ended event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->ended()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $round->id, '5');

    expect(Score::count())->toBe(0);
});
