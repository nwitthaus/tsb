<?php

use App\Livewire\EventScoringGrid;
use App\Livewire\EventTeamsManager;
use App\Models\Event;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('scorekeeper can view event edit page', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createScorekeeperWithEvent();

    $this->actingAs($user)
        ->get(route('organizations.events.edit', [$organization, $event]))
        ->assertOk();
});

test('scorekeeper cannot see edit form on event edit page', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createScorekeeperWithEvent();

    $this->actingAs($user)
        ->get(route('organizations.events.edit', [$organization, $event]))
        ->assertDontSee('Save Changes');
});

test('scorekeeper can view teams page', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createScorekeeperWithEvent();

    $this->actingAs($user)
        ->get(route('organizations.events.teams', [$organization, $event]))
        ->assertOk();
});

test('scorekeeper cannot add team', function () {
    ['user' => $user, 'event' => $event] = createScorekeeperWithEvent();

    Livewire::actingAs($user)
        ->test(EventTeamsManager::class, ['event' => $event])
        ->call('addTeam', 'New Team', 1)
        ->assertForbidden();
});

test('scorekeeper cannot update team', function () {
    ['user' => $user, 'event' => $event] = createScorekeeperWithEvent();
    $team = Team::factory()->create(['event_id' => $event->id]);

    Livewire::actingAs($user)
        ->test(EventTeamsManager::class, ['event' => $event])
        ->call('updateTeam', $team->id, 'Updated Name', 5)
        ->assertForbidden();
});

test('scorekeeper cannot remove team', function () {
    ['user' => $user, 'event' => $event] = createScorekeeperWithEvent();
    $team = Team::factory()->create(['event_id' => $event->id]);

    Livewire::actingAs($user)
        ->test(EventTeamsManager::class, ['event' => $event])
        ->call('removeTeam', $team->id)
        ->assertForbidden();
});

test('scorekeeper can view scoring grid', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createScorekeeperWithEvent();

    $this->actingAs($user)
        ->get(route('organizations.events.scoring', [$organization, $event]))
        ->assertOk();
});

test('scorekeeper can enter scores', function () {
    ['user' => $user, 'event' => $event] = createScorekeeperWithEvent();
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id]);

    Livewire::actingAs($user)
        ->test(EventScoringGrid::class, ['event' => $event])
        ->call('saveScore', $team->id, $round->id, '8.5');

    $this->assertDatabaseHas('scores', [
        'team_id' => $team->id,
        'round_id' => $round->id,
        'value' => 8.5,
    ]);
});

test('scorekeeper cannot add round', function () {
    ['user' => $user, 'event' => $event] = createScorekeeperWithEvent();

    Livewire::actingAs($user)
        ->test(EventScoringGrid::class, ['event' => $event])
        ->call('addRound')
        ->assertForbidden();
});

test('scorekeeper cannot remove last round', function () {
    ['user' => $user, 'event' => $event] = createScorekeeperWithEvent();
    Round::factory()->create(['event_id' => $event->id]);

    Livewire::actingAs($user)
        ->test(EventScoringGrid::class, ['event' => $event])
        ->call('removeLastRound')
        ->assertForbidden();
});

test('scorekeeper cannot end event', function () {
    ['user' => $user, 'event' => $event] = createScorekeeperWithEvent();

    Livewire::actingAs($user)
        ->test(EventScoringGrid::class, ['event' => $event])
        ->call('endEvent')
        ->assertForbidden();
});

test('scorekeeper cannot reopen event', function () {
    ['user' => $user, 'event' => $event] = createScorekeeperWithEvent(['ended_at' => now()]);

    Livewire::actingAs($user)
        ->test(EventScoringGrid::class, ['event' => $event])
        ->call('reopenEvent')
        ->assertForbidden();
});

test('scorekeeper cannot create event', function () {
    ['user' => $user, 'organization' => $organization] = createScorekeeperWithEvent();

    $this->actingAs($user)
        ->get(route('organizations.events.create', $organization))
        ->assertForbidden();
});

test('scorekeeper can view organization show page', function () {
    ['user' => $user, 'organization' => $organization] = createScorekeeperWithEvent();

    $this->actingAs($user)
        ->get(route('organizations.show', $organization))
        ->assertOk();
});

test('non-member cannot enter scores', function () {
    $event = Event::factory()->create();
    $team = Team::factory()->create(['event_id' => $event->id]);
    Round::factory()->create(['event_id' => $event->id]);

    Livewire::actingAs(User::factory()->create())
        ->test(EventScoringGrid::class, ['event' => $event])
        ->assertForbidden();
});

test('scorekeeper does not see settings card on org show page', function () {
    ['user' => $user, 'organization' => $organization] = createScorekeeperWithEvent();

    $this->actingAs($user)
        ->get(route('organizations.show', $organization))
        ->assertDontSee('Manage Settings');
});
