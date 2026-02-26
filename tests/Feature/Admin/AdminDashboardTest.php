<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

test('admin dashboard shows all users', function () {
    $admin = User::factory()->superAdmin()->create();
    $users = User::factory()->count(3)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSee($users[0]->name)
        ->assertSee($users[1]->name)
        ->assertSee($users[2]->name);
});

test('admin dashboard shows all events from any user', function () {
    $admin = User::factory()->superAdmin()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $event1 = Event::factory()->create(['user_id' => $user1->id, 'name' => 'User One Event']);
    $event2 = Event::factory()->create(['user_id' => $user2->id, 'name' => 'User Two Event']);

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSee('User One Event')
        ->assertSee('User Two Event');
});

test('admin can delete a user', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->call('deleteUser', $user->id);

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('admin cannot delete themselves', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->call('deleteUser', $admin->id);

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('admin can delete any event', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create(['name' => 'Some Event']);

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->call('deleteEvent', $event->id);

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});
