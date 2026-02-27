<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Organization')] class extends Component {
    public Organization $organization;

    public string $name = '';

    public string $slug = '';

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
        $this->name = $organization->name;
        $this->slug = $organization->slug;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('organizations', 'slug')->ignore($this->organization->id)],
        ];
    }

    /** @return Collection<int, \App\Models\User> */
    #[Computed]
    public function members(): Collection
    {
        return $this->organization->users()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate();

        $this->organization->update($validated);

        Flux::toast(__('Organization updated successfully.'));

        $this->redirect(route('admin.organizations.index'), navigate: true);
    }

    public function removeMember(int $userId): void
    {
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
        $this->organization->delete();

        Flux::toast(__('Organization deleted.'));

        $this->redirect(route('admin.organizations.index'), navigate: true);
    }
}; ?>

<div class="max-w-lg space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Edit Organization') }}</flux:heading>
        <flux:subheading>{{ $organization->name }}</flux:subheading>
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

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.organizations.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Update Organization') }}
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
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                wire:click="removeMember({{ $member->id }})"
                                wire:confirm="{{ __('Remove this member from the organization?') }}"
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:separator />

    <div class="space-y-3">
        <flux:heading size="lg">{{ __('Quick Links') }}</flux:heading>
        <div class="flex gap-2">
            <flux:button :href="route('organizations.settings', $organization)" wire:navigate>
                {{ __('Organization Settings') }}
            </flux:button>
            <flux:button :href="route('organizations.show', $organization)" wire:navigate>
                {{ __('View Organization') }}
            </flux:button>
        </div>
    </div>

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
