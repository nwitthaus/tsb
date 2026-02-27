# Organization Dashboard Restructure Design

**Date:** 2026-02-27
**Goal:** Restructure the organization owner/scorekeeper dashboard to follow the same admin CRUD patterns (sectioned cards, searchable tables, consistent navigation) while remaining simplified for daily use.

---

## Design Principles

- Apply the **Sectioned Cards** pattern from `DESIGN-SYSTEM.md` section 10 to all dashboard pages
- Follow the admin CRUD structure: index with tables, card-based create/edit forms, back navigation
- Keep it simplified — fewer sections than admin, no danger zones on daily-use pages
- Preserve existing Livewire component logic for Teams Manager and Scoring Grid

---

## Page Structure

```
/dashboard                                          → Org picker (unchanged)
/organizations/{org}                                → Org Dashboard Hub (minimal nav)
/organizations/{org}/settings                       → Settings (restructured cards)
/organizations/{org}/events                         → Events Index (tabbed: Active|Past)    [NEW]
/organizations/{org}/events/create                  → Event Create (card form)               [RESTYLE]
/organizations/{org}/events/{event}                 → Event Edit (details + quick links)     [RESTYLE]
/organizations/{org}/events/{event}/teams           → Teams Manager (card wrapper)           [RESTYLE]
/organizations/{org}/events/{event}/scoring         → Scoring Grid (card wrapper)            [RESTYLE]
```

---

## Page Designs

### 1. Organization Dashboard Hub — `/organizations/{org}`

**What changes:** Replace the current event cards + past events table with a minimal navigation hub.

**Layout:**
- Header: back arrow (ghost) → `/dashboard`, org name `<flux:heading size="xl">`, subheading "Organization Dashboard"
- Two side-by-side quick link cards (responsive: stack on mobile)

**Quick link cards** (plain `<flux:card>` with icon, heading, description, button):
- **Events** — calendar icon, "Manage your trivia events", primary button → events index
- **Settings** — cog icon, "Organization settings & members", default button → settings

**Authorization:** `authorize('view', $organization)` — same as current.

**What's removed:** All event listing (moved to events index), create event button (moved to events index header).

### 2. Events Index — `/organizations/{org}/events` [NEW PAGE]

**New route:** `organizations/{organization}/events` → `pages::organizations.events.index`

**Layout (max-w-3xl):**
- Header: back arrow → org dashboard, "Events" heading + "Create Event" primary button
- Search: `<flux:input>` with magnifying-glass icon, `wire:model.live.debounce.300ms="search"`
- Tabs: `<flux:tabs>` with Active and Past tabs (using `wire:model` or `$tab` property)
- Each tab: sectioned card wrapping a `<flux:table>` with pagination (15/page)

**Active tab table columns:**
| Column | Content |
|--------|---------|
| Name | Event name |
| Join Code | Event slug |
| Scheduled | `starts_at` formatted date |
| Teams | Count |
| Actions | Edit (pencil icon), Delete (trash icon with confirm) |

**Past tab table columns:**
| Column | Content |
|--------|---------|
| Name | Event name |
| Scheduled | `starts_at` formatted date |
| Teams | Count |
| Ended | `ended_at->diffForHumans()` |
| Actions | View (pencil icon) |

**Empty states:** "No active events. Create one to get started." / "No past events."

**PHP logic:**
- Computed properties for `activeEvents` and `pastEvents` with search filtering
- `deleteEvent()` method with authorization
- Eager loading: `withCount('teams')`

### 3. Event Create — `/organizations/{org}/events/create` [RESTYLE]

**What changes:** Wrap the existing form in the sectioned card pattern.

**Layout (max-w-3xl):**
- Header: back arrow → events index, "Create Event" heading, subheading with org name
- Single sectioned card — "Event Details":
  - Header: `bg-zinc-50`, "Event Details" heading, "Configure your new trivia event." subheading
  - Content: existing form fields (Name, Join Code, Scheduled Start, Tables, Rounds)
- Footer within card: Cancel (ghost → events index) + Create Event (primary)

**PHP logic:** Unchanged. Redirect target changes from `events.teams` → `events.teams` (stays the same since teams page will also be restyled).

### 4. Event Edit — `/organizations/{org}/events/{event}` [RESTYLE]

**What changes:** Replace the tab-based layout with sectioned cards and quick links. The event edit page is now the parent of teams/scoring pages.

**New route:** `organizations/{organization}/events/{event}` → `pages::organizations.events.edit`
**Old route removed:** `events/{event}` → was `pages::events.show`

**Layout (max-w-3xl):**
- Header: back arrow → events index, event name heading with status badge (Active green / Ended zinc), subheading with org name

**Card 1 — Event Details:**
- Header: "Event Details" heading, "Update your event's name, join code, and schedule." subheading
- Content (if can update): inline form with Name, Join Code, Scheduled Start, Save button
- Content (if read-only): display-only text fields
- Status control in header area: "End Event" or "Reopen Event" button (conditionally shown)

**Card 2 — Share Scoreboard:**
- Header: "Share Scoreboard" heading, "Share the live scoreboard with your audience." subheading
- Content: QR code (150px), scoreboard URL link, Copy Link + Download QR buttons

**Card 3 — Quick Links:**
- Header: "Manage" heading, "Access teams and scoring for this event." subheading
- Content: two link rows
  - Manage Teams → teams page (with team count badge)
  - Manage Scoring → scoring page (with round count badge)

**PHP logic:** Merges current `events.show` logic. Add `endEvent()` and `reopenEvent()` methods (currently on EventScoringGrid — keep there or move here).

### 5. Teams Manager — `/organizations/{org}/events/{event}/teams` [RESTYLE]

**What changes:** Replace tab navigation with sectioned card wrapper and back navigation.

**New route:** `organizations/{organization}/events/{event}/teams` → `pages::organizations.events.teams`

**Layout (max-w-3xl):**
- Header: back arrow → event edit, event name heading + "Teams" subheading, status badge
- Sectioned card wrapping the existing `<livewire:event-teams-manager>` component

**PHP logic:** The SFC page itself is minimal — just mount + authorize. The `EventTeamsManager` component handles all logic unchanged.

### 6. Scoring Grid — `/organizations/{org}/events/{event}/scoring` [RESTYLE]

**What changes:** Same as teams — replace tabs with sectioned card wrapper and back navigation.

**New route:** `organizations/{organization}/events/{event}/scoring` → `pages::organizations.events.scoring`

**Layout (max-w-3xl):**
- Header: back arrow → event edit, event name heading + "Scoring" subheading, status badge
- Sectioned card wrapping the existing `<livewire:event-scoring-grid>` component

**Note:** The scoring grid may need full width — if so, skip `max-w-3xl` for this page only and let the grid expand.

### 7. Settings — `/organizations/{org}/settings` [RESTRUCTURE]

**What changes:** Already uses sectioned cards. Improvements:

- **Members card:** Already uses `<flux:table>` — keep as-is
- **Invitations card:** Already has inline form + pending list — keep as-is
- **General card:** Already has inline form — keep as-is
- **Danger Zone:** Already uses red card pattern — keep as-is

**Conclusion:** Settings page is already well-structured. No changes needed.

---

## Route Changes

### New routes:
```php
Route::livewire('organizations/{organization}/events', 'pages::organizations.events.index')
    ->name('organizations.events.index');
Route::livewire('organizations/{organization}/events/{event}', 'pages::organizations.events.edit')
    ->name('organizations.events.edit');
Route::livewire('organizations/{organization}/events/{event}/teams', 'pages::organizations.events.teams')
    ->name('organizations.events.teams');
Route::livewire('organizations/{organization}/events/{event}/scoring', 'pages::organizations.events.scoring')
    ->name('organizations.events.scoring');
```

### Routes to remove (after migration):
```php
// These move under organizations/{org}/events/...
Route::livewire('events/{event}', ...)        → organizations.events.edit
Route::livewire('events/{event}/teams', ...)  → organizations.events.teams
Route::livewire('events/{event}/scoring', ...) → organizations.events.scoring
```

### Routes that stay:
```php
Route::livewire('organizations/{organization}/events/create', ...) // already exists
Route::livewire('organizations/{organization}/settings', ...)       // already exists
Route::livewire('organizations/{organization}', ...)                // restyled
```

---

## File Changes Summary

| File | Action | Notes |
|------|--------|-------|
| `routes/web.php` | Modify | Add new routes, remove old event routes |
| `pages/organizations/⚡show.blade.php` | Rewrite | Minimal nav hub |
| `pages/organizations/events/⚡index.blade.php` | Create | New events index with tabs |
| `pages/organizations/events/⚡create.blade.php` | Create | Restyled event create (moves from `pages/events/`) |
| `pages/organizations/events/⚡edit.blade.php` | Create | New event edit with cards + quick links |
| `pages/organizations/events/⚡teams.blade.php` | Create | Restyled teams page |
| `pages/organizations/events/⚡scoring.blade.php` | Create | Restyled scoring page |
| `pages/events/⚡show.blade.php` | Delete | Replaced by organizations.events.edit |
| `pages/events/⚡create.blade.php` | Delete | Replaced by organizations.events.create |
| `pages/events/⚡teams.blade.php` | Delete | Replaced by organizations.events.teams |
| `pages/events/⚡scoring.blade.php` | Delete | Replaced by organizations.events.scoring |
| `pages/organizations/⚡settings.blade.php` | No change | Already well-structured |
| Existing tests | Update | Route name changes require test updates |
| New tests | Create | Feature tests for new pages |

---

## Authorization

No new policies needed. Existing checks:
- **View org / events index:** `authorize('view', $organization)` — any org member
- **Create/edit events:** `authorize('update', $organization)` — org owners only
- **Delete events:** `authorize('delete', $event)` — org owners only
- **Settings:** `authorize('update', $organization)` — org owners only

Scorekeepers can view the events index and event details but cannot create, edit, or delete.

---

## What's NOT Changing

- `EventTeamsManager` and `EventScoringGrid` Livewire class components — logic untouched
- Organization Settings page — already follows the sectioned card pattern
- `/dashboard` page — org picker behavior unchanged
- Admin CRUD pages — completely separate, untouched
- Public scoreboard — unaffected
- Models, migrations, policies — no changes needed
