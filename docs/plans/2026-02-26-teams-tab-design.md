# Teams Tab Design

## Overview

Add a third tab (Teams) between Details and Scoring for managing team names and table numbers. Move all team management off the scoring grid so it becomes score-entry only.

## Route

| Route | Name | Page |
|---|---|---|
| `events/{event}/teams` | `events.teams` | Teams management page |

## Tab Bar (all three pages)

Details | **Teams** | Scoring

## Teams Page

New SFC at `resources/views/pages/events/⚡teams.blade.php`:

- Tab bar (Teams active)
- Editable table: each row has inline-editable name and table number fields (save on blur)
- "Add Team" row at bottom with empty inputs + Add button
- Remove button (X) per row
- Sort buttons (Sort A-Z, Sort by Table)

## Scoring Grid Changes

- Remove: Add Team button/modal, Remove Team X buttons, Sort buttons
- Keep: Add Round, Remove Last Round, End Event
- Grid becomes score-entry only (team names/table numbers shown read-only)
