<?php

test('event edit page shows join code', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent(['slug' => 'quiz42']);

    $this->actingAs($user)
        ->get(route('organizations.events.edit', [$organization, $event]))
        ->assertSee('quiz42');
});

test('event edit page shows scoreboard URL', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent(['slug' => 'quiz42']);

    $this->actingAs($user)
        ->get(route('organizations.events.edit', [$organization, $event]))
        ->assertSee('/quiz42');
});

test('event edit page shows QR code SVG', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent(['slug' => 'quiz42']);

    $this->actingAs($user)
        ->get(route('organizations.events.edit', [$organization, $event]))
        ->assertSee('<svg', false)
        ->assertSee('Share Scoreboard');
});

test('event edit page shows copy link button', function () {
    ['user' => $user, 'organization' => $organization, 'event' => $event] = createOwnerWithEvent(['slug' => 'quiz42']);

    $this->actingAs($user)
        ->get(route('organizations.events.edit', [$organization, $event]))
        ->assertSee('Copy Link');
});
