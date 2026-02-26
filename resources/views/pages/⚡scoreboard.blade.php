<?php

use App\Models\Event;
use App\Models\Round;
use App\Models\Score;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.public')]
#[Title('Scoreboard')]
class extends Component {
    public Event $event;

    public function mount(string $slug): void
    {
        $event = Event::where('slug', $slug)->first();

        if (! $event) {
            abort(404);
        }

        $this->event = $event;
    }

    #[Computed]
    public function teams(): Collection
    {
        return $this->event->teams()
            ->with('scores')
            ->get()
            ->map(function (Team $team) {
                $total = $team->scores->sum('value');
                $team->setAttribute('total', $total);

                return $team;
            })
            ->sortByDesc('total')
            ->values();
    }

    #[Computed]
    public function rounds(): Collection
    {
        return $this->event->rounds;
    }

    #[Computed]
    public function showNameColumn(): bool
    {
        return $this->teams->contains(fn (Team $team) => $team->name !== null);
    }

    #[Computed]
    public function showTableColumn(): bool
    {
        return $this->teams->contains(fn (Team $team) => $team->table_number !== null);
    }
}; ?>

{{-- wire:poll.5s only on active events --}}
<div @if ($event->isActive()) wire:poll.5s @endif>
    {{-- Header --}}
    <div class="mb-6 text-center">
        <h1 class="text-3xl font-bold text-neutral-900 dark:text-white">{{ $event->name }}</h1>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $event->starts_at->format('M j, Y g:i A') }}</p>
        @if ($event->isActive())
            <p class="mt-1 text-lg text-emerald-600 dark:text-emerald-400 font-medium">Live Scoreboard</p>
        @else
            <p class="mt-1 text-lg text-amber-600 dark:text-amber-400 font-medium">Final Scores</p>
        @endif
    </div>

    {{-- Scoreboard Table --}}
    @if ($this->teams->isNotEmpty())
        <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800">
                        <th class="px-3 py-2.5 text-center font-semibold w-12">#</th>
                        @if ($this->showNameColumn)
                            <th class="px-3 py-2.5 text-left font-semibold">Team</th>
                        @endif
                        @if ($this->showTableColumn)
                            <th class="px-3 py-2.5 text-center font-semibold w-16">Table</th>
                        @endif
                        @foreach ($this->rounds as $round)
                            <th class="px-3 py-2.5 text-center font-semibold w-16">R{{ $round->sort_order }}</th>
                        @endforeach
                        <th class="px-3 py-2.5 text-center font-semibold w-20">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $rank = 0;
                        $prevTotal = null;
                        $rankCounter = 0;
                    @endphp
                    @foreach ($this->teams as $team)
                        @php
                            $rankCounter++;
                            $currentTotal = $team->total;
                            if ($currentTotal !== $prevTotal) {
                                $rank = $rankCounter;
                            }
                            $prevTotal = $currentTotal;
                        @endphp
                        <tr class="border-b border-neutral-100 dark:border-neutral-800">
                            <td class="px-3 py-2 text-center font-semibold text-neutral-500">{{ $rank }}</td>
                            @if ($this->showNameColumn)
                                <td class="px-3 py-2 font-medium">{{ $team->displayName() }}</td>
                            @endif
                            @if ($this->showTableColumn)
                                <td class="px-3 py-2 text-center text-neutral-500">{{ $team->table_number ?? '' }}</td>
                            @endif
                            @foreach ($this->rounds as $round)
                                @php
                                    $score = $team->scores->firstWhere('round_id', $round->id);
                                @endphp
                                <td class="px-3 py-2 text-center">
                                    {{ $score ? number_format((float) $score->value, 1) : '-' }}
                                </td>
                            @endforeach
                            <td class="px-3 py-2 text-center font-bold">
                                {{ $currentTotal > 0 ? number_format($currentTotal, 1) : '0.0' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="rounded-lg border border-dashed border-neutral-300 p-8 text-center dark:border-neutral-600">
            <p class="text-neutral-500 dark:text-neutral-400">No teams yet. Check back soon!</p>
        </div>
    @endif

    {{-- Join code --}}
    <div class="mt-6 text-center text-sm text-neutral-500 dark:text-neutral-400">
        Join code: <span class="font-mono font-semibold">{{ $event->slug }}</span>
    </div>
</div>
