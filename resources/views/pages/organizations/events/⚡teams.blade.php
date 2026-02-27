<?php

use App\Models\Event;
use App\Models\Organization;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams')] class extends Component {
    public Organization $organization;

    public Event $event;

    public function mount(Organization $organization, Event $event): void
    {
        $this->authorize('view', $event);
        $this->organization = $organization;
    }
}; ?>

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('organizations.events.edit', [$organization, $event])" wire:navigate />
        <div class="flex items-center gap-3">
            <div>
                <flux:heading size="xl">{{ $event->name }}</flux:heading>
                <flux:subheading>{{ __('Teams') }}</flux:subheading>
            </div>
            @if ($event->isActive())
                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
            @else
                <flux:badge color="zinc" size="sm">{{ __('Ended') }}</flux:badge>
            @endif
        </div>
    </div>

    {{-- Teams Manager --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Teams') }}</flux:heading>
            <flux:subheading>{{ __('Add, edit, and reorder teams for this event.') }}</flux:subheading>
        </div>
        <div class="bg-white p-5 dark:bg-zinc-900">
            <livewire:event-teams-manager :event="$event" />
        </div>
    </div>
</div>
