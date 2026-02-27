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
            ->with('organization')
            ->withCount('teams')
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('organization', function ($q2) use ($search) {
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
        <div>
            <flux:heading size="xl">{{ __('Events') }}</flux:heading>
            <flux:subheading>{{ __('Manage trivia events across all organizations.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('admin.events.create')" wire:navigate>
            {{ __('Create Event') }}
        </flux:button>
    </div>

    {{-- Events --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-2">
                <flux:heading size="lg">{{ __('Events') }}</flux:heading>
                <flux:badge size="sm" color="zinc">{{ $this->events->total() }}</flux:badge>
            </div>
            <flux:subheading>{{ __('All trivia events across organizations.') }}</flux:subheading>
        </div>
        <div class="bg-white p-5 dark:bg-zinc-900">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search events...')" class="mb-4" />

            <flux:table :paginate="$this->events">
                <flux:table.columns>
                    <flux:table.column>{{ __('Event') }}</flux:table.column>
                    <flux:table.column>{{ __('Organization') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Scheduled') }}</flux:table.column>
                    <flux:table.column>{{ __('Teams') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->events as $event)
                        <flux:table.row :key="$event->id">
                            <flux:table.cell variant="strong">{{ $event->name }}</flux:table.cell>
                            <flux:table.cell>{{ $event->organization->name }}</flux:table.cell>
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
                                    <flux:button size="sm" variant="ghost" :href="route('organizations.events.edit', [$event->organization, $event])" wire:navigate>
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
    </div>
</div>
