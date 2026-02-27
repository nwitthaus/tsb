<?php

use App\Models\Event;
use App\Models\Organization;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Event')] class extends Component {
    public Event $event;

    public string $name = '';

    public string $starts_at = '';

    public ?int $organization_id = null;

    public function mount(Event $event): void
    {
        $this->event = $event;
        $this->name = $event->name;
        $this->starts_at = $event->starts_at->format('Y-m-d\TH:i');
        $this->organization_id = $event->organization_id;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'organization_id' => ['required', 'exists:organizations,id'],
        ];
    }

    /** @return Collection<int, Organization> */
    #[Computed]
    public function organizations(): Collection
    {
        return Organization::query()->orderBy('name')->get(['id', 'name']);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $this->event->update([
            'name' => $validated['name'],
            'starts_at' => $validated['starts_at'],
            'organization_id' => $validated['organization_id'],
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

<div class="max-w-lg space-y-6">
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
            <flux:button size="sm" wire:click="reopenEvent" wire:confirm="{{ __('Reopen this event? It will be marked as active.') }}">
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

        <flux:select wire:model="organization_id" :label="__('Organization')" :placeholder="__('Select an organization...')">
            @foreach ($this->organizations as $org)
                <flux:select.option :value="$org->id">{{ $org->name }}</flux:select.option>
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
