<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access edit organization page', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.organizations.edit', $org))
        ->assertForbidden();
});

test('admin can see edit organization form', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.edit', ['organization' => $org])
        ->assertSet('name', $org->name)
        ->assertSet('slug', $org->slug);
});

test('admin can update organization name and slug', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.edit', ['organization' => $org])
        ->set('name', 'Updated Name')
        ->set('slug', 'updated-name')
        ->call('save')
        ->assertRedirect(route('admin.organizations.index'));

    $this->assertDatabaseHas('organizations', [
        'id' => $org->id,
        'name' => 'Updated Name',
        'slug' => 'updated-name',
    ]);
});

test('edit organization validates required fields', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.edit', ['organization' => $org])
        ->set('name', '')
        ->set('slug', '')
        ->call('save')
        ->assertHasErrors(['name', 'slug']);
});

test('edit organization validates unique slug', function () {
    $admin = User::factory()->superAdmin()->create();
    $org1 = Organization::factory()->create(['slug' => 'taken-slug']);
    $org2 = Organization::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.edit', ['organization' => $org2])
        ->set('slug', 'taken-slug')
        ->call('save')
        ->assertHasErrors(['slug']);
});

test('edit organization allows keeping own slug', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create(['slug' => 'my-slug']);

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.edit', ['organization' => $org])
        ->set('name', 'New Name')
        ->set('slug', 'my-slug')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('organizations', [
        'id' => $org->id,
        'name' => 'New Name',
        'slug' => 'my-slug',
    ]);
});

test('admin can see members on edit page', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create();
    $member = User::factory()->create(['name' => 'Team Member']);
    $org->users()->attach($member, ['role' => OrganizationRole::Scorekeeper->value]);

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.edit', ['organization' => $org])
        ->assertSee('Team Member')
        ->assertSee('Scorekeeper');
});

test('admin can remove a member', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create();
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $org->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);
    $org->users()->attach($member, ['role' => OrganizationRole::Scorekeeper->value]);

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.edit', ['organization' => $org])
        ->call('removeMember', $member->id);

    $this->assertDatabaseMissing('organization_user', [
        'organization_id' => $org->id,
        'user_id' => $member->id,
    ]);
});

test('admin cannot remove the last owner', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create();
    $owner = User::factory()->create();
    $org->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.edit', ['organization' => $org])
        ->call('removeMember', $owner->id);

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $org->id,
        'user_id' => $owner->id,
    ]);
});

test('admin can delete organization from edit page', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.edit', ['organization' => $org])
        ->call('deleteOrganization')
        ->assertRedirect(route('admin.organizations.index'));

    $this->assertDatabaseMissing('organizations', ['id' => $org->id]);
});

test('edit organization has links to org settings and view', function () {
    $admin = User::factory()->superAdmin()->create();
    $org = Organization::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.organizations.edit', ['organization' => $org])
        ->assertSeeHtml(route('organizations.settings', $org))
        ->assertSeeHtml(route('organizations.show', $org));
});
