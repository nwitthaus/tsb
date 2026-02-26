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
    <livewire:event-scoring-grid :event="$event" />
</div>
