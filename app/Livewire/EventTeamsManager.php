<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Team;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EventTeamsManager extends Component
{
    public Event $event;

    public bool $canManage = false;

    /** @var Collection<int, Team> */
    public Collection $teams;

    public function mount(Event $event): void
    {
        $this->authorize('view', $event);
        $this->event = $event;
        $this->canManage = auth()->user()->can('update', $event);
        $this->loadTeams();
    }

    public function loadTeams(): void
    {
        $this->teams = $this->event->teams()->get();
    }

    public function addTeam(?string $name, ?int $tableNumber): void
    {
        $this->authorize('update', $this->event);

        $this->resetErrorBag('team');

        if (! $this->event->isActive()) {
            return;
        }

        if (! $name && ! $tableNumber) {
            $this->addError('team', 'A team must have at least a name or table number.');

            return;
        }

        $validator = Validator::make(
            ['name' => $name, 'table_number' => $tableNumber],
            [
                'name' => ['nullable', 'string', 'max:255', Rule::unique('teams')->where('event_id', $this->event->id)->whereNull('deleted_at')],
                'table_number' => ['nullable', 'integer', 'min:1', Rule::unique('teams')->where('event_id', $this->event->id)->whereNull('deleted_at')],
            ],
            [
                'name.unique' => 'A team with this name already exists.',
                'table_number.unique' => 'A team with this table number already exists.',
            ],
        );

        if ($validator->fails()) {
            $this->addError('team', $validator->errors()->first());

            return;
        }

        $maxSortOrder = $this->event->teams()->max('sort_order') ?? 0;

        $this->event->teams()->create([
            'name' => $name,
            'table_number' => $tableNumber,
            'sort_order' => $maxSortOrder + 1,
        ]);

        $this->loadTeams();
    }

    public function updateTeam(int $teamId, ?string $name, ?int $tableNumber): void
    {
        $this->authorize('update', $this->event);

        $this->resetErrorBag('team');

        if (! $this->event->isActive()) {
            return;
        }

        $team = $this->event->teams()->findOrFail($teamId);

        if (! $name && ! $tableNumber) {
            $this->addError('team', 'A team must have at least a name or table number.');

            return;
        }

        $validator = Validator::make(
            ['name' => $name, 'table_number' => $tableNumber],
            [
                'name' => ['nullable', 'string', 'max:255', Rule::unique('teams')->where('event_id', $this->event->id)->whereNull('deleted_at')->ignore($team->id)],
                'table_number' => ['nullable', 'integer', 'min:1', Rule::unique('teams')->where('event_id', $this->event->id)->whereNull('deleted_at')->ignore($team->id)],
            ],
            [
                'name.unique' => 'A team with this name already exists.',
                'table_number.unique' => 'A team with this table number already exists.',
            ],
        );

        if ($validator->fails()) {
            $this->addError('team', $validator->errors()->first());

            return;
        }

        $team->update([
            'name' => $name,
            'table_number' => $tableNumber,
        ]);

        $this->loadTeams();
    }

    public function removeTeam(int $teamId): void
    {
        $this->authorize('update', $this->event);

        if (! $this->event->isActive()) {
            return;
        }

        $team = $this->event->teams()->findOrFail($teamId);
        $team->delete();
        $this->loadTeams();
    }

    public function restoreTeam(int $teamId): void
    {
        $this->authorize('update', $this->event);

        if (! $this->event->isActive()) {
            return;
        }

        $team = $this->event->teams()->withTrashed()->findOrFail($teamId);
        $team->restore();
        $this->loadTeams();
    }

    public function reorderTeams(string $order): void
    {
        $this->authorize('update', $this->event);

        if (! $this->event->isActive()) {
            return;
        }

        $teams = match ($order) {
            'alphabetical' => $this->event->teams()->reorder()->orderBy('name')->get(),
            'table_number' => $this->event->teams()->reorder()->orderBy('table_number')->get(),
            default => $this->event->teams,
        };

        $teams->each(function (Team $team, int $index): void {
            $team->update(['sort_order' => $index + 1]);
        });

        $this->loadTeams();
    }

    public function render(): View
    {
        return view('livewire.event-teams-manager');
    }
}
