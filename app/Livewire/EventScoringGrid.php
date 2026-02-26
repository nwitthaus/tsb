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

    public function render(): View
    {
        return view('livewire.event-scoring-grid');
    }
}
