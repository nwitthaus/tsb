<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

test('admin overview shows stat cards', function () {
    $admin = User::factory()->superAdmin()->create();
    User::factory()->count(3)->create();
    Event::factory()->count(2)->create();
    Event::factory()->ended()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSee('4')
        ->assertSee('3')
        ->assertSee('2');
});

test('admin overview has links to users and events pages', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSeeHtml(route('admin.users.index'))
        ->assertSeeHtml(route('admin.events.index'));
});
