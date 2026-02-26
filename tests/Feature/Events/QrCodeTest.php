<?php

use App\Models\Event;
use App\Models\User;

test('event management page shows join code', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id, 'slug' => 'quiz42']);

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertSee('quiz42');
});

test('event management page shows scoreboard URL', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id, 'slug' => 'quiz42']);

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertSee('/quiz42');
});

test('event management page shows QR code SVG', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id, 'slug' => 'quiz42']);

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertSee('<svg', false)
        ->assertSee('Share this scoreboard');
});

test('event management page shows copy link button', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id, 'slug' => 'quiz42']);

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertSee('Copy Link');
});
