<?php

use App\Models\Organization;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Create Event')] class extends Component {
    public Organization $organization;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:100|unique:events,slug|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
    public string $slug = '';

    #[Validate('required|date|after_or_equal:today')]
    public string $starts_at = '';

    #[Validate('nullable|integer|min:1|max:200')]
    public ?int $tables = null;

    #[Validate('nullable|integer|min:1|max:50')]
    public ?int $rounds = null;

    public function mount(Organization $organization): void
    {
        $this->authorize('update', $organization);
        $this->organization = $organization;
    }

    public function updated(string $property): void
    {
        if ($property === 'name') {
            $this->slug = Str::slug($this->name);
        }
    }

    public function save(): void
    {
        $this->authorize('update', $this->organization);

        $validated = $this->validate();

        $tables = $validated['tables'] ?? null;
        $rounds = $validated['rounds'] ?? null;
        unset($validated['tables'], $validated['rounds']);

        $event = $this->organization->events()->create($validated);

        if ($tables) {
            for ($i = 1; $i <= $tables; $i++) {
                $event->teams()->create([
                    'table_number' => $i,
                    'sort_order' => $i,
                ]);
            }
        }

        if ($rounds) {
            for ($i = 1; $i <= $rounds; $i++) {
                $event->rounds()->create([
                    'sort_order' => $i,
                ]);
            }
        }

        $this->redirect(route('organizations.events.teams', [$this->organization, $event]), navigate: true);
    }
}; ?>

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('organizations.events.index', $organization)" wire:navigate />
        <div>
            <flux:heading size="xl">{{ __('Create Event') }}</flux:heading>
            <flux:subheading>{{ $organization->name }}</flux:subheading>
        </div>
    </div>

    <form wire:submit="save">
        {{-- Event Details Card --}}
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Event Details') }}</flux:heading>
                <flux:subheading>{{ __('Configure your new trivia event.') }}</flux:subheading>
            </div>
            <div class="space-y-6 bg-white p-5 dark:bg-zinc-900">
                <flux:input
                    wire:model.live.debounce.300ms="name"
                    :label="__('Event Name')"
                    :placeholder="__('Tuesday Trivia at Joe\'s')"
                    required
                    autofocus
                />

                <flux:input
                    wire:model="slug"
                    :label="__('Join Code')"
                    :description="__('The URL slug teams will use to find your scoreboard.')"
                    :placeholder="__('tuesday-trivia-at-joes')"
                    required
                />

                <flux:input
                    wire:model="starts_at"
                    type="datetime-local"
                    :label="__('Scheduled Start')"
                    :min="now()->format('Y-m-d\TH:i')"
                    required
                />

                <div class="grid gap-6 sm:grid-cols-2">
                    <flux:input
                        wire:model="tables"
                        type="number"
                        :label="__('Number of Tables')"
                        :description="__('Pre-creates teams numbered 1 through this value.')"
                        :placeholder="__('e.g. 20')"
                        min="1"
                        max="200"
                    />

                    <flux:input
                        wire:model="rounds"
                        type="number"
                        :label="__('Number of Rounds')"
                        :description="__('Pre-creates rounds on the scoring grid.')"
                        :placeholder="__('e.g. 6')"
                        min="1"
                        max="50"
                    />
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button :href="route('organizations.events.index', $organization)" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Create Event') }}
            </flux:button>
        </div>
    </form>
</div>
