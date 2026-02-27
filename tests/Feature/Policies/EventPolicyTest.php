<?php

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;

test('org owner can view event', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    expect($user->can('view', $event))->toBeTrue();
});

test('org owner can update event', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    expect($user->can('update', $event))->toBeTrue();
});

test('org owner can delete event', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    expect($user->can('delete', $event))->toBeTrue();
});

test('org scorekeeper can view event', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    expect($user->can('view', $event))->toBeTrue();
});

test('org scorekeeper cannot update event', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    expect($user->can('update', $event))->toBeFalse();
});

test('org scorekeeper cannot delete event', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    expect($user->can('delete', $event))->toBeFalse();
});

test('non-member cannot view event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    expect($user->can('view', $event))->toBeFalse();
});

test('non-member cannot update event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    expect($user->can('update', $event))->toBeFalse();
});

test('non-member cannot delete event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    expect($user->can('delete', $event))->toBeFalse();
});

test('any authenticated user can create event', function () {
    $user = User::factory()->create();

    expect($user->can('create', Event::class))->toBeTrue();
});

test('super admin bypasses all checks', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    expect($admin->can('view', $event))->toBeTrue()
        ->and($admin->can('update', $event))->toBeTrue()
        ->and($admin->can('delete', $event))->toBeTrue()
        ->and($admin->can('create', Event::class))->toBeTrue();
});
