<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Users')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, User> */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->withCount('organizations')
            ->when($this->search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15);
    }

    public function deleteUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            return;
        }

        $user->delete();

        Flux::toast(__('User deleted.'));
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Users') }}</flux:heading>
            <flux:subheading>{{ __('Manage user accounts and permissions.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('admin.users.create')" wire:navigate>
            {{ __('Create User') }}
        </flux:button>
    </div>

    {{-- Users --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-2">
                <flux:heading size="lg">{{ __('Users') }}</flux:heading>
                <flux:badge size="sm" color="zinc">{{ $this->users->total() }}</flux:badge>
            </div>
            <flux:subheading>{{ __('All registered user accounts.') }}</flux:subheading>
        </div>
        <div class="bg-white p-5 dark:bg-zinc-900">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search users...')" class="mb-4" />

            <flux:table :paginate="$this->users">
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Email') }}</flux:table.column>
                    <flux:table.column>{{ __('Organizations') }}</flux:table.column>
                    <flux:table.column>{{ __('Registered') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->users as $user)
                        <flux:table.row :key="$user->id">
                            <flux:table.cell variant="strong">
                                <div class="flex items-center gap-2">
                                    {{ $user->name }}
                                    @if ($user->is_super_admin)
                                        <flux:badge color="amber" size="sm">{{ __('Admin') }}</flux:badge>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $user->email }}</flux:table.cell>
                            <flux:table.cell>{{ $user->organizations_count }}</flux:table.cell>
                            <flux:table.cell>{{ $user->created_at->format('M j, Y') }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-1">
                                    <flux:button size="sm" variant="ghost" icon="pencil" :href="route('admin.users.edit', $user)" wire:navigate />
                                    @if ($user->id !== auth()->id())
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="trash"
                                            wire:click="deleteUser({{ $user->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this user? This action cannot be undone.') }}"
                                        />
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>
