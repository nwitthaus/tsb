# Trivia Scoreboard Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a live trivia night scoring web app where hosts create events, manage teams/rounds, enter scores via a spreadsheet grid, and teams view a public scoreboard on their phones.

**Architecture:** Laravel 12 + Livewire v4 + Flux UI Pro. Host scoring grid is a Livewire component with Alpine.js keyboard navigation. Public scoreboard uses `wire:poll.5s` for near real-time updates. All data persisted in MySQL. Authorization via EventPolicy.

**Tech Stack:** PHP 8.4, Laravel 12, Livewire v4, Flux UI Pro, Tailwind CSS v4, Alpine.js, Pest v4, MySQL 8.0

**Design doc:** `docs/plans/2026-02-26-trivia-scoreboard-design.md`

---

## Phase 1: Data Foundation

### Task 1: Create Event Model, Migration, and Factory

**Files:**
- Create: `app/Models/Event.php`
- Create: `database/migrations/xxxx_xx_xx_create_events_table.php`
- Create: `database/factories/EventFactory.php`
- Modify: `app/Models/User.php` (add events relationship)
- Test: `tests/Feature/Models/EventTest.php`

**Step 1: Generate model with migration and factory**

Run: `php artisan make:model Event -mf --no-interaction`

**Step 2: Write the migration**

Edit the generated migration:

```php
Schema::create('events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('slug', 100)->unique();
    $table->timestamp('ended_at')->nullable();
    $table->timestamps();
});
```

**Step 3: Write the model**

Edit `app/Models/Event.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class)->orderBy('sort_order');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class)->orderBy('sort_order');
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return Str::limit($slug, 100, '');
    }
}
```

**Step 4: Add events relationship to User model**

Add to `app/Models/User.php` before the `initials()` method:

```php
public function events(): HasMany
{
    return $this->hasMany(Event::class);
}
```

Add the import: `use Illuminate\Database\Eloquent\Relations\HasMany;`

**Step 5: Write the factory**

Edit `database/factories/EventFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'ended_at' => null,
        ];
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => now(),
        ]);
    }
}
```

**Step 6: Write the test**

Create `tests/Feature/Models/EventTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\User;

test('event belongs to a user', function () {
    $event = Event::factory()->create();

    expect($event->user)->toBeInstanceOf(User::class);
});

test('user has many events', function () {
    $user = User::factory()->create();
    Event::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->events)->toHaveCount(3);
});

test('event knows if it is active', function () {
    $active = Event::factory()->create();
    $ended = Event::factory()->ended()->create();

    expect($active->isActive())->toBeTrue()
        ->and($ended->isActive())->toBeFalse();
});

test('slug is generated from event name', function () {
    $slug = Event::generateSlug('Tuesday Trivia at Joes');

    expect($slug)->toBe('tuesday-trivia-at-joes');
});

test('slug is unique with auto-increment suffix', function () {
    Event::factory()->create(['slug' => 'trivia-night']);

    $slug = Event::generateSlug('Trivia Night');

    expect($slug)->toBe('trivia-night-1');
});
```

**Step 7: Run migration and tests**

Run: `php artisan migrate --no-interaction`
Run: `php artisan test --compact tests/Feature/Models/EventTest.php`
Expected: All 5 tests pass.

**Step 8: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`
Run: `php artisan ide-helper:models --write-mixin --no-interaction`

Commit: `feat: add Event model with migration, factory, and relationships`

---

### Task 2: Create Team Model, Migration, and Factory

**Files:**
- Create: `app/Models/Team.php`
- Create: `database/migrations/xxxx_xx_xx_create_teams_table.php`
- Create: `database/factories/TeamFactory.php`
- Test: `tests/Feature/Models/TeamTest.php`

**Step 1: Generate model with migration and factory**

Run: `php artisan make:model Team -mf --no-interaction`

**Step 2: Write the migration**

```php
Schema::create('teams', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->string('name')->nullable();
    $table->unsignedInteger('table_number')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->softDeletes();
    $table->timestamps();
});
```

**Step 3: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'table_number',
        'sort_order',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    /**
     * Get the display name for this team (name, table number, or both).
     */
    public function displayName(): string
    {
        if ($this->name && $this->table_number) {
            return $this->name;
        }

        return $this->name ?? 'Table '.$this->table_number;
    }
}
```

**Step 4: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->unique()->words(2, true),
            'table_number' => fake()->numberBetween(1, 30),
            'sort_order' => 0,
        ];
    }

    public function nameOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'table_number' => null,
        ]);
    }

    public function tableOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
        ]);
    }
}
```

**Step 5: Write the test**

Create `tests/Feature/Models/TeamTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\Team;

test('team belongs to an event', function () {
    $team = Team::factory()->create();

    expect($team->event)->toBeInstanceOf(Event::class);
});

test('team can be soft deleted and restored', function () {
    $team = Team::factory()->create();

    $team->delete();
    expect($team->trashed())->toBeTrue();
    expect(Team::count())->toBe(0);

    $team->restore();
    expect($team->trashed())->toBeFalse();
    expect(Team::count())->toBe(1);
});

test('display name returns name when both name and table number exist', function () {
    $team = Team::factory()->create(['name' => 'Quizly Bears', 'table_number' => 3]);

    expect($team->displayName())->toBe('Quizly Bears');
});

test('display name returns name when only name exists', function () {
    $team = Team::factory()->nameOnly()->create(['name' => 'Brain Stormers']);

    expect($team->displayName())->toBe('Brain Stormers');
});

test('display name returns table number when only table number exists', function () {
    $team = Team::factory()->tableOnly()->create(['table_number' => 12]);

    expect($team->displayName())->toBe('Table 12');
});
```

**Step 6: Run migration and tests**

Run: `php artisan migrate --no-interaction`
Run: `php artisan test --compact tests/Feature/Models/TeamTest.php`
Expected: All 5 tests pass.

**Step 7: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`
Run: `php artisan ide-helper:models --write-mixin --no-interaction`

Commit: `feat: add Team model with soft deletes, migration, and factory`

---

### Task 3: Create Round Model, Migration, and Factory

**Files:**
- Create: `app/Models/Round.php`
- Create: `database/migrations/xxxx_xx_xx_create_rounds_table.php`
- Create: `database/factories/RoundFactory.php`
- Test: `tests/Feature/Models/RoundTest.php`

**Step 1: Generate model with migration and factory**

Run: `php artisan make:model Round -mf --no-interaction`

**Step 2: Write the migration**

```php
Schema::create('rounds', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->unsignedInteger('sort_order');
    $table->timestamps();
});
```

**Step 3: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'sort_order',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
```

**Step 4: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Round>
 */
class RoundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'sort_order' => 1,
        ];
    }
}
```

**Step 5: Write the test**

Create `tests/Feature/Models/RoundTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\Round;

test('round belongs to an event', function () {
    $round = Round::factory()->create();

    expect($round->event)->toBeInstanceOf(Event::class);
});

test('event rounds are ordered by sort_order', function () {
    $event = Event::factory()->create();
    Round::factory()->create(['event_id' => $event->id, 'sort_order' => 3]);
    Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
    Round::factory()->create(['event_id' => $event->id, 'sort_order' => 2]);

    expect($event->rounds->pluck('sort_order')->all())->toBe([1, 2, 3]);
});
```

**Step 6: Run migration and tests**

Run: `php artisan migrate --no-interaction`
Run: `php artisan test --compact tests/Feature/Models/RoundTest.php`
Expected: All 2 tests pass.

**Step 7: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`
Run: `php artisan ide-helper:models --write-mixin --no-interaction`

Commit: `feat: add Round model with migration and factory`

---

### Task 4: Create Score Model, Migration, and Factory

**Files:**
- Create: `app/Models/Score.php`
- Create: `database/migrations/xxxx_xx_xx_create_scores_table.php`
- Create: `database/factories/ScoreFactory.php`
- Test: `tests/Feature/Models/ScoreTest.php`

**Step 1: Generate model with migration and factory**

Run: `php artisan make:model Score -mf --no-interaction`

**Step 2: Write the migration**

```php
Schema::create('scores', function (Blueprint $table) {
    $table->id();
    $table->foreignId('team_id')->constrained()->cascadeOnDelete();
    $table->foreignId('round_id')->constrained()->cascadeOnDelete();
    $table->decimal('value', 5, 1);
    $table->timestamps();

    $table->unique(['team_id', 'round_id']);
});
```

**Step 3: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'round_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:1',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }
}
```

**Step 4: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Models\Round;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Score>
 */
class ScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'round_id' => Round::factory(),
            'value' => fake()->randomFloat(1, 0, 10),
        ];
    }
}
```

**Step 5: Write the test**

Create `tests/Feature/Models/ScoreTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\Round;
use App\Models\Score;
use App\Models\Team;

test('score belongs to a team and round', function () {
    $score = Score::factory()->create();

    expect($score->team)->toBeInstanceOf(Team::class)
        ->and($score->round)->toBeInstanceOf(Round::class);
});

test('team_id and round_id combination is unique', function () {
    $event = Event::factory()->create();
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Score::factory()->create(['team_id' => $team->id, 'round_id' => $round->id, 'value' => 5]);

    expect(fn () => Score::factory()->create([
        'team_id' => $team->id,
        'round_id' => $round->id,
        'value' => 8,
    ]))->toThrow(Exception::class);
});

test('deleting a team cascades to scores', function () {
    $score = Score::factory()->create();
    $teamId = $score->team_id;

    $score->team->forceDelete();

    expect(Score::where('team_id', $teamId)->count())->toBe(0);
});

test('deleting a round cascades to scores', function () {
    $score = Score::factory()->create();
    $roundId = $score->round_id;

    $score->round->delete();

    expect(Score::where('round_id', $roundId)->count())->toBe(0);
});
```

**Step 6: Run migration and tests**

Run: `php artisan migrate --no-interaction`
Run: `php artisan test --compact tests/Feature/Models/ScoreTest.php`
Expected: All 4 tests pass.

**Step 7: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`
Run: `php artisan ide-helper:models --write-mixin --no-interaction`

Commit: `feat: add Score model with unique constraint, migration, and factory`

---

### Task 5: Create EventPolicy

**Files:**
- Create: `app/Policies/EventPolicy.php`
- Test: `tests/Feature/Policies/EventPolicyTest.php`

**Step 1: Generate policy**

Run: `php artisan make:policy EventPolicy --model=Event --no-interaction`

**Step 2: Write the policy**

Edit `app/Policies/EventPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function view(User $user, Event $event): bool
    {
        return $user->id === $event->user_id;
    }

    public function create(User $user): bool
    {
        return ! $user->events()->whereNull('ended_at')->exists();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->id === $event->user_id;
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->id === $event->user_id;
    }
}
```

**Step 3: Write the test**

Create `tests/Feature/Policies/EventPolicyTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\User;

test('owner can view their event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    expect($user->can('view', $event))->toBeTrue();
});

test('non-owner cannot view event', function () {
    $event = Event::factory()->create();
    $otherUser = User::factory()->create();

    expect($otherUser->can('view', $event))->toBeFalse();
});

test('user can create event when they have no active events', function () {
    $user = User::factory()->create();

    expect($user->can('create', Event::class))->toBeTrue();
});

test('user cannot create event when they have an active event', function () {
    $user = User::factory()->create();
    Event::factory()->create(['user_id' => $user->id, 'ended_at' => null]);

    expect($user->can('create', Event::class))->toBeFalse();
});

test('user can create event when all their events are ended', function () {
    $user = User::factory()->create();
    Event::factory()->ended()->create(['user_id' => $user->id]);

    expect($user->can('create', Event::class))->toBeTrue();
});

test('owner can update their event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    expect($user->can('update', $event))->toBeTrue();
});

test('non-owner cannot update event', function () {
    $event = Event::factory()->create();
    $otherUser = User::factory()->create();

    expect($otherUser->can('update', $event))->toBeFalse();
});
```

**Step 4: Run tests**

Run: `php artisan test --compact tests/Feature/Policies/EventPolicyTest.php`
Expected: All 7 tests pass.

**Step 5: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: add EventPolicy with ownership and one-active-event enforcement`

---

### Task 6: Update Database Seeder

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`

**Step 1: Update seeder to create sample event data**

Add event, teams, rounds, and scores for the test user:

```php
<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Round;
use App\Models\Score;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'name' => 'Tuesday Trivia at Joe\'s',
            'slug' => 'tuesday-trivia',
        ]);

        $teams = collect([
            ['name' => 'Quizly Bears', 'table_number' => 3, 'sort_order' => 1],
            ['name' => 'Brain Stormers', 'table_number' => 7, 'sort_order' => 2],
            ['name' => null, 'table_number' => 12, 'sort_order' => 3],
        ])->map(fn ($data) => Team::factory()->create([...$data, 'event_id' => $event->id]));

        $rounds = collect([1, 2, 3])->map(
            fn ($order) => Round::factory()->create(['event_id' => $event->id, 'sort_order' => $order])
        );

        $sampleScores = [
            [7.5, 8.0, null],
            [6.0, 9.0, null],
            [5.0, null, null],
        ];

        $teams->each(function ($team, $teamIndex) use ($rounds, $sampleScores) {
            $rounds->each(function ($round, $roundIndex) use ($team, $teamIndex, $sampleScores) {
                $value = $sampleScores[$teamIndex][$roundIndex];
                if ($value !== null) {
                    Score::factory()->create([
                        'team_id' => $team->id,
                        'round_id' => $round->id,
                        'value' => $value,
                    ]);
                }
            });
        });
    }
}
```

**Step 2: Run seeder to verify**

Run: `php artisan migrate:fresh --seed --no-interaction`
Expected: No errors.

**Step 3: Run all model tests to verify nothing broke**

Run: `php artisan test --compact tests/Feature/Models/ tests/Feature/Policies/`
Expected: All tests pass.

**Step 4: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: update seeder with sample event, teams, rounds, and scores`

---

## Phase 2: Event CRUD & Dashboard

### Task 7: Create Event Page

**Files:**
- Create: `resources/views/pages/events/create.blade.php` (Livewire SFC)
- Modify: `routes/web.php` (add event routes)
- Test: `tests/Feature/Events/CreateEventTest.php`

**Step 1: Add event routes**

Edit `routes/web.php` to add event routes inside the auth middleware group:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('events/create', 'pages::events.create')->name('events.create');
    Route::livewire('events/{event}', 'pages::events.show')->name('events.show');
});
```

**Step 2: Create the Livewire SFC**

Create `resources/views/pages/events/create.blade.php`:

This is a full-page Livewire component (SFC) with:
- A form with event name input and slug input
- Slug auto-generates from name via `updated('name')` hook
- Validates name (required, max 255) and slug (required, max 100, unique, regex for alphanumeric + hyphens)
- On submit: creates the event and redirects to `events.show`
- Checks `authorize('create', Event::class)` before saving
- Uses `<x-layouts::app>` layout and Flux UI form components

**Step 3: Write the test**

Create `tests/Feature/Events/CreateEventTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\User;

test('guests cannot access create event page', function () {
    $this->get(route('events.create'))->assertRedirect(route('login'));
});

test('authenticated user can view create event page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('events.create'))
        ->assertOk();
});

test('user can create an event', function () {
    $user = User::factory()->create();

    Livewire\Livewire::actingAs($user)
        ->test('pages::events.create')
        ->set('name', 'Tuesday Trivia')
        ->set('slug', 'tuesday-trivia')
        ->call('save')
        ->assertRedirect(route('events.show', Event::first()));

    expect(Event::count())->toBe(1)
        ->and(Event::first()->name)->toBe('Tuesday Trivia')
        ->and(Event::first()->slug)->toBe('tuesday-trivia')
        ->and(Event::first()->user_id)->toBe($user->id);
});

test('slug auto-generates from name', function () {
    Livewire\Livewire::actingAs(User::factory()->create())
        ->test('pages::events.create')
        ->set('name', 'Tuesday Trivia at Joe\'s')
        ->assertSet('slug', 'tuesday-trivia-at-joes');
});

test('event name is required', function () {
    Livewire\Livewire::actingAs(User::factory()->create())
        ->test('pages::events.create')
        ->set('name', '')
        ->set('slug', 'some-slug')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('slug must be unique', function () {
    Event::factory()->create(['slug' => 'taken-slug']);

    Livewire\Livewire::actingAs(User::factory()->create())
        ->test('pages::events.create')
        ->set('name', 'My Event')
        ->set('slug', 'taken-slug')
        ->call('save')
        ->assertHasErrors(['slug' => 'unique']);
});

test('user with active event cannot create another', function () {
    $user = User::factory()->create();
    Event::factory()->create(['user_id' => $user->id, 'ended_at' => null]);

    $this->actingAs($user)
        ->get(route('events.create'))
        ->assertForbidden();
});
```

**Step 4: Run tests, fix until green**

Run: `php artisan test --compact tests/Feature/Events/CreateEventTest.php`

**Step 5: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: add create event page with slug auto-generation and validation`

---

### Task 8: Update Dashboard

**Files:**
- Modify: `resources/views/dashboard.blade.php` (replace placeholder with event list)
- Test: `tests/Feature/DashboardTest.php` (add new tests)

**Step 1: Rewrite dashboard view**

Replace the placeholder content in `resources/views/dashboard.blade.php` with:
- If user has an active event: show it prominently with a "Manage" link to `events.show`
- List of past (ended) events: name, date ended, team count, link to view
- "Create Event" button (only if no active event)

Use Flux UI components (`<flux:card>`, `<flux:button>`, `<flux:table>`, etc.) and pass data from a Livewire component or convert dashboard to a Livewire SFC.

**Step 2: Update tests**

Add to `tests/Feature/DashboardTest.php`:

```php
test('dashboard shows active event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id, 'name' => 'My Trivia']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('My Trivia');
});

test('dashboard shows create event button when no active event', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Create Event');
});

test('dashboard shows past events', function () {
    $user = User::factory()->create();
    Event::factory()->ended()->create(['user_id' => $user->id, 'name' => 'Old Trivia']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Old Trivia');
});
```

**Step 3: Run tests**

Run: `php artisan test --compact tests/Feature/DashboardTest.php`

**Step 4: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: update dashboard with active event display and past events archive`

---

## Phase 3: Event Management & Scoring Grid

### Task 9: Event Show Page — Scaffold

**Files:**
- Create: `app/Livewire/EventScoringGrid.php` (class-based component — this is too complex for SFC)
- Create: `resources/views/livewire/event-scoring-grid.blade.php`
- Create: `resources/views/pages/events/show.blade.php` (page wrapper)
- Test: `tests/Feature/Events/ShowEventTest.php`

**Step 1: Create the Livewire component class**

Run: `php artisan make:livewire EventScoringGrid --no-interaction`

This component receives an Event model, loads teams/rounds/scores, and provides:
- Properties: `$event`, `$teams`, `$rounds`, `$scores` (keyed by `{teamId}-{roundId}`)
- Methods: `saveScore($teamId, $roundId, $value)`, `addTeam($name, $tableNumber)`, `removeTeam($teamId)`, `restoreTeam($teamId)`, `addRound()`, `removeLastRound()`, `reorderTeams($order)`, `endEvent()`, `reopenEvent()`

**Step 2: Create the page wrapper**

Create `resources/views/pages/events/show.blade.php` that uses `<x-layouts::app>` and renders `<livewire:event-scoring-grid :event="$event" />`.

Since this is a route with a model parameter, use a class-based full-page component or a controller to resolve the Event model and authorize access.

**Step 3: Write initial tests**

Create `tests/Feature/Events/ShowEventTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\User;

test('guests cannot access event management', function () {
    $event = Event::factory()->create();

    $this->get(route('events.show', $event))->assertRedirect(route('login'));
});

test('owner can access their event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee($event->name);
});

test('non-owner cannot access event', function () {
    $event = Event::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->get(route('events.show', $event))
        ->assertForbidden();
});
```

**Step 4: Run tests**

Run: `php artisan test --compact tests/Feature/Events/ShowEventTest.php`

**Step 5: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: scaffold event show page with authorization`

---

### Task 10: Scoring Grid — Team Management

**Files:**
- Modify: `app/Livewire/EventScoringGrid.php`
- Modify: `resources/views/livewire/event-scoring-grid.blade.php`
- Test: `tests/Feature/Livewire/EventScoringGridTeamTest.php`

**Step 1: Write tests for team management**

Create `tests/Feature/Livewire/EventScoringGridTeamTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\Team;
use App\Models\User;

test('host can add a team with name and table number', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('addTeam', 'Quizly Bears', 3)
        ->assertHasNoErrors();

    expect($event->teams()->count())->toBe(1)
        ->and($event->teams()->first()->name)->toBe('Quizly Bears')
        ->and($event->teams()->first()->table_number)->toBe(3);
});

test('host can add a team with name only', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('addTeam', 'Brain Stormers', null)
        ->assertHasNoErrors();

    expect($event->teams()->first()->table_number)->toBeNull();
});

test('host can add a team with table number only', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('addTeam', null, 12)
        ->assertHasNoErrors();

    expect($event->teams()->first()->name)->toBeNull();
});

test('adding a team requires at least name or table number', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('addTeam', null, null)
        ->assertHasErrors();
});

test('host can soft delete a team', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('removeTeam', $team->id);

    expect($team->fresh()->trashed())->toBeTrue();
});

test('host can restore a soft deleted team', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $team->delete();

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('restoreTeam', $team->id);

    expect($team->fresh()->trashed())->toBeFalse();
});
```

**Step 2: Implement team management methods in the component**

Add `addTeam()`, `removeTeam()`, `restoreTeam()` methods with validation.

**Step 3: Run tests**

Run: `php artisan test --compact tests/Feature/Livewire/EventScoringGridTeamTest.php`

**Step 4: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: add team management to scoring grid (add, remove, restore)`

---

### Task 11: Scoring Grid — Round Management

**Files:**
- Modify: `app/Livewire/EventScoringGrid.php`
- Test: `tests/Feature/Livewire/EventScoringGridRoundTest.php`

**Step 1: Write tests**

Create `tests/Feature/Livewire/EventScoringGridRoundTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\Round;
use App\Models\Score;
use App\Models\Team;
use App\Models\User;

test('host can add a round', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('addRound');

    expect($event->rounds()->count())->toBe(1)
        ->and($event->rounds()->first()->sort_order)->toBe(1);
});

test('adding rounds auto-increments sort order', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    $component = Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event]);

    $component->call('addRound');
    $component->call('addRound');
    $component->call('addRound');

    expect($event->rounds()->pluck('sort_order')->all())->toBe([1, 2, 3]);
});

test('host can remove the last round', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
    $lastRound = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 2]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('removeLastRound');

    expect($event->rounds()->count())->toBe(1)
        ->and(Round::find($lastRound->id))->toBeNull();
});

test('removing last round cascades to its scores', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
    Score::factory()->create(['team_id' => $team->id, 'round_id' => $round->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('removeLastRound');

    expect(Score::count())->toBe(0);
});

test('cannot remove round when there are no rounds', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('removeLastRound')
        ->assertHasNoErrors();

    expect($event->rounds()->count())->toBe(0);
});
```

**Step 2: Implement round management methods**

Add `addRound()` and `removeLastRound()` to the component.

**Step 3: Run tests**

Run: `php artisan test --compact tests/Feature/Livewire/EventScoringGridRoundTest.php`

**Step 4: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: add round management to scoring grid (add, remove last)`

---

### Task 12: Scoring Grid — Score Entry

**Files:**
- Modify: `app/Livewire/EventScoringGrid.php`
- Test: `tests/Feature/Livewire/EventScoringGridScoreTest.php`

**Step 1: Write tests**

Create `tests/Feature/Livewire/EventScoringGridScoreTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\Round;
use App\Models\Score;
use App\Models\Team;
use App\Models\User;

test('host can save a score', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $round->id, '7.5');

    expect(Score::first()->value)->toBe('7.5');
});

test('host can update an existing score', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
    Score::factory()->create(['team_id' => $team->id, 'round_id' => $round->id, 'value' => 5]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $round->id, '8');

    expect(Score::first()->value)->toBe('8.0')
        ->and(Score::count())->toBe(1);
});

test('clearing a score deletes it', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
    Score::factory()->create(['team_id' => $team->id, 'round_id' => $round->id, 'value' => 5]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $round->id, '');

    expect(Score::count())->toBe(0);
});

test('score must be non-negative', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $round->id, '-1')
        ->assertHasErrors();
});

test('score cannot exceed 999.9', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $round->id, '1000')
        ->assertHasErrors();
});

test('score must be numeric', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $team = Team::factory()->create(['event_id' => $event->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $team->id, $round->id, 'abc')
        ->assertHasErrors();
});

test('cannot save score for team not in this event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $otherEvent = Event::factory()->create();
    $foreignTeam = Team::factory()->create(['event_id' => $otherEvent->id]);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('saveScore', $foreignTeam->id, $round->id, '5')
        ->assertHasErrors();
});
```

**Step 2: Implement saveScore method**

The `saveScore($teamId, $roundId, $value)` method should:
- Validate the team belongs to this event
- Validate the round belongs to this event
- Validate score value (numeric, >= 0, <= 999.9)
- If value is empty/null: delete existing score
- Otherwise: `updateOrCreate` the score

**Step 3: Run tests**

Run: `php artisan test --compact tests/Feature/Livewire/EventScoringGridScoreTest.php`

**Step 4: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: add score entry with auto-save, validation, and delete-on-clear`

---

### Task 13: Scoring Grid — Blade Template with Alpine Keyboard Navigation

**Files:**
- Modify: `resources/views/livewire/event-scoring-grid.blade.php`

**Step 1: Build the grid template**

The template should render:
- The spreadsheet grid table with teams as rows, rounds as columns, total as last column
- Each score cell is an `<input>` with Alpine.js handling:
  - `@keydown.enter` and `@keydown.tab` move focus to the next row in the same column
  - `@blur` calls `$wire.saveScore(teamId, roundId, $el.value)` (Livewire action)
  - `x-data` on each cell or the grid for focus management
- Saved cells (score exists) get a CSS class for the subtle background tint
- Empty cells remain default background
- Total column: computed sum, read-only
- Controls above/below the grid:
  - "Add Round" button
  - "Remove Last Round" button (with Flux confirmation modal)
  - "Add Team" button (opens a Flux modal with name + table number inputs)
  - "End Event" button
- Join code and QR code display area
- Team reordering controls

Use `@skill(tailwindcss-development)` and `@skill(fluxui-development)` for the UI.

**Step 2: Verify keyboard navigation works manually**

This is a UI-driven step. After implementation, the host should be able to:
1. Click into R1/Team1 cell
2. Type a number, press Enter
3. Cursor moves to R1/Team2 cell
4. Type a number, press Tab
5. Cursor moves to R1/Team3 cell

**Step 3: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: build scoring grid UI with Alpine keyboard navigation and saved-cell styling`

---

### Task 14: Event Lifecycle (End / Reopen)

**Files:**
- Modify: `app/Livewire/EventScoringGrid.php`
- Modify: `resources/views/livewire/event-scoring-grid.blade.php`
- Test: `tests/Feature/Livewire/EventLifecycleTest.php`

**Step 1: Write tests**

Create `tests/Feature/Livewire/EventLifecycleTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\User;

test('host can end an active event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('endEvent');

    expect($event->fresh()->ended_at)->not->toBeNull();
});

test('host can reopen an ended event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->ended()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('reopenEvent');

    expect($event->fresh()->ended_at)->toBeNull();
});

test('cannot reopen event if user already has an active event', function () {
    $user = User::factory()->create();
    Event::factory()->create(['user_id' => $user->id, 'ended_at' => null]);
    $endedEvent = Event::factory()->ended()->create(['user_id' => $user->id]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $endedEvent])
        ->call('reopenEvent')
        ->assertHasErrors();

    expect($endedEvent->fresh()->ended_at)->not->toBeNull();
});

test('ended event shows read-only grid', function () {
    $user = User::factory()->create();
    $event = Event::factory()->ended()->create(['user_id' => $user->id, 'name' => 'Past Trivia']);

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Past Trivia')
        ->assertSee('Reopen');
});
```

**Step 2: Implement endEvent and reopenEvent**

- `endEvent()`: sets `ended_at` to `now()`
- `reopenEvent()`: checks no other active event exists for this user, then clears `ended_at`
- Template conditionally renders inputs vs read-only values based on `$event->isActive()`

**Step 3: Run tests**

Run: `php artisan test --compact tests/Feature/Livewire/EventLifecycleTest.php`

**Step 4: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: add event end/reopen lifecycle with read-only mode for ended events`

---

### Task 15: Team Reordering

**Files:**
- Modify: `app/Livewire/EventScoringGrid.php`
- Test: `tests/Feature/Livewire/EventScoringGridTeamTest.php` (add reorder tests)

**Step 1: Write tests**

Add to the existing team test file:

```php
test('host can reorder teams alphabetically', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    Team::factory()->create(['event_id' => $event->id, 'name' => 'Zebras', 'sort_order' => 1]);
    Team::factory()->create(['event_id' => $event->id, 'name' => 'Alphas', 'sort_order' => 2]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('reorderTeams', 'alphabetical');

    $names = $event->teams()->pluck('name')->all();
    expect($names)->toBe(['Alphas', 'Zebras']);
});

test('host can reorder teams by table number', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    Team::factory()->create(['event_id' => $event->id, 'table_number' => 10, 'sort_order' => 1]);
    Team::factory()->create(['event_id' => $event->id, 'table_number' => 2, 'sort_order' => 2]);

    Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event])
        ->call('reorderTeams', 'table_number');

    $tables = $event->teams()->pluck('table_number')->all();
    expect($tables)->toBe([2, 10]);
});
```

**Step 2: Implement reorderTeams**

The `reorderTeams($order)` method updates `sort_order` based on the chosen order: `'alphabetical'`, `'table_number'`, or `'manual'` (drag-and-drop via a separate method accepting an ordered array of IDs).

**Step 3: Run tests**

Run: `php artisan test --compact tests/Feature/Livewire/EventScoringGridTeamTest.php`

**Step 4: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: add team reordering (alphabetical, by table number)`

---

## Phase 4: Public Scoreboard

### Task 16: Public Scoreboard Component

**Files:**
- Create: `resources/views/pages/scoreboard.blade.php` (Livewire SFC — no auth layout)
- Modify: `routes/web.php` (add public route)
- Test: `tests/Feature/Scoreboard/PublicScoreboardTest.php`

**Step 1: Add the public route**

Add to `routes/web.php` **after** all other routes (since `/{slug}` is a catch-all pattern):

```php
Route::livewire('{slug}', 'pages::scoreboard')->name('scoreboard');
```

**Step 2: Create the Livewire SFC**

Create `resources/views/pages/scoreboard.blade.php`:

This component:
- Receives the slug, loads the Event with teams, rounds, and scores
- Returns 404 if slug not found
- Uses a minimal layout (no sidebar/app chrome — standalone public page)
- Renders the score grid: Rank | Team Name | Table # | R1 | R2 | ... | Total
- Sorted by total score descending, ties share rank
- `wire:poll.5s` on the grid container for auto-refresh
- Adaptive columns: team name column only if any team has a name, table column only if any team has a table number
- When event is ended: "Final Scores" header, no polling
- Mobile-responsive: horizontal scroll on round columns, pinned team/total columns
- Displays join code at the bottom

**Step 3: Write tests**

Create `tests/Feature/Scoreboard/PublicScoreboardTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\Round;
use App\Models\Score;
use App\Models\Team;

test('public scoreboard is accessible without authentication', function () {
    $event = Event::factory()->create(['slug' => 'test-event']);

    $this->get('/test-event')->assertOk();
});

test('invalid slug returns 404', function () {
    $this->get('/nonexistent-slug')->assertNotFound();
});

test('scoreboard displays event name', function () {
    $event = Event::factory()->create(['slug' => 'my-quiz', 'name' => 'My Quiz Night']);

    $this->get('/my-quiz')->assertSee('My Quiz Night');
});

test('scoreboard shows teams ranked by total score', function () {
    $event = Event::factory()->create(['slug' => 'ranked-quiz']);
    $team1 = Team::factory()->create(['event_id' => $event->id, 'name' => 'Low Scorers']);
    $team2 = Team::factory()->create(['event_id' => $event->id, 'name' => 'High Scorers']);
    $round = Round::factory()->create(['event_id' => $event->id, 'sort_order' => 1]);
    Score::factory()->create(['team_id' => $team1->id, 'round_id' => $round->id, 'value' => 3]);
    Score::factory()->create(['team_id' => $team2->id, 'round_id' => $round->id, 'value' => 8]);

    $this->get('/ranked-quiz')
        ->assertSeeInOrder(['High Scorers', 'Low Scorers']);
});

test('scoreboard shows final scores banner when event is ended', function () {
    $event = Event::factory()->ended()->create(['slug' => 'done-quiz']);

    $this->get('/done-quiz')->assertSee('Final Scores');
});

test('scoreboard hides table column when no teams have table numbers', function () {
    $event = Event::factory()->create(['slug' => 'no-tables']);
    Team::factory()->nameOnly()->create(['event_id' => $event->id]);

    $this->get('/no-tables')->assertDontSee('Table');
});
```

**Step 4: Run tests**

Run: `php artisan test --compact tests/Feature/Scoreboard/PublicScoreboardTest.php`

**Step 5: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: add public scoreboard with polling, ranking, and adaptive columns`

---

## Phase 5: Landing Page & Event Access

### Task 17: Update Landing Page with Join Code Input

**Files:**
- Modify: `resources/views/welcome.blade.php`
- Test: `tests/Feature/LandingPageTest.php`

**Step 1: Rewrite the welcome page**

Replace the default Laravel welcome page with a branded landing page for Trivia Scoreboard:
- App name/logo prominently displayed
- Join code input field with "View Scoreboard" button
- On submit: redirects to `/{slug}`. If slug doesn't exist, shows a friendly error.
- Brief tagline explaining what the app does
- Login/Register links for hosts

Use `@skill(frontend-design)` and `@skill(tailwindcss-development)` for a polished design.

**Step 2: Write tests**

Create `tests/Feature/LandingPageTest.php`:

```php
<?php

use App\Models\Event;

test('landing page is accessible', function () {
    $this->get('/')->assertOk();
});

test('join code redirects to scoreboard', function () {
    Event::factory()->create(['slug' => 'quiz42']);

    $this->post('/', ['code' => 'quiz42'])->assertRedirect('/quiz42');
});

test('invalid join code shows error', function () {
    $this->post('/', ['code' => 'nonexistent'])
        ->assertSessionHasErrors('code');
});
```

**Step 3: Run tests**

Run: `php artisan test --compact tests/Feature/LandingPageTest.php`

**Step 4: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: update landing page with join code input and branded design`

---

### Task 18: QR Code Generation

**Files:**
- Modify: `composer.json` (add QR code package)
- Modify: `app/Livewire/EventScoringGrid.php` (add QR code generation)
- Modify: `resources/views/livewire/event-scoring-grid.blade.php` (display QR code)
- Test: `tests/Feature/Events/QrCodeTest.php`

**Step 1: Install QR code package**

Run: `composer require simplesoftwareio/simple-qrcode --no-interaction`

If this package doesn't support Laravel 12 / PHP 8.4, use `chillerlan/php-qrcode` instead.

**Step 2: Add QR code display to the scoring grid**

On the event management page, display:
- The full scoreboard URL as text
- A QR code image encoding that URL
- A "Download QR" button to save the image

**Step 3: Write test**

Create `tests/Feature/Events/QrCodeTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\User;

test('event management page shows join code', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id, 'slug' => 'quiz42']);

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertSee('quiz42');
});
```

**Step 4: Run tests**

Run: `php artisan test --compact tests/Feature/Events/QrCodeTest.php`

**Step 5: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `feat: add QR code generation and join code display on event page`

---

## Phase 6: Polish & Final Integration

### Task 19: Run Full Test Suite and Fix Issues

**Step 1: Run all tests**

Run: `php artisan test --compact`

Fix any failures.

**Step 2: Run PHPStan**

Run: `vendor/bin/phpstan analyse --memory-limit=512M`

Fix any errors (update baseline if needed for new false positives).

**Step 3: Run Pint on all files**

Run: `vendor/bin/pint --format agent`

**Step 4: Regenerate IDE helper**

Run: `php artisan ide-helper:models --write-mixin --no-interaction`
Run: `php artisan ide-helper:generate --no-interaction`

**Step 5: Commit**

Commit: `chore: fix lint, static analysis, and regenerate IDE helpers`

---

### Task 20: Final Integration Test

**Files:**
- Create: `tests/Feature/FullEventFlowTest.php`

**Step 1: Write an end-to-end test**

```php
<?php

use App\Models\User;

test('complete event flow: create, add teams, add rounds, score, end', function () {
    $user = User::factory()->create();

    // Create event
    $component = Livewire\Livewire::actingAs($user)
        ->test('pages::events.create')
        ->set('name', 'Integration Test Trivia')
        ->set('slug', 'integration-test')
        ->call('save');

    $event = $user->events()->first();
    expect($event)->not->toBeNull();

    // Set up teams and rounds, enter scores
    $grid = Livewire\Livewire::actingAs($user)
        ->test('event-scoring-grid', ['event' => $event]);

    $grid->call('addTeam', 'Team Alpha', 1);
    $grid->call('addTeam', 'Team Beta', 2);
    $grid->call('addRound');
    $grid->call('addRound');

    $teams = $event->teams;
    $rounds = $event->rounds;

    $grid->call('saveScore', $teams[0]->id, $rounds[0]->id, '8');
    $grid->call('saveScore', $teams[0]->id, $rounds[1]->id, '7');
    $grid->call('saveScore', $teams[1]->id, $rounds[0]->id, '9');
    $grid->call('saveScore', $teams[1]->id, $rounds[1]->id, '6');

    // Verify public scoreboard
    $this->get('/integration-test')
        ->assertOk()
        ->assertSeeInOrder(['Team Alpha', 'Team Beta']);

    // End event
    $grid->call('endEvent');
    expect($event->fresh()->isActive())->toBeFalse();

    // Verify final scores on public scoreboard
    $this->get('/integration-test')->assertSee('Final Scores');
});
```

**Step 2: Run the integration test**

Run: `php artisan test --compact tests/Feature/FullEventFlowTest.php`

**Step 3: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

Commit: `test: add full event flow integration test`

---

## Summary

| Phase | Tasks | What's Built |
|-------|-------|-------------|
| 1: Data Foundation | Tasks 1-6 | Models, migrations, factories, policy, seeder |
| 2: Event CRUD & Dashboard | Tasks 7-8 | Create event page, updated dashboard |
| 3: Scoring Grid | Tasks 9-15 | Event management, team/round management, score entry, keyboard nav, end/reopen |
| 4: Public Scoreboard | Task 16 | Public scoreboard with polling and ranking |
| 5: Landing Page & Access | Tasks 17-18 | Landing page with join code, QR code |
| 6: Polish | Tasks 19-20 | Full test suite, static analysis, integration test |
