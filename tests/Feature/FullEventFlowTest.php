<?php

use App\Models\User;

test('complete event flow: create, add teams, add rounds, score, end', function () {
    $user = User::factory()->create();

    // Create event
    Livewire\Livewire::actingAs($user)
        ->test('pages::events.create')
        ->set('name', 'Integration Test Trivia')
        ->set('slug', 'integration-test')
        ->call('save');

    $event = $user->events()->first();
    expect($event)->not->toBeNull();

    // Set up teams and rounds, enter scores
    $grid = Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event]);

    $grid->call('addTeam', 'Team Alpha', 1);
    $grid->call('addTeam', 'Team Beta', 2);
    $grid->call('addRound');
    $grid->call('addRound');

    $event->refresh();
    $teams = $event->teams;
    $rounds = $event->rounds;

    $grid->call('saveScore', $teams[0]->id, $rounds[0]->id, '8');
    $grid->call('saveScore', $teams[0]->id, $rounds[1]->id, '7');
    $grid->call('saveScore', $teams[1]->id, $rounds[0]->id, '9');
    $grid->call('saveScore', $teams[1]->id, $rounds[1]->id, '6');

    // Verify public scoreboard shows both teams (tied at 15 — order not guaranteed)
    $this->get('/integration-test')
        ->assertOk()
        ->assertSee('Team Alpha')
        ->assertSee('Team Beta')
        ->assertSee('Live Scoreboard');

    // End event
    $grid->call('endEvent');
    expect($event->fresh()->isActive())->toBeFalse();

    // Verify final scores on public scoreboard
    $this->get('/integration-test')
        ->assertOk()
        ->assertSee('Final Scores')
        ->assertSee('Team Alpha')
        ->assertSee('Team Beta');
});
