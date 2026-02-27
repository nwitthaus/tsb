<?php

use App\Models\Event;
use App\Models\User;

test('edit page loads and displays event details', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent(['name' => 'Tuesday Trivia']);

    $this->actingAs($user)
        ->get(route('organizations.events.edit', [$organization, $event]))
        ->assertOk()
        ->assertSee('Tuesday Trivia');
});

test('can update event name', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent(['name' => 'Old Name']);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.edit', ['organization' => $organization, 'event' => $event])
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($event->fresh()->name)->toBe('New Name');
});

test('can update event slug', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent(['slug' => 'old-slug']);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.edit', ['organization' => $organization, 'event' => $event])
        ->set('slug', 'new-slug')
        ->call('save')
        ->assertHasNoErrors();

    expect($event->fresh()->slug)->toBe('new-slug');
});

test('can update event start time', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent();

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.edit', ['organization' => $organization, 'event' => $event])
        ->set('starts_at', '2026-06-15T19:00')
        ->call('save')
        ->assertHasNoErrors();

    expect($event->fresh()->starts_at->format('Y-m-d H:i'))->toBe('2026-06-15 19:00');
});

test('slug must be unique across events', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent(['slug' => 'my-slug']);
    Event::factory()->create(['slug' => 'taken-slug']);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.edit', ['organization' => $organization, 'event' => $event])
        ->set('slug', 'taken-slug')
        ->call('save')
        ->assertHasErrors(['slug']);
});

test('slug allows keeping same slug on own event', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent(['slug' => 'my-slug']);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.edit', ['organization' => $organization, 'event' => $event])
        ->set('slug', 'my-slug')
        ->call('save')
        ->assertHasNoErrors();
});

test('slug rejects invalid format', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent();

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.events.edit', ['organization' => $organization, 'event' => $event])
        ->set('slug', 'Invalid Slug!')
        ->call('save')
        ->assertHasErrors(['slug']);
});

test('unauthorized user cannot view event details', function () {
    ['organization' => $organization, 'event' => $event] = createOwnerWithEvent();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('organizations.events.edit', [$organization, $event]))
        ->assertForbidden();
});

test('edit page displays scoreboard share section', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent(['slug' => 'my-trivia']);

    $this->actingAs($user)
        ->get(route('organizations.events.edit', [$organization, $event]))
        ->assertOk()
        ->assertSee('Share Scoreboard')
        ->assertSee('my-trivia');
});
