# Trivia Scoreboard - Application Design

## Overview

A multi-tenant SaaS web app for live trivia night scoring. Hosts create events, set up teams and rounds, then enter scores as the game progresses. Teams view a public scoreboard on their phones with rankings and round-by-round breakdowns that update in near real-time.

Free for everyone. No monetization or usage limits.

## Users

- **Hosts** (authenticated): Create and manage trivia events. Enter scores via a laptop-optimized spreadsheet grid.
- **Teams/Spectators** (unauthenticated): View the public scoreboard on their phones via a short join code or QR code.

## Data Model

### events

| Column     | Type               | Notes                                        |
|------------|--------------------|----------------------------------------------|
| id         | bigint PK          |                                              |
| user_id    | bigint FK          | The host (owner)                             |
| name       | string             | e.g., "Tuesday Trivia at Joe's"              |
| slug       | string unique      | URL-friendly join code (e.g., `quiz42`)      |
| ended_at   | timestamp nullable | Null = active, set when host ends event      |
| created_at | timestamp          |                                              |
| updated_at | timestamp          |                                              |

### teams

| Column     | Type               | Notes                                        |
|------------|--------------------|----------------------------------------------|
| id         | bigint PK          |                                              |
| event_id   | bigint FK          |                                              |
| name       | string nullable    | At least one of name or table_number required|
| table_number | integer nullable |                                              |
| sort_order | integer            | Controls row order in the grid               |
| deleted_at | timestamp nullable | Soft delete support                          |
| created_at | timestamp          |                                              |
| updated_at | timestamp          |                                              |

### rounds

| Column     | Type               | Notes                                        |
|------------|--------------------|----------------------------------------------|
| id         | bigint PK          |                                              |
| event_id   | bigint FK          |                                              |
| sort_order | integer            | Doubles as the round number (1, 2, 3...)     |
| created_at | timestamp          |                                              |
| updated_at | timestamp          |                                              |

Rounds are numbered, not named. Grid headers display "R1", "R2", "R3", etc.

### scores

| Column     | Type               | Notes                                        |
|------------|--------------------|----------------------------------------------|
| id         | bigint PK          |                                              |
| team_id    | bigint FK          |                                              |
| round_id   | bigint FK          |                                              |
| value      | decimal(5,1)       | Supports 0, 0.5, 1, 7.5, etc.               |
| created_at | timestamp          |                                              |
| updated_at | timestamp          |                                              |

Unique constraint on (team_id, round_id) - one score per team per round.

### Relationships

- User hasMany Events
- Event belongsTo User
- Event hasMany Teams (with soft deletes)
- Event hasMany Rounds
- Team hasMany Scores
- Round hasMany Scores
- Score belongsTo Team, belongsTo Round

## Event Lifecycle

Events have a simple lifecycle: created and immediately live, then ended when done.

- **Active:** `ended_at` is null. Host can manage teams, rounds, and scores. Public scoreboard polls for updates.
- **Ended:** `ended_at` is set. Scoreboard shows "Final Scores" and stops polling. Host sees a read-only grid.
- **Reopened:** Host can reopen an ended event by clearing `ended_at`, returning it to active state.

One active event per host at a time.

## Pages & Routes

### Authenticated (Host)

**Dashboard** (`GET /dashboard`)
- Active event with quick link to manage it
- List of past events (name, date, team count) with links to view final scores
- "Create Event" button

**Create Event** (`GET /events/create`)
- Form: event name (required), slug (auto-generated from name, editable)
- On submit: event created and host redirected to manage page

**Manage Event** (`GET /events/{event}`)
- The spreadsheet-style scoring grid (see Scoring Grid UX below)
- Controls to add/remove teams and rounds
- Join code and QR code displayed for sharing
- "End Event" button
- If event is ended: read-only grid with "Reopen" button

**View Past Event** (`GET /events/{event}` when ended)
- Read-only final score grid

### Public (No Auth)

**Home** (`GET /`)
- Landing page with join code input + "View Scoreboard" button

**Scoreboard** (`GET /{slug}`)
- Public live scoreboard grid (see Public Scoreboard UX below)

## Scoring Grid UX (Host)

The core interface. Optimized for laptop with keyboard navigation.

```
+--------------------+--------+---------+---------+---------+-------+
| Team               | Table  | R1      | R2      | R3      | Total |
+--------------------+--------+---------+---------+---------+-------+
| Quizly Bears       |   3    | [ 7.5 ] | [ 8   ] | [     ] | 15.5  |
| Brain Stormers     |   7    | [ 6   ] | [ 9   ] | [     ] | 15    |
| Table 12           |  12    | [ 5   ] | [     ] | [     ] |  5    |
+--------------------+--------+---------+---------+---------+-------+
```

### Behavior

- Each score cell is an editable input
- **Keyboard navigation:** Enter or Tab moves cursor down to the next team in the same round column. Alpine.js handles this client-side.
- **Auto-save:** On cell blur, a Livewire action fires `saveScore(teamId, roundId, value)`. The score is persisted immediately.
- **Visual feedback:** Saved cells get a subtle background tint (e.g., light green or blue). Empty cells remain the default background. This lets hosts spot missed scores at a glance.
- **Clearing a cell:** If the host empties a cell and leaves, the score is deleted and the cell returns to default background.
- **Total column:** Read-only, auto-calculated sum of the row's scores.
- **Adding a round:** Button appends a new column. Round number auto-increments.
- **Removing a round:** Only the last (most recent) round can be removed. Confirmation dialog since it deletes associated scores.
- **Adding a team:** Button appends a new row. Prompts for name and/or table number.
- **Removing a team:** Soft-deletes the team (with confirmation dialog). Can be restored.
- **Reordering teams:** Host can reorder rows (alphabetical, by table number, or drag-and-drop).

### Validation

- Score value: non-negative number, decimal allowed, max 999.9
- Team: at least one of name or table_number required
- Team name: max 255 characters
- Table number: positive integer

## Public Scoreboard UX

```
+------+------------------+-------+----+----+----+-------+
| Rank | Team             | Table | R1 | R2 | R3 | Total |
+------+------------------+-------+----+----+----+-------+
|  1   | Quizly Bears     |   3   |7.5 |  8 |  - | 15.5  |
|  2   | Brain Stormers   |   7   |  6 |  9 |  - | 15    |
|  3   | Table 12         |  12   |  5 |  - |  - |  5    |
+------+------------------+-------+----+----+----+-------+
```

### Behavior

- Sorted by total score descending. Ties share the same rank number.
- Dashes for rounds with no score entered yet.
- `wire:poll.5s` refreshes data automatically.
- **Mobile-responsive:** Round columns scroll horizontally; team name and total stay pinned.
- **Adaptive columns:** Team name column only appears if any team has a name. Table column only appears if any team has a table number. If both exist, both show.
- Event name displayed as the page header.
- Join code shown so latecomers can find the page.
- When event has ended: "Final Scores" banner replaces "Live Scoreboard", polling stops.

## Event Access

### Slug / Join Code

- Auto-generated from event name (e.g., "Tuesday Trivia at Joe's" -> `tuesday-trivia-at-joes`)
- Host can edit to something shorter/custom (e.g., `quiz42`)
- Must be unique across all events
- Alphanumeric + hyphens, max 100 characters

### QR Code

- Generated server-side (PHP QR code library)
- Encodes the full scoreboard URL
- Displayed on the event management page
- Downloadable as an image for printing

### Homepage Join

- Input field on landing page for entering a join code
- Redirects to `/{slug}`
- Friendly error if slug doesn't exist

## Architecture

### Stack

- **Backend:** Laravel 12, Livewire v4, Fortify (auth)
- **Frontend:** Tailwind CSS v4, Flux UI Pro components, Alpine.js
- **Database:** MySQL 8.0 (via Laravel Herd)
- **Real-time:** Polling via `wire:poll.5s` (no WebSockets)

### Key Livewire Components

- **EventScoringGrid:** The main spreadsheet grid on the event management page. Handles score auto-save, team/round management, keyboard navigation (via Alpine).
- **PublicScoreboard:** The public-facing scoreboard with polling. Read-only.
- **CreateEvent:** Event creation form with slug auto-generation.

### Authorization

- `EventPolicy`: Only the event owner can manage their event (create, update, end, reopen, manage teams/rounds/scores).
- Auth middleware on all `/events/*` routes.
- Public scoreboard requires no authentication.
- Rate limiting on the scoreboard poll endpoint.

### Input Validation

- Scores: numeric, >= 0, max 999.9
- Team names: string, max 255 chars, sanitized
- Slugs: alphanumeric + hyphens, max 100 chars, unique

## Testing Strategy

### Feature Tests (Pest)

- Event CRUD: create, view, end, reopen, archive
- Team management: add, edit, soft-delete, restore, reorder (with auth checks)
- Round management: add, remove last (with auth checks)
- Score entry: save, update, delete, validation (negative numbers, non-numeric, too large)
- Public scoreboard: accessible without auth, correct ranking, correct totals, adaptive columns
- Authorization: host cannot manage another host's event
- Slug uniqueness and validation
- Event lifecycle: one active event per host

### Livewire Component Tests

- Scoring grid: cell save, saved state visual feedback, keyboard navigation
- Team/round add/remove updates grid correctly
- Public scoreboard: correct data display, empty states, ended state

### Unit Tests

- Score total calculation per team
- Rank calculation with tie handling
- Slug generation from event name
