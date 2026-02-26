<?php

use App\Models\Event;
use App\Models\User;

test('guests cannot access create event page', function () {
    $this->get(route('events.create'))->assertRedirect(route('login'));
});

test('authenticated user can view create event page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('events.create'))
        ->assertOk();
});

test('user can create an event', function () {
    $user = User::factory()->create();
    $startsAt = now()->addDay()->format('Y-m-d\TH:i');

    Livewire\Livewire::actingAs($user)
        ->test('pages::events.create')
        ->set('name', 'Tuesday Trivia')
        ->set('slug', 'tuesday-trivia')
        ->set('starts_at', $startsAt)
        ->call('save')
        ->assertRedirect(route('events.scoring', Event::first()));

    expect(Event::count())->toBe(1)
        ->and(Event::first()->name)->toBe('Tuesday Trivia')
        ->and(Event::first()->slug)->toBe('tuesday-trivia')
        ->and(Event::first()->starts_at)->not->toBeNull()
        ->and(Event::first()->user_id)->toBe($user->id);
});

test('slug auto-generates from name', function () {
    Livewire\Livewire::actingAs(User::factory()->create())
        ->test('pages::events.create')
        ->set('name', 'Tuesday Trivia at Joe\'s')
        ->assertSet('slug', 'tuesday-trivia-at-joes');
});

test('event name is required', function () {
    Livewire\Livewire::actingAs(User::factory()->create())
        ->test('pages::events.create')
        ->set('name', '')
        ->set('slug', 'some-slug')
        ->set('starts_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('starts_at is required', function () {
    Livewire\Livewire::actingAs(User::factory()->create())
        ->test('pages::events.create')
        ->set('name', 'My Event')
        ->set('slug', 'my-event')
        ->set('starts_at', '')
        ->call('save')
        ->assertHasErrors(['starts_at' => 'required']);
});

test('starts_at must be a valid date', function () {
    Livewire\Livewire::actingAs(User::factory()->create())
        ->test('pages::events.create')
        ->set('name', 'My Event')
        ->set('slug', 'my-event')
        ->set('starts_at', 'not-a-date')
        ->call('save')
        ->assertHasErrors(['starts_at' => 'date']);
});

test('starts_at must be today or later', function () {
    Livewire\Livewire::actingAs(User::factory()->create())
        ->test('pages::events.create')
        ->set('name', 'My Event')
        ->set('slug', 'my-event')
        ->set('starts_at', '2020-01-01T10:00')
        ->call('save')
        ->assertHasErrors(['starts_at' => 'after_or_equal']);
});

test('slug must be unique', function () {
    Event::factory()->create(['slug' => 'taken-slug']);

    Livewire\Livewire::actingAs(User::factory()->create())
        ->test('pages::events.create')
        ->set('name', 'My Event')
        ->set('slug', 'taken-slug')
        ->call('save')
        ->assertHasErrors(['slug' => 'unique']);
});

test('user with active event can create another', function () {
    $user = User::factory()->create();
    Event::factory()->create(['user_id' => $user->id, 'ended_at' => null]);

    $this->actingAs($user)
        ->get(route('events.create'))
        ->assertOk();
});
