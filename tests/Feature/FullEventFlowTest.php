<?php

use Livewire\Livewire;

test('complete event flow: create, add teams, add rounds, score, end', function () {
    ['user' => $user, 'organization' => $organization] = createOwnerWithOrganization();

    // Create event
    Livewire::actingAs($user)
        ->test('pages::organizations.events.create', ['organization' => $organization])
        ->set('name', 'Integration Test Trivia')
        ->set('slug', 'integration-test')
        ->set('starts_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save');

    $event = $organization->events()->first();
    expect($event)->not->toBeNull();

    // Add teams via teams manager
    $teamsManager = Livewire::actingAs($user)
        ->test('event-teams-manager', ['event' => $event]);

    $teamsManager->call('addTeam', 'Team Alpha', 1);
    $teamsManager->call('addTeam', 'Team Beta', 2);

    // Set up rounds and enter scores via scoring grid
    $grid = Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event]);

    $grid->call('addRound');
    $grid->call('addRound');

    $event->refresh();
    $teams = $event->teams;
    $rounds = $event->rounds;

    $grid->call('saveScore', $teams[0]->id, $rounds[0]->id, '8');
    $grid->call('saveScore', $teams[0]->id, $rounds[1]->id, '7');
    $grid->call('saveScore', $teams[1]->id, $rounds[0]->id, '9');
    $grid->call('saveScore', $teams[1]->id, $rounds[1]->id, '6');

    // Verify public scoreboard shows both teams (tied at 15 -- order not guaranteed)
    $this->get('/integration-test')
        ->assertOk()
        ->assertSee('Team Alpha')
        ->assertSee('Team Beta')
        ->assertSee('Live');

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
