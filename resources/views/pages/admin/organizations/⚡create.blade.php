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

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.organizations.index')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ __('Create Organization') }}</flux:heading>
            <flux:subheading>{{ __('Create a new organization and assign an owner.') }}</flux:subheading>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Organization Details --}}
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Organization Details') }}</flux:heading>
                <flux:subheading>{{ __('Set the organization name and assign an owner.') }}</flux:subheading>
            </div>
            <div class="space-y-6 bg-white p-5 dark:bg-zinc-900">
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
            </div>
        </div>

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
