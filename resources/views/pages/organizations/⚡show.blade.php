<?php

use App\Models\Organization;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Organization')] class extends Component {
    public Organization $organization;

    public function mount(Organization $organization): void
    {
        $this->authorize('view', $organization);
        $this->organization = $organization;
    }
}; ?>

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('dashboard')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $organization->name }}</flux:heading>
            <flux:subheading>{{ __('Organization Dashboard') }}</flux:subheading>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="grid gap-4 sm:grid-cols-2">
        <flux:card class="flex flex-col items-start gap-3">
            <flux:icon.calendar-days class="text-zinc-400" />
            <div>
                <flux:heading size="lg">{{ __('Events') }}</flux:heading>
                <flux:subheading>{{ __('Manage your trivia events.') }}</flux:subheading>
            </div>
            <flux:button variant="primary" :href="route('organizations.events.index', $organization)" wire:navigate class="mt-auto">
                {{ __('View Events') }}
            </flux:button>
        </flux:card>

        @can('update', $organization)
            <flux:card class="flex flex-col items-start gap-3">
                <flux:icon.cog-6-tooth class="text-zinc-400" />
                <div>
                    <flux:heading size="lg">{{ __('Settings') }}</flux:heading>
                    <flux:subheading>{{ __('Organization settings & members.') }}</flux:subheading>
                </div>
                <flux:button :href="route('organizations.settings', $organization)" wire:navigate class="mt-auto">
                    {{ __('Manage Settings') }}
                </flux:button>
            </flux:card>
        @endcan
    </div>
</div>
