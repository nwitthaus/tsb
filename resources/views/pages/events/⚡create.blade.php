<?php

use App\Models\Event;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Create Event')] class extends Component {
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

    public function mount(): void
    {
        $this->authorize('create', Event::class);
    }

    public function updated(string $property): void
    {
        if ($property === 'name') {
            $this->slug = Str::slug($this->name);
        }
    }

    public function save(): void
    {
        $this->authorize('create', Event::class);

        $validated = $this->validate();

        $tables = $validated['tables'] ?? null;
        $rounds = $validated['rounds'] ?? null;
        unset($validated['tables'], $validated['rounds']);

        $event = auth()->user()->events()->create($validated);

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

        $this->redirect(route('events.teams', $event), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-lg">
    <flux:heading size="xl">{{ __('Create Event') }}</flux:heading>
    <flux:subheading>{{ __('Set up a new trivia event. You can add teams and rounds after creating it.') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-6">
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
            :description="__('This is the URL slug teams will use to find your scoreboard.')"
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

        <flux:input
            wire:model="tables"
            type="number"
            :label="__('Number of Tables')"
            :description="__('Pre-creates teams numbered 1 through this value. Leave blank to add teams manually.')"
            :placeholder="__('e.g. 20')"
            min="1"
            max="200"
        />

        <flux:input
            wire:model="rounds"
            type="number"
            :label="__('Number of Rounds')"
            :description="__('Pre-creates rounds on the scoring grid. You can add or remove rounds later.')"
            :placeholder="__('e.g. 6')"
            min="1"
            max="50"
        />

        <div class="flex justify-end">
            <flux:button variant="primary" type="submit">
                {{ __('Create Event') }}
            </flux:button>
        </div>
    </form>
</div>
