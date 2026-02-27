<?php

use App\Enums\OrganizationRole;
use App\Livewire\EventScoringGrid;
use App\Livewire\EventTeamsManager;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('scorekeeper can view event show page', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertOk();
});

test('scorekeeper cannot see edit form on event show page', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertDontSee('Save Changes');
});

test('scorekeeper can view teams page', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    $this->actingAs($user)
        ->get(route('events.teams', $event))
        ->assertOk();
});

test('scorekeeper cannot add team', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($user)
        ->test(EventTeamsManager::class, ['event' => $event])
        ->call('addTeam', 'New Team', 1)
        ->assertForbidden();
});

test('scorekeeper cannot update team', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);

    Livewire::actingAs($user)
        ->test(EventTeamsManager::class, ['event' => $event])
        ->call('updateTeam', $team->id, 'Updated Name', 5)
        ->assertForbidden();
});

test('scorekeeper cannot remove team', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);

    Livewire::actingAs($user)
        ->test(EventTeamsManager::class, ['event' => $event])
        ->call('removeTeam', $team->id)
        ->assertForbidden();
});

test('scorekeeper can view scoring grid', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    $this->actingAs($user)
        ->get(route('events.scoring', $event))
        ->assertOk();
});

test('scorekeeper can enter scores', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
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
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($user)
        ->test(EventScoringGrid::class, ['event' => $event])
        ->call('addRound')
        ->assertForbidden();
});

test('scorekeeper cannot remove last round', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    Round::factory()->create(['event_id' => $event->id]);

    Livewire::actingAs($user)
        ->test(EventScoringGrid::class, ['event' => $event])
        ->call('removeLastRound')
        ->assertForbidden();
});

test('scorekeeper cannot end event', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($user)
        ->test(EventScoringGrid::class, ['event' => $event])
        ->call('endEvent')
        ->assertForbidden();
});

test('scorekeeper cannot reopen event', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create([
        'organization_id' => $organization->id,
        'ended_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(EventScoringGrid::class, ['event' => $event])
        ->call('reopenEvent')
        ->assertForbidden();
});

test('scorekeeper cannot create event', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);

    $this->actingAs($user)
        ->get(route('events.create', $organization))
        ->assertForbidden();
});

test('scorekeeper can view organization show page', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);

    $this->actingAs($user)
        ->get(route('organizations.show', $organization))
        ->assertOk();
});

test('non-member cannot enter scores', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $event = Event::factory()->create(['organization_id' => $organization->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id]);

    Livewire::actingAs($user)
        ->test(EventScoringGrid::class, ['event' => $event])
        ->assertForbidden();
});

test('scorekeeper does not see create event button on org show page', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);

    $this->actingAs($user)
        ->get(route('organizations.show', $organization))
        ->assertDontSee('Create Event');
});
