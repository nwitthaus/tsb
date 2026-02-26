<?php

use App\Models\Event;

test('landing page is accessible', function () {
    $this->get('/')->assertOk();
});

test('join code redirects to scoreboard', function () {
    Event::factory()->create(['slug' => 'quiz42']);

    Livewire\Livewire::test('pages::welcome')
        ->set('code', 'quiz42')
        ->call('join')
        ->assertRedirect('/quiz42');
});

test('invalid join code shows error', function () {
    Livewire\Livewire::test('pages::welcome')
        ->set('code', 'nonexistent')
        ->call('join')
        ->assertHasErrors('code');
});
