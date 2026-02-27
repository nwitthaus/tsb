<?php

use App\Models\Event;
use App\Models\Organization;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Event')] class extends Component {
    public string $name = '';

    public string $starts_at = '';

    public ?int $organization_id = null;

    public ?int $tables = null;

    public ?int $rounds = null;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'organization_id' => ['required', 'exists:organizations,id'],
            'tables' => ['nullable', 'integer', 'min:1', 'max:200'],
            'rounds' => ['nullable', 'integer', 'min:1', 'max:50'],
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

        $event = Event::create([
            'name' => $validated['name'],
            'slug' => Event::generateSlug($validated['name']),
            'starts_at' => $validated['starts_at'],
            'organization_id' => $validated['organization_id'],
        ]);

        if ($validated['tables']) {
            for ($i = 1; $i <= $validated['tables']; $i++) {
                $event->teams()->create([
                    'table_number' => $i,
                    'sort_order' => $i,
                ]);
            }
        }

        if ($validated['rounds']) {
            for ($i = 1; $i <= $validated['rounds']; $i++) {
                $event->rounds()->create([
                    'sort_order' => $i,
                ]);
            }
        }

        Flux::toast(__('Event created successfully.'));

        $this->redirect(route('admin.events.index'), navigate: true);
    }
}; ?>

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.events.index')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ __('Create Event') }}</flux:heading>
            <flux:subheading>{{ __('Create a new trivia event and assign it to an organization.') }}</flux:subheading>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Event Details --}}
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Event Details') }}</flux:heading>
                <flux:subheading>{{ __('Set the event name, schedule, and organization.') }}</flux:subheading>
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

        {{-- Setup --}}
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Setup') }}</flux:heading>
                <flux:subheading>{{ __('Optionally pre-create tables and rounds for the scoring grid.') }}</flux:subheading>
            </div>
            <div class="space-y-6 bg-white p-5 dark:bg-zinc-900">
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
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.events.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Create Event') }}
            </flux:button>
        </div>
    </form>
</div>
