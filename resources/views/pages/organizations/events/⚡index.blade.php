<?php

use App\Models\Event;
use App\Models\Organization;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Events')] class extends Component {
    public Organization $organization;

    #[Url]
    public string $search = '';

    #[Url]
    public string $tab = 'active';

    public function mount(Organization $organization): void
    {
        $this->authorize('view', $organization);
        $this->organization = $organization;
    }

    /** @return Collection<int, Event> */
    #[Computed]
    public function activeEvents(): Collection
    {
        return $this->organization->events()
            ->whereNull('ended_at')
            ->withCount('teams')
            ->when($this->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest('starts_at')
            ->get();
    }

    /** @return Collection<int, Event> */
    #[Computed]
    public function pastEvents(): Collection
    {
        return $this->organization->events()
            ->whereNotNull('ended_at')
            ->withCount('teams')
            ->when($this->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest('ended_at')
            ->get();
    }

    public function deleteEvent(int $eventId): void
    {
        $event = Event::findOrFail($eventId);
        $this->authorize('delete', $event);
        $event->delete();

        Flux::toast(__('Event deleted.'));
    }
}; ?>

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <flux:button variant="ghost" icon="arrow-left" :href="route('organizations.show', $organization)" wire:navigate />
            <div>
                <flux:heading size="xl">{{ __('Events') }}</flux:heading>
                <flux:subheading>{{ $organization->name }}</flux:subheading>
            </div>
        </div>
        @can('update', $organization)
            <flux:button variant="primary" :href="route('organizations.events.create', $organization)" wire:navigate>
                {{ __('Create Event') }}
            </flux:button>
        @endcan
    </div>

    {{-- Search --}}
    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search events...')" />

    {{-- Tabs --}}
    <flux:tabs wire:model="tab">
        <flux:tab name="active">{{ __('Active') }}</flux:tab>
        <flux:tab name="past">{{ __('Past') }}</flux:tab>
    </flux:tabs>

    {{-- Active Events --}}
    @if ($tab === 'active')
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center gap-2">
                    <flux:heading size="lg">{{ __('Active Events') }}</flux:heading>
                    <flux:badge size="sm" color="zinc">{{ $this->activeEvents->count() }}</flux:badge>
                </div>
            </div>
            <div class="bg-white p-5 dark:bg-zinc-900">
                @if ($this->activeEvents->isEmpty())
                    <div class="py-8 text-center">
                        <flux:icon.calendar-days class="mx-auto size-10 text-zinc-400" />
                        <flux:heading size="lg" class="mt-3">{{ __('No active events') }}</flux:heading>
                        <flux:subheading class="mt-1">
                            @can('update', $organization)
                                {{ __('Create one to get started.') }}
                            @else
                                {{ __('No active events.') }}
                            @endcan
                        </flux:subheading>
                        @can('update', $organization)
                            <flux:button variant="primary" :href="route('organizations.events.create', $organization)" wire:navigate class="mt-4">
                                {{ __('Create Event') }}
                            </flux:button>
                        @endcan
                    </div>
                @else
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Event') }}</flux:table.column>
                            <flux:table.column>{{ __('Join Code') }}</flux:table.column>
                            <flux:table.column>{{ __('Scheduled') }}</flux:table.column>
                            <flux:table.column>{{ __('Teams') }}</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->activeEvents as $event)
                                <flux:table.row :key="$event->id">
                                    <flux:table.cell variant="strong">{{ $event->name }}</flux:table.cell>
                                    <flux:table.cell class="font-mono text-xs">{{ $event->slug }}</flux:table.cell>
                                    <flux:table.cell>{{ $event->starts_at->format('M j, Y g:i A') }}</flux:table.cell>
                                    <flux:table.cell>{{ $event->teams_count }}</flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex items-center gap-1">
                                            @can('update', $organization)
                                                <flux:button size="sm" variant="ghost" icon="pencil" :href="route('organizations.events.edit', [$organization, $event])" wire:navigate />
                                            @endcan
                                            @can('delete', $event)
                                                <flux:button
                                                    size="sm"
                                                    variant="ghost"
                                                    icon="trash"
                                                    wire:click="deleteEvent({{ $event->id }})"
                                                    wire:confirm="{{ __('Delete this event? This will permanently remove the event and all its data. This cannot be undone.') }}"
                                                />
                                            @endcan
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </div>
        </div>
    @endif

    {{-- Past Events --}}
    @if ($tab === 'past')
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center gap-2">
                    <flux:heading size="lg">{{ __('Past Events') }}</flux:heading>
                    <flux:badge size="sm" color="zinc">{{ $this->pastEvents->count() }}</flux:badge>
                </div>
            </div>
            <div class="bg-white p-5 dark:bg-zinc-900">
                @if ($this->pastEvents->isEmpty())
                    <div class="py-8 text-center">
                        <flux:icon.calendar-days class="mx-auto size-10 text-zinc-400" />
                        <flux:heading size="lg" class="mt-3">{{ __('No past events') }}</flux:heading>
                        <flux:subheading class="mt-1">{{ __('No past events.') }}</flux:subheading>
                    </div>
                @else
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
                                    <flux:table.cell variant="strong">{{ $event->name }}</flux:table.cell>
                                    <flux:table.cell>{{ $event->starts_at->format('M j, Y g:i A') }}</flux:table.cell>
                                    <flux:table.cell>{{ $event->teams_count }}</flux:table.cell>
                                    <flux:table.cell>{{ $event->ended_at->diffForHumans() }}</flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex items-center gap-1">
                                            @can('update', $organization)
                                                <flux:button size="sm" variant="ghost" icon="pencil" :href="route('organizations.events.edit', [$organization, $event])" wire:navigate />
                                            @endcan
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </div>
        </div>
    @endif
</div>
