<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows user organizations', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['name' => 'My Trivia Co']);
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    // Create a second org so redirect doesn't kick in
    $org2 = Organization::factory()->create(['name' => 'Other Org']);
    $org2->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('My Trivia Co')
        ->assertSee('Other Org');
});

test('dashboard redirects to org show when user has exactly one organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('organizations.show', $organization));
});

test('dashboard shows create organization button when no organizations', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Create Organization');
});
