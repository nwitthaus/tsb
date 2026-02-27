<?php

use App\Models\Event;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Event')] class extends Component {
    public Event $event;

    public string $name = '';

    public string $starts_at = '';

    public ?int $user_id = null;

    public function mount(Event $event): void
    {
        $this->event = $event;
        $this->name = $event->name;
        $this->starts_at = $event->starts_at->format('Y-m-d\TH:i');
        $this->user_id = $event->user_id;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'starts_at' => 'required|date',
            'user_id' => 'required|exists:users,id',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, User> */
    #[Computed]
    public function users(): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $this->event->update([
            'name' => $validated['name'],
            'starts_at' => $validated['starts_at'],
            'user_id' => $validated['user_id'],
        ]);

        Flux::toast(__('Event updated successfully.'));

        $this->redirect(route('admin.events.index'), navigate: true);
    }

    public function endEvent(): void
    {
        $this->event->update(['ended_at' => now()]);
    }

    public function reopenEvent(): void
    {
        $this->event->update(['ended_at' => null]);
    }

    public function deleteEvent(): void
    {
        $this->event->delete();

        Flux::toast(__('Event deleted.'));

        $this->redirect(route('admin.events.index'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-lg space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Edit Event') }}</flux:heading>
        <flux:subheading>{{ $event->name }}</flux:subheading>
    </div>

    <div class="flex items-center gap-3">
        @if ($event->isActive())
            <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
            <flux:button size="sm" wire:click="endEvent" wire:confirm="{{ __('End this event? It will be marked as ended.') }}">
                {{ __('End Event') }}
            </flux:button>
        @else
            <flux:badge color="zinc" size="sm">{{ __('Ended') }}</flux:badge>
            <flux:button size="sm" wire:click="reopenEvent">
                {{ __('Reopen Event') }}
            </flux:button>
        @endif
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:input
            wire:model="name"
            :label="__('Event Name')"
            :placeholder="__('Tuesday Trivia at Joe\'s')"
            required
            autofocus
        />

        <flux:input
            wire:model="starts_at"
            type="datetime-local"
            :label="__('Scheduled Start')"
            required
        />

        <flux:select wire:model="user_id" :label="__('Host')" :placeholder="__('Select a host...')">
            @foreach ($this->users as $user)
                <flux:select.option :value="$user->id">{{ $user->name }} ({{ $user->email }})</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.events.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Update Event') }}
            </flux:button>
        </div>
    </form>

    <flux:separator />

    <div class="space-y-3">
        <flux:heading size="lg">{{ __('Quick Links') }}</flux:heading>
        <div class="flex gap-2">
            <flux:button :href="route('events.show', $event)" wire:navigate>
                {{ __('Manage Details') }}
            </flux:button>
            <flux:button :href="route('events.teams', $event)" wire:navigate>
                {{ __('Manage Teams') }}
            </flux:button>
        </div>
    </div>

    <flux:separator />

    <div class="space-y-3">
        <flux:heading size="lg">{{ __('Danger Zone') }}</flux:heading>
        <flux:card class="border-red-200 dark:border-red-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading>{{ __('Delete Event') }}</flux:heading>
                    <flux:subheading>{{ __('Permanently delete this event and all its data.') }}</flux:subheading>
                </div>
                <flux:button
                    variant="danger"
                    wire:click="deleteEvent"
                    wire:confirm="{{ __('Delete this event? This will permanently remove the event and all its data. This cannot be undone.') }}"
                >
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </flux:card>
    </div>
</div>
