<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('owner can view organization settings', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    $this->actingAs($user)
        ->get(route('organizations.settings', $organization))
        ->assertOk();
});

test('scorekeeper cannot view organization settings', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);

    $this->actingAs($user)
        ->get(route('organizations.settings', $organization))
        ->assertForbidden();
});

test('owner can update organization name', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['name' => 'Old Name']);
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.settings', ['organization' => $organization])
        ->set('name', 'New Name')
        ->call('save');

    expect($organization->fresh()->name)->toBe('New Name');
});

test('owner can update organization slug', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['slug' => 'old-slug']);
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.settings', ['organization' => $organization])
        ->set('slug', 'new-slug')
        ->call('save');

    expect($organization->fresh()->slug)->toBe('new-slug');
});

test('owner can remove a member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);
    $organization->users()->attach($member, ['role' => OrganizationRole::Scorekeeper->value]);

    Livewire\Livewire::actingAs($owner)
        ->test('pages::organizations.settings', ['organization' => $organization])
        ->call('removeMember', $member->id);

    expect($organization->users()->where('user_id', $member->id)->exists())->toBeFalse();
});

test('cannot remove the last owner', function () {
    $owner = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($owner)
        ->test('pages::organizations.settings', ['organization' => $organization])
        ->call('removeMember', $owner->id);

    expect($organization->users()->where('user_id', $owner->id)->exists())->toBeTrue();
});

test('owner can delete organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.settings', ['organization' => $organization])
        ->call('deleteOrganization')
        ->assertRedirect(route('dashboard'));

    expect(Organization::find($organization->id))->toBeNull();
});

test('settings page shows members', function () {
    $owner = User::factory()->create(['name' => 'Alice Owner']);
    $scorekeeper = User::factory()->create(['name' => 'Bob Scorer']);
    $organization = Organization::factory()->create();
    $organization->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);
    $organization->users()->attach($scorekeeper, ['role' => OrganizationRole::Scorekeeper->value]);

    $this->actingAs($owner)
        ->get(route('organizations.settings', $organization))
        ->assertSee('Alice Owner')
        ->assertSee('Bob Scorer');
});
