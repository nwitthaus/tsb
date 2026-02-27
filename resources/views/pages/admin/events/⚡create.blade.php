<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Event')] class extends Component {
    public string $name = '';

    public string $starts_at = '';

    public ?int $user_id = null;

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

        Event::create([
            'name' => $validated['name'],
            'slug' => Event::generateSlug($validated['name']),
            'starts_at' => $validated['starts_at'],
            'user_id' => $validated['user_id'],
        ]);

        session()->flash('status', __('Event created successfully.'));

        $this->redirect(route('admin.events.index'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-lg">
    <flux:heading size="xl">{{ __('Create Event') }}</flux:heading>
    <flux:subheading>{{ __('Create a new trivia event and assign it to a host.') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-6">
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
                {{ __('Create Event') }}
            </flux:button>
        </div>
    </form>
</div>
