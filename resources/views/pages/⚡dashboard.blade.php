<?php

use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    /** @return Collection<int, Organization> */
    #[Computed]
    public function organizations(): Collection
    {
        return auth()->user()->organizations()
            ->withCount([
                'events as active_events_count' => fn ($query) => $query->whereNull('ended_at'),
                'events as past_events_count' => fn ($query) => $query->whereNotNull('ended_at'),
            ])
            ->orderBy('name')
            ->get();
    }

    public function mount(): void
    {
        if ($this->organizations->count() === 1) {
            $this->redirect(route('organizations.show', $this->organizations->first()), navigate: true);
        }
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
        <flux:button variant="primary" :href="route('organizations.create')" wire:navigate>
            {{ __('Create Organization') }}
        </flux:button>
    </div>

    @forelse ($this->organizations as $organization)
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">{{ $organization->name }}</flux:heading>
                    <flux:subheading>
                        {{ trans_choice(':count active event|:count active events', $organization->active_events_count) }}
                        &middot;
                        {{ trans_choice(':count past event|:count past events', $organization->past_events_count) }}
                    </flux:subheading>
                </div>
                <flux:button variant="primary" :href="route('organizations.show', $organization)" wire:navigate>
                    {{ __('View') }}
                </flux:button>
            </div>
        </flux:card>
    @empty
        <flux:card>
            <flux:subheading>{{ __('You don\'t belong to any organizations yet. Create one to get started.') }}</flux:subheading>
        </flux:card>
    @endforelse
</div>
