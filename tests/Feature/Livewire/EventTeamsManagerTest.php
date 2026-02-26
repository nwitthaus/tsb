<?php

use App\Models\Event;
use App\Models\Team;
use App\Models\User;

test('teams page loads and displays teams tab', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id, 'name' => 'Tuesday Trivia']);

    $this->actingAs($user)
        ->get(route('events.teams', $event))
        ->assertOk()
        ->assertSee('Tuesday Trivia')
        ->assertSee('Teams')
        ->assertSeeLivewire('event-teams-manager');
});

test('unauthorized user cannot view teams page', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->get(route('events.teams', $event))
        ->assertForbidden();
});

test('host can update a team name', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id, 'name' => 'Old Name', 'table_number' => 5]);

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('updateTeam', $team->id, 'New Name', 5)
        ->assertHasNoErrors();

    expect($team->fresh()->name)->toBe('New Name');
});

test('host can update a team table number', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id, 'name' => 'Quizzers', 'table_number' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('updateTeam', $team->id, 'Quizzers', 7)
        ->assertHasNoErrors();

    expect($team->fresh()->table_number)->toBe(7);
});

test('updating team rejects duplicate name within event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    Team::factory()->create(['event_id' => $event->id, 'name' => 'Taken Name']);
    $team = Team::factory()->create(['event_id' => $event->id, 'name' => 'My Team']);

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('updateTeam', $team->id, 'Taken Name', null)
        ->assertHasErrors();

    expect($team->fresh()->name)->toBe('My Team');
});

test('updating team allows keeping same name', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id, 'name' => 'Quizzers', 'table_number' => 3]);

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('updateTeam', $team->id, 'Quizzers', 3)
        ->assertHasNoErrors();
});

test('updating team requires at least name or table number', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id, 'name' => 'Quizzers']);

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('updateTeam', $team->id, null, null)
        ->assertHasErrors();

    expect($team->fresh()->name)->toBe('Quizzers');
});

test('cannot update team on ended event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->ended()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id, 'name' => 'Old Name']);

    Livewire\Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event])
        ->call('updateTeam', $team->id, 'New Name', null);

    expect($team->fresh()->name)->toBe('Old Name');
});
