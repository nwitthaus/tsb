<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin Dashboard')] class extends Component {
    public string $search = '';

    /** @return Collection<int, User> */
    #[Computed]
    public function users(): Collection
    {
        return User::query()
            ->withCount('events')
            ->when($this->search, fn ($query, $search) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, Event> */
    #[Computed]
    public function events(): Collection
    {
        return Event::query()
            ->with('user')
            ->withCount('teams')
            ->when($this->search, fn ($query, $search) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            )
            ->latest()
            ->get();
    }

    #[Computed]
    public function totalUsers(): int
    {
        return User::count();
    }

    #[Computed]
    public function totalEvents(): int
    {
        return Event::count();
    }

    #[Computed]
    public function activeEvents(): int
    {
        return Event::query()->whereNull('ended_at')->count();
    }

    public function deleteUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            return;
        }

        $user->delete();
    }

    public function deleteEvent(int $eventId): void
    {
        Event::findOrFail($eventId)->delete();
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl">{{ __('Admin Dashboard') }}</flux:heading>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card>
            <flux:heading size="lg">{{ $this->totalUsers }}</flux:heading>
            <flux:subheading>{{ __('Total Users') }}</flux:subheading>
        </flux:card>
        <flux:card>
            <flux:heading size="lg">{{ $this->totalEvents }}</flux:heading>
            <flux:subheading>{{ __('Total Events') }}</flux:subheading>
        </flux:card>
        <flux:card>
            <flux:heading size="lg">{{ $this->activeEvents }}</flux:heading>
            <flux:subheading>{{ __('Active Events') }}</flux:subheading>
        </flux:card>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search users or events...') }}" icon="magnifying-glass" />

    <div>
        <flux:heading size="lg" class="mb-4">{{ __('Users') }}</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Events') }}</flux:table.column>
                <flux:table.column>{{ __('Registered') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell>
                            {{ $user->name }}
                            @if ($user->is_super_admin)
                                <flux:badge color="amber" size="sm">{{ __('Admin') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>{{ $user->events_count }}</flux:table.cell>
                        <flux:table.cell>{{ $user->created_at->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($user->id !== auth()->id())
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="deleteUser({{ $user->id }})"
                                    wire:confirm="{{ __('Delete this user? This will permanently remove the user and all their events. This cannot be undone.') }}"
                                />
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <div>
        <flux:heading size="lg" class="mb-4">{{ __('Events') }}</flux:heading>
        <flux:table>
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
</div>
