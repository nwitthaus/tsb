<?php

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;

test('organization has many events', function () {
    $organization = Organization::factory()->create();
    Event::factory()->count(3)->create(['organization_id' => $organization->id]);

    expect($organization->events)->toHaveCount(3);
});

test('organization belongs to many users with role pivot', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    expect($organization->users)->toHaveCount(1)
        ->and($organization->users->first()->pivot->role)->toBe(OrganizationRole::Owner->value);
});

test('organization owners scope returns only owners', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create();
    $scorekeeper = User::factory()->create();

    $organization->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);
    $organization->users()->attach($scorekeeper, ['role' => OrganizationRole::Scorekeeper->value]);

    expect($organization->owners)->toHaveCount(1)
        ->and($organization->owners->first()->id)->toBe($owner->id);
});

test('slug is generated from organization name', function () {
    $slug = Organization::generateSlug('My Trivia Org');

    expect($slug)->toBe('my-trivia-org');
});

test('slug is unique with auto-increment suffix', function () {
    Organization::factory()->create(['slug' => 'trivia-org']);

    $slug = Organization::generateSlug('Trivia Org');

    expect($slug)->toBe('trivia-org-1');
});

test('user isOrganizationOwner returns true for owner', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    expect($user->isOrganizationOwner($organization))->toBeTrue();
});

test('user isOrganizationOwner returns false for scorekeeper', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);

    expect($user->isOrganizationOwner($organization))->toBeFalse();
});

test('user isOrganizationMember returns true for any member', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);

    expect($user->isOrganizationMember($organization))->toBeTrue();
});

test('user isOrganizationMember returns false for non-member', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    expect($user->isOrganizationMember($organization))->toBeFalse();
});

test('user hasOrganizationRole returns correct results', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);

    expect($user->hasOrganizationRole($organization, OrganizationRole::Scorekeeper))->toBeTrue()
        ->and($user->hasOrganizationRole($organization, OrganizationRole::Owner))->toBeFalse();
});
