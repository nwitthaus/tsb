<?php

use App\Models\Event;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows active event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id, 'name' => 'My Trivia']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('My Trivia');
});

test('dashboard shows create event button when no active event', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Create Event');
});

test('dashboard shows past events', function () {
    $user = User::factory()->create();
    Event::factory()->ended()->create(['user_id' => $user->id, 'name' => 'Old Trivia']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Old Trivia');
});

test('host can delete their event from dashboard', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->call('deleteEvent', $event->id);

    expect(Event::find($event->id))->toBeNull();
});

test('user cannot delete another users event', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    Livewire\Livewire::actingAs($other)
        ->test('pages::dashboard')
        ->call('deleteEvent', $event->id)
        ->assertForbidden();

    expect(Event::find($event->id))->not->toBeNull();
});

test('deleting event removes its teams and scores', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = $event->teams()->create(['name' => 'Quizzers', 'sort_order' => 1]);
    $round = $event->rounds()->create(['sort_order' => 1]);
    $team->scores()->create(['round_id' => $round->id, 'value' => 8]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->call('deleteEvent', $event->id);

    expect(Event::find($event->id))->toBeNull()
        ->and(\App\Models\Team::where('event_id', $event->id)->count())->toBe(0);
});
