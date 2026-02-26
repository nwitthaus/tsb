<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Score;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
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
        $this->authorize('update', $event);
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
                $this->scores[$team->id.'-'.$score->round_id] = (float) $score->value + 0;
            }
        }
    }

    public function addRound(): void
    {
        if (! $this->event->isActive()) {
            return;
        }

        $maxSortOrder = $this->event->rounds()->max('sort_order') ?? 0;

        $this->event->rounds()->create([
            'sort_order' => $maxSortOrder + 1,
        ]);

        $this->loadGrid();
    }

    public function removeLastRound(): void
    {
        if (! $this->event->isActive()) {
            return;
        }

        $lastRound = $this->event->rounds()->reorder()->orderByDesc('sort_order')->first();

        if ($lastRound) {
            $lastRound->delete();
            $this->loadGrid();
        }
    }

    public function saveScore(int $teamId, int $roundId, ?string $value): void
    {
        if (! $this->event->isActive()) {
            return;
        }

        if (! $this->event->teams()->where('id', $teamId)->exists()
            || ! $this->event->rounds()->where('id', $roundId)->exists()) {
            return;
        }

        if ($value === null || $value === '') {
            Score::query()
                ->where('team_id', $teamId)
                ->where('round_id', $roundId)
                ->delete();

            unset($this->scores[$teamId.'-'.$roundId]);

            return;
        }

        $validator = Validator::make(
            ['value' => $value],
            ['value' => ['required', 'numeric', 'min:0', 'max:999.9']],
        );

        if ($validator->fails()) {
            $this->addError('score', $validator->errors()->first('value'));

            return;
        }

        Score::query()->updateOrCreate(
            ['team_id' => $teamId, 'round_id' => $roundId],
            ['value' => $value],
        );

        $this->scores[$teamId.'-'.$roundId] = (float) $value + 0;
    }

    public function endEvent(): void
    {
        if (! $this->event->isActive()) {
            return;
        }

        $this->event->update(['ended_at' => now()]);
        $this->event->refresh();
    }

    public function reopenEvent(): void
    {
        $this->event->update(['ended_at' => null]);
        $this->event->refresh();
    }

    public function render(): View
    {
        return view('livewire.event-scoring-grid');
    }
}
