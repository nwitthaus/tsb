<?php

use App\Models\Event;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Event')] class extends Component {
    public string $name = '';

    public string $starts_at = '';

    public ?int $user_id = null;

    public ?int $tables = null;

    public ?int $rounds = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'starts_at' => 'required|date',
            'user_id' => 'required|exists:users,id',
            'tables' => 'nullable|integer|min:1|max:200',
            'rounds' => 'nullable|integer|min:1|max:50',
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

        $event = Event::create([
            'name' => $validated['name'],
            'slug' => Event::generateSlug($validated['name']),
            'starts_at' => $validated['starts_at'],
            'user_id' => $validated['user_id'],
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

<div class="max-w-lg space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Create Event') }}</flux:heading>
        <flux:subheading>{{ __('Create a new trivia event and assign it to a host.') }}</flux:subheading>
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
