<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Create Organization')] class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:100|unique:organizations,slug|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
    public string $slug = '';

    public function updated(string $property): void
    {
        if ($property === 'name') {
            $this->slug = Str::slug($this->name);
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $organization = Organization::create($validated);

        $organization->users()->attach(auth()->id(), [
            'role' => OrganizationRole::Owner->value,
        ]);

        $this->redirect(route('organizations.show', $organization), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-lg">
    <flux:heading size="xl">{{ __('Create Organization') }}</flux:heading>
    <flux:subheading>{{ __('Create a new organization to manage your trivia events.') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:input
            wire:model.live.debounce.300ms="name"
            :label="__('Organization Name')"
            :placeholder="__('Joe\'s Bar Trivia')"
            required
            autofocus
        />

        <flux:input
            wire:model="slug"
            :label="__('URL Slug')"
            :description="__('A unique identifier for your organization.')"
            :placeholder="__('joes-bar-trivia')"
            required
        />

        <div class="flex justify-end">
            <flux:button variant="primary" type="submit">
                {{ __('Create Organization') }}
            </flux:button>
        </div>
    </form>
</div>
