<?php

use App\Models\Event;
use App\Models\Round;
use App\Models\Score;
use App\Models\Team;

test('public scoreboard is accessible without authentication', function () {
    $event = Event::factory()->create(['slug' => 'test-event']);

    $this->get('/test-event')->assertOk();
});

test('invalid slug returns 404', function () {
    $this->get('/nonexistent-slug')->assertNotFound();
});

test('scoreboard displays event name', function () {
    $event = Event::factory()->create(['slug' => 'my-quiz', 'name' => 'My Quiz Night']);

    $this->get('/my-quiz')->assertSee('My Quiz Night');
});

test('scoreboard shows teams ranked by total score', function () {
    $event = Event::factory()->create(['slug' => 'ranked-quiz']);
    $team1 = Team::factory()->create(['event_id' => $event->id, 'name' => 'Low Scorers']);
    $team2 = Team::factory()->create(['event_id' => $event->id, 'name' => 'High Scorers']);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
    Score::factory()->create(['team_id' => $team1->id, 'round_id' => $round->id, 'value' => 3]);
    Score::factory()->create(['team_id' => $team2->id, 'round_id' => $round->id, 'value' => 8]);

    $this->get('/ranked-quiz')
        ->assertSeeInOrder(['High Scorers', 'Low Scorers']);
});

test('scoreboard shows final scores banner when event is ended', function () {
    $event = Event::factory()->ended()->create(['slug' => 'done-quiz']);

    $this->get('/done-quiz')->assertSee('Final Scores');
});

test('scoreboard hides table column when no teams have table numbers', function () {
    $event = Event::factory()->create(['slug' => 'no-tables']);
    Team::factory()->nameOnly()->create(['event_id' => $event->id]);

    $this->get('/no-tables')->assertDontSee('Table');
});
