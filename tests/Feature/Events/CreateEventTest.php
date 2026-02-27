<?php

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;

test('guests cannot access create event page', function () {
    $organization = Organization::factory()->create();
    $this->get(route('organizations.events.create', $organization))->assertRedirect(route('login'));
});

test('organization owner can view create event page', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    $this->actingAs($user)
        ->get(route('organizations.events.create', $organization))
        ->assertOk();
});

test('scorekeeper cannot view create event page', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);

    $this->actingAs($user)
        ->get(route('organizations.events.create', $organization))
        ->assertForbidden();
});

test('owner can create an event', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);
    $startsAt = now()->addDay()->format('Y-m-d\TH:i');

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', 'Tuesday Trivia')
        ->set('slug', 'tuesday-trivia')
        ->set('starts_at', $startsAt)
        ->call('save')
        ->assertRedirect(route('organizations.events.teams', [$organization, Event::first()]));

    expect(Event::count())->toBe(1)
        ->and(Event::first()->name)->toBe('Tuesday Trivia')
        ->and(Event::first()->slug)->toBe('tuesday-trivia')
        ->and(Event::first()->starts_at)->not->toBeNull()
        ->and(Event::first()->organization_id)->toBe($organization->id);
});

test('slug auto-generates from name', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', 'Tuesday Trivia at Joe\'s')
        ->assertSet('slug', 'tuesday-trivia-at-joes');
});

test('event name is required', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', '')
        ->set('slug', 'some-slug')
        ->set('starts_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('starts_at is required', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', 'My Event')
        ->set('slug', 'my-event')
        ->set('starts_at', '')
        ->call('save')
        ->assertHasErrors(['starts_at' => 'required']);
});

test('starts_at must be a valid date', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', 'My Event')
        ->set('slug', 'my-event')
        ->set('starts_at', 'not-a-date')
        ->call('save')
        ->assertHasErrors(['starts_at' => 'date']);
});

test('starts_at must be today or later', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', 'My Event')
        ->set('slug', 'my-event')
        ->set('starts_at', '2020-01-01T10:00')
        ->call('save')
        ->assertHasErrors(['starts_at' => 'after_or_equal']);
});

test('slug must be unique', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);
    Event::factory()->create(['slug' => 'taken-slug', 'organization_id' => $organization->id]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', 'My Event')
        ->set('slug', 'taken-slug')
        ->call('save')
        ->assertHasErrors(['slug' => 'unique']);
});

test('creating event with tables pre-creates numbered teams', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', 'Table Trivia')
        ->set('slug', 'table-trivia')
        ->set('starts_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('tables', 5)
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::first();
    expect($event->teams()->count())->toBe(5)
        ->and($event->teams()->pluck('table_number')->all())->toBe([1, 2, 3, 4, 5])
        ->and($event->teams()->pluck('sort_order')->all())->toBe([1, 2, 3, 4, 5]);
});

test('creating event without tables creates no teams', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', 'No Tables')
        ->set('slug', 'no-tables')
        ->set('starts_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect(Event::first()->teams()->count())->toBe(0);
});

test('tables must be at least 1', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', 'My Event')
        ->set('slug', 'my-event')
        ->set('starts_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('tables', 0)
        ->call('save')
        ->assertHasErrors(['tables']);
});

test('creating event with rounds pre-creates rounds', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', 'Round Trivia')
        ->set('slug', 'round-trivia')
        ->set('starts_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('rounds', 4)
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::first();
    expect($event->rounds()->count())->toBe(4)
        ->and($event->rounds()->pluck('sort_order')->all())->toBe([1, 2, 3, 4]);
});

test('creating event without rounds creates no rounds', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', 'No Rounds')
        ->set('slug', 'no-rounds')
        ->set('starts_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect(Event::first()->rounds()->count())->toBe(0);
});

test('rounds must be at least 1', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', 'My Event')
        ->set('slug', 'my-event')
        ->set('starts_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('rounds', 0)
        ->call('save')
        ->assertHasErrors(['rounds']);
});

test('owner with active event can create another', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);
    Event::factory()->create(['organization_id' => $organization->id, 'ended_at' => null]);

    $this->actingAs($user)
        ->get(route('organizations.events.create', $organization))
        ->assertOk();
});
