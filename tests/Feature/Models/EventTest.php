<?php

use App\Models\Event;
use App\Models\User;

test('event belongs to a user', function () {
    $event = Event::factory()->create();

    expect($event->user)->toBeInstanceOf(User::class);
});

test('user has many events', function () {
    $user = User::factory()->create();
    Event::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->events)->toHaveCount(3);
});

test('event knows if it is active', function () {
    $active = Event::factory()->create();
    $ended = Event::factory()->ended()->create();

    expect($active->isActive())->toBeTrue()
        ->and($ended->isActive())->toBeFalse();
});

test('slug is generated from event name', function () {
    $slug = Event::generateSlug('Tuesday Trivia at Joes');

    expect($slug)->toBe('tuesday-trivia-at-joes');
});

test('slug is unique with auto-increment suffix', function () {
    Event::factory()->create(['slug' => 'trivia-night']);

    $slug = Event::generateSlug('Trivia Night');

    expect($slug)->toBe('trivia-night-1');
});
