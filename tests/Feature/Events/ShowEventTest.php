<?php

use App\Models\Event;
use App\Models\User;

test('guests cannot access event management', function () {
    $event = Event::factory()->create();

    $this->get(route('organizations.events.edit', [$event->organization, $event]))->assertRedirect(route('login'));
});

test('owner can access their event', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent();

    $this->actingAs($user)
        ->get(route('organizations.events.edit', [$organization, $event]))
        ->assertOk()
        ->assertSee($event->name);
});

test('non-owner cannot access event', function () {
    $event = Event::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->get(route('organizations.events.edit', [$event->organization, $event]))
        ->assertForbidden();
});
