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
<div @if ($event->isActive()) wire:poll.5s @endif class="font-body">
    {{-- Blink animation for live dot --}}
    <style>
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }
        .animate-blink { animation: blink 1.2s ease-in-out infinite; }
    </style>

    {{-- Header --}}
    <div class="overflow-hidden rounded-t-xl bg-gradient-to-r from-red-900 to-red-600 px-6 py-5">
        <div class="flex items-center justify-between">
            <h1 class="font-display text-xl uppercase tracking-wide text-white">{{ $event->name }}</h1>
            @if ($event->isActive())
                <span class="inline-flex items-center gap-1.5 rounded bg-red-500 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-white">
                    <span class="inline-block size-2 animate-blink rounded-full bg-white"></span>
                    Live
                </span>
            @else
                <span class="inline-flex items-center rounded bg-amber-500 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-white">
                    Final Scores
                </span>
            @endif
        </div>
    </div>

    {{-- Scoreboard Table --}}
    @if ($this->teams->isNotEmpty())
        <div class="overflow-x-auto rounded-b-xl border border-t-0 border-neutral-200">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-red-600">
                        <th class="px-3 py-2.5 text-center text-[10px] font-semibold uppercase tracking-widest text-neutral-400 w-12">#</th>
                        @if ($this->showNameColumn)
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-widest text-neutral-400">Team</th>
                        @endif
                        @if ($this->showTableColumn)
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold uppercase tracking-widest text-neutral-400 w-16">Tbl</th>
                        @endif
                        @foreach ($this->rounds as $round)
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold uppercase tracking-widest text-neutral-400 w-16 {{ $loop->first ? 'border-l-3 border-neutral-200' : '' }}">R{{ $round->sort_order }}</th>
                        @endforeach
                        <th class="px-3 py-2.5 text-center text-[10px] font-semibold uppercase tracking-widest text-neutral-400 w-20 border-l-3 border-neutral-200">Total</th>
                    </tr>
                </thead>
                <tbody class="text-sm font-medium">
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

                            $rankColor = match ($rank) {
                                1 => 'text-red-600 font-bold',
                                2 => 'text-slate-500 font-bold',
                                3 => 'text-amber-700 font-bold',
                                default => 'text-neutral-400',
                            };
                        @endphp
                        <tr class="border-b border-neutral-100 even:bg-red-50/40">
                            <td class="px-3 py-2.5 text-center {{ $rankColor }}">{{ $rank }}</td>
                            @if ($this->showNameColumn)
                                <td class="px-3 py-2.5 font-bold text-neutral-900">{{ $team->name }}</td>
                            @endif
                            @if ($this->showTableColumn)
                                <td class="px-3 py-2.5 text-center text-neutral-500">{{ $team->table_number ?? '' }}</td>
                            @endif
                            @foreach ($this->rounds as $round)
                                @php
                                    $score = $team->scores->firstWhere('round_id', $round->id);
                                @endphp
                                <td class="px-3 py-2.5 text-center text-neutral-500 {{ $loop->first ? 'border-l-3 border-neutral-200' : '' }}">
                                    @if ($score)
                                        {{ fmod((float) $score->value, 1) ? number_format((float) $score->value, 1) : (int) $score->value }}
                                    @else
                                        <span class="text-neutral-300">&ndash;</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-3 py-2.5 text-center text-base font-bold text-neutral-900 border-l-3 border-neutral-200">
                                {{ fmod($currentTotal, 1) ? number_format($currentTotal, 1) : (int) $currentTotal }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="rounded-b-xl border border-t-0 border-dashed border-neutral-300 p-8 text-center">
            <p class="text-neutral-500">No teams yet. Check back soon!</p>
        </div>
    @endif

</div>
