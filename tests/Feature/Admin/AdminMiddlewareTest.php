<?php

use App\Models\Event;
use App\Models\User;

test('guest is redirected to login', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('regular user gets 403', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('super admin can access admin dashboard', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful();
});

test('regular user gets 403 on all admin routes', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.users.edit', $user))->assertForbidden();
    $this->actingAs($user)->get(route('admin.events.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.events.create'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.events.edit', $event))->assertForbidden();
});
