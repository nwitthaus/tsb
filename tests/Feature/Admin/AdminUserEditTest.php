<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('non-admin cannot access edit user page', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.edit', $target))
        ->assertForbidden();
});

test('admin can see edit user form', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->assertSet('name', $user->name)
        ->assertSet('email', $user->email);
});

test('admin can update a user', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->set('name', 'Updated Name')
        ->set('email', 'updated@example.com')
        ->call('save')
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

test('admin can toggle super admin status', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->set('is_super_admin', true)
        ->call('save');

    expect($user->fresh()->is_super_admin)->toBeTrue();
});

test('admin cannot demote themselves', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $admin])
        ->set('is_super_admin', false)
        ->call('save');

    expect($admin->fresh()->is_super_admin)->toBeTrue();
});

test('admin can update password', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();
    $oldPassword = $user->password;

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->set('password', 'newpassword123')
        ->set('password_confirmation', 'newpassword123')
        ->call('save');

    expect($user->fresh()->password)->not->toBe($oldPassword);
    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
});

test('admin can save without changing password', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();
    $oldPassword = $user->password;

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->set('name', 'Changed Name')
        ->call('save');

    expect($user->fresh()->password)->toBe($oldPassword);
    expect($user->fresh()->name)->toBe('Changed Name');
});

test('edit user validates unique email ignoring current user', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();
    $otherUser = User::factory()->create(['email' => 'other@example.com']);

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->set('email', 'other@example.com')
        ->call('save')
        ->assertHasErrors('email');
});

test('edit user allows keeping same email', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors('email');
});

test('super admin toggle is disabled when editing yourself', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $admin])
        ->assertSet('isSelf', true);
});
