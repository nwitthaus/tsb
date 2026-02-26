# Event Edit Page + Scoring Grid Separation Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Split the current event show page into a Details edit page and a separate Scoring grid page, connected by tabs.

**Architecture:** The existing `events.show` SFC becomes the edit page with event metadata fields and QR/share. A new `events.scoring` SFC wraps the existing `EventScoringGrid` component. Both pages share a `flux:tabs` bar for navigation. The QR code/share section and computed properties move from `EventScoringGrid` to the show page. The `EventScoringGrid` heading/QR section gets removed since it moves to the edit page.

**Tech Stack:** Laravel 12, Livewire 4, Flux UI Pro, Pest 4

---

### Task 1: Add the `events.scoring` route

**Files:**
- Modify: `routes/web.php`

**Step 1: Add route**

Add the scoring route *before* the existing `events.show` route so it matches first:

```php
Route::livewire('events/{event}/scoring', 'pages::events.scoring')->name('events.scoring');
Route::livewire('events/{event}', 'pages::events.show')->name('events.show');
```

**Step 2: Verify routes list**

Run: `php artisan route:list --path=events`
Expected: Both `events.show` and `events.scoring` routes present.

---

### Task 2: Create the scoring page SFC

**Files:**
- Create: `resources/views/pages/events/⚡scoring.blade.php`

**Step 1: Create the SFC**

```blade
<?php

use App\Models\Event;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Scoring Grid')] class extends Component {
    public Event $event;

    public function mount(Event $event): void
    {
        $this->authorize('view', $this->event);
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-2">{{ $event->name }}</flux:heading>

    <flux:tabs class="mb-6">
        <flux:tab :href="route('events.show', $event)" wire:navigate>{{ __('Details') }}</flux:tab>
        <flux:tab selected>{{ __('Scoring') }}</flux:tab>
    </flux:tabs>

    <livewire:event-scoring-grid :event="$event" />
</div>
```

---

### Task 3: Remove QR/share section and heading from `EventScoringGrid`

**Files:**
- Modify: `resources/views/livewire/event-scoring-grid.blade.php`
- Modify: `app/Livewire/EventScoringGrid.php`

**Step 1: Remove header and QR/share from the blade view**

Remove lines 11-43 from `event-scoring-grid.blade.php` (the header div and share scoreboard div). The view should start directly with the ended event banner (`@if (! $event->isActive())`).

**Step 2: Remove QR computed properties from the PHP class**

Remove `scoreboardUrl()`, `qrCode()`, and `qrCodePng()` computed methods from `EventScoringGrid.php`. Also remove the QRCode-related imports (`use chillerlan\QRCode\QRCode` and `use chillerlan\QRCode\QROptions`).

---

### Task 4: Rewrite the event show/edit page SFC

**Files:**
- Modify: `resources/views/pages/events/⚡show.blade.php`

**Step 1: Rewrite the show page as an edit form with tabs and QR/share**

The page needs:
- PHP: Event model binding, validation for name/slug/starts_at, `save()` action, QR computed properties (moved from EventScoringGrid), `scoreboardUrl` computed
- Blade: Heading, flux:tabs (Details selected, Scoring links to `events.scoring`), edit form (name, slug, starts_at), QR/share section

```blade
<?php

use App\Models\Event;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Event Details')] class extends Component {
    public Event $event;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:100|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
    public string $slug = '';

    #[Validate('required|date')]
    public string $starts_at = '';

    public function mount(Event $event): void
    {
        $this->authorize('view', $this->event);

        $this->name = $this->event->name;
        $this->slug = $this->event->slug;
        $this->starts_at = $this->event->starts_at->format('Y-m-d\TH:i');
    }

    /**
     * @return array<string, mixed>
     */
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

<div>
    <flux:heading size="xl" class="mb-2">{{ $event->name }}</flux:heading>

    <flux:tabs class="mb-6">
        <flux:tab selected>{{ __('Details') }}</flux:tab>
        <flux:tab :href="route('events.scoring', $event)" wire:navigate>{{ __('Scoring') }}</flux:tab>
    </flux:tabs>

    <div class="max-w-lg space-y-6">
        <form wire:submit="save" class="space-y-6">
            <flux:input
                wire:model="name"
                :label="__('Event Name')"
                required
            />

            <flux:input
                wire:model="slug"
                :label="__('Join Code')"
                :description="__('The URL slug teams use to find your scoreboard.')"
                required
            />

            <flux:input
                wire:model="starts_at"
                type="datetime-local"
                :label="__('Scheduled Start')"
                required
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">
                    {{ __('Save Changes') }}
                </flux:button>

                <x-action-message on="saved">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </div>

    {{-- Share Scoreboard --}}
    <div class="mt-8 flex items-start gap-6 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
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
</div>
```

**Step 3: Dispatch 'saved' event after save**

In the `save()` method, after `$this->event->update($validated)`, add:
```php
$this->dispatch('saved');
```

---

### Task 5: Update the create page redirect

**Files:**
- Modify: `resources/views/pages/events/⚡create.blade.php`

**Step 1: Change redirect to scoring page**

After creating an event, redirect to the scoring grid (not the details page) since the user will want to add teams/rounds immediately:

```php
$this->redirect(route('events.scoring', $event), navigate: true);
```

---

### Task 6: Update existing tests

**Files:**
- Modify: `tests/Feature/Livewire/EventLifecycleTest.php`

**Step 1: Fix the ended event read-only test**

The test at line 41 checks `route('events.show', $event)` and asserts it sees `Reopen`. Since Reopen is now on the scoring page, update this test:

```php
test('ended event shows read-only grid', function () {
    $user = User::factory()->create();
    $event = Event::factory()->ended()->create(['user_id' => $user->id, 'name' => 'Past Trivia']);

    $this->actingAs($user)
        ->get(route('events.scoring', $event))
        ->assertOk()
        ->assertSee('Past Trivia')
        ->assertSee('Reopen');
});
```

---

### Task 7: Write tests for the edit page

**Files:**
- Create: `tests/Feature/Livewire/EventDetailsTest.php`

**Step 1: Write tests**

Tests to write:
- Show page loads and displays event details form
- Can update event name
- Can update event slug (validates uniqueness excluding self)
- Can update starts_at
- Slug validation rejects invalid format
- Slug validation rejects duplicate slug from another event
- Unauthorized user cannot view event details
- QR code and scoreboard link display on details page
- Tabs are rendered with correct links

**Step 2: Run tests**

Run: `php artisan test --compact tests/Feature/Livewire/EventDetailsTest.php`
Expected: All pass.

---

### Task 8: Write test for scoring page

**Files:**
- Modify: `tests/Feature/Livewire/EventDetailsTest.php` (or a separate file)

**Step 1: Test scoring page loads and renders grid**

```php
test('scoring page loads and renders scoring grid', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('events.scoring', $event))
        ->assertOk()
        ->assertSeeLivewire('event-scoring-grid');
});
```

**Step 2: Run all event tests**

Run: `php artisan test --compact --filter=Event`
Expected: All pass.

---

### Task 9: Run Pint and PHPStan

**Step 1: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 2: Run PHPStan**

Run: `vendor/bin/phpstan analyse --memory-limit=512M`

Fix any issues found.

---

### Task 10: Final verification

**Step 1: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests pass.
