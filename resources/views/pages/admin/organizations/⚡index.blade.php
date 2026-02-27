<?php

use App\Models\Organization;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Organizations')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, Organization> */
    #[Computed]
    public function organizations(): LengthAwarePaginator
    {
        return Organization::query()
            ->withCount(['users', 'events'])
            ->with('owners')
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);
    }

    public function deleteOrganization(int $organizationId): void
    {
        $organization = Organization::findOrFail($organizationId);
        $organization->delete();

        Flux::toast(__('Organization deleted.'));
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Organizations') }}</flux:heading>
            <flux:subheading>{{ __('Manage organizations across the platform.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('admin.organizations.create')" wire:navigate>
            {{ __('Create Organization') }}
        </flux:button>
    </div>

    {{-- Organizations --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-2">
                <flux:heading size="lg">{{ __('Organizations') }}</flux:heading>
                <flux:badge size="sm" color="zinc">{{ $this->organizations->total() }}</flux:badge>
            </div>
            <flux:subheading>{{ __('All registered organizations.') }}</flux:subheading>
        </div>
        <div class="bg-white p-5 dark:bg-zinc-900">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search organizations...')" class="mb-4" />

            <flux:table :paginate="$this->organizations">
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Slug') }}</flux:table.column>
                    <flux:table.column>{{ __('Owners') }}</flux:table.column>
                    <flux:table.column>{{ __('Members') }}</flux:table.column>
                    <flux:table.column>{{ __('Events') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->organizations as $organization)
                        <flux:table.row :key="$organization->id">
                            <flux:table.cell variant="strong">{{ $organization->name }}</flux:table.cell>
                            <flux:table.cell>{{ $organization->slug }}</flux:table.cell>
                            <flux:table.cell>{{ $organization->owners->pluck('name')->join(', ') ?: '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $organization->users_count }}</flux:table.cell>
                            <flux:table.cell>{{ $organization->events_count }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-1">
                                    <flux:button size="sm" variant="ghost" icon="pencil" :href="route('admin.organizations.edit', $organization)" wire:navigate />
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        wire:click="deleteOrganization({{ $organization->id }})"
                                        wire:confirm="{{ __('Delete this organization? This will permanently remove the organization, all events, teams, rounds, and scores. This cannot be undone.') }}"
                                    />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>
