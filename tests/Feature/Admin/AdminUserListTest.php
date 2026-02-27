<?php

use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access user list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('admin can see user list', function () {
    $admin = User::factory()->superAdmin()->create();
    $users = User::factory()->count(3)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->assertSee($users[0]->name)
        ->assertSee($users[1]->name)
        ->assertSee($users[2]->name);
});

test('user list shows admin badge for super admins', function () {
    $admin = User::factory()->superAdmin()->create();
    User::factory()->superAdmin()->create(['name' => 'Other Admin']);

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->assertSee('Admin');
});

test('user list is searchable', function () {
    $admin = User::factory()->superAdmin()->create();
    User::factory()->create(['name' => 'Jane Doe']);
    User::factory()->create(['name' => 'John Smith']);

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->set('search', 'Jane')
        ->assertSee('Jane Doe')
        ->assertDontSee('John Smith');
});

test('admin can delete a user from user list', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->call('deleteUser', $user->id);

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('admin cannot delete themselves from user list', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->call('deleteUser', $admin->id);

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('user list has link to create user', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->assertSeeHtml(route('admin.users.create'));
});
