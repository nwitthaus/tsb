<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('owner can view organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    expect($user->can('view', $organization))->toBeTrue();
});

test('owner can update organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    expect($user->can('update', $organization))->toBeTrue();
});

test('owner can delete organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    expect($user->can('delete', $organization))->toBeTrue();
});

test('owner can invite to organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    expect($user->can('invite', $organization))->toBeTrue();
});

test('owner can remove member from organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    expect($user->can('removeMember', $organization))->toBeTrue();
});

test('scorekeeper can view organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);

    expect($user->can('view', $organization))->toBeTrue();
});

test('scorekeeper cannot update organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);

    expect($user->can('update', $organization))->toBeFalse();
});

test('scorekeeper cannot delete organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);

    expect($user->can('delete', $organization))->toBeFalse();
});

test('scorekeeper cannot invite to organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);

    expect($user->can('invite', $organization))->toBeFalse();
});

test('scorekeeper cannot remove member from organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);

    expect($user->can('removeMember', $organization))->toBeFalse();
});

test('non-member cannot view organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    expect($user->can('view', $organization))->toBeFalse();
});

test('super admin can do everything', function () {
    $admin = User::factory()->superAdmin()->create();
    $organization = Organization::factory()->create();

    expect($admin->can('view', $organization))->toBeTrue()
        ->and($admin->can('update', $organization))->toBeTrue()
        ->and($admin->can('delete', $organization))->toBeTrue()
        ->and($admin->can('invite', $organization))->toBeTrue()
        ->and($admin->can('removeMember', $organization))->toBeTrue();
});
