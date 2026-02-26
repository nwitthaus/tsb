<?php

use App\Models\Event;
use App\Models\User;

test('host can end an active event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('endEvent');

    expect($event->fresh()->ended_at)->not->toBeNull();
});

test('host can reopen an ended event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->ended()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('reopenEvent');

    expect($event->fresh()->ended_at)->toBeNull();
});

test('cannot reopen event if user already has an active event', function () {
    $user = User::factory()->create();
    Event::factory()->create(['user_id' => $user->id, 'ended_at' => null]);
    $endedEvent = Event::factory()->ended()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $endedEvent])
        ->call('reopenEvent')
        ->assertHasErrors();

    expect($endedEvent->fresh()->ended_at)->not->toBeNull();
});

test('ended event shows read-only grid', function () {
    $user = User::factory()->create();
    $event = Event::factory()->ended()->create(['user_id' => $user->id, 'name' => 'Past Trivia']);

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Past Trivia')
        ->assertSee('Reopen');
});
