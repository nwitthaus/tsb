<?php

use App\Models\Event;
use App\Models\Organization;

test('event belongs to an organization', function () {
    $event = Event::factory()->create();

    expect($event->organization)->toBeInstanceOf(Organization::class);
});

test('organization has many events', function () {
    $organization = Organization::factory()->create();
    Event::factory()->count(3)->create(['organization_id' => $organization->id]);

    expect($organization->events)->toHaveCount(3);
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
