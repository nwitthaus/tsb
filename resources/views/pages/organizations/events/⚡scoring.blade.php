<?php

use App\Models\Event;
use App\Models\Organization;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Scoring Grid')] class extends Component {
    public Organization $organization;

    public Event $event;

    public function mount(Organization $organization, Event $event): void
    {
        $this->authorize('view', $event);
        $this->organization = $organization;
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('organizations.events.edit', [$organization, $event])" wire:navigate />
        <div class="flex items-center gap-3">
            <div>
                <flux:heading size="xl">{{ $event->name }}</flux:heading>
                <flux:subheading>{{ __('Scoring') }}</flux:subheading>
            </div>
            @if ($event->isActive())
                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
            @else
                <flux:badge color="zinc" size="sm">{{ __('Ended') }}</flux:badge>
            @endif
        </div>
    </div>

    {{-- Scoring Grid (no max-w constraint — grid needs full width) --}}
    <livewire:event-scoring-grid :event="$event" />
</div>
