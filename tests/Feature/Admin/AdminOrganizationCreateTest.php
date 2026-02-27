<?php

use App\Enums\OrganizationRole;
use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access admin create organization page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.organizations.create'))
        ->assertForbidden();
});

test('admin can see create organization form with user dropdown', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create(['name' => 'Jane Doe']);

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.create')
        ->assertSee('Jane Doe');
});

test('admin can create an organization with an owner', function () {
    $admin = User::factory()->superAdmin()->create();
    $owner = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.create')
        ->set('name', 'Trivia Co')
        ->set('owner_id', $owner->id)
        ->call('save')
        ->assertRedirect(route('admin.organizations.index'));

    $this->assertDatabaseHas('organizations', [
        'name' => 'Trivia Co',
    ]);

    $this->assertDatabaseHas('organization_user', [
        'user_id' => $owner->id,
        'role' => OrganizationRole::Owner->value,
    ]);
});

test('admin create organization validates required fields', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.create')
        ->call('save')
        ->assertHasErrors(['name', 'owner_id']);
});

test('admin create organization generates slug automatically', function () {
    $admin = User::factory()->superAdmin()->create();
    $owner = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.create')
        ->set('name', 'Friday Night Trivia')
        ->set('owner_id', $owner->id)
        ->call('save');

    $this->assertDatabaseHas('organizations', [
        'slug' => 'friday-night-trivia',
    ]);
});
