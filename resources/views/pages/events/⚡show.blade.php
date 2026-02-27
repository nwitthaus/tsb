<?php

use App\Models\Event;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Event Details')] class extends Component {
    public Event $event;

    public string $name = '';

    public string $slug = '';

    public string $starts_at = '';

    public function mount(Event $event): void
    {
        $this->authorize('view', $this->event);

        $this->name = $this->event->name;
        $this->slug = $this->event->slug;
        $this->starts_at = $this->event->starts_at->format('Y-m-d\TH:i');
    }

    /**
     * @return array<string, mixed>
     */
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

        $this->dispatch('saved');
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

<div>
    <flux:heading size="xl" class="mb-2">{{ $event->name }}</flux:heading>

    <flux:tabs class="mb-6">
        <flux:tab selected>{{ __('Details') }}</flux:tab>
        <flux:tab :href="route('events.teams', $event)" wire:navigate>{{ __('Teams') }}</flux:tab>
        <flux:tab :href="route('events.scoring', $event)" wire:navigate>{{ __('Scoring') }}</flux:tab>
    </flux:tabs>

    @can('update', $event)
        <div class="max-w-lg space-y-6">
            <form wire:submit="save" class="space-y-6">
                <flux:input
                    wire:model="name"
                    :label="__('Event Name')"
                    required
                />

                <flux:input
                    wire:model="slug"
                    :label="__('Join Code')"
                    :description="__('The URL slug teams use to find your scoreboard.')"
                    required
                />

                <flux:input
                    wire:model="starts_at"
                    type="datetime-local"
                    :label="__('Scheduled Start')"
                    required
                />

                <div class="flex items-center gap-4">
                    <flux:button variant="primary" type="submit">
                        {{ __('Save Changes') }}
                    </flux:button>

                    <x-action-message on="saved">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </form>
        </div>
    @else
        <div class="max-w-lg space-y-4">
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

    {{-- Share Scoreboard --}}
    <div class="mt-8 flex items-start gap-6 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
        <div class="shrink-0">
            <div class="size-[150px] rounded bg-white p-2 [&_svg]:size-full">
                {!! $this->qrCode !!}
            </div>
        </div>
        <div>
            <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Share this scoreboard') }}</p>
            <a href="{{ $this->scoreboardUrl }}" target="_blank" class="mt-1 block font-mono text-sm text-blue-600 hover:underline dark:text-blue-400">{{ $this->scoreboardUrl }}</a>
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
