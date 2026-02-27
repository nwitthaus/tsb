<?php

use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access admin create event page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.events.create'))
        ->assertForbidden();
});

test('admin can see create event form with organization dropdown', function () {
    $admin = User::factory()->superAdmin()->create();
    Organization::factory()->create(['name' => 'Trivia Co']);

    Livewire::actingAs($admin)
        ->test('pages::admin.events.create')
        ->assertSee('Trivia Co');
});

test('admin can create an event assigned to any organization', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create(['name' => 'Trivia Co']);

    Livewire::actingAs($admin)
        ->test('pages::admin.events.create')
        ->set('name', 'Wednesday Trivia')
        ->set('starts_at', '2026-03-15T19:00')
        ->set('organization_id', $org->id)
        ->call('save')
        ->assertRedirect(route('admin.events.index'));

    $this->assertDatabaseHas('events', [
        'name' => 'Wednesday Trivia',
        'organization_id' => $org->id,
    ]);
});

test('admin create event validates required fields', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.create')
        ->call('save')
        ->assertHasErrors(['name', 'starts_at', 'organization_id']);
});

test('admin create event generates slug automatically', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.create')
        ->set('name', 'Friday Night Trivia')
        ->set('starts_at', '2026-03-20T20:00')
        ->set('organization_id', $org->id)
        ->call('save');

    $this->assertDatabaseHas('events', [
        'slug' => 'friday-night-trivia',
    ]);
});
