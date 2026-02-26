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
