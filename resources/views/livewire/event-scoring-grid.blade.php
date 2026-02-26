<div x-data="{
    newTeamName: '',
    newTeamTableNumber: '',
    move(row, col, totalRows, totalCols, dRow, dCol) {
        const nextRow = (row + dRow + totalRows) % totalRows;
        const nextCol = (col + dCol + totalCols) % totalCols;
        const nextInput = this.$root.querySelector(`[data-row='${nextRow}'][data-col='${nextCol}']`);
        if (nextInput) nextInput.focus();
    }
}">
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $event->name }}</flux:heading>
            <flux:subheading>{{ $event->starts_at->format('M j, Y g:i A') }} &middot; {{ __('Join code:') }} <span class="font-mono font-semibold">{{ $event->slug }}</span></flux:subheading>
        </div>
    </div>

    {{-- Share Scoreboard --}}
    <div class="mb-6 flex items-start gap-6 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
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

    {{-- Ended Event Banner --}}
    @if (! $event->isActive())
        <flux:callout variant="warning" class="mb-6">
            <flux:callout.heading>{{ __('Final Scores') }}</flux:callout.heading>
            <flux:callout.text>{{ __('This event has ended. Scores are read-only.') }}</flux:callout.text>
            <x-slot:actions>
                <flux:button size="sm" wire:click="reopenEvent">{{ __('Reopen Event') }}</flux:button>
            </x-slot:actions>
        </flux:callout>
    @endif

    {{-- Control Bar (active event only) --}}
    @if ($event->isActive())
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <flux:modal.trigger name="add-team">
                <flux:button size="sm" icon="plus">{{ __('Add Team') }}</flux:button>
            </flux:modal.trigger>

            <flux:button size="sm" icon="plus" wire:click="addRound">{{ __('Add Round') }}</flux:button>

            @if ($rounds->isNotEmpty())
                <flux:modal.trigger name="confirm-remove-round">
                    <flux:button size="sm" variant="danger" icon="minus">{{ __('Remove Last Round') }}</flux:button>
                </flux:modal.trigger>
            @endif

            @if ($teams->count() > 1)
                <flux:button size="sm" variant="ghost" wire:click="reorderTeams('alphabetical')">{{ __('Sort A-Z') }}</flux:button>
                <flux:button size="sm" variant="ghost" wire:click="reorderTeams('table_number')">{{ __('Sort by Table') }}</flux:button>
            @endif

            <div class="ml-auto">
                <flux:modal.trigger name="confirm-end-event">
                    <flux:button size="sm" variant="danger">{{ __('End Event') }}</flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    @endif

    {{-- Scoring Grid --}}
    @if ($teams->isNotEmpty())
        <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
            <table class="w-full table-fixed text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800">
                        <th class="w-1/4 px-3 py-2 text-left font-medium">{{ __('Team') }}</th>
                        <th class="w-16 px-3 py-2 text-center font-medium">{{ __('Table') }}</th>
                        @foreach ($rounds as $round)
                            <th class="px-3 py-2 text-center font-medium">R{{ $round->sort_order }}</th>
                        @endforeach
                        <th class="px-3 py-2 text-center font-medium">{{ __('Total') }}</th>
                        @if ($event->isActive())
                            <th class="w-10 px-3 py-2"></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($teams as $rowIndex => $team)
                        <tr class="border-b border-neutral-100 dark:border-neutral-800" wire:key="team-{{ $team->id }}">
                            <td class="truncate px-3 py-1 font-medium">{{ $team->displayName() }}</td>
                            <td class="px-3 py-1 text-center text-neutral-500">{{ $team->table_number }}</td>
                            @foreach ($rounds as $colIndex => $round)
                                @php
                                    $key = $team->id . '-' . $round->id;
                                    $hasScore = isset($scores[$key]);
                                @endphp
                                <td class="px-1 py-1" wire:key="cell-{{ $key }}">
                                    @if ($event->isActive())
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            value="{{ $scores[$key] ?? '' }}"
                                            data-row="{{ $rowIndex }}"
                                            data-col="{{ $colIndex }}"
                                            class="w-full rounded border px-2 py-1 text-center text-sm transition-colors
                                                {{ $hasScore
                                                    ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-950/30'
                                                    : 'border-neutral-300 bg-white dark:border-neutral-600 dark:bg-neutral-900'
                                                }}"
                                            @blur="$wire.saveScore({{ $team->id }}, {{ $round->id }}, $el.value)"
                                            @keydown.enter.prevent="move({{ $rowIndex }}, {{ $colIndex }}, {{ $teams->count() }}, {{ $rounds->count() }}, 1, 0)"
                                            @keydown.tab.prevent="move({{ $rowIndex }}, {{ $colIndex }}, {{ $teams->count() }}, {{ $rounds->count() }}, $event.shiftKey ? -1 : 1, 0)"
                                            @keydown.up.prevent="move({{ $rowIndex }}, {{ $colIndex }}, {{ $teams->count() }}, {{ $rounds->count() }}, -1, 0)"
                                            @keydown.down.prevent="move({{ $rowIndex }}, {{ $colIndex }}, {{ $teams->count() }}, {{ $rounds->count() }}, 1, 0)"
                                            @keydown.left.prevent="move({{ $rowIndex }}, {{ $colIndex }}, {{ $teams->count() }}, {{ $rounds->count() }}, 0, -1)"
                                            @keydown.right.prevent="move({{ $rowIndex }}, {{ $colIndex }}, {{ $teams->count() }}, {{ $rounds->count() }}, 0, 1)"
                                        />
                                    @else
                                        <span class="block py-1 text-center {{ $hasScore ? 'font-medium' : 'text-neutral-400' }}">
                                            {{ $scores[$key] ?? '-' }}
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-3 py-1 text-center font-semibold">
                                @php
                                    $total = 0;
                                    foreach ($rounds as $r) {
                                        $total += (float) ($scores[$team->id . '-' . $r->id] ?? 0);
                                    }
                                @endphp
                                {{ $total > 0 ? number_format($total, 1) : '-' }}
                            </td>
                            @if ($event->isActive())
                                <td class="px-1 py-1">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="x-mark"
                                        wire:click="removeTeam({{ $team->id }})"
                                        wire:confirm="{{ __('Remove this team? This can be undone.') }}"
                                    />
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="rounded-lg border border-dashed border-neutral-300 p-8 text-center dark:border-neutral-600">
            <flux:subheading>{{ __('No teams yet. Add a team to get started.') }}</flux:subheading>
        </div>
    @endif

    {{-- Add Team Modal --}}
    <flux:modal name="add-team" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Add Team') }}</flux:heading>
                <flux:subheading>{{ __('Enter a team name and/or table number.') }}</flux:subheading>
            </div>

            <flux:input x-model="newTeamName" :label="__('Team Name')" :placeholder="__('Quizly Bears')" />
            <flux:input x-model="newTeamTableNumber" type="number" :label="__('Table Number')" :placeholder="__('3')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" x-on:click="
                    $wire.addTeam(newTeamName || null, newTeamTableNumber ? parseInt(newTeamTableNumber) : null);
                    newTeamName = '';
                    newTeamTableNumber = '';
                    $flux.modal('add-team').close();
                ">{{ __('Add Team') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Confirm Remove Last Round Modal --}}
    <flux:modal name="confirm-remove-round" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Remove Last Round?') }}</flux:heading>
                <flux:subheading>{{ __('This will delete the last round and all its scores. This cannot be undone.') }}</flux:subheading>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="removeLastRound" x-on:click="$flux.modal('confirm-remove-round').close()">{{ __('Remove Round') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Confirm End Event Modal --}}
    <flux:modal name="confirm-end-event" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('End Event?') }}</flux:heading>
                <flux:subheading>{{ __('The scoreboard will show final scores and stop updating. You can reopen the event later.') }}</flux:subheading>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="endEvent" x-on:click="$flux.modal('confirm-end-event').close()">{{ __('End Event') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
