<?php

use App\Models\Event;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Events')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, Event> */
    #[Computed]
    public function events(): LengthAwarePaginator
    {
        return Event::query()
            ->with('user')
            ->withCount('teams')
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(15);
    }

    public function deleteEvent(int $eventId): void
    {
        $event = Event::findOrFail($eventId);
        $event->delete();

        Flux::toast(__('Event deleted.'));
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Manage Events') }}</flux:heading>
        <flux:button variant="primary" :href="route('admin.events.create')" wire:navigate>
            {{ __('Create Event') }}
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search events...')" />

    <flux:table :paginate="$this->events">
        <flux:table.columns>
            <flux:table.column>{{ __('Event') }}</flux:table.column>
            <flux:table.column>{{ __('Host') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Scheduled') }}</flux:table.column>
            <flux:table.column>{{ __('Teams') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->events as $event)
                <flux:table.row :key="$event->id">
                    <flux:table.cell variant="strong">{{ $event->name }}</flux:table.cell>
                    <flux:table.cell>{{ $event->user->name }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($event->isActive())
                            <flux:badge color="green" size="sm" inset="top bottom">{{ __('Active') }}</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm" inset="top bottom">{{ __('Ended') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $event->starts_at->format('M j, Y g:i A') }}</flux:table.cell>
                    <flux:table.cell>{{ $event->teams_count }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil" :href="route('admin.events.edit', $event)" wire:navigate />
                            <flux:button size="sm" variant="ghost" :href="route('events.show', $event)" wire:navigate>
                                {{ __('Manage') }}
                            </flux:button>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                wire:click="deleteEvent({{ $event->id }})"
                                wire:confirm="{{ __('Delete this event? This will permanently remove the event and all its data. This cannot be undone.') }}"
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
