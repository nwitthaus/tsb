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

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button
            variant="ghost"
            icon="arrow-left"
            :href="route('organizations.show', $organization)"
            wire:navigate
        />
        <div>
            <flux:heading size="xl">{{ $organization->name }}</flux:heading>
            <flux:subheading>{{ __('Organization Settings') }}</flux:subheading>
        </div>
    </div>

    {{-- General --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('General') }}</flux:heading>
            <flux:subheading>{{ __('Manage your organization\'s name and URL slug.') }}</flux:subheading>
        </div>
        <div class="bg-white p-5 dark:bg-zinc-900">
            <form wire:submit="save" class="flex flex-col items-end gap-3 sm:flex-row">
                <div class="w-full flex-1">
                    <flux:input
                        wire:model="name"
                        :label="__('Organization Name')"
                        required
                        autofocus
                    />
                </div>
                <div class="w-full flex-1">
                    <flux:input
                        wire:model="slug"
                        :label="__('URL Slug')"
                        required
                    />
                </div>
                <flux:button variant="primary" type="submit" class="shrink-0 max-sm:w-full sm:mb-px">
                    {{ __('Save') }}
                </flux:button>
            </form>
        </div>
    </div>

    {{-- Members --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-2">
                <flux:heading size="lg">{{ __('Members') }}</flux:heading>
                <flux:badge size="sm" color="zinc">{{ $this->members->count() }}</flux:badge>
            </div>
            <flux:subheading>{{ __('People who belong to this organization.') }}</flux:subheading>
        </div>
        <div class="bg-white p-5 dark:bg-zinc-900">
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
                                        icon="x-mark"
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
    </div>

    {{-- Invite Member --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Invite Member') }}</flux:heading>
            <flux:subheading>{{ __('Send an invitation to add someone to this organization.') }}</flux:subheading>
        </div>
        <div class="bg-white p-5 dark:bg-zinc-900">
            <form wire:submit="invite" class="flex flex-col items-end gap-3 sm:flex-row">
                <div class="w-full flex-1">
                    <flux:input
                        wire:model="inviteEmail"
                        type="email"
                        :label="__('Email Address')"
                        :placeholder="__('colleague@example.com')"
                        required
                    />
                </div>
                <div class="w-full sm:w-44">
                    <flux:select wire:model="inviteRole" :label="__('Role')">
                        <flux:select.option value="scorekeeper">{{ __('Scorekeeper') }}</flux:select.option>
                        <flux:select.option value="owner">{{ __('Owner') }}</flux:select.option>
                    </flux:select>
                </div>
                <flux:button variant="primary" type="submit" class="shrink-0 max-sm:w-full sm:mb-px">
                    {{ __('Invite') }}
                </flux:button>
            </form>

            @if ($this->pendingInvitations->isNotEmpty())
                <div class="mt-4 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <flux:text class="mb-2 text-xs font-medium uppercase tracking-wider text-zinc-400">{{ __('Pending') }}</flux:text>
                    @foreach ($this->pendingInvitations as $invitation)
                        <div class="flex items-center justify-between py-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:icon.clock variant="mini" class="text-amber-500" />
                                <flux:text>{{ $invitation->email }}</flux:text>
                                <flux:badge size="sm" :color="$invitation->role === 'owner' ? 'amber' : 'zinc'">
                                    {{ ucfirst($invitation->role) }}
                                </flux:badge>
                                <flux:text class="text-xs">{{ __('via :name', ['name' => $invitation->inviter->name]) }}</flux:text>
                            </div>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="x-mark"
                                wire:click="cancelInvitation({{ $invitation->id }})"
                                wire:confirm="{{ __('Cancel this invitation?') }}"
                            />
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Danger Zone --}}
    <div class="overflow-hidden rounded-lg border border-red-200 dark:border-red-900">
        <div class="border-b border-red-200 bg-red-50 px-5 py-4 dark:border-red-900 dark:bg-red-950/30">
            <flux:heading size="lg" class="!text-red-700 dark:!text-red-400">{{ __('Danger Zone') }}</flux:heading>
            <flux:subheading class="!text-red-500/80">{{ __('Irreversible actions that affect this organization.') }}</flux:subheading>
        </div>
        <div class="flex flex-col items-start justify-between gap-3 bg-white p-5 sm:flex-row sm:items-center dark:bg-zinc-900">
            <div>
                <flux:heading>{{ __('Delete Organization') }}</flux:heading>
                <flux:subheading>{{ __('Permanently remove organization, events, teams, and scores.') }}</flux:subheading>
            </div>
            <flux:button
                variant="danger"
                size="sm"
                class="shrink-0"
                wire:click="deleteOrganization"
                wire:confirm="{{ __('Delete this organization? This will permanently remove the organization, all events, teams, rounds, and scores. This cannot be undone.') }}"
            >
                {{ __('Delete') }}
            </flux:button>
        </div>
    </div>
</div>
