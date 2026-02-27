<?php

use App\Models\Event;
use App\Models\User;

test('guests cannot access event management', function () {
    $event = Event::factory()->create();

    $this->get(route('events.show', $event))->assertRedirect(route('login'));
});

test('owner can access their event', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee($event->name);
});

test('non-owner cannot access event', function () {
    $event = Event::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->get(route('events.show', $event))
        ->assertForbidden();
});
