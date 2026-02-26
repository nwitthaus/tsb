<?php

use App\Models\Event;
use App\Models\User;

test('owner can view their event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    expect($user->can('view', $event))->toBeTrue();
});

test('non-owner cannot view event', function () {
    $event = Event::factory()->create();
    $otherUser = User::factory()->create();

    expect($otherUser->can('view', $event))->toBeFalse();
});

test('user can create event when they have no active events', function () {
    $user = User::factory()->create();

    expect($user->can('create', Event::class))->toBeTrue();
});

test('user cannot create event when they have an active event', function () {
    $user = User::factory()->create();
    Event::factory()->create(['user_id' => $user->id, 'ended_at' => null]);

    expect($user->can('create', Event::class))->toBeFalse();
});

test('user can create event when all their events are ended', function () {
    $user = User::factory()->create();
    Event::factory()->ended()->create(['user_id' => $user->id]);

    expect($user->can('create', Event::class))->toBeTrue();
});

test('owner can update their event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    expect($user->can('update', $event))->toBeTrue();
});

test('non-owner cannot update event', function () {
    $event = Event::factory()->create();
    $otherUser = User::factory()->create();

    expect($otherUser->can('update', $event))->toBeFalse();
});
