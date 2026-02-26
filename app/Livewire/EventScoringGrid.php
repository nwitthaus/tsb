<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Round;
use App\Models\Team;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class EventScoringGrid extends Component
{
    public Event $event;

    /** @var Collection<int, Team> */
    public Collection $teams;

    /** @var Collection<int, Round> */
    public Collection $rounds;

    /** @var array<string, string> */
    public array $scores = [];

    public function mount(Event $event): void
    {
        $this->event = $event;
        $this->loadGrid();
    }

    public function loadGrid(): void
    {
        $this->event->refresh();
        $this->teams = $this->event->teams()->with('scores')->get();
        $this->rounds = $this->event->rounds;

        $this->scores = [];
        foreach ($this->teams as $team) {
            foreach ($team->scores as $score) {
                $this->scores[$team->id.'-'.$score->round_id] = $score->value;
            }
        }
    }

    public function addTeam(?string $name, ?int $tableNumber): void
    {
        if (! $name && ! $tableNumber) {
            $this->addError('team', 'A team must have at least a name or table number.');

            return;
        }

        $maxSortOrder = $this->event->teams()->max('sort_order') ?? 0;

        $this->event->teams()->create([
            'name' => $name,
            'table_number' => $tableNumber,
            'sort_order' => $maxSortOrder + 1,
        ]);

        $this->loadGrid();
    }

    public function removeTeam(int $teamId): void
    {
        $team = $this->event->teams()->findOrFail($teamId);
        $team->delete();
        $this->loadGrid();
    }

    public function restoreTeam(int $teamId): void
    {
        $team = $this->event->teams()->withTrashed()->findOrFail($teamId);
        $team->restore();
        $this->loadGrid();
    }

    public function addRound(): void
    {
        $maxSortOrder = $this->event->rounds()->max('sort_order') ?? 0;

        $this->event->rounds()->create([
            'sort_order' => $maxSortOrder + 1,
        ]);

        $this->loadGrid();
    }

    public function removeLastRound(): void
    {
        $lastRound = $this->event->rounds()->reorder()->orderByDesc('sort_order')->first();

        if ($lastRound) {
            $lastRound->delete();
            $this->loadGrid();
        }
    }

    public function render(): View
    {
        return view('livewire.event-scoring-grid');
    }
}
