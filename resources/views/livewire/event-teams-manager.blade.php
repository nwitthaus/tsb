<div x-data="{
    newName: '',
    newTableNumber: '',
}">
    @if ($event->isActive())
        @error('team') <flux:callout variant="danger" class="mb-4"><flux:callout.text>{{ $message }}</flux:callout.text></flux:callout> @enderror

        <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800">
                        <th class="px-3 py-2 text-left font-medium">{{ __('Team Name') }}</th>
                        <th class="w-28 px-3 py-2 text-center font-medium">{{ __('Table #') }}</th>
                        <th class="w-10 px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($teams as $team)
                        <tr class="border-b border-neutral-100 dark:border-neutral-800" wire:key="team-{{ $team->id }}">
                            <td class="px-2 py-1">
                                <input
                                    type="text"
                                    value="{{ $team->name }}"
                                    placeholder="{{ __('Team name') }}"
                                    class="w-full rounded border border-neutral-300 bg-white px-2 py-1.5 text-sm dark:border-neutral-600 dark:bg-neutral-900"
                                    @blur="$wire.updateTeam({{ $team->id }}, $el.value || null, {{ $team->table_number ?? 'null' }})"
                                />
                            </td>
                            <td class="px-2 py-1">
                                <input
                                    type="number"
                                    value="{{ $team->table_number }}"
                                    placeholder="#"
                                    min="1"
                                    class="w-full rounded border border-neutral-300 bg-white px-2 py-1.5 text-center text-sm dark:border-neutral-600 dark:bg-neutral-900"
                                    @blur="$wire.updateTeam({{ $team->id }}, '{{ str_replace("'", "\\'", $team->name ?? '') }}', $el.value ? parseInt($el.value) : null)"
                                />
                            </td>
                            <td class="px-1 py-1">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="x-mark"
                                    wire:click="removeTeam({{ $team->id }})"
                                    wire:confirm="{{ __('Remove this team? This can be undone.') }}"
                                />
                            </td>
                        </tr>
                    @endforeach

                    {{-- Add Team Row --}}
                    <tr class="bg-neutral-50/50 dark:bg-neutral-800/50">
                        <td class="px-2 py-1">
                            <input
                                x-model="newName"
                                type="text"
                                placeholder="{{ __('New team name') }}"
                                class="w-full rounded border border-dashed border-neutral-300 bg-white px-2 py-1.5 text-sm dark:border-neutral-600 dark:bg-neutral-900"
                                @keydown.enter.prevent="
                                    await $wire.addTeam(newName || null, newTableNumber ? parseInt(newTableNumber) : null);
                                    if (! Object.keys($wire.__instance.canonical.errors).length) {
                                        newName = '';
                                        newTableNumber = '';
                                    }
                                "
                            />
                        </td>
                        <td class="px-2 py-1">
                            <input
                                x-model="newTableNumber"
                                type="number"
                                placeholder="#"
                                min="1"
                                class="w-full rounded border border-dashed border-neutral-300 bg-white px-2 py-1.5 text-center text-sm dark:border-neutral-600 dark:bg-neutral-900"
                                @keydown.enter.prevent="
                                    await $wire.addTeam(newName || null, newTableNumber ? parseInt(newTableNumber) : null);
                                    if (! Object.keys($wire.__instance.canonical.errors).length) {
                                        newName = '';
                                        newTableNumber = '';
                                    }
                                "
                            />
                        </td>
                        <td class="px-1 py-1">
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="plus"
                                x-on:click="
                                    await $wire.addTeam(newName || null, newTableNumber ? parseInt(newTableNumber) : null);
                                    if (! Object.keys($wire.__instance.canonical.errors).length) {
                                        newName = '';
                                        newTableNumber = '';
                                    }
                                "
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if ($teams->count() > 1)
            <div class="mt-4 flex gap-2">
                <flux:button size="sm" variant="ghost" wire:click="reorderTeams('alphabetical')">{{ __('Sort A-Z') }}</flux:button>
                <flux:button size="sm" variant="ghost" wire:click="reorderTeams('table_number')">{{ __('Sort by Table') }}</flux:button>
            </div>
        @endif
    @else
        {{-- Read-only view for ended events --}}
        @if ($teams->isNotEmpty())
            <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800">
                            <th class="px-3 py-2 text-left font-medium">{{ __('Team Name') }}</th>
                            <th class="w-28 px-3 py-2 text-center font-medium">{{ __('Table #') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($teams as $team)
                            <tr class="border-b border-neutral-100 dark:border-neutral-800" wire:key="team-{{ $team->id }}">
                                <td class="px-3 py-2">{{ $team->displayName() }}</td>
                                <td class="px-3 py-2 text-center text-neutral-500">{{ $team->table_number }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="rounded-lg border border-dashed border-neutral-300 p-8 text-center dark:border-neutral-600">
                <flux:subheading>{{ __('No teams yet. The event has ended.') }}</flux:subheading>
            </div>
        @endif
    @endif
</div>
