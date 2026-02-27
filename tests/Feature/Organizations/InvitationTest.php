<?php

use App\Enums\OrganizationRole;
use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('owner can send invitation', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);

    Livewire\Livewire::actingAs($owner)
        ->test('pages::organizations.settings', ['organization' => $organization])
        ->set('inviteEmail', 'newuser@example.com')
        ->set('inviteRole', 'scorekeeper')
        ->call('invite');

    Mail::assertSent(OrganizationInvitationMail::class, function ($mail) {
        return $mail->hasTo('newuser@example.com');
    });

    expect($organization->invitations()->count())->toBe(1)
        ->and($organization->invitations()->first()->email)->toBe('newuser@example.com')
        ->and($organization->invitations()->first()->role)->toBe('scorekeeper');
});

test('cannot invite existing member', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $member = User::factory()->create(['email' => 'member@example.com']);
    $organization = Organization::factory()->create();
    $organization->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);
    $organization->users()->attach($member, ['role' => OrganizationRole::Scorekeeper->value]);

    Livewire\Livewire::actingAs($owner)
        ->test('pages::organizations.settings', ['organization' => $organization])
        ->set('inviteEmail', 'member@example.com')
        ->set('inviteRole', 'scorekeeper')
        ->call('invite')
        ->assertHasErrors('inviteEmail');

    Mail::assertNothingSent();
});

test('cannot invite email with pending invitation', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);

    OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'pending@example.com',
        'invited_by' => $owner->id,
    ]);

    Livewire\Livewire::actingAs($owner)
        ->test('pages::organizations.settings', ['organization' => $organization])
        ->set('inviteEmail', 'pending@example.com')
        ->set('inviteRole', 'scorekeeper')
        ->call('invite')
        ->assertHasErrors('inviteEmail');

    Mail::assertNothingSent();
});

test('scorekeeper cannot send invitation', function () {
    $scorekeeper = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($scorekeeper, ['role' => OrganizationRole::Scorekeeper->value]);

    Livewire\Livewire::actingAs($scorekeeper)
        ->test('pages::organizations.settings', ['organization' => $organization])
        ->assertForbidden();
});

test('owner can cancel pending invitation', function () {
    $owner = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'invited_by' => $owner->id,
    ]);

    Livewire\Livewire::actingAs($owner)
        ->test('pages::organizations.settings', ['organization' => $organization])
        ->call('cancelInvitation', $invitation->id);

    expect(OrganizationInvitation::find($invitation->id))->toBeNull();
});

test('user can accept invitation', function () {
    $user = User::factory()->create(['email' => 'invited@example.com']);
    $owner = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'invited@example.com',
        'role' => 'scorekeeper',
        'invited_by' => $owner->id,
    ]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::invitations.accept', ['token' => $invitation->token])
        ->call('accept')
        ->assertRedirect(route('organizations.show', $organization));

    expect($organization->users()->where('user_id', $user->id)->first()->pivot->role)->toBe('scorekeeper')
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('cannot accept invitation with wrong email', function () {
    $user = User::factory()->create(['email' => 'wrong@example.com']);
    $owner = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'correct@example.com',
        'invited_by' => $owner->id,
    ]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::invitations.accept', ['token' => $invitation->token])
        ->assertSee('Email Mismatch')
        ->call('accept');

    expect($organization->users()->where('user_id', $user->id)->first())->toBeNull();
});

test('cannot accept already accepted invitation', function () {
    $user = User::factory()->create(['email' => 'invited@example.com']);

    $invitation = OrganizationInvitation::factory()->accepted()->create([
        'email' => 'invited@example.com',
        'invited_by' => User::factory()->create()->id,
    ]);

    $this->actingAs($user)
        ->get(route('invitations.accept', $invitation->token))
        ->assertNotFound();
});

test('invalid token returns 404', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('invitations.accept', 'invalid-token'))
        ->assertNotFound();
});
