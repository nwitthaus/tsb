# Organizations Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add organizations as a first-class entity so multiple users can collaborate on events with role-based access (owner/scorekeeper).

**Architecture:** Events move from belonging to a User to belonging to an Organization. Users join organizations via a pivot with roles. Authorization flows through organization membership. Email-based invitation system for adding members.

**Tech Stack:** Laravel 12, Livewire 4, Flux UI Pro, Pest 4, Fortify

**Design doc:** `docs/plans/2026-02-27-organizations-design.md`

---

### Task 1: Database Layer — Organization Model, Migrations, Factory

Create the Organization model and supporting database infrastructure. Modify the existing events migration to use `organization_id` instead of `user_id`. Since this is greenfield, we edit the existing migration directly.

**Files:**
- Create: `app/Models/Organization.php`
- Create: `app/Enums/OrganizationRole.php`
- Create: `database/factories/OrganizationFactory.php`
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_organizations_table.php`
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_organization_user_table.php`
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_organization_invitations_table.php`
- Modify: `database/migrations/2026_02_26_085324_create_events_table.php:16` — change `user_id` to `organization_id`
- Modify: `database/factories/EventFactory.php:19` — change `user_id` to `organization_id`
- Modify: `app/Models/Event.php:19-41` — change `user_id` fillable and `user()` relationship to `organization_id` and `organization()`
- Modify: `app/Models/User.php:64-67` — replace `events()` HasMany with `organizations()` BelongsToMany

**Step 1: Create OrganizationRole enum**

```php
// app/Enums/OrganizationRole.php
<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Scorekeeper = 'scorekeeper';
}
```

**Step 2: Create organizations migration**

Run: `php artisan make:migration create_organizations_table --no-interaction`

Schema:
```php
$table->id();
$table->string('name');
$table->string('slug', 100)->unique();
$table->timestamps();
```

**Step 3: Create organization_user pivot migration**

Run: `php artisan make:migration create_organization_user_table --no-interaction`

Schema:
```php
$table->id();
$table->foreignId('organization_id')->constrained()->cascadeOnDelete();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->string('role')->default('owner');
$table->timestamps();
$table->unique(['organization_id', 'user_id']);
```

**Step 4: Create organization_invitations migration**

Run: `php artisan make:migration create_organization_invitations_table --no-interaction`

Schema:
```php
$table->id();
$table->foreignId('organization_id')->constrained()->cascadeOnDelete();
$table->string('email');
$table->string('role')->default('scorekeeper');
$table->string('token', 64)->unique();
$table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
$table->timestamp('accepted_at')->nullable();
$table->timestamps();
```

**Step 5: Modify existing events migration**

In `database/migrations/2026_02_26_085324_create_events_table.php`, change line 16:
- FROM: `$table->foreignId('user_id')->constrained()->cascadeOnDelete();`
- TO: `$table->foreignId('organization_id')->constrained()->cascadeOnDelete();`

**Step 6: Create Organization model**

Run: `php artisan make:model Organization --no-interaction`

```php
// Key content:
protected $fillable = ['name', 'slug'];

public function events(): HasMany
{
    return $this->hasMany(Event::class);
}

public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class)
        ->withPivot('role')
        ->withTimestamps();
}

public function invitations(): HasMany
{
    return $this->hasMany(OrganizationInvitation::class);
}

public function owners(): BelongsToMany
{
    return $this->belongsToMany(User::class)
        ->withPivot('role')
        ->wherePivot('role', OrganizationRole::Owner->value);
}

public static function generateSlug(string $name): string
{
    // Same pattern as Event::generateSlug
}
```

**Step 7: Create OrganizationFactory**

Run: `php artisan make:factory OrganizationFactory --no-interaction`

```php
public function definition(): array
{
    $name = fake()->unique()->company();
    return [
        'name' => $name,
        'slug' => Str::slug($name),
    ];
}
```

**Step 8: Update Event model**

In `app/Models/Event.php`:
- Change `$fillable`: `'user_id'` → `'organization_id'`
- Replace `user()` BelongsTo with `organization()` BelongsTo referencing Organization

**Step 9: Update EventFactory**

In `database/factories/EventFactory.php`:
- Change `'user_id' => User::factory()` → `'organization_id' => Organization::factory()`

**Step 10: Update User model**

In `app/Models/User.php`:
- Remove `events(): HasMany`
- Add `organizations(): BelongsToMany` with pivot `role` and timestamps
- Add helper methods (detailed in Task 2)

**Step 11: Run migrate:fresh to verify schema**

Run: `php artisan migrate:fresh --no-interaction`
Expected: All migrations run successfully, no errors.

**Step 12: Run ide-helper**

Run: `php artisan ide-helper:models --write-mixin --no-interaction`

**Step 13: Commit**

```
feat: add Organization model and restructure events to belong to organizations
```

---

### Task 2: Authorization — Policies and User Helpers

Update EventPolicy to use organization membership. Create OrganizationPolicy. Add helper methods to User model.

**Files:**
- Create: `app/Policies/OrganizationPolicy.php`
- Modify: `app/Policies/EventPolicy.php` — rewrite all checks to use org membership
- Modify: `app/Models/User.php` — add org role helper methods

**Step 1: Add helper methods to User model**

```php
public function isOrganizationOwner(Organization $organization): bool
{
    return $this->organizations()
        ->wherePivot('organization_id', $organization->id)
        ->wherePivot('role', OrganizationRole::Owner->value)
        ->exists();
}

public function isOrganizationMember(Organization $organization): bool
{
    return $this->organizations()
        ->wherePivot('organization_id', $organization->id)
        ->exists();
}

public function hasOrganizationRole(Organization $organization, OrganizationRole $role): bool
{
    return $this->organizations()
        ->wherePivot('organization_id', $organization->id)
        ->wherePivot('role', $role->value)
        ->exists();
}
```

**Step 2: Update EventPolicy**

```php
public function view(User $user, Event $event): bool
{
    return $user->isOrganizationMember($event->organization);
}

public function create(User $user): bool
{
    // Will be checked at the org level in controllers
    return true;
}

public function update(User $user, Event $event): bool
{
    return $user->isOrganizationOwner($event->organization);
}

public function delete(User $user, Event $event): bool
{
    return $user->isOrganizationOwner($event->organization);
}
```

**Step 3: Create OrganizationPolicy**

Run: `php artisan make:policy OrganizationPolicy --no-interaction`

```php
public function before(User $user, string $ability): ?bool
{
    if ($user->isSuperAdmin()) {
        return true;
    }
    return null;
}

public function view(User $user, Organization $organization): bool
{
    return $user->isOrganizationMember($organization);
}

public function update(User $user, Organization $organization): bool
{
    return $user->isOrganizationOwner($organization);
}

public function delete(User $user, Organization $organization): bool
{
    return $user->isOrganizationOwner($organization);
}

public function invite(User $user, Organization $organization): bool
{
    return $user->isOrganizationOwner($organization);
}

public function removeMember(User $user, Organization $organization): bool
{
    return $user->isOrganizationOwner($organization);
}
```

**Step 4: Write tests for Organization model relationships and policies**

Create: `tests/Feature/Models/OrganizationTest.php`

Test cases:
- Organization has many events
- Organization belongs to many users with role pivot
- Organization owners scope returns only owners
- Organization slug generation works (including uniqueness)

Create: `tests/Feature/Policies/OrganizationPolicyTest.php`

Test cases:
- Owner can view, update, delete, invite, removeMember
- Scorekeeper can view but cannot update, delete, invite, removeMember
- Non-member cannot view, update, delete
- Super admin can do everything

Update: `tests/Feature/Policies/EventPolicyTest.php`

Test cases (rewrite to use org membership):
- Org owner can view, update, delete event
- Org scorekeeper can view event but cannot update or delete
- Non-member cannot view, update, or delete
- Super admin bypasses all

**Step 5: Run tests**

Run: `php artisan test --compact --filter=OrganizationTest`
Run: `php artisan test --compact --filter=OrganizationPolicyTest`
Run: `php artisan test --compact --filter=EventPolicyTest`

**Step 6: Commit**

```
feat: add organization authorization policies and user role helpers
```

---

### Task 3: Update Dashboard, Event Creation, and Seeder

Refactor the dashboard to show events through organization membership. Update event creation to go through an organization. Update the seeder.

**Files:**
- Modify: `resources/views/pages/⚡dashboard.blade.php:14,21` — query events via org membership
- Modify: `resources/views/pages/events/⚡create.blade.php:47` — create event via organization
- Modify: `database/seeders/DatabaseSeeder.php:26-27` — create org, attach user, create event via org
- Modify: `routes/web.php` — add organization routes, update event create route

**Step 1: Update DatabaseSeeder**

```php
// After creating $user, before creating $event:
$organization = Organization::factory()->create([
    'name' => 'Joe\'s Bar Trivia',
    'slug' => 'joes-bar',
]);
$organization->users()->attach($user, ['role' => OrganizationRole::Owner->value]);

// Change event creation:
$event = Event::factory()->create([
    'organization_id' => $organization->id,
    'name' => 'Tuesday Trivia at Joe\'s',
    'slug' => 'tuesday-trivia',
    'starts_at' => now()->addHours(2),
]);
```

Also attach the super admin user to the same org as an owner for testing.

**Step 2: Run migrate:fresh --seed to verify**

Run: `php artisan migrate:fresh --seed --no-interaction`

**Step 3: Update routes**

In `routes/web.php`, add organization-scoped routes:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    // Organization routes
    Route::livewire('organizations/create', 'pages::organizations.create')->name('organizations.create');
    Route::livewire('organizations/{organization}', 'pages::organizations.show')->name('organizations.show');
    Route::livewire('organizations/{organization}/settings', 'pages::organizations.settings')->name('organizations.settings');
    Route::livewire('organizations/{organization}/events/create', 'pages::events.create')->name('events.create');

    // Event routes (no longer need org prefix, policy handles auth)
    Route::livewire('events/{event}/teams', 'pages::events.teams')->name('events.teams');
    Route::livewire('events/{event}/scoring', 'pages::events.scoring')->name('events.scoring');
    Route::livewire('events/{event}', 'pages::events.show')->name('events.show');

    // Admin routes (unchanged)
    ...
});
```

**Step 4: Update dashboard page**

In `resources/views/pages/⚡dashboard.blade.php`:

The dashboard should show the user's organizations. If the user has exactly one org, show that org's events directly. If multiple, show an org picker or list orgs with their events.

Simple approach: list organizations with their active event counts, link to org show page. Add "Create Organization" button.

```php
#[Computed]
public function organizations(): Collection
{
    return auth()->user()->organizations()
        ->withCount(['events as active_events_count' => fn ($q) => $q->whereNull('ended_at')])
        ->get();
}
```

**Step 5: Create organization show page**

Create: `resources/views/pages/organizations/⚡show.blade.php`

This replaces the old dashboard's event listing but scoped to one organization. Shows active events and past events for that org. The "Create Event" button links to `organizations/{organization}/events/create`.

Mount loads the organization and authorizes `view`.

**Step 6: Update event create page**

In `resources/views/pages/events/⚡create.blade.php`:
- Mount receives `Organization $organization` from route model binding
- Authorization: verify user is an org owner
- Change `auth()->user()->events()->create($validated)` → `$this->organization->events()->create($validated)`

**Step 7: Update existing dashboard/event tests**

- `tests/Feature/DashboardTest.php` — rewrite setup to create org + attach user, assertions for org listing
- `tests/Feature/Events/CreateEventTest.php` — rewrite to use org-scoped route, org-based factory setup

**Step 8: Run all affected tests**

Run: `php artisan test --compact --filter=Dashboard`
Run: `php artisan test --compact --filter=CreateEvent`

**Step 9: Commit**

```
feat: add organization dashboard and org-scoped event creation
```

---

### Task 4: Organization CRUD Pages

Build the organization create page and settings page (member management, invitations).

**Files:**
- Create: `resources/views/pages/organizations/⚡create.blade.php`
- Create: `resources/views/pages/organizations/⚡settings.blade.php`
- Create: `tests/Feature/Organizations/CreateOrganizationTest.php`
- Create: `tests/Feature/Organizations/OrganizationSettingsTest.php`

**Step 1: Create organization create page**

Fields: name (auto-generates slug), slug (editable).
On save: create org, attach current user as owner, redirect to org show page.

**Step 2: Write tests for org creation**

Test cases:
- Authenticated user can view create form
- Can create org with valid data
- User is attached as owner after creation
- Slug is auto-generated and unique
- Validation: name required, slug required/unique/format

**Step 3: Create organization settings page**

Sections:
- Edit org name/slug
- Member list with roles (table: name, email, role, remove button)
- Invite form (email + role select)
- Pending invitations list (email, role, resend/cancel buttons)

Owner-only access (authorize in mount).

**Step 4: Write tests for org settings**

Test cases:
- Owner can view settings
- Scorekeeper cannot view settings (403)
- Owner can update org name/slug
- Owner can remove a member
- Owner cannot remove themselves if they're the last owner

**Step 5: Run tests**

Run: `php artisan test --compact --filter=Organization`

**Step 6: Commit**

```
feat: add organization create and settings pages
```

---

### Task 5: Invitation System

Build the email invitation flow: sending invites, accepting invites, invitation mail notification.

**Files:**
- Create: `app/Models/OrganizationInvitation.php`
- Create: `database/factories/OrganizationInvitationFactory.php`
- Create: `app/Mail/OrganizationInvitationMail.php`
- Create: `resources/views/mail/organization-invitation.blade.php`
- Create: `resources/views/pages/invitations/⚡accept.blade.php`
- Modify: `routes/web.php` — add invitation accept route
- Create: `tests/Feature/Organizations/InvitationTest.php`

**Step 1: Create OrganizationInvitation model and factory**

Run: `php artisan make:model OrganizationInvitation --factory --no-interaction`

Model relationships: `belongsTo Organization`, `belongsTo User` (invited_by).
Factory generates: random email, random token (Str::random(64)), role default scorekeeper.

**Step 2: Create invitation mail**

Run: `php artisan make:mail OrganizationInvitationMail --no-interaction`

The mail receives an `OrganizationInvitation` and renders a view with:
- Organization name
- Inviter name
- Role being offered
- Accept link: `/invitations/{token}`

**Step 3: Add invite/cancel/resend actions to settings page**

In the settings page component:
- `invite()` method: validate email + role, check not already a member, check no pending invite, create invitation, send mail
- `cancelInvitation(int $id)` method: delete the invitation
- `resendInvitation(int $id)` method: resend the mail

**Step 4: Create invitation accept page**

Route: `Route::livewire('invitations/{token}', 'pages::invitations.accept')->name('invitations.accept');`

This route should be inside the `auth` middleware group (user must be logged in).

Mount:
- Find invitation by token, abort 404 if not found or already accepted
- If the authenticated user's email doesn't match the invitation email, show an error
- Accept: attach user to org with the invitation's role, set `accepted_at`, redirect to org dashboard

Handle the case where user isn't logged in: the `auth` middleware will redirect to login. After login they'll come back to the accept URL. Same for registration — they register, get redirected back.

**Step 5: Write invitation tests**

Test cases:
- Owner can send invitation (mail is sent)
- Cannot invite existing member (validation error)
- Cannot invite email with pending invitation (validation error)
- Invitation email contains correct link
- Accepting valid invitation adds user to org with correct role
- Accepting invitation redirects to org dashboard
- Cannot accept invitation with wrong email (403 or error)
- Cannot accept already-accepted invitation (404)
- Owner can cancel pending invitation
- Scorekeeper cannot send invitations (403)

**Step 6: Run tests**

Run: `php artisan test --compact --filter=Invitation`

**Step 7: Commit**

```
feat: add email-based organization invitation system
```

---

### Task 6: Update Admin Pages

Update admin event CRUD to use organizations instead of users. Update admin user list.

**Files:**
- Modify: `resources/views/pages/admin/events/⚡index.blade.php:28,33-35,77` — swap `user` for `organization`
- Modify: `resources/views/pages/admin/events/⚡create.blade.php:15,29,37-39,46-51,98-102` — swap user dropdown for org dropdown
- Modify: `resources/views/pages/admin/events/⚡edit.blade.php:17,24,35,50-53,117-121` — swap user for org
- Modify: `resources/views/pages/admin/users/⚡index.blade.php:28,86` — replace `events_count` with `organizations_count`

**Step 1: Update admin event index**

- Change `->with('user')` to `->with('organization')`
- Change search `->orWhereHas('user', ...)` to `->orWhereHas('organization', ...)`
- Change table column "Host" to "Organization"
- Change `$event->user->name` to `$event->organization->name`

**Step 2: Update admin event create**

- Change `$user_id` property to `$organization_id`
- Change validation from `'user_id' => 'required|exists:users,id'` to `'organization_id' => 'required|exists:organizations,id'`
- Change `users()` computed to `organizations()` computed, loading `Organization::query()->orderBy('name')->get()`
- Change Event::create to use `'organization_id'`
- Change the select dropdown from users to organizations

**Step 3: Update admin event edit**

Same pattern as create:
- `$user_id` → `$organization_id`
- Mount: `$this->user_id = $event->user_id` → `$this->organization_id = $event->organization_id`
- Validation, save, and dropdown all swap user for organization

**Step 4: Update admin user list**

- Change `->withCount('events')` to `->withCount('organizations')`
- Change column header from "Events" to "Organizations"
- Change `$user->events_count` to `$user->organizations_count`

**Step 5: Update admin tests**

Fix all tests in `tests/Feature/Admin/`:
- `AdminEventCreateTest.php` — swap `user_id` for `organization_id` in all set/assertDatabaseHas calls
- `AdminEventEditTest.php` — swap `user_id` for `organization_id`
- `AdminEventListTest.php` — update factory usage, update "Host" → "Organization" assertions
- `AdminUserListTest.php` — update `events_count` → `organizations_count`
- `AdminOverviewTest.php` — update Event::factory() calls
- `AdminMiddlewareTest.php` — update Event::factory() calls

**Step 6: Run admin tests**

Run: `php artisan test --compact --filter=Admin`

**Step 7: Commit**

```
refactor: update admin pages to use organizations instead of users
```

---

### Task 7: Update All Remaining Tests

Fix every remaining test that uses `['user_id' => $user->id]` in Event factory calls or references the old user→events relationship.

**Files (all need `['user_id' => ...]` → org-based setup):**
- `tests/Feature/Events/ShowEventTest.php`
- `tests/Feature/Events/QrCodeTest.php`
- `tests/Feature/Livewire/EventScoringGridRoundTest.php`
- `tests/Feature/Livewire/EventScoringGridScoreTest.php`
- `tests/Feature/Livewire/EventScoringGridTeamTest.php`
- `tests/Feature/Livewire/EventTeamsManagerTest.php`
- `tests/Feature/Livewire/EventDetailsTest.php`
- `tests/Feature/Livewire/EventLifecycleTest.php`
- `tests/Feature/FullEventFlowTest.php`
- `tests/Feature/Models/EventTest.php`
- `tests/Feature/Models/RoundTest.php`
- `tests/Feature/Models/TeamTest.php`
- `tests/Feature/Models/ScoreTest.php`
- `tests/Feature/Scoreboard/PublicScoreboardTest.php`
- `tests/Feature/LandingPageTest.php`

**Pattern for each test file:**

The common change is replacing:
```php
$user = User::factory()->create();
$event = Event::factory()->create(['user_id' => $user->id]);
```

With:
```php
$user = User::factory()->create();
$organization = Organization::factory()->create();
$organization->users()->attach($user, ['role' => 'owner']);
$event = Event::factory()->create(['organization_id' => $organization->id]);
```

Consider adding a test helper or `beforeEach` to reduce repetition. A helper like:

```php
// tests/Pest.php or a trait
function createUserWithOrganization(string $role = 'owner'): array
{
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user, ['role' => $role]);
    return [$user, $organization];
}
```

**Step 1: Add test helper function to `tests/Pest.php`**

**Step 2: Update `tests/Feature/Models/EventTest.php`**

- Rewrite "event belongs to a user" → "event belongs to an organization"
- Rewrite "user has many events" → "organization has many events"

**Step 3: Update all remaining test files** using the helper

**Step 4: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests pass.

**Step 5: Commit**

```
test: update all tests for organization-based event ownership
```

---

### Task 8: Scorekeeper Access Controls in UI

Ensure scorekeepers see the right UI — they can access scoring but not event/team/round management.

**Files:**
- Modify: `resources/views/pages/organizations/⚡show.blade.php` — hide "Create Event" for scorekeepers
- Modify: `resources/views/pages/events/⚡show.blade.php` — hide edit controls for scorekeepers
- Modify: `resources/views/pages/events/⚡teams.blade.php` — hide management controls for scorekeepers
- Modify: `resources/views/pages/events/⚡scoring.blade.php` — scoring stays accessible to all members
- Create: `tests/Feature/Organizations/ScorekeeperAccessTest.php`

**Step 1: Add `canManage` computed property to event pages**

In event show/teams pages, add a computed that checks if the current user is an owner:
```php
#[Computed]
public function canManage(): bool
{
    return auth()->user()->isOrganizationOwner($this->event->organization);
}
```

Use `@if($this->canManage)` to wrap management controls (edit buttons, delete buttons, add team/round forms).

**Step 2: Write scorekeeper access tests**

Test cases:
- Scorekeeper can view org show page but doesn't see "Create Event" button
- Scorekeeper can view event show page but doesn't see edit controls
- Scorekeeper can view teams page but doesn't see add/remove/reorder controls
- Scorekeeper can access scoring grid and enter scores
- Scorekeeper cannot POST to create event route (403)
- Scorekeeper cannot POST to update/delete event (403)

**Step 3: Run tests**

Run: `php artisan test --compact --filter=Scorekeeper`

**Step 4: Commit**

```
feat: add scorekeeper UI access controls for event management
```

---

### Task 9: Final Verification

Run the full test suite, static analysis, and code formatting.

**Step 1: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 2: Run PHPStan**

Run: `vendor/bin/phpstan analyse --memory-limit=512M`
Fix any errors.

**Step 3: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests pass.

**Step 4: Run ide-helper**

Run: `php artisan ide-helper:models --write-mixin --no-interaction`

**Step 5: Commit any formatting/analysis fixes**

```
chore: fix formatting and static analysis issues
```
