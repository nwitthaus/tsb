<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access event list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.events.index'))
        ->assertForbidden();
});

test('admin can see event list', function () {
    $admin = User::factory()->superAdmin()->create();
    $events = Event::factory()->count(3)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.index')
        ->assertSee($events[0]->name)
        ->assertSee($events[1]->name)
        ->assertSee($events[2]->name);
});

test('event list shows host name', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create(['name' => 'Jane Host']);
    Event::factory()->for($host)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.index')
        ->assertSee('Jane Host');
});

test('event list is searchable', function () {
    $admin = User::factory()->superAdmin()->create();
    Event::factory()->create(['name' => 'Monday Trivia']);
    Event::factory()->create(['name' => 'Friday Quiz']);

    Livewire::actingAs($admin)
        ->test('pages::admin.events.index')
        ->set('search', 'Monday')
        ->assertSee('Monday Trivia')
        ->assertDontSee('Friday Quiz');
});

test('admin can delete an event', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.index')
        ->call('deleteEvent', $event->id);

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});

test('event list has link to create event', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.index')
        ->assertSeeHtml(route('admin.events.create'));
});

test('event list shows active and ended badges', function () {
    $admin = User::factory()->superAdmin()->create();
    Event::factory()->create(['name' => 'Active Event']);
    Event::factory()->ended()->create(['name' => 'Ended Event']);

    Livewire::actingAs($admin)
        ->test('pages::admin.events.index')
        ->assertSee('Active')
        ->assertSee('Ended');
});
