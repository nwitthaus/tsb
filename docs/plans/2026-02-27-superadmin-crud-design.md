# Superadmin CRUD UI Design

## Overview

Expand the superadmin UI from a single dashboard page into a full CRUD interface for managing users and events. Add dedicated list, create, and edit pages for both resources with proper navigation.

## Navigation

Expand the sidebar to show sub-items under Admin when the user is on admin pages:

```
Platform
  Dashboard
  ▼ Admin (expanded when on admin pages)
      Overview
      Users
      Events
```

The sidebar already conditionally renders the Admin item for superadmins. Add collapsible children that expand when on any `admin.*` route.

## Routes

All routes under the existing `super-admin` middleware group:

| Route | Name | Page |
|---|---|---|
| `/admin` | `admin.dashboard` | Overview (stats dashboard) |
| `/admin/users` | `admin.users.index` | User list |
| `/admin/users/create` | `admin.users.create` | Create user form |
| `/admin/users/{user}` | `admin.users.edit` | Edit user form |
| `/admin/events` | `admin.events.index` | Event list |
| `/admin/events/create` | `admin.events.create` | Create event form |
| `/admin/events/{event}` | `admin.events.edit` | Edit event form |

## Component Pattern

All pages are SFC Livewire pages in `resources/views/pages/admin/`. List pages use `#[Computed]` properties for reactive queries. Form pages use Livewire's built-in validation with rules defined in the component.

## Pages

### Admin Overview (`/admin`)

Slim down the current dashboard to be a stats-only overview:

- Stats cards: Total Users, Total Events, Active Events
- Quick links to Users and Events list pages
- Remove the inline tables (those move to dedicated list pages)

### User List (`/admin/users`)

- Searchable by name/email (live debounce 300ms)
- Paginated (15 per page)
- Columns: Name (with admin badge), Email, Events count, Registered date, Actions
- Actions per row: Edit button, Delete button (with confirmation)
- "Create User" button at top

### Create User (`/admin/users/create`)

Fields:
- Name (required, string, max 255)
- Email (required, email, unique:users, max 255)
- Password (required, min 8, confirmed)
- Password Confirmation
- Super Admin toggle (boolean, default false)

On save: Create user, hash password, send email verification notification, redirect to user list with success toast.

### Edit User (`/admin/users/{user}`)

Fields:
- Name (required, string, max 255)
- Email (required, email, unique:users ignoring current, max 255)
- Super Admin toggle (boolean) — disabled if editing yourself
- New Password (optional, min 8, confirmed) — leave blank to keep current
- New Password Confirmation

Constraints:
- Cannot demote your own superadmin status
- Cannot delete yourself (delete button hidden for current user)

On save: Update user fields, optionally update password if provided, redirect to user list with success toast.

### Event List (`/admin/events`)

- Searchable by event name or host name (live debounce 300ms)
- Paginated (15 per page)
- Columns: Event name, Host, Status (Active/Ended badge), Scheduled, Teams count, Actions
- Actions per row: Edit button, Manage button (links to scoring page), Delete button (with confirmation)
- "Create Event" button at top

### Create Event (`/admin/events/create`)

Fields:
- Name (required, string, max 255)
- Starts At (required, datetime picker)
- Host (required, select dropdown of all users)

On save: Create event with selected user as owner, generate slug, redirect to event list with success toast.

### Edit Event (`/admin/events/{event}`)

Fields:
- Name (required, string, max 255)
- Starts At (required, datetime)
- Host (required, select dropdown of all users)
- Status actions: End Event button (if active) / Reopen Event button (if ended)

Links:
- "Manage Scoring" button linking to the event's scoring page
- "Manage Teams" button linking to the event's teams page

On save: Update event fields, redirect to event list with success toast.

## Delete Behaviors

- **Delete user**: Cascades to all their events (and those events' teams/rounds/scores). Confirmation dialog warns about this. Cannot delete yourself.
- **Delete event**: Cascades to teams/rounds/scores. Confirmation dialog warns about this.

## Flash Messages

Use Flux toast notifications for success/error feedback after create, update, and delete operations.

## Testing

Each page gets a feature test covering:
- Authorization (superadmin required, non-admin gets 403)
- CRUD operations (create, read, update, delete)
- Validation rules
- Self-protection (cannot delete/demote self)
- Cascade behavior on delete
