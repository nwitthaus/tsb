<?php

use App\Models\Event;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Manage Event')] class extends Component {
    public Event $event;

    public function mount(Event $event): void
    {
        $this->authorize('view', $this->event);
    }
}; ?>

<div>
    <flux:heading size="xl">{{ $event->name }}</flux:heading>
    <flux:subheading>{{ __('Event management page - coming soon') }}</flux:subheading>
</div>
