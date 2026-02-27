<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin Overview')] class extends Component {
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
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Admin Overview') }}</flux:heading>
        <flux:subheading>{{ __('System-wide statistics and quick links.') }}</flux:subheading>
    </div>

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

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading>{{ __('Users') }}</flux:heading>
                    <flux:subheading>{{ __('Manage user accounts') }}</flux:subheading>
                </div>
                <flux:button variant="primary" :href="route('admin.users.index')" wire:navigate>
                    {{ __('Manage Users') }}
                </flux:button>
            </div>
        </flux:card>
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading>{{ __('Events') }}</flux:heading>
                    <flux:subheading>{{ __('Manage trivia events') }}</flux:subheading>
                </div>
                <flux:button variant="primary" :href="route('admin.events.index')" wire:navigate>
                    {{ __('Manage Events') }}
                </flux:button>
            </div>
        </flux:card>
    </div>
</div>
