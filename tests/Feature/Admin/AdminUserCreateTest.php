<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access create user page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.create'))
        ->assertForbidden();
});

test('admin can see create user form', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->assertSee('Create User');
});

test('admin can create a user', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->set('name', 'New User')
        ->set('email', 'newuser@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
    ]);
});

test('admin can create a super admin user', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->set('name', 'Admin User')
        ->set('email', 'admin@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('is_super_admin', true)
        ->call('save')
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'admin@example.com',
        'is_super_admin' => true,
    ]);
});

test('create user validates required fields', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->call('save')
        ->assertHasErrors(['name', 'email', 'password']);
});

test('create user validates unique email', function () {
    $admin = User::factory()->superAdmin()->create();
    $existingUser = User::factory()->create(['email' => 'taken@example.com']);

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->set('name', 'Test User')
        ->set('email', 'taken@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertHasErrors('email');
});

test('create user validates password confirmation', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'different123')
        ->call('save')
        ->assertHasErrors('password');
});

test('admin can create a user and assign to an organization', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create(['name' => 'Trivia Co']);

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->set('name', 'Org User')
        ->set('email', 'orguser@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('organization_id', $org->id)
        ->set('organization_role', 'owner')
        ->call('save')
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', ['email' => 'orguser@example.com']);
    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $org->id,
        'role' => OrganizationRole::Owner->value,
    ]);
});

test('admin can create a user without an organization', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->set('name', 'Solo User')
        ->set('email', 'solo@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'solo@example.com')->first();
    expect($user->organizations)->toBeEmpty();
});

test('create user shows organization dropdown', function () {
    $admin = User::factory()->superAdmin()->create();
    Organization::factory()->create(['name' => 'Trivia Co']);

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->assertSee('Trivia Co');
});
