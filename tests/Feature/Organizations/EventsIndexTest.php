<?php

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

test('owner can see events index', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->assertSee($event->name);
});

test('scorekeeper can see events index', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->assertSee($event->name);
});

test('non-member cannot see events index', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $this->actingAs($user)
        ->get(route('organizations.events.index', $organization))
        ->assertForbidden();
});

test('events index shows active events on active tab', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Event::factory()->create(['organization_id' => $organization->id, 'name' => 'Active Trivia']);
    Event::factory()->ended()->create(['organization_id' => $organization->id, 'name' => 'Past Trivia']);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->assertSet('tab', 'active')
        ->assertSee('Active Trivia')
        ->assertDontSee('Past Trivia');
});

test('events index shows past events on past tab', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Event::factory()->create(['organization_id' => $organization->id, 'name' => 'Active Trivia']);
    Event::factory()->ended()->create(['organization_id' => $organization->id, 'name' => 'Past Trivia']);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->set('tab', 'past')
        ->assertSee('Past Trivia')
        ->assertDontSee('Active Trivia');
});

test('events index is searchable by name', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Event::factory()->create(['organization_id' => $organization->id, 'name' => 'Tuesday Trivia']);
    Event::factory()->create(['organization_id' => $organization->id, 'name' => 'Friday Quiz']);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->set('search', 'Tuesday')
        ->assertSee('Tuesday Trivia')
        ->assertDontSee('Friday Quiz');
});

test('owner can delete event from index', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->call('deleteEvent', $event->id);

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});

test('scorekeeper cannot delete event from index', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->call('deleteEvent', $event->id)
        ->assertForbidden();

    $this->assertDatabaseHas('events', ['id' => $event->id]);
});
