<?php

use App\Models\Event;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams')] class extends Component {
    public Event $event;

    public function mount(Event $event): void
    {
        $this->authorize('view', $this->event);
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-2">{{ $event->name }}</flux:heading>

    <flux:tabs class="mb-6">
        <flux:tab :href="route('events.show', $event)" wire:navigate>{{ __('Details') }}</flux:tab>
        <flux:tab selected>{{ __('Teams') }}</flux:tab>
        <flux:tab :href="route('events.scoring', $event)" wire:navigate>{{ __('Scoring') }}</flux:tab>
    </flux:tabs>

    <livewire:event-teams-manager :event="$event" />
</div>
