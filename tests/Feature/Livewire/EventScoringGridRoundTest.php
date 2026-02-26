<?php

use App\Models\Event;
use App\Models\Round;
use App\Models\Score;
use App\Models\Team;
use App\Models\User;

test('host can add a round', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('addRound');

    expect($event->rounds()->count())->toBe(1)
        ->and($event->rounds()->first()->sort_order)->toBe(1);
});

test('adding rounds auto-increments sort order', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    $component = Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event]);

    $component->call('addRound');
    $component->call('addRound');
    $component->call('addRound');

    expect($event->rounds()->pluck('sort_order')->all())->toBe([1, 2, 3]);
});

test('host can remove the last round', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
    $lastRound = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 2]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('removeLastRound');

    expect($event->rounds()->count())->toBe(1)
        ->and(Round::find($lastRound->id))->toBeNull();
});

test('removing last round cascades to its scores', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
    Score::factory()->create(['team_id' => $team->id, 'round_id' => $round->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('removeLastRound');

    expect(Score::count())->toBe(0);
});

test('cannot remove round when there are no rounds', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('removeLastRound')
        ->assertHasNoErrors();

    expect($event->rounds()->count())->toBe(0);
});

test('scoring grid rounds use responsive compact column widths', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    Team::factory()->create(['event_id' => $event->id]);
    Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->assertSeeHtml('w-12 px-1 py-2 text-center font-medium sm:w-14 sm:px-1.5 md:w-16 md:px-2')
        ->assertSeeHtml('w-14 px-1 py-2 text-center font-medium sm:w-16 sm:px-1.5 md:px-2');
});
