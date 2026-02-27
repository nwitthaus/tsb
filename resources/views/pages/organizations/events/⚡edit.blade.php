<?php

use App\Models\Event;
use App\Models\Organization;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Event Details')] class extends Component {
    public Organization $organization;

    public Event $event;

    public string $name = '';

    public string $slug = '';

    public string $starts_at = '';

    public function mount(Organization $organization, Event $event): void
    {
        $this->authorize('view', $event);

        $this->organization = $organization;
        $this->name = $event->name;
        $this->slug = $event->slug;
        $this->starts_at = $event->starts_at->format('Y-m-d\TH:i');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:events,slug,' . $this->event->id,
            ],
            'starts_at' => ['required', 'date'],
        ];
    }

    public function save(): void
    {
        $this->authorize('update', $this->event);

        $validated = $this->validate();
        $this->event->update($validated);

        Flux::toast(__('Event updated.'));
    }

    public function endEvent(): void
    {
        $this->authorize('update', $this->event);
        $this->event->update(['ended_at' => now()]);

        Flux::toast(__('Event ended.'));
    }

    public function reopenEvent(): void
    {
        $this->authorize('update', $this->event);
        $this->event->update(['ended_at' => null]);

        Flux::toast(__('Event reopened.'));
    }

    #[Computed]
    public function scoreboardUrl(): string
    {
        return url('/' . $this->event->slug);
    }

    #[Computed]
    public function qrCode(): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'outputBase64' => false,
            'scale' => 5,
            'addQuietzone' => true,
        ]);

        return (new QRCode($options))->render($this->scoreboardUrl());
    }

    #[Computed]
    public function qrCodePng(): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'scale' => 10,
            'addQuietzone' => true,
        ]);

        return (new QRCode($options))->render($this->scoreboardUrl());
    }
}; ?>

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('organizations.events.index', $organization)" wire:navigate />
        <div class="flex items-center gap-3">
            <div>
                <flux:heading size="xl">{{ $event->name }}</flux:heading>
                <flux:subheading>{{ $organization->name }}</flux:subheading>
            </div>
            @if ($event->isActive())
                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
            @else
                <flux:badge color="zinc" size="sm">{{ __('Ended') }}</flux:badge>
            @endif
        </div>
    </div>

    {{-- Event Details --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Event Details') }}</flux:heading>
            <flux:subheading>{{ __('Update your event\'s name, join code, and schedule.') }}</flux:subheading>
        </div>
        <div class="bg-white p-5 dark:bg-zinc-900">
            @can('update', $event)
                <form wire:submit="save" class="space-y-6">
                    <flux:input wire:model="name" :label="__('Event Name')" required />
                    <flux:input wire:model="slug" :label="__('Join Code')" :description="__('The URL slug teams use to find your scoreboard.')" required />
                    <flux:input wire:model="starts_at" type="datetime-local" :label="__('Scheduled Start')" required />
                    <flux:button variant="primary" type="submit">{{ __('Save Changes') }}</flux:button>
                </form>
            @else
                <div class="space-y-4">
                    <div>
                        <flux:heading size="sm">{{ __('Event Name') }}</flux:heading>
                        <flux:text>{{ $event->name }}</flux:text>
                    </div>
                    <div>
                        <flux:heading size="sm">{{ __('Join Code') }}</flux:heading>
                        <flux:text>{{ $event->slug }}</flux:text>
                    </div>
                    <div>
                        <flux:heading size="sm">{{ __('Scheduled Start') }}</flux:heading>
                        <flux:text>{{ $event->starts_at->format('M j, Y g:i A') }}</flux:text>
                    </div>
                </div>
            @endcan
        </div>
    </div>

    {{-- Status --}}
    @can('update', $event)
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
    @endcan

    {{-- Share Scoreboard --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Share Scoreboard') }}</flux:heading>
            <flux:subheading>{{ __('Share the live scoreboard with your audience.') }}</flux:subheading>
        </div>
        <div class="flex items-start gap-6 bg-white p-5 dark:bg-zinc-900">
            <div class="shrink-0">
                <div class="size-[150px] rounded bg-white p-2 [&_svg]:size-full">
                    {!! $this->qrCode !!}
                </div>
            </div>
            <div>
                <a href="{{ $this->scoreboardUrl }}" target="_blank" class="font-mono text-sm text-blue-600 hover:underline dark:text-blue-400">{{ $this->scoreboardUrl }}</a>
                <div class="mt-2 flex gap-2">
                    <flux:button size="sm" variant="ghost" icon="clipboard" x-on:click="navigator.clipboard.writeText('{{ $this->scoreboardUrl }}')">
                        {{ __('Copy Link') }}
                    </flux:button>
                    <flux:button size="sm" variant="ghost" icon="arrow-down-tray" x-on:click="
                        const a = document.createElement('a');
                        a.href = '{{ $this->qrCodePng }}';
                        a.download = '{{ $event->slug }}-qr.png';
                        a.click();
                    ">
                        {{ __('Download QR') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    {{-- Manage --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Manage') }}</flux:heading>
            <flux:subheading>{{ __('Access teams and scoring for this event.') }}</flux:subheading>
        </div>
        <div class="flex gap-2 bg-white p-5 dark:bg-zinc-900">
            <flux:button :href="route('organizations.events.teams', [$organization, $event])" wire:navigate>
                {{ __('Manage Teams') }}
            </flux:button>
            <flux:button :href="route('organizations.events.scoring', [$organization, $event])" wire:navigate>
                {{ __('Manage Scoring') }}
            </flux:button>
        </div>
    </div>
</div>
