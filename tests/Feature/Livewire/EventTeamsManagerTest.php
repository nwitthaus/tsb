<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('teams page loads and displays teams manager', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent(['name' => 'Tuesday Trivia']);

    $this->actingAs($user)
        ->get(route('organizations.events.teams', [$organization, $event]))
        ->assertOk()
        ->assertSee('Tuesday Trivia')
        ->assertSee('Teams')
        ->assertSeeLivewire('event-teams-manager');
});

test('unauthorized user cannot view teams page', function () {
    ['organization' => $organization, 'event' => $event] = createOwnerWithEvent();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('organizations.events.teams', [$organization, $event]))
        ->assertForbidden();
});

test('host can update a team name', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();
    $team = Team::factory()->create(['event_id' => $event->id, 'name' => 'Old Name', 'table_number' => 5]);

    Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('updateTeam', $team->id, 'New Name', 5)
        ->assertHasNoErrors();

    expect($team->fresh()->name)->toBe('New Name');
});

test('host can update a team table number', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();
    $team = Team::factory()->create(['event_id' => $event->id, 'name' => 'Quizzers', 'table_number' => 1]);

    Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('updateTeam', $team->id, 'Quizzers', 7)
        ->assertHasNoErrors();

    expect($team->fresh()->table_number)->toBe(7);
});

test('updating team rejects duplicate name within event', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();
    Team::factory()->create(['event_id' => $event->id, 'name' => 'Taken Name']);
    $team = Team::factory()->create(['event_id' => $event->id, 'name' => 'My Team']);

    Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('updateTeam', $team->id, 'Taken Name', null)
        ->assertHasErrors();

    expect($team->fresh()->name)->toBe('My Team');
});

test('updating team allows keeping same name', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();
    $team = Team::factory()->create(['event_id' => $event->id, 'name' => 'Quizzers', 'table_number' => 3]);

    Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('updateTeam', $team->id, 'Quizzers', 3)
        ->assertHasNoErrors();
});

test('updating team requires at least name or table number', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();
    $team = Team::factory()->create(['event_id' => $event->id, 'name' => 'Quizzers']);

    Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('updateTeam', $team->id, null, null)
        ->assertHasErrors();

    expect($team->fresh()->name)->toBe('Quizzers');
});

test('cannot update team on ended event', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent(['ended_at' => now()]);
    $team = Team::factory()->create(['event_id' => $event->id, 'name' => 'Old Name']);

    Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('updateTeam', $team->id, 'New Name', null);

    expect($team->fresh()->name)->toBe('Old Name');
});
