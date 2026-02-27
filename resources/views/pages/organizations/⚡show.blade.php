<?php

use App\Models\Event;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Organization')] class extends Component {
    public Organization $organization;

    public function mount(Organization $organization): void
    {
        $this->authorize('view', $organization);
        $this->organization = $organization;
    }

    /** @return Collection<int, Event> */
    #[Computed]
    public function activeEvents(): Collection
    {
        return $this->organization->events()->whereNull('ended_at')->latest()->get();
    }

    /** @return Collection<int, Event> */
    #[Computed]
    public function pastEvents(): Collection
    {
        return $this->organization->events()
            ->whereNotNull('ended_at')
            ->withCount('teams')
            ->latest('ended_at')
            ->get();
    }

    public function deleteEvent(int $eventId): void
    {
        $event = Event::findOrFail($eventId);
        $this->authorize('delete', $event);
        $event->delete();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $organization->name }}</flux:heading>
            <flux:subheading>
                <flux:link href="{{ route('dashboard') }}" wire:navigate>{{ __('Dashboard') }}</flux:link>
            </flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            @can('update', $organization)
                <flux:button :href="route('organizations.settings', $organization)" wire:navigate>
                    {{ __('Settings') }}
                </flux:button>
                <flux:button variant="primary" :href="route('events.create', $organization)" wire:navigate>
                    {{ __('Create Event') }}
                </flux:button>
            @endcan
        </div>
    </div>

    <flux:heading size="lg">{{ __('Active Events') }}</flux:heading>

    @forelse ($this->activeEvents as $event)
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">{{ $event->name }}</flux:heading>
                    <flux:subheading>{{ __('Join code:') }} {{ $event->slug }}</flux:subheading>
                    <flux:subheading>{{ $event->starts_at->format('M j, Y g:i A') }}</flux:subheading>
                </div>
                <div class="flex items-center gap-2">
                    <flux:button variant="primary" :href="route('events.show', $event)" wire:navigate>
                        {{ __('Manage Event') }}
                    </flux:button>
                    @can('delete', $event)
                        <flux:button
                            variant="ghost"
                            icon="trash"
                            wire:click="deleteEvent({{ $event->id }})"
                            wire:confirm="{{ __('Delete this event? This will permanently remove the event and all its teams, rounds, and scores. This cannot be undone.') }}"
                        />
                    @endcan
                </div>
            </div>
        </flux:card>
    @empty
        <flux:card>
            <flux:subheading>{{ __('No active events.') }}
                @can('update', $organization)
                    {{ __('Create one to get started.') }}
                @endcan
            </flux:subheading>
        </flux:card>
    @endforelse

    @if ($this->pastEvents->isNotEmpty())
        <div>
            <flux:heading size="lg" class="mb-4">{{ __('Past Events') }}</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Event') }}</flux:table.column>
                    <flux:table.column>{{ __('Scheduled') }}</flux:table.column>
                    <flux:table.column>{{ __('Teams') }}</flux:table.column>
                    <flux:table.column>{{ __('Ended') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->pastEvents as $event)
                        <flux:table.row :key="$event->id">
                            <flux:table.cell>{{ $event->name }}</flux:table.cell>
                            <flux:table.cell>{{ $event->starts_at->format('M j, Y g:i A') }}</flux:table.cell>
                            <flux:table.cell>{{ $event->teams_count }}</flux:table.cell>
                            <flux:table.cell>{{ $event->ended_at->diffForHumans() }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-1">
                                    <flux:button size="sm" variant="ghost" :href="route('events.show', $event)" wire:navigate>
                                        {{ __('View') }}
                                    </flux:button>
                                    @can('delete', $event)
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="trash"
                                            wire:click="deleteEvent({{ $event->id }})"
                                            wire:confirm="{{ __('Delete this event? This will permanently remove the event and all its teams, rounds, and scores. This cannot be undone.') }}"
                                        />
                                    @endcan
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif
</div>
