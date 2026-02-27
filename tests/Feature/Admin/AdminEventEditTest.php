<?php

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access edit event page', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.events.edit', $event))
        ->assertForbidden();
});

test('admin can see edit event form', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->assertSet('name', $event->name)
        ->assertSet('organization_id', $event->organization_id);
});

test('admin can update event details', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();
    $newOrg = Organization::factory()->create(['name' => 'New Org']);

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->set('name', 'Updated Event Name')
        ->set('organization_id', $newOrg->id)
        ->call('save')
        ->assertRedirect(route('admin.events.index'));

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'name' => 'Updated Event Name',
        'organization_id' => $newOrg->id,
    ]);
});

test('admin can end an active event', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    expect($event->ended_at)->toBeNull();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->call('endEvent');

    $event->refresh();
    expect($event->ended_at)->not->toBeNull();
});

test('admin can reopen an ended event', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->ended()->create();

    expect($event->ended_at)->not->toBeNull();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->call('reopenEvent');

    $event->refresh();
    expect($event->ended_at)->toBeNull();
});

test('admin can delete an event from edit page', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->call('deleteEvent')
        ->assertRedirect(route('admin.events.index'));

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});

test('edit event validates required fields', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('edit event has links to manage scoring and teams', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->assertSeeHtml(route('events.show', $event))
        ->assertSeeHtml(route('events.teams', $event));
});
