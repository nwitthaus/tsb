<?php

use App\Models\Event;
use App\Models\User;

test('show page loads and displays event details form', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent(['name' => 'Tuesday Trivia']);

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Tuesday Trivia')
        ->assertSee('Details')
        ->assertSee('Teams')
        ->assertSee('Scoring');
});

test('can update event name', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent(['name' => 'Old Name']);

    Livewire\Livewire::actingAs($user)
        ->test('pages::events.show', ['event' => $event])
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($event->fresh()->name)->toBe('New Name');
});

test('can update event slug', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent(['slug' => 'old-slug']);

    Livewire\Livewire::actingAs($user)
        ->test('pages::events.show', ['event' => $event])
        ->set('slug', 'new-slug')
        ->call('save')
        ->assertHasNoErrors();

    expect($event->fresh()->slug)->toBe('new-slug');
});

test('can update event start time', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();

    Livewire\Livewire::actingAs($user)
        ->test('pages::events.show', ['event' => $event])
        ->set('starts_at', '2026-06-15T19:00')
        ->call('save')
        ->assertHasNoErrors();

    expect($event->fresh()->starts_at->format('Y-m-d H:i'))->toBe('2026-06-15 19:00');
});

test('slug must be unique across events', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent(['slug' => 'my-slug']);
    Event::factory()->create(['slug' => 'taken-slug']);

    Livewire\Livewire::actingAs($user)
        ->test('pages::events.show', ['event' => $event])
        ->set('slug', 'taken-slug')
        ->call('save')
        ->assertHasErrors(['slug']);
});

test('slug allows keeping same slug on own event', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent(['slug' => 'my-slug']);

    Livewire\Livewire::actingAs($user)
        ->test('pages::events.show', ['event' => $event])
        ->set('slug', 'my-slug')
        ->call('save')
        ->assertHasNoErrors();
});

test('slug rejects invalid format', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();

    Livewire\Livewire::actingAs($user)
        ->test('pages::events.show', ['event' => $event])
        ->set('slug', 'Invalid Slug!')
        ->call('save')
        ->assertHasErrors(['slug']);
});

test('unauthorized user cannot view event details', function () {
    ['event' => $event] = createOwnerWithEvent();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('events.show', $event))
        ->assertForbidden();
});

test('show page displays scoreboard QR section', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent(['slug' => 'my-trivia']);

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Share this scoreboard')
        ->assertSee('my-trivia');
});

test('scoring page loads and renders scoring grid', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();

    $this->actingAs($user)
        ->get(route('events.scoring', $event))
        ->assertOk()
        ->assertSeeLivewire('event-scoring-grid');
});

test('unauthorized user cannot view scoring page', function () {
    ['event' => $event] = createOwnerWithEvent();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('events.scoring', $event))
        ->assertForbidden();
});
