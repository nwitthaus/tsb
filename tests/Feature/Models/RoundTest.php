<?php

use App\Models\Event;
use App\Models\Round;

test('round belongs to an event', function () {
    $round = Round::factory()->create();

    expect($round->event)->toBeInstanceOf(Event::class);
});

test('event rounds are ordered by sort_order', function () {
    $event = Event::factory()->create();
    Round::factory()->create(['event_id' => $event->id, 'sort_order' => 3]);
    Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
    Round::factory()->create(['event_id' => $event->id, 'sort_order' => 2]);

    expect($event->rounds->pluck('sort_order')->all())->toBe([1, 2, 3]);
});
