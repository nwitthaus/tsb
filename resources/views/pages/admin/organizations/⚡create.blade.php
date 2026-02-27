<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Organization')] class extends Component {
    public string $name = '';

    public ?int $owner_id = null;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'owner_id' => ['required', 'exists:users,id'],
        ];
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $organization = Organization::create([
            'name' => $validated['name'],
            'slug' => Organization::generateSlug($validated['name']),
        ]);

        $organization->users()->attach($validated['owner_id'], [
            'role' => OrganizationRole::Owner->value,
        ]);

        Flux::toast(__('Organization created successfully.'));

        $this->redirect(route('admin.organizations.index'), navigate: true);
    }
}; ?>

<div class="max-w-lg space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Create Organization') }}</flux:heading>
        <flux:subheading>{{ __('Create a new organization and assign an owner.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:input
            wire:model="name"
            :label="__('Organization Name')"
            :placeholder="__('Acme Trivia Co')"
            required
            autofocus
        />

        <flux:select wire:model="owner_id" :label="__('Owner')" :placeholder="__('Select an owner...')">
            @foreach ($this->users as $user)
                <flux:select.option :value="$user->id">{{ $user->name }} ({{ $user->email }})</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.organizations.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Create Organization') }}
            </flux:button>
        </div>
    </form>
</div>
