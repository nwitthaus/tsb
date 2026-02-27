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

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.events.index')" wire:navigate />
        <div class="flex items-center gap-3">
            <div>
                <flux:heading size="xl">{{ $event->name }}</flux:heading>
                <flux:subheading>{{ __('Edit Event') }}</flux:subheading>
            </div>
            @if ($event->isActive())
                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
            @else
                <flux:badge color="zinc" size="sm">{{ __('Ended') }}</flux:badge>
            @endif
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Event Details --}}
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Event Details') }}</flux:heading>
                <flux:subheading>{{ __('Update the event name, schedule, and organization.') }}</flux:subheading>
            </div>
            <div class="space-y-6 bg-white p-5 dark:bg-zinc-900">
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
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.events.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Update Event') }}
            </flux:button>
        </div>
    </form>

    {{-- Status --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Status') }}</flux:heading>
            <flux:subheading>{{ __('Control whether this event is active or ended.') }}</flux:subheading>
        </div>
        <div class="flex flex-col items-start justify-between gap-3 bg-white p-5 sm:flex-row sm:items-center dark:bg-zinc-900">
            @if ($event->isActive())
                <div>
                    <flux:heading>{{ __('Event is Active') }}</flux:heading>
                    <flux:subheading>{{ __('End this event to stop accepting scores.') }}</flux:subheading>
                </div>
                <flux:button size="sm" class="shrink-0" wire:click="endEvent" wire:confirm="{{ __('End this event? It will be marked as ended.') }}">
                    {{ __('End Event') }}
                </flux:button>
            @else
                <div>
                    <flux:heading>{{ __('Event has Ended') }}</flux:heading>
                    <flux:subheading>{{ __('Reopen this event to resume accepting scores.') }}</flux:subheading>
                </div>
                <flux:button size="sm" class="shrink-0" wire:click="reopenEvent" wire:confirm="{{ __('Reopen this event? It will be marked as active.') }}">
                    {{ __('Reopen Event') }}
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Quick Links') }}</flux:heading>
            <flux:subheading>{{ __('Navigate to related pages for this event.') }}</flux:subheading>
        </div>
        <div class="flex gap-2 bg-white p-5 dark:bg-zinc-900">
            <flux:button :href="route('organizations.events.edit', [$event->organization, $event])" wire:navigate>
                {{ __('Manage Details') }}
            </flux:button>
            <flux:button :href="route('organizations.events.teams', [$event->organization, $event])" wire:navigate>
                {{ __('Manage Teams') }}
            </flux:button>
        </div>
    </div>

    {{-- Danger Zone --}}
    <div class="overflow-hidden rounded-lg border border-red-200 dark:border-red-900">
        <div class="border-b border-red-200 bg-red-50 px-5 py-4 dark:border-red-900 dark:bg-red-950/30">
            <flux:heading size="lg" class="!text-red-700 dark:!text-red-400">{{ __('Danger Zone') }}</flux:heading>
            <flux:subheading class="!text-red-500/80">{{ __('Irreversible actions that affect this event.') }}</flux:subheading>
        </div>
        <div class="flex flex-col items-start justify-between gap-3 bg-white p-5 sm:flex-row sm:items-center dark:bg-zinc-900">
            <div>
                <flux:heading>{{ __('Delete Event') }}</flux:heading>
                <flux:subheading>{{ __('Permanently delete this event and all its data.') }}</flux:subheading>
            </div>
            <flux:button
                variant="danger"
                size="sm"
                class="shrink-0"
                wire:click="deleteEvent"
                wire:confirm="{{ __('Delete this event? This will permanently remove the event and all its data. This cannot be undone.') }}"
            >
                {{ __('Delete') }}
            </flux:button>
        </div>
    </div>
</div>
