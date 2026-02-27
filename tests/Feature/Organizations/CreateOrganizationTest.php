<?php

use App\Models\Organization;
use App\Models\User;

test('guests cannot access create organization page', function () {
    $this->get(route('organizations.create'))->assertRedirect(route('login'));
});

test('authenticated user can view create organization page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('organizations.create'))
        ->assertOk();
});

test('user can create an organization', function () {
    $user = User::factory()->create();

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.create')
        ->set('name', 'My Trivia Co')
        ->set('slug', 'my-trivia-co')
        ->call('save')
        ->assertRedirect(route('organizations.show', Organization::first()));

    expect(Organization::count())->toBe(1)
        ->and(Organization::first()->name)->toBe('My Trivia Co')
        ->and(Organization::first()->slug)->toBe('my-trivia-co');
});

test('creator is attached as owner', function () {
    $user = User::factory()->create();

    Livewire\Livewire::actingAs($user)
        ->test('pages::organizations.create')
        ->set('name', 'My Trivia Co')
        ->set('slug', 'my-trivia-co')
        ->call('save');

    $org = Organization::first();
    expect($org->users()->where('user_id', $user->id)->first()->pivot->role)->toBe('owner');
});

test('slug auto-generates from name', function () {
    Livewire\Livewire::actingAs(User::factory()->create())
        ->test('pages::organizations.create')
        ->set('name', 'Joe\'s Bar Trivia')
        ->assertSet('slug', 'joes-bar-trivia');
});

test('organization name is required', function () {
    Livewire\Livewire::actingAs(User::factory()->create())
        ->test('pages::organizations.create')
        ->set('name', '')
        ->set('slug', 'some-slug')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('slug must be unique', function () {
    Organization::factory()->create(['slug' => 'taken']);

    Livewire\Livewire::actingAs(User::factory()->create())
        ->test('pages::organizations.create')
        ->set('name', 'My Org')
        ->set('slug', 'taken')
        ->call('save')
        ->assertHasErrors(['slug' => 'unique']);
});
