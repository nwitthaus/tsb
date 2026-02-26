<?php

use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    /** @return ?Event */
    #[Computed]
    public function activeEvent(): ?Event
    {
        return auth()->user()->events()->whereNull('ended_at')->first();
    }

    /** @return Collection<int, Event> */
    #[Computed]
    public function pastEvents(): Collection
    {
        return auth()->user()->events()
            ->whereNotNull('ended_at')
            ->withCount('teams')
            ->latest('ended_at')
            ->get();
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>

    @if ($this->activeEvent)
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">{{ $this->activeEvent->name }}</flux:heading>
                    <flux:subheading>{{ __('Active Event') }} &middot; {{ __('Join code:') }} {{ $this->activeEvent->slug }}</flux:subheading>
                </div>
                <flux:button variant="primary" :href="route('events.show', $this->activeEvent)" wire:navigate>
                    {{ __('Manage Event') }}
                </flux:button>
            </div>
        </flux:card>
    @else
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">{{ __('No Active Event') }}</flux:heading>
                    <flux:subheading>{{ __('Create a new trivia event to get started.') }}</flux:subheading>
                </div>
                <flux:button variant="primary" :href="route('events.create')" wire:navigate>
                    {{ __('Create Event') }}
                </flux:button>
            </div>
        </flux:card>
    @endif

    @if ($this->pastEvents->isNotEmpty())
        <div>
            <flux:heading size="lg" class="mb-4">{{ __('Past Events') }}</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Event') }}</flux:table.column>
                    <flux:table.column>{{ __('Teams') }}</flux:table.column>
                    <flux:table.column>{{ __('Ended') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->pastEvents as $event)
                        <flux:table.row :key="$event->id">
                            <flux:table.cell>{{ $event->name }}</flux:table.cell>
                            <flux:table.cell>{{ $event->teams_count }}</flux:table.cell>
                            <flux:table.cell>{{ $event->ended_at->diffForHumans() }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="ghost" :href="route('events.show', $event)" wire:navigate>
                                    {{ __('View') }}
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif
</div>
