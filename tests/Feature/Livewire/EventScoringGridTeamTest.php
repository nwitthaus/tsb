<?php

use App\Models\Event;
use App\Models\Team;

test('host can add a team with name and table number', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('addTeam', 'Quizly Bears', 3)
        ->assertHasNoErrors();

    expect($event->teams()->count())->toBe(1)
        ->and($event->teams()->first()->name)->toBe('Quizly Bears')
        ->and($event->teams()->first()->table_number)->toBe(3);
});

test('host can add a team with name only', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('addTeam', 'Brain Stormers', null)
        ->assertHasNoErrors();

    expect($event->teams()->first()->table_number)->toBeNull();
});

test('host can add a team with table number only', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('addTeam', null, 12)
        ->assertHasNoErrors();

    expect($event->teams()->first()->name)->toBeNull();
});

test('adding a team requires at least name or table number', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('addTeam', null, null)
        ->assertHasErrors();
});

test('team name must be unique within an event', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();
    Team::factory()->create(['event_id' => $event->id, 'name' => 'Quizzers']);

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('addTeam', 'Quizzers', null)
        ->assertHasErrors();

    expect($event->teams()->count())->toBe(1);
});

test('table number must be unique within an event', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();
    Team::factory()->create(['event_id' => $event->id, 'table_number' => 4]);

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('addTeam', null, 4)
        ->assertHasErrors();

    expect($event->teams()->count())->toBe(1);
});

test('duplicate team name is allowed across different events', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event1] = createOwnerWithEvent();
    $event2 = Event::factory()->create(['organization_id' => $organization->id]);
    Team::factory()->create(['event_id' => $event1->id, 'name' => 'Quizzers']);

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event2])
        ->call('addTeam', 'Quizzers', null)
        ->assertHasNoErrors();

    expect($event2->teams()->count())->toBe(1);
});

test('soft deleted team name can be reused', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();
    $team = Team::factory()->create(['event_id' => $event->id, 'name' => 'Quizzers']);
    $team->delete();

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('addTeam', 'Quizzers', null)
        ->assertHasNoErrors();
});

test('host can soft delete a team', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();
    $team = Team::factory()->create(['event_id' => $event->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('removeTeam', $team->id);

    expect($team->fresh()->trashed())->toBeTrue();
});

test('host can restore a soft deleted team', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();
    $team = Team::factory()->create(['event_id' => $event->id]);
    $team->delete();

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('restoreTeam', $team->id);

    expect($team->fresh()->trashed())->toBeFalse();
});

test('host can reorder teams alphabetically', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();
    Team::factory()->create(['event_id' => $event->id, 'name' => 'Zebras', 'sort_order' => 1]);
    Team::factory()->create(['event_id' => $event->id, 'name' => 'Alphas', 'sort_order' => 2]);

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('reorderTeams', 'alphabetical');

    $names = $event->teams()->pluck('name')->all();
    expect($names)->toBe(['Alphas', 'Zebras']);
});

test('host can reorder teams by table number', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();
    Team::factory()->create(['event_id' => $event->id, 'table_number' => 10, 'sort_order' => 1]);
    Team::factory()->create(['event_id' => $event->id, 'table_number' => 2, 'sort_order' => 2]);

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('reorderTeams', 'table_number');

    $tables = $event->teams()->pluck('table_number')->all();
    expect($tables)->toBe([2, 10]);
});

test('scoring grid uses constrained team column width', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();
    Team::factory()->create(['event_id' => $event->id, 'name' => 'Campus Lutheran Trivia Champs']);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->assertSeeHtml('table-fixed')
        ->assertSeeHtml('w-44 px-3 py-2 text-left font-medium');
});
