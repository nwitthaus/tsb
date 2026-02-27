<?php

use App\Enums\OrganizationRole;
use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Organization Settings')] class extends Component {
    public Organization $organization;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:100|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
    public string $slug = '';

    public string $inviteEmail = '';

    public string $inviteRole = 'scorekeeper';

    public function mount(Organization $organization): void
    {
        $this->authorize('update', $organization);
        $this->organization = $organization;
        $this->name = $organization->name;
        $this->slug = $organization->slug;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|unique:organizations,slug,' . $this->organization->id,
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> */
    #[Computed]
    public function members(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->organization->users()->orderBy('name')->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrganizationInvitation> */
    #[Computed]
    public function pendingInvitations(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->organization->invitations()
            ->whereNull('accepted_at')
            ->with('inviter')
            ->latest()
            ->get();
    }

    public function save(): void
    {
        $this->authorize('update', $this->organization);
        $validated = $this->validate();

        $this->organization->update($validated);

        Flux::toast(__('Organization updated.'));
    }

    public function invite(): void
    {
        $this->authorize('invite', $this->organization);

        $this->validate([
            'inviteEmail' => 'required|email',
            'inviteRole' => 'required|in:owner,scorekeeper',
        ]);

        // Check if already a member
        if ($this->organization->users()->where('email', $this->inviteEmail)->first() !== null) {
            $this->addError('inviteEmail', __('This person is already a member.'));

            return;
        }

        // Check if already has a pending invitation
        if ($this->organization->invitations()->where('email', $this->inviteEmail)->whereNull('accepted_at')->first() !== null) {
            $this->addError('inviteEmail', __('This person already has a pending invitation.'));

            return;
        }

        $invitation = $this->organization->invitations()->create([
            'email' => $this->inviteEmail,
            'role' => $this->inviteRole,
            'token' => Str::random(64),
            'invited_by' => auth()->id(),
        ]);

        Mail::to($this->inviteEmail)->send(
            new OrganizationInvitationMail($invitation)
        );

        $this->reset('inviteEmail', 'inviteRole');

        Flux::toast(__('Invitation sent.'));
    }

    public function cancelInvitation(int $invitationId): void
    {
        $this->authorize('invite', $this->organization);

        $invitation = $this->organization->invitations()->findOrFail($invitationId);
        $invitation->delete();

        Flux::toast(__('Invitation cancelled.'));
    }

    public function removeMember(int $userId): void
    {
        $this->authorize('removeMember', $this->organization);

        // Prevent removing the last owner
        if ($this->organization->owners()->count() <= 1) {
            $member = $this->organization->users()->where('user_id', $userId)->first();
            if ($member && $member->pivot->role === OrganizationRole::Owner->value) {
                Flux::toast(__('Cannot remove the last owner.'), variant: 'danger');

                return;
            }
        }

        $this->organization->users()->detach($userId);

        Flux::toast(__('Member removed.'));
    }

    public function deleteOrganization(): void
    {
        $this->authorize('delete', $this->organization);

        $this->organization->delete();

        Flux::toast(__('Organization deleted.'));

        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div class="max-w-lg space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Organization Settings') }}</flux:heading>
        <flux:subheading>
            <flux:link :href="route('organizations.show', $organization)" wire:navigate>{{ $organization->name }}</flux:link>
        </flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:input
            wire:model="name"
            :label="__('Organization Name')"
            required
            autofocus
        />

        <flux:input
            wire:model="slug"
            :label="__('URL Slug')"
            required
        />

        <div class="flex justify-end">
            <flux:button variant="primary" type="submit">
                {{ __('Save Changes') }}
            </flux:button>
        </div>
    </form>

    <flux:separator />

    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Members') }}</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Role') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->members as $member)
                    <flux:table.row :key="$member->id">
                        <flux:table.cell>{{ $member->name }}</flux:table.cell>
                        <flux:table.cell>{{ $member->email }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$member->pivot->role === 'owner' ? 'amber' : 'zinc'">
                                {{ ucfirst($member->pivot->role) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($member->id !== auth()->id())
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="removeMember({{ $member->id }})"
                                    wire:confirm="{{ __('Remove this member from the organization?') }}"
                                />
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:separator />

    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Invite Member') }}</flux:heading>

        <form wire:submit="invite" class="space-y-4">
            <flux:input
                wire:model="inviteEmail"
                type="email"
                :label="__('Email Address')"
                :placeholder="__('colleague@example.com')"
                required
            />

            <flux:select wire:model="inviteRole" :label="__('Role')">
                <flux:select.option value="owner">{{ __('Owner') }}</flux:select.option>
                <flux:select.option value="scorekeeper">{{ __('Scorekeeper') }}</flux:select.option>
            </flux:select>

            <div class="flex justify-end">
                <flux:button variant="primary" type="submit">
                    {{ __('Send Invitation') }}
                </flux:button>
            </div>
        </form>
    </div>

    @if ($this->pendingInvitations->isNotEmpty())
        <flux:separator />

        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Pending Invitations') }}</flux:heading>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Email') }}</flux:table.column>
                    <flux:table.column>{{ __('Role') }}</flux:table.column>
                    <flux:table.column>{{ __('Invited By') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->pendingInvitations as $invitation)
                        <flux:table.row :key="$invitation->id">
                            <flux:table.cell>{{ $invitation->email }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$invitation->role === 'owner' ? 'amber' : 'zinc'">
                                    {{ ucfirst($invitation->role) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $invitation->inviter->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="x-mark"
                                    wire:click="cancelInvitation({{ $invitation->id }})"
                                    wire:confirm="{{ __('Cancel this invitation?') }}"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif

    <flux:separator />

    <div class="space-y-3">
        <flux:heading size="lg">{{ __('Danger Zone') }}</flux:heading>
        <flux:card class="border-red-200 dark:border-red-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading>{{ __('Delete Organization') }}</flux:heading>
                    <flux:subheading>{{ __('Permanently delete this organization and all its events.') }}</flux:subheading>
                </div>
                <flux:button
                    variant="danger"
                    wire:click="deleteOrganization"
                    wire:confirm="{{ __('Delete this organization? This will permanently remove the organization, all events, teams, rounds, and scores. This cannot be undone.') }}"
                >
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </flux:card>
    </div>
</div>
