<?php

use App\Models\Event;
use App\Models\Team;

test('team belongs to an event', function () {
    $team = Team::factory()->create();

    expect($team->event)->toBeInstanceOf(Event::class);
});

test('team can be soft deleted and restored', function () {
    $team = Team::factory()->create();

    $team->delete();
    expect($team->trashed())->toBeTrue();
    expect(Team::count())->toBe(0);

    $team->restore();
    expect($team->trashed())->toBeFalse();
    expect(Team::count())->toBe(1);
});

test('display name returns name when both name and table number exist', function () {
    $team = Team::factory()->create(['name' => 'Quizly Bears', 'table_number' => 3]);

    expect($team->displayName())->toBe('Quizly Bears');
});

test('display name returns name when only name exists', function () {
    $team = Team::factory()->nameOnly()->create(['name' => 'Brain Stormers']);

    expect($team->displayName())->toBe('Brain Stormers');
});

test('display name returns table number when only table number exists', function () {
    $team = Team::factory()->tableOnly()->create(['table_number' => 12]);

    expect($team->displayName())->toBe('Table 12');
});
