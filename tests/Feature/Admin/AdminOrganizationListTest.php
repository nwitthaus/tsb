<?php

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access organization list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.organizations.index'))
        ->assertForbidden();
});

test('admin can see organization list', function () {
    $admin = User::factory()->superAdmin()->create();
    $organizations = Organization::factory()->count(3)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.index')
        ->assertSee($organizations[0]->name)
        ->assertSee($organizations[1]->name)
        ->assertSee($organizations[2]->name);
});

test('organization list shows member and event counts', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create(['name' => 'Trivia Co']);
    $org->users()->attach(User::factory()->create(), ['role' => OrganizationRole::Owner->value]);
    $org->users()->attach(User::factory()->create(), ['role' => OrganizationRole::Scorekeeper->value]);
    Event::factory()->count(3)->create(['organization_id' => $org->id]);

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.index')
        ->assertSee('Trivia Co')
        ->assertSeeInOrder(['2', '3']);
});

test('organization list shows owner names', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create();
    $owner = User::factory()->create(['name' => 'Jane Owner']);
    $org->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.index')
        ->assertSee('Jane Owner');
});

test('organization list is searchable by name', function () {
    $admin = User::factory()->superAdmin()->create();
    Organization::factory()->create(['name' => 'Trivia Masters']);
    Organization::factory()->create(['name' => 'Quiz Kings']);

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.index')
        ->set('search', 'Trivia')
        ->assertSee('Trivia Masters')
        ->assertDontSee('Quiz Kings');
});

test('organization list is searchable by slug', function () {
    $admin = User::factory()->superAdmin()->create();
    Organization::factory()->create(['name' => 'Trivia Masters', 'slug' => 'trivia-masters']);
    Organization::factory()->create(['name' => 'Quiz Kings', 'slug' => 'quiz-kings']);

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.index')
        ->set('search', 'quiz-kings')
        ->assertDontSee('Trivia Masters')
        ->assertSee('Quiz Kings');
});

test('admin can delete an organization', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.index')
        ->call('deleteOrganization', $org->id);

    $this->assertDatabaseMissing('organizations', ['id' => $org->id]);
});

test('organization list has link to create organization', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.index')
        ->assertSeeHtml(route('admin.organizations.create'));
});
