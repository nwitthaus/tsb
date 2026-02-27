# Organizations Design

## Problem

At a trivia event, multiple people often need access to the scoring grid and team/round management. Currently, events belong to a single user with no way to share access.

## Solution

Add organizations as a first-class entity. Users belong to organizations with roles. Events belong to organizations instead of users.

## Data Model

### New tables

**`organizations`**: `id`, `name`, `slug` (unique), `created_at`, `updated_at`

**`organization_user`** (pivot): `id`, `organization_id`, `user_id`, `role` (enum: owner, scorekeeper), `created_at`, `updated_at`. Unique constraint on `(organization_id, user_id)`.

**`organization_invitations`**: `id`, `organization_id`, `email`, `role` (enum: owner, scorekeeper), `token` (unique), `invited_by` (user_id FK), `accepted_at` (nullable), `created_at`, `updated_at`.

### Modified tables

**`events`**: Replace `user_id` with `organization_id`.

### Relationships

- Organization hasMany Events
- Organization belongsToMany Users (with role pivot)
- User belongsToMany Organizations
- Event belongsTo Organization

### Roles

- **Owner**: Full control — manage events, teams, rounds, scores, invite/remove members, edit org settings, delete org.
- **Scorekeeper**: Can view events, teams, and rounds. Can enter/edit scores. Cannot create events, manage teams/rounds, or invite people.

## Authorization

### OrganizationPolicy

- `view` — user is a member (any role)
- `update` — user is an owner
- `delete` — user is an owner
- `invite` — user is an owner
- `removeMember` — user is an owner (can't remove self if last owner)

### EventPolicy (updated)

- `view` — user is a member of the event's organization (any role)
- `create` — user is an owner of the organization
- `update` — user is an owner of the organization
- `delete` — user is an owner of the organization

### Score authorization

- `update` — user is a member of the event's organization (any role, so scorekeepers can score)

### User model helpers

- `hasOrganizationRole(Organization $org, string $role): bool`
- `isOrganizationOwner(Organization $org): bool`
- `isOrganizationMember(Organization $org): bool`

### Dashboard scoping

Events queried through user's organization memberships instead of direct user ownership.

## Invitation Flow

1. Owner goes to org settings and enters an email + selects a role.
2. System creates an `organization_invitations` row with a unique token.
3. Email is sent with a link: `/invitations/{token}`.
4. Recipient clicks the link:
   - Logged in → accepts invite, added to org, redirected to dashboard.
   - Has account but not logged in → redirected to login, then back to accept.
   - No account → redirected to register, then back to accept.
5. Invitation marked as accepted (`accepted_at` set).

### Edge cases

- Email already a member → validation error.
- Email already has pending invite → validation error or resend.
- Owner removes a member → pivot row deleted, access lost immediately.
- Last owner tries to leave → blocked, must transfer ownership first.

### No auto-org on registration

Users either create an organization from the dashboard or accept an invitation.

## Routing

### New routes

- `/organizations` — list user's orgs
- `/organizations/create` — create org form
- `/organizations/{organization}` — org dashboard (events list)
- `/organizations/{organization}/settings` — member management, invitations, org editing
- `/invitations/{token}` — accept invitation

### Modified routes

- `/dashboard` — redirects to sole org, or shows org picker if multiple
- `/events/create` → `/organizations/{organization}/events/create`
- `/events/{event}`, `/events/{event}/teams`, `/events/{event}/scoring` — stay but scoped through org membership

### Scorekeeper experience

- Sees org's events on dashboard (read-only for event management)
- Can access scoring grid and enter scores
- Cannot see team/round management controls, event creation, or org settings

### Unchanged

- Admin area (super admin bypasses all policies)
- Public scoreboard (`/{slug}`)

## Migration Strategy

Greenfield project — no data migration needed.

1. Create `organizations`, `organization_user`, `organization_invitations` tables (fresh migrations)
2. Modify `events` migration: replace `user_id` with `organization_id`
3. Update factories and seeders
4. `php artisan migrate:fresh --seed`
