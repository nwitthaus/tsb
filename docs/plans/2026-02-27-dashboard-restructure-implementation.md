# Organization Dashboard Restructure Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Restructure the organization dashboard to follow admin CRUD patterns with sectioned cards, searchable tables, and consistent back navigation.

**Architecture:** Move all event pages under the `/organizations/{org}/events/` URL namespace. Replace the org show page with a minimal nav hub. Create a new events index with tabbed Active/Past tables. Restyle event create/edit/teams/scoring pages using the Sectioned Cards pattern from DESIGN-SYSTEM.md §10.

**Tech Stack:** Laravel 12, Livewire v4 SFC pages (⚡ prefix), Flux UI Pro components, Pest v4 tests

**Design doc:** `docs/plans/2026-02-27-dashboard-restructure-design.md`

---

### Task 1: Update routes and create directory structure

**Files:**
- Modify: `routes/web.php`
- Create directory: `resources/views/pages/organizations/events/`

**Step 1: Create the directory for new event pages**

Run: `mkdir -p resources/views/pages/organizations/events`

**Step 2: Update routes/web.php**

Replace the organization and event route blocks with:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    // Organization routes
    Route::livewire('organizations/create', 'pages::organizations.create')->name('organizations.create');
    Route::livewire('organizations/{organization}/settings', 'pages::organizations.settings')->name('organizations.settings');
    Route::livewire('organizations/{organization}/events', 'pages::organizations.events.index')->name('organizations.events.index');
    Route::livewire('organizations/{organization}/events/create', 'pages::organizations.events.create')->name('organizations.events.create');
    Route::livewire('organizations/{organization}/events/{event}', 'pages::organizations.events.edit')->name('organizations.events.edit');
    Route::livewire('organizations/{organization}/events/{event}/teams', 'pages::organizations.events.teams')->name('organizations.events.teams');
    Route::livewire('organizations/{organization}/events/{event}/scoring', 'pages::organizations.events.scoring')->name('organizations.events.scoring');
    Route::livewire('organizations/{organization}', 'pages::organizations.show')->name('organizations.show');

    // Invitation routes
    Route::livewire('invitations/{token}', 'pages::invitations.accept')->name('invitations.accept');

    // Admin routes (unchanged)
    Route::middleware('super-admin')->prefix('admin')->group(function () {
        // ... keep existing admin routes unchanged ...
    });
});
```

Key changes:
- Remove old `events/{event}`, `events/{event}/teams`, `events/{event}/scoring` routes
- Remove old `organizations/{organization}/events/create` route
- Add new routes under `organizations/{organization}/events/*` with `organizations.events.*` names
- The `organizations/{organization}` catch-all route MUST come after the more specific routes

**Step 3: Update admin event edit quick links to use new route names**

In `resources/views/pages/admin/events/⚡edit.blade.php`, update the Quick Links section:
- `route('events.show', $event)` → `route('organizations.events.edit', [$event->organization, $event])`
- `route('events.teams', $event)` → `route('organizations.events.teams', [$event->organization, $event])`

In `resources/views/pages/admin/events/⚡index.blade.php`, update:
- `route('events.show', $event)` → `route('organizations.events.edit', [$event->organization, $event])`

**Step 4: Update organization settings back link**

In `resources/views/pages/organizations/⚡settings.blade.php`, the back link `route('organizations.show', $organization)` stays the same — no change needed.

**Step 5: Update invitation accept redirect**

Check `resources/views/pages/invitations/⚡accept.blade.php` for any references to old event routes and update if needed.

**Step 6: Commit**

```bash
git add routes/web.php resources/views/pages/admin/events/ resources/views/pages/organizations/
git commit -m "refactor: restructure routes for org dashboard event pages"
```

---

### Task 2: Rewrite Organization Dashboard Hub

**Files:**
- Modify: `resources/views/pages/organizations/⚡show.blade.php`
- Test: `tests/Feature/DashboardTest.php` (check for references)
- Test: `tests/Feature/Organizations/ScorekeeperAccessTest.php`

**Step 1: Rewrite the org show page**

Replace `resources/views/pages/organizations/⚡show.blade.php` with a minimal nav hub:

```php
<?php

use App\Models\Organization;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Organization')] class extends Component {
    public Organization $organization;

    public function mount(Organization $organization): void
    {
        $this->authorize('view', $organization);
        $this->organization = $organization;
    }
}; ?>

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('dashboard')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $organization->name }}</flux:heading>
            <flux:subheading>{{ __('Organization Dashboard') }}</flux:subheading>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="grid gap-4 sm:grid-cols-2">
        <flux:card class="flex flex-col items-start gap-3">
            <flux:icon.calendar-days class="text-zinc-400" />
            <div>
                <flux:heading size="lg">{{ __('Events') }}</flux:heading>
                <flux:subheading>{{ __('Manage your trivia events.') }}</flux:subheading>
            </div>
            <flux:button variant="primary" :href="route('organizations.events.index', $organization)" wire:navigate class="mt-auto">
                {{ __('View Events') }}
            </flux:button>
        </flux:card>

        <flux:card class="flex flex-col items-start gap-3">
            <flux:icon.cog-6-tooth class="text-zinc-400" />
            <div>
                <flux:heading size="lg">{{ __('Settings') }}</flux:heading>
                <flux:subheading>{{ __('Organization settings & members.') }}</flux:subheading>
            </div>
            @can('update', $organization)
                <flux:button :href="route('organizations.settings', $organization)" wire:navigate class="mt-auto">
                    {{ __('Manage Settings') }}
                </flux:button>
            @endcan
        </flux:card>
    </div>
</div>
```

**Step 2: Update existing tests referencing old org show behavior**

Tests that assert event listings on the org show page need updating. Check:
- `tests/Feature/Organizations/ScorekeeperAccessTest.php` — update route names and assertions
- `tests/Feature/DashboardTest.php` — likely just checks redirect, should be fine

**Step 3: Run tests**

Run: `php artisan test --compact --filter=ScorekeeperAccess`
Run: `php artisan test --compact --filter=Dashboard`

**Step 4: Commit**

```bash
git add resources/views/pages/organizations/ tests/
git commit -m "refactor: simplify org show page to minimal navigation hub"
```

---

### Task 3: Create Events Index Page

**Files:**
- Create: `resources/views/pages/organizations/events/⚡index.blade.php`
- Create: `tests/Feature/Organizations/EventsIndexTest.php`

**Step 1: Create the events index page**

Create `resources/views/pages/organizations/events/⚡index.blade.php`:

```php
<?php

use App\Models\Event;
use App\Models\Organization;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Events')] class extends Component {
    public Organization $organization;

    #[Url]
    public string $search = '';

    #[Url]
    public string $tab = 'active';

    public function mount(Organization $organization): void
    {
        $this->authorize('view', $organization);
        $this->organization = $organization;
    }

    /** @return Collection<int, Event> */
    #[Computed]
    public function activeEvents(): Collection
    {
        return $this->organization->events()
            ->whereNull('ended_at')
            ->withCount('teams')
            ->when($this->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->latest('starts_at')
            ->get();
    }

    /** @return Collection<int, Event> */
    #[Computed]
    public function pastEvents(): Collection
    {
        return $this->organization->events()
            ->whereNotNull('ended_at')
            ->withCount('teams')
            ->when($this->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->latest('ended_at')
            ->get();
    }

    public function deleteEvent(int $eventId): void
    {
        $event = Event::findOrFail($eventId);
        $this->authorize('delete', $event);
        $event->delete();

        Flux::toast(__('Event deleted.'));
    }
}; ?>

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <flux:button variant="ghost" icon="arrow-left" :href="route('organizations.show', $organization)" wire:navigate />
            <div>
                <flux:heading size="xl">{{ __('Events') }}</flux:heading>
                <flux:subheading>{{ $organization->name }}</flux:subheading>
            </div>
        </div>
        @can('update', $organization)
            <flux:button variant="primary" :href="route('organizations.events.create', $organization)" wire:navigate>
                {{ __('Create Event') }}
            </flux:button>
        @endcan
    </div>

    {{-- Search --}}
    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search events...')" />

    {{-- Tabs --}}
    <flux:tabs wire:model="tab">
        <flux:tab name="active">{{ __('Active') }}</flux:tab>
        <flux:tab name="past">{{ __('Past') }}</flux:tab>
    </flux:tabs>

    {{-- Active Events --}}
    @if ($tab === 'active')
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center gap-2">
                    <flux:heading size="lg">{{ __('Active Events') }}</flux:heading>
                    <flux:badge size="sm" color="zinc">{{ $this->activeEvents->count() }}</flux:badge>
                </div>
                <flux:subheading>{{ __('Events currently accepting scores.') }}</flux:subheading>
            </div>
            <div class="bg-white p-5 dark:bg-zinc-900">
                @if ($this->activeEvents->isNotEmpty())
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Event') }}</flux:table.column>
                            <flux:table.column>{{ __('Join Code') }}</flux:table.column>
                            <flux:table.column>{{ __('Scheduled') }}</flux:table.column>
                            <flux:table.column>{{ __('Teams') }}</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->activeEvents as $event)
                                <flux:table.row :key="$event->id">
                                    <flux:table.cell variant="strong">{{ $event->name }}</flux:table.cell>
                                    <flux:table.cell class="font-mono text-xs">{{ $event->slug }}</flux:table.cell>
                                    <flux:table.cell>{{ $event->starts_at->format('M j, Y g:i A') }}</flux:table.cell>
                                    <flux:table.cell>{{ $event->teams_count }}</flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex items-center gap-1">
                                            <flux:button size="sm" variant="ghost" icon="pencil" :href="route('organizations.events.edit', [$organization, $event])" wire:navigate />
                                            @can('delete', $event)
                                                <flux:button
                                                    size="sm"
                                                    variant="ghost"
                                                    icon="trash"
                                                    wire:click="deleteEvent({{ $event->id }})"
                                                    wire:confirm="{{ __('Delete this event? This will permanently remove the event and all its data. This cannot be undone.') }}"
                                                />
                                            @endcan
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <flux:subheading>
                        {{ __('No active events.') }}
                        @can('update', $organization)
                            {{ __('Create one to get started.') }}
                        @endcan
                    </flux:subheading>
                @endif
            </div>
        </div>
    @endif

    {{-- Past Events --}}
    @if ($tab === 'past')
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center gap-2">
                    <flux:heading size="lg">{{ __('Past Events') }}</flux:heading>
                    <flux:badge size="sm" color="zinc">{{ $this->pastEvents->count() }}</flux:badge>
                </div>
                <flux:subheading>{{ __('Events that have ended.') }}</flux:subheading>
            </div>
            <div class="bg-white p-5 dark:bg-zinc-900">
                @if ($this->pastEvents->isNotEmpty())
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Event') }}</flux:table.column>
                            <flux:table.column>{{ __('Scheduled') }}</flux:table.column>
                            <flux:table.column>{{ __('Teams') }}</flux:table.column>
                            <flux:table.column>{{ __('Ended') }}</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->pastEvents as $event)
                                <flux:table.row :key="$event->id">
                                    <flux:table.cell variant="strong">{{ $event->name }}</flux:table.cell>
                                    <flux:table.cell>{{ $event->starts_at->format('M j, Y g:i A') }}</flux:table.cell>
                                    <flux:table.cell>{{ $event->teams_count }}</flux:table.cell>
                                    <flux:table.cell>{{ $event->ended_at->diffForHumans() }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button size="sm" variant="ghost" icon="pencil" :href="route('organizations.events.edit', [$organization, $event])" wire:navigate />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <flux:subheading>{{ __('No past events.') }}</flux:subheading>
                @endif
            </div>
        </div>
    @endif
</div>
```

**Step 2: Write tests for events index**

Create `tests/Feature/Organizations/EventsIndexTest.php`:

```php
<?php

use App\Enums\OrganizationRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

test('owner can see events index', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->assertSee($event->name);
});

test('scorekeeper can see events index', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->assertSee($event->name);
});

test('non-member cannot see events index', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $this->actingAs($user)
        ->get(route('organizations.events.index', $organization))
        ->assertForbidden();
});

test('events index shows active events on active tab', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    $active = Event::factory()->create(['organization_id' => $organization->id, 'name' => 'Active Trivia']);
    $ended = Event::factory()->ended()->create(['organization_id' => $organization->id, 'name' => 'Past Trivia']);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->assertSet('tab', 'active')
        ->assertSee('Active Trivia')
        ->assertDontSee('Past Trivia');
});

test('events index shows past events on past tab', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    $active = Event::factory()->create(['organization_id' => $organization->id, 'name' => 'Active Trivia']);
    $ended = Event::factory()->ended()->create(['organization_id' => $organization->id, 'name' => 'Past Trivia']);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->set('tab', 'past')
        ->assertSee('Past Trivia')
        ->assertDontSee('Active Trivia');
});

test('events index is searchable by name', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

    Event::factory()->create(['organization_id' => $organization->id, 'name' => 'Tuesday Trivia']);
    Event::factory()->create(['organization_id' => $organization->id, 'name' => 'Friday Quiz']);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->set('search', 'Tuesday')
        ->assertSee('Tuesday Trivia')
        ->assertDontSee('Friday Quiz');
});

test('owner can delete event from index', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->call('deleteEvent', $event->id);

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});

test('scorekeeper cannot delete event from index', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Scorekeeper->value]);
    $event = Event::factory()->create(['organization_id' => $organization->id]);

    Livewire::actingAs($user)
        ->test('pages::organizations.events.index', ['organization' => $organization])
        ->call('deleteEvent', $event->id)
        ->assertForbidden();

    $this->assertDatabaseHas('events', ['id' => $event->id]);
});
```

**Step 3: Run tests**

Run: `php artisan test --compact --filter=EventsIndex`

**Step 4: Commit**

```bash
git add resources/views/pages/organizations/events/ tests/Feature/Organizations/EventsIndexTest.php
git commit -m "feat: add org events index page with tabbed Active/Past tables"
```

---

### Task 4: Restyle Event Create Page

**Files:**
- Create: `resources/views/pages/organizations/events/⚡create.blade.php`
- Delete: `resources/views/pages/events/⚡create.blade.php`
- Modify: `tests/Feature/Events/CreateEventTest.php` (update component references)

**Step 1: Create the restyled event create page**

Create `resources/views/pages/organizations/events/⚡create.blade.php` — same PHP logic as old `pages/events/⚡create.blade.php` but with sectioned card layout:

```php
<?php

use App\Models\Organization;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Create Event')] class extends Component {
    public Organization $organization;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:100|unique:events,slug|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
    public string $slug = '';

    #[Validate('required|date|after_or_equal:today')]
    public string $starts_at = '';

    #[Validate('nullable|integer|min:1|max:200')]
    public ?int $tables = null;

    #[Validate('nullable|integer|min:1|max:50')]
    public ?int $rounds = null;

    public function mount(Organization $organization): void
    {
        $this->authorize('update', $organization);
        $this->organization = $organization;
    }

    public function updated(string $property): void
    {
        if ($property === 'name') {
            $this->slug = Str::slug($this->name);
        }
    }

    public function save(): void
    {
        $this->authorize('update', $this->organization);

        $validated = $this->validate();

        $tables = $validated['tables'] ?? null;
        $rounds = $validated['rounds'] ?? null;
        unset($validated['tables'], $validated['rounds']);

        $event = $this->organization->events()->create($validated);

        if ($tables) {
            for ($i = 1; $i <= $tables; $i++) {
                $event->teams()->create([
                    'table_number' => $i,
                    'sort_order' => $i,
                ]);
            }
        }

        if ($rounds) {
            for ($i = 1; $i <= $rounds; $i++) {
                $event->rounds()->create([
                    'sort_order' => $i,
                ]);
            }
        }

        $this->redirect(route('organizations.events.teams', [$this->organization, $event]), navigate: true);
    }
}; ?>

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('organizations.events.index', $organization)" wire:navigate />
        <div>
            <flux:heading size="xl">{{ __('Create Event') }}</flux:heading>
            <flux:subheading>{{ $organization->name }}</flux:subheading>
        </div>
    </div>

    <form wire:submit="save">
        {{-- Event Details Card --}}
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Event Details') }}</flux:heading>
                <flux:subheading>{{ __('Configure your new trivia event.') }}</flux:subheading>
            </div>
            <div class="space-y-6 bg-white p-5 dark:bg-zinc-900">
                <flux:input
                    wire:model.live.debounce.300ms="name"
                    :label="__('Event Name')"
                    :placeholder="__('Tuesday Trivia at Joe\'s')"
                    required
                    autofocus
                />

                <flux:input
                    wire:model="slug"
                    :label="__('Join Code')"
                    :description="__('The URL slug teams will use to find your scoreboard.')"
                    :placeholder="__('tuesday-trivia-at-joes')"
                    required
                />

                <flux:input
                    wire:model="starts_at"
                    type="datetime-local"
                    :label="__('Scheduled Start')"
                    :min="now()->format('Y-m-d\TH:i')"
                    required
                />

                <div class="grid gap-6 sm:grid-cols-2">
                    <flux:input
                        wire:model="tables"
                        type="number"
                        :label="__('Number of Tables')"
                        :description="__('Pre-creates teams numbered 1 through this value.')"
                        :placeholder="__('e.g. 20')"
                        min="1"
                        max="200"
                    />

                    <flux:input
                        wire:model="rounds"
                        type="number"
                        :label="__('Number of Rounds')"
                        :description="__('Pre-creates rounds on the scoring grid.')"
                        :placeholder="__('e.g. 6')"
                        min="1"
                        max="50"
                    />
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button :href="route('organizations.events.index', $organization)" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Create Event') }}
            </flux:button>
        </div>
    </form>
</div>
```

**Step 2: Delete old event create page**

Delete: `resources/views/pages/events/⚡create.blade.php`

**Step 3: Update tests**

In `tests/Feature/Events/CreateEventTest.php`:
- Replace all `'pages::events.create'` → `'pages::organizations.events.create'`
- Replace all `route('events.teams', ...)` redirect assertions → `route('organizations.events.teams', [$organization, ...])`
- Replace all `route('events.create', ...)` → `route('organizations.events.create', ...)`

In `tests/Feature/FullEventFlowTest.php`:
- Replace `'pages::events.create'` → `'pages::organizations.events.create'`
- Update any redirect assertions to new route names

**Step 4: Run tests**

Run: `php artisan test --compact --filter=CreateEvent`
Run: `php artisan test --compact --filter=FullEventFlow`

**Step 5: Commit**

```bash
git add resources/views/pages/organizations/events/ tests/
git rm "resources/views/pages/events/⚡create.blade.php"
git commit -m "refactor: restyle event create page with sectioned card layout"
```

---

### Task 5: Create Event Edit Page

**Files:**
- Create: `resources/views/pages/organizations/events/⚡edit.blade.php`
- Delete: `resources/views/pages/events/⚡show.blade.php`
- Modify: `tests/Feature/Livewire/EventDetailsTest.php`
- Modify: `tests/Feature/Events/ShowEventTest.php`
- Modify: `tests/Feature/Events/QrCodeTest.php`

**Step 1: Create the event edit page**

Create `resources/views/pages/organizations/events/⚡edit.blade.php`:

```php
<?php

use App\Models\Event;
use App\Models\Organization;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Event Details')] class extends Component {
    public Organization $organization;

    public Event $event;

    public string $name = '';

    public string $slug = '';

    public string $starts_at = '';

    public function mount(Organization $organization, Event $event): void
    {
        $this->authorize('view', $event);

        $this->organization = $organization;
        $this->name = $event->name;
        $this->slug = $event->slug;
        $this->starts_at = $event->starts_at->format('Y-m-d\TH:i');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:events,slug,' . $this->event->id,
            ],
            'starts_at' => ['required', 'date'],
        ];
    }

    public function save(): void
    {
        $this->authorize('update', $this->event);

        $validated = $this->validate();
        $this->event->update($validated);

        Flux::toast(__('Event updated.'));
    }

    public function endEvent(): void
    {
        $this->authorize('update', $this->event);
        $this->event->update(['ended_at' => now()]);

        Flux::toast(__('Event ended.'));
    }

    public function reopenEvent(): void
    {
        $this->authorize('update', $this->event);
        $this->event->update(['ended_at' => null]);

        Flux::toast(__('Event reopened.'));
    }

    #[Computed]
    public function scoreboardUrl(): string
    {
        return url('/' . $this->event->slug);
    }

    #[Computed]
    public function qrCode(): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'outputBase64' => false,
            'scale' => 5,
            'addQuietzone' => true,
        ]);

        return (new QRCode($options))->render($this->scoreboardUrl());
    }

    #[Computed]
    public function qrCodePng(): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'scale' => 10,
            'addQuietzone' => true,
        ]);

        return (new QRCode($options))->render($this->scoreboardUrl());
    }
}; ?>

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('organizations.events.index', $organization)" wire:navigate />
        <div class="flex items-center gap-3">
            <div>
                <flux:heading size="xl">{{ $event->name }}</flux:heading>
                <flux:subheading>{{ $organization->name }}</flux:subheading>
            </div>
            @if ($event->isActive())
                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
            @else
                <flux:badge color="zinc" size="sm">{{ __('Ended') }}</flux:badge>
            @endif
        </div>
    </div>

    {{-- Event Details --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Event Details') }}</flux:heading>
            <flux:subheading>{{ __('Update your event\'s name, join code, and schedule.') }}</flux:subheading>
        </div>
        <div class="bg-white p-5 dark:bg-zinc-900">
            @can('update', $event)
                <form wire:submit="save" class="space-y-6">
                    <flux:input wire:model="name" :label="__('Event Name')" required />
                    <flux:input wire:model="slug" :label="__('Join Code')" :description="__('The URL slug teams use to find your scoreboard.')" required />
                    <flux:input wire:model="starts_at" type="datetime-local" :label="__('Scheduled Start')" required />
                    <flux:button variant="primary" type="submit">{{ __('Save Changes') }}</flux:button>
                </form>
            @else
                <div class="space-y-4">
                    <div>
                        <flux:heading size="sm">{{ __('Event Name') }}</flux:heading>
                        <flux:text>{{ $event->name }}</flux:text>
                    </div>
                    <div>
                        <flux:heading size="sm">{{ __('Join Code') }}</flux:heading>
                        <flux:text>{{ $event->slug }}</flux:text>
                    </div>
                    <div>
                        <flux:heading size="sm">{{ __('Scheduled Start') }}</flux:heading>
                        <flux:text>{{ $event->starts_at->format('M j, Y g:i A') }}</flux:text>
                    </div>
                </div>
            @endcan
        </div>
    </div>

    {{-- Status --}}
    @can('update', $event)
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Status') }}</flux:heading>
                <flux:subheading>{{ __('Control whether this event is active or ended.') }}</flux:subheading>
            </div>
            <div class="flex flex-col items-start justify-between gap-3 bg-white p-5 sm:flex-row sm:items-center dark:bg-zinc-900">
                @if ($event->isActive())
                    <div>
                        <flux:heading>{{ __('Event is Active') }}</flux:heading>
                        <flux:subheading>{{ __('End this event to stop accepting scores.') }}</flux:subheading>
                    </div>
                    <flux:button size="sm" class="shrink-0" wire:click="endEvent" wire:confirm="{{ __('End this event? It will be marked as ended.') }}">
                        {{ __('End Event') }}
                    </flux:button>
                @else
                    <div>
                        <flux:heading>{{ __('Event has Ended') }}</flux:heading>
                        <flux:subheading>{{ __('Reopen this event to resume accepting scores.') }}</flux:subheading>
                    </div>
                    <flux:button size="sm" class="shrink-0" wire:click="reopenEvent" wire:confirm="{{ __('Reopen this event? It will be marked as active.') }}">
                        {{ __('Reopen Event') }}
                    </flux:button>
                @endif
            </div>
        </div>
    @endcan

    {{-- Share Scoreboard --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Share Scoreboard') }}</flux:heading>
            <flux:subheading>{{ __('Share the live scoreboard with your audience.') }}</flux:subheading>
        </div>
        <div class="flex items-start gap-6 bg-white p-5 dark:bg-zinc-900">
            <div class="shrink-0">
                <div class="size-[150px] rounded bg-white p-2 [&_svg]:size-full">
                    {!! $this->qrCode !!}
                </div>
            </div>
            <div>
                <a href="{{ $this->scoreboardUrl }}" target="_blank" class="font-mono text-sm text-blue-600 hover:underline dark:text-blue-400">{{ $this->scoreboardUrl }}</a>
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
    </div>

    {{-- Manage --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Manage') }}</flux:heading>
            <flux:subheading>{{ __('Access teams and scoring for this event.') }}</flux:subheading>
        </div>
        <div class="flex gap-2 bg-white p-5 dark:bg-zinc-900">
            <flux:button :href="route('organizations.events.teams', [$organization, $event])" wire:navigate>
                {{ __('Manage Teams') }}
            </flux:button>
            <flux:button :href="route('organizations.events.scoring', [$organization, $event])" wire:navigate>
                {{ __('Manage Scoring') }}
            </flux:button>
        </div>
    </div>
</div>
```

**Step 2: Delete old event show page**

Delete: `resources/views/pages/events/⚡show.blade.php`

**Step 3: Update tests**

In `tests/Feature/Livewire/EventDetailsTest.php`:
- Replace `'pages::events.show'` → `'pages::organizations.events.edit'`
- Replace `['event' => $event]` → `['organization' => $event->organization, 'event' => $event]`

In `tests/Feature/Events/ShowEventTest.php`:
- Replace `route('events.show', $event)` → `route('organizations.events.edit', [$organization, $event])`

In `tests/Feature/Events/QrCodeTest.php`:
- Replace `'pages::events.show'` → `'pages::organizations.events.edit'`
- Replace `['event' => $event]` → `['organization' => $event->organization, 'event' => $event]`

In `tests/Feature/Livewire/EventLifecycleTest.php`:
- Update any route references from `events.show` to `organizations.events.edit`

**Step 4: Run tests**

Run: `php artisan test --compact --filter=EventDetails`
Run: `php artisan test --compact --filter=ShowEvent`
Run: `php artisan test --compact --filter=QrCode`
Run: `php artisan test --compact --filter=EventLifecycle`

**Step 5: Commit**

```bash
git add resources/views/pages/organizations/events/ tests/
git rm "resources/views/pages/events/⚡show.blade.php"
git commit -m "refactor: restyle event edit page with sectioned cards and quick links"
```

---

### Task 6: Restyle Teams and Scoring Pages

**Files:**
- Create: `resources/views/pages/organizations/events/⚡teams.blade.php`
- Create: `resources/views/pages/organizations/events/⚡scoring.blade.php`
- Delete: `resources/views/pages/events/⚡teams.blade.php`
- Delete: `resources/views/pages/events/⚡scoring.blade.php`
- Modify: `tests/Feature/Livewire/EventTeamsManagerTest.php`
- Modify: `tests/Feature/Organizations/ScorekeeperAccessTest.php`

**Step 1: Create restyled teams page**

Create `resources/views/pages/organizations/events/⚡teams.blade.php`:

```php
<?php

use App\Models\Event;
use App\Models\Organization;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams')] class extends Component {
    public Organization $organization;

    public Event $event;

    public function mount(Organization $organization, Event $event): void
    {
        $this->authorize('view', $event);
        $this->organization = $organization;
    }
}; ?>

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('organizations.events.edit', [$organization, $event])" wire:navigate />
        <div class="flex items-center gap-3">
            <div>
                <flux:heading size="xl">{{ $event->name }}</flux:heading>
                <flux:subheading>{{ __('Teams') }}</flux:subheading>
            </div>
            @if ($event->isActive())
                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
            @else
                <flux:badge color="zinc" size="sm">{{ __('Ended') }}</flux:badge>
            @endif
        </div>
    </div>

    {{-- Teams Manager --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Teams') }}</flux:heading>
            <flux:subheading>{{ __('Add, edit, and reorder teams for this event.') }}</flux:subheading>
        </div>
        <div class="bg-white p-5 dark:bg-zinc-900">
            <livewire:event-teams-manager :event="$event" />
        </div>
    </div>
</div>
```

**Step 2: Create restyled scoring page**

Create `resources/views/pages/organizations/events/⚡scoring.blade.php`:

```php
<?php

use App\Models\Event;
use App\Models\Organization;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Scoring Grid')] class extends Component {
    public Organization $organization;

    public Event $event;

    public function mount(Organization $organization, Event $event): void
    {
        $this->authorize('view', $event);
        $this->organization = $organization;
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('organizations.events.edit', [$organization, $event])" wire:navigate />
        <div class="flex items-center gap-3">
            <div>
                <flux:heading size="xl">{{ $event->name }}</flux:heading>
                <flux:subheading>{{ __('Scoring') }}</flux:subheading>
            </div>
            @if ($event->isActive())
                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
            @else
                <flux:badge color="zinc" size="sm">{{ __('Ended') }}</flux:badge>
            @endif
        </div>
    </div>

    {{-- Scoring Grid (no max-w-3xl — grid needs full width) --}}
    <livewire:event-scoring-grid :event="$event" />
</div>
```

**Step 3: Delete old pages**

Delete: `resources/views/pages/events/⚡teams.blade.php`
Delete: `resources/views/pages/events/⚡scoring.blade.php`

**Step 4: Update tests**

In `tests/Feature/Livewire/EventTeamsManagerTest.php`:
- Update any HTTP route assertions from `events.teams` → `organizations.events.teams`

In `tests/Feature/Organizations/ScorekeeperAccessTest.php`:
- Update all old route names to new ones

**Step 5: Run tests**

Run: `php artisan test --compact --filter=EventTeamsManager`
Run: `php artisan test --compact --filter=ScorekeeperAccess`

**Step 6: Commit**

```bash
git add resources/views/pages/organizations/events/ tests/
git rm "resources/views/pages/events/⚡teams.blade.php" "resources/views/pages/events/⚡scoring.blade.php"
git commit -m "refactor: restyle teams and scoring pages with sectioned card layout"
```

---

### Task 7: Update All Remaining Route References and Clean Up

**Files:**
- Modify: Various test files and views with old route references
- Delete: `resources/views/pages/events/` directory (should be empty)

**Step 1: Search for any remaining old route references**

Search for: `route('events.show'`, `route('events.teams'`, `route('events.scoring'`, `route('events.create'`

Update each occurrence:
- `route('events.show', $event)` → `route('organizations.events.edit', [$event->organization, $event])` or `[$organization, $event]`
- `route('events.teams', $event)` → `route('organizations.events.teams', [$event->organization, $event])`
- `route('events.scoring', $event)` → `route('organizations.events.scoring', [$event->organization, $event])`
- `route('events.create', $org)` → `route('organizations.events.create', $org)`

Key files to check:
- `resources/views/pages/admin/events/⚡edit.blade.php` — Quick Links (already updated in Task 1)
- `resources/views/pages/admin/events/⚡index.blade.php` — Manage button (already updated in Task 1)
- Any remaining test files not yet updated

**Step 2: Remove empty events directory**

If `resources/views/pages/events/` is now empty, delete it.

**Step 3: Run full test suite**

Run: `php artisan test --compact`

Fix any remaining failures from stale route references.

**Step 4: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 5: Run PHPStan**

Run: `vendor/bin/phpstan analyse --memory-limit=512M`

**Step 6: Commit**

```bash
git add -A
git commit -m "chore: update all route references and clean up old event pages"
```

---

### Task 8: Final Review and Verification

**Step 1: Verify all routes resolve**

Run: `php artisan route:list --path=organizations`

Verify these routes exist:
- `organizations.show`
- `organizations.settings`
- `organizations.events.index`
- `organizations.events.create`
- `organizations.events.edit`
- `organizations.events.teams`
- `organizations.events.scoring`

**Step 2: Verify old routes are removed**

Run: `php artisan route:list --path=events`

Verify these routes do NOT exist:
- `events.show`
- `events.teams`
- `events.scoring`

The only `events.*` routes should be admin routes (`admin.events.*`).

**Step 3: Run full test suite**

Run: `php artisan test --compact`

All tests must pass.

**Step 4: Manual smoke test**

Ask the user to visit the following URLs and verify the UI:
- Organization dashboard hub
- Events index (active + past tabs)
- Event create form
- Event edit page
- Teams manager
- Scoring grid
- Organization settings (should be unchanged)

**Step 5: Final commit if any fixes**

```bash
git add -A
git commit -m "fix: address review feedback from dashboard restructure"
```
