<?php

use App\Models\User;
use Livewire\Livewire;

test('admin dashboard shows overview heading', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSee(__('Admin Overview'));
});
