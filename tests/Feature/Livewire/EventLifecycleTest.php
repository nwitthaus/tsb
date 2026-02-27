<?php

use App\Models\Event;

test('host can end an active event', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent();

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('endEvent');

    expect($event->fresh()->ended_at)->not->toBeNull();
});

test('host can reopen an ended event', function () {
    ['user' => $user, 'event' => $event] = createOwnerWithEvent(['ended_at' => now()]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('reopenEvent');

    expect($event->fresh()->ended_at)->toBeNull();
});

test('can reopen event even if user has another active event', function () {
    ['user' => $user, 'organization' => $organization] = createOwnerWithEvent();
    $endedEvent = Event::factory()->create(['organization_id' => $organization->id, 'ended_at' => now()]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $endedEvent])
        ->call('reopenEvent')
        ->assertHasNoErrors();

    expect($endedEvent->fresh()->ended_at)->toBeNull();
});

test('ended event shows read-only grid', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent(['ended_at' => now(), 'name' => 'Past Trivia']);

    $this->actingAs($user)
        ->get(route('organizations.events.scoring', [$organization, $event]))
        ->assertOk()
        ->assertSee('Past Trivia')
        ->assertSee('Reopen');
});
