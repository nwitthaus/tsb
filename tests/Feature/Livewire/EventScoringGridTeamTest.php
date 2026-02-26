<?php

use App\Models\Event;
use App\Models\Team;
use App\Models\User;

test('host can add a team with name and table number', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('addTeam', 'Quizly Bears', 3)
        ->assertHasNoErrors();

    expect($event->teams()->count())->toBe(1)
        ->and($event->teams()->first()->name)->toBe('Quizly Bears')
        ->and($event->teams()->first()->table_number)->toBe(3);
});

test('host can add a team with name only', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('addTeam', 'Brain Stormers', null)
        ->assertHasNoErrors();

    expect($event->teams()->first()->table_number)->toBeNull();
});

test('host can add a team with table number only', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('addTeam', null, 12)
        ->assertHasNoErrors();

    expect($event->teams()->first()->name)->toBeNull();
});

test('adding a team requires at least name or table number', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('addTeam', null, null)
        ->assertHasErrors();
});

test('host can soft delete a team', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('removeTeam', $team->id);

    expect($team->fresh()->trashed())->toBeTrue();
});

test('host can restore a soft deleted team', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $team->delete();

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('restoreTeam', $team->id);

    expect($team->fresh()->trashed())->toBeFalse();
});

test('host can reorder teams alphabetically', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    Team::factory()->create(['event_id' => $event->id, 'name' => 'Zebras', 'sort_order' => 1]);
    Team::factory()->create(['event_id' => $event->id, 'name' => 'Alphas', 'sort_order' => 2]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('reorderTeams', 'alphabetical');

    $names = $event->teams()->pluck('name')->all();
    expect($names)->toBe(['Alphas', 'Zebras']);
});

test('host can reorder teams by table number', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    Team::factory()->create(['event_id' => $event->id, 'table_number' => 10, 'sort_order' => 1]);
    Team::factory()->create(['event_id' => $event->id, 'table_number' => 2, 'sort_order' => 2]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('reorderTeams', 'table_number');

    $tables = $event->teams()->pluck('table_number')->all();
    expect($tables)->toBe([2, 10]);
});
