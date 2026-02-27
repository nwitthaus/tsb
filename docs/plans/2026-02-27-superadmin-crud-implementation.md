# Superadmin CRUD UI Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Expand the superadmin UI from a single dashboard page into full CRUD for users and events with dedicated list, create, and edit pages.

**Architecture:** SFC Livewire pages in `resources/views/pages/admin/` using Flux UI Pro components. Sidebar navigation expanded with Admin sub-items. All routes under existing `super-admin` middleware. TDD approach with Pest feature tests.

**Tech Stack:** Laravel 12, Livewire 4 (SFC), Flux UI Pro v2, Pest v4, Tailwind CSS v4

---

### Task 1: Expand sidebar navigation with admin sub-items

**Files:**
- Modify: `resources/views/layouts/app/sidebar.blade.php:13-23`
- Modify: `resources/views/layouts/app/header.blade.php:12-21` (desktop header navbar)
- Modify: `resources/views/layouts/app/header.blade.php:59-69` (mobile sidebar)

**Step 1: Update sidebar layout to show admin sub-items**

In `resources/views/layouts/app/sidebar.blade.php`, replace the single Admin sidebar item (lines 18-21) with a collapsible group:

```blade
@if (auth()->user()->isSuperAdmin())
    <flux:sidebar.group :heading="__('Admin')" expandable :expanded="request()->routeIs('admin.*')">
        <flux:sidebar.item icon="chart-bar" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
            {{ __('Overview') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
            {{ __('Users') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="calendar" :href="route('admin.events.index')" :current="request()->routeIs('admin.events.*')" wire:navigate>
            {{ __('Events') }}
        </flux:sidebar.item>
    </flux:sidebar.group>
@endif
```

Remove the old single `Admin` sidebar item from the `Platform` group.

**Step 2: Update header layout similarly**

In `resources/views/layouts/app/header.blade.php`, replace the single Admin navbar item (lines 16-19) with a dropdown:

```blade
@if (auth()->user()->isSuperAdmin())
    <flux:navbar.item icon="shield-check" :href="route('admin.dashboard')" :current="request()->routeIs('admin.*')" wire:navigate>
        {{ __('Admin') }}
    </flux:navbar.item>
@endif
```

Keep the header simple — just the single Admin link. The sidebar handles sub-navigation.

Also update the mobile sidebar section (lines 64-67) to match the sidebar.blade.php pattern with the expandable group.

**Step 3: Verify navigation renders**

Run: `php artisan test --compact --filter=AdminMiddleware`
Expected: All existing tests pass.

**Step 4: Commit**

```
feat: expand sidebar with admin sub-navigation
```

---

### Task 2: Add new admin routes

**Files:**
- Modify: `routes/web.php:14-16`

**Step 1: Add all admin routes**

Replace the admin route group in `routes/web.php` (lines 14-16):

```php
Route::middleware('super-admin')->prefix('admin')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('admin.dashboard');
    Route::livewire('users', 'pages::admin.users.index')->name('admin.users.index');
    Route::livewire('users/create', 'pages::admin.users.create')->name('admin.users.create');
    Route::livewire('users/{user}', 'pages::admin.users.edit')->name('admin.users.edit');
    Route::livewire('events', 'pages::admin.events.index')->name('admin.events.index');
    Route::livewire('events/create', 'pages::admin.events.create')->name('admin.events.create');
    Route::livewire('events/{event}', 'pages::admin.events.edit')->name('admin.events.edit');
});
```

**Step 2: Verify existing tests still pass**

Run: `php artisan test --compact --filter=Admin`
Expected: All existing admin tests still pass.

**Step 3: Commit**

```
feat: add admin CRUD routes for users and events
```

---

### Task 3: Slim down admin dashboard to overview page

**Files:**
- Modify: `resources/views/pages/admin/⚡dashboard.blade.php`

**Step 1: Write test for slimmed-down overview**

Create `tests/Feature/Admin/AdminOverviewTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

test('admin overview shows stat cards', function () {
    $admin = User::factory()->superAdmin()->create();
    User::factory()->count(3)->create();
    Event::factory()->count(2)->create();
    Event::factory()->ended()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSee('4') // total users (3 + admin)
        ->assertSee('3') // total events
        ->assertSee('2'); // active events
});

test('admin overview has links to users and events pages', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSeeHtml(route('admin.users.index'))
        ->assertSeeHtml(route('admin.events.index'));
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=AdminOverview`
Expected: Fails (page still has old layout).

**Step 3: Rewrite dashboard to overview**

Replace `resources/views/pages/admin/⚡dashboard.blade.php` entirely:

```blade
<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin Overview')] class extends Component {
    #[Computed]
    public function totalUsers(): int
    {
        return User::count();
    }

    #[Computed]
    public function totalEvents(): int
    {
        return Event::count();
    }

    #[Computed]
    public function activeEvents(): int
    {
        return Event::query()->whereNull('ended_at')->count();
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl">{{ __('Admin Overview') }}</flux:heading>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card>
            <flux:heading size="lg">{{ $this->totalUsers }}</flux:heading>
            <flux:subheading>{{ __('Total Users') }}</flux:subheading>
        </flux:card>
        <flux:card>
            <flux:heading size="lg">{{ $this->totalEvents }}</flux:heading>
            <flux:subheading>{{ __('Total Events') }}</flux:subheading>
        </flux:card>
        <flux:card>
            <flux:heading size="lg">{{ $this->activeEvents }}</flux:heading>
            <flux:subheading>{{ __('Active Events') }}</flux:subheading>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading>{{ __('Users') }}</flux:heading>
                    <flux:subheading>{{ __('Manage user accounts') }}</flux:subheading>
                </div>
                <flux:button variant="primary" :href="route('admin.users.index')" wire:navigate>
                    {{ __('Manage Users') }}
                </flux:button>
            </div>
        </flux:card>
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading>{{ __('Events') }}</flux:heading>
                    <flux:subheading>{{ __('Manage trivia events') }}</flux:subheading>
                </div>
                <flux:button variant="primary" :href="route('admin.events.index')" wire:navigate>
                    {{ __('Manage Events') }}
                </flux:button>
            </div>
        </flux:card>
    </div>
</div>
```

**Step 4: Update existing dashboard tests**

Modify `tests/Feature/Admin/AdminDashboardTest.php` — remove tests that tested inline user/event tables and delete actions (those move to the list page tests). Keep only the stat card test:

```php
<?php

use App\Models\User;
use Livewire\Livewire;

test('admin dashboard shows stat cards', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSee(__('Admin Overview'));
});
```

**Step 5: Run tests**

Run: `php artisan test --compact --filter=Admin`
Expected: All pass.

**Step 6: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 7: Commit**

```
refactor: slim admin dashboard to stats-only overview page
```

---

### Task 4: User list page with search, pagination, and delete

**Files:**
- Create: `resources/views/pages/admin/users/⚡index.blade.php`
- Create: `tests/Feature/Admin/AdminUserListTest.php`

**Step 1: Write tests for user list page**

Create `tests/Feature/Admin/AdminUserListTest.php`:

```php
<?php

use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access user list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('admin can see user list', function () {
    $admin = User::factory()->superAdmin()->create();
    $users = User::factory()->count(3)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->assertSee($users[0]->name)
        ->assertSee($users[1]->name)
        ->assertSee($users[2]->name);
});

test('user list shows admin badge for super admins', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->assertSee(__('Admin'));
});

test('user list is searchable', function () {
    $admin = User::factory()->superAdmin()->create();
    $jane = User::factory()->create(['name' => 'Jane Doe']);
    $john = User::factory()->create(['name' => 'John Smith']);

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->set('search', 'Jane')
        ->assertSee('Jane Doe')
        ->assertDontSee('John Smith');
});

test('admin can delete a user from user list', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->call('deleteUser', $user->id);

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('admin cannot delete themselves from user list', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->call('deleteUser', $admin->id);

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('user list has link to create user', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->assertSeeHtml(route('admin.users.create'));
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=AdminUserList`
Expected: Fails — page doesn't exist yet.

**Step 3: Create user list page**

Create `resources/views/pages/admin/users/⚡index.blade.php`:

```blade
<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Users')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->withCount('events')
            ->when($this->search, fn ($query, $search) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->paginate(15);
    }

    public function deleteUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            return;
        }

        $user->delete();

        Flux::toast(__('User deleted.'));
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Users') }}</flux:heading>
        <flux:button variant="primary" :href="route('admin.users.create')" wire:navigate>
            {{ __('Create User') }}
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name or email...') }}" icon="magnifying-glass" />

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Events') }}</flux:table.column>
            <flux:table.column>{{ __('Registered') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell>
                        {{ $user->name }}
                        @if ($user->is_super_admin)
                            <flux:badge color="amber" size="sm">{{ __('Admin') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>{{ $user->events_count }}</flux:table.cell>
                    <flux:table.cell>{{ $user->created_at->format('M j, Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil" :href="route('admin.users.edit', $user)" wire:navigate />
                            @if ($user->id !== auth()->id())
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="deleteUser({{ $user->id }})"
                                    wire:confirm="{{ __('Delete this user? This will permanently remove the user and all their events. This cannot be undone.') }}"
                                />
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
```

**Step 4: Run tests**

Run: `php artisan test --compact --filter=AdminUserList`
Expected: All pass.

**Step 5: Run Pint and PHPStan**

Run: `vendor/bin/pint --dirty --format agent`
Run: `vendor/bin/phpstan analyse --memory-limit=512M`

**Step 6: Commit**

```
feat: add admin user list page with search, pagination, and delete
```

---

### Task 5: Create user page

**Files:**
- Create: `resources/views/pages/admin/users/⚡create.blade.php`
- Create: `tests/Feature/Admin/AdminUserCreateTest.php`

**Step 1: Write tests**

Create `tests/Feature/Admin/AdminUserCreateTest.php`:

```php
<?php

use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access create user page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.create'))
        ->assertForbidden();
});

test('admin can see create user form', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->assertSee(__('Create User'));
});

test('admin can create a user', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->set('name', 'New User')
        ->set('email', 'newuser@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'is_super_admin' => false,
    ]);
});

test('admin can create a super admin user', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->set('name', 'Admin User')
        ->set('email', 'admin@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('is_super_admin', true)
        ->call('save')
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'admin@example.com',
        'is_super_admin' => true,
    ]);
});

test('create user validates required fields', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->call('save')
        ->assertHasErrors(['name', 'email', 'password']);
});

test('create user validates unique email', function () {
    $admin = User::factory()->superAdmin()->create();
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->set('name', 'Test')
        ->set('email', 'taken@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('create user validates password confirmation', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.create')
        ->set('name', 'Test')
        ->set('email', 'test@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'differentpassword')
        ->call('save')
        ->assertHasErrors(['password']);
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=AdminUserCreate`

**Step 3: Create the page**

Create `resources/views/pages/admin/users/⚡create.blade.php`:

```blade
<?php

use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Create User')] class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_super_admin = false;

    public function save(): void
    {
        $validated = $this->validate();

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_super_admin' => $this->is_super_admin,
        ]);

        session()->flash('status', __('User created successfully.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-lg">
    <flux:heading size="xl">{{ __('Create User') }}</flux:heading>
    <flux:subheading>{{ __('Add a new user to the system.') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:input
            wire:model="name"
            :label="__('Name')"
            required
            autofocus
        />

        <flux:input
            wire:model="email"
            type="email"
            :label="__('Email')"
            required
        />

        <flux:input
            wire:model="password"
            type="password"
            :label="__('Password')"
            required
        />

        <flux:input
            wire:model="password_confirmation"
            type="password"
            :label="__('Confirm Password')"
            required
        />

        <flux:switch
            wire:model="is_super_admin"
            :label="__('Super Admin')"
            :description="__('Grant full administrative access.')"
        />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">
                {{ __('Create User') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</div>
```

**Step 4: Run tests**

Run: `php artisan test --compact --filter=AdminUserCreate`
Expected: All pass.

**Step 5: Run Pint and PHPStan**

Run: `vendor/bin/pint --dirty --format agent`
Run: `vendor/bin/phpstan analyse --memory-limit=512M`

**Step 6: Commit**

```
feat: add admin create user page with validation
```

---

### Task 6: Edit user page

**Files:**
- Create: `resources/views/pages/admin/users/⚡edit.blade.php`
- Create: `tests/Feature/Admin/AdminUserEditTest.php`

**Step 1: Write tests**

Create `tests/Feature/Admin/AdminUserEditTest.php`:

```php
<?php

use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access edit user page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.edit', $user))
        ->assertForbidden();
});

test('admin can see edit user form', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->assertSet('name', $user->name)
        ->assertSet('email', $user->email);
});

test('admin can update a user', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->set('name', 'Updated Name')
        ->set('email', 'updated@example.com')
        ->call('save')
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

test('admin can toggle super admin status', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->set('is_super_admin', true)
        ->call('save');

    expect($user->fresh()->is_super_admin)->toBeTrue();
});

test('admin cannot demote themselves', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $admin])
        ->set('is_super_admin', false)
        ->call('save');

    expect($admin->fresh()->is_super_admin)->toBeTrue();
});

test('admin can update password', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();
    $oldPassword = $user->password;

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->set('password', 'newpassword123')
        ->set('password_confirmation', 'newpassword123')
        ->call('save');

    expect($user->fresh()->password)->not->toBe($oldPassword);
});

test('admin can save without changing password', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();
    $oldPassword = $user->password;

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->set('name', 'New Name')
        ->call('save');

    expect($user->fresh()->password)->toBe($oldPassword);
    expect($user->fresh()->name)->toBe('New Name');
});

test('edit user validates unique email ignoring current user', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();
    $other = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->set('email', $other->email)
        ->call('save')
        ->assertHasErrors(['email']);
});

test('edit user allows keeping same email', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $user])
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors(['email']);
});

test('super admin toggle is disabled when editing yourself', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.edit', ['user' => $admin])
        ->assertSet('isSelf', true);
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=AdminUserEdit`

**Step 3: Create the page**

Create `resources/views/pages/admin/users/⚡edit.blade.php`:

```blade
<?php

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit User')] class extends Component {
    public User $user;

    public string $name = '';

    public string $email = '';

    public bool $is_super_admin = false;

    public string $password = '';

    public string $password_confirmation = '';

    public bool $isSelf = false;

    public function mount(User $user): void
    {
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_super_admin = $user->is_super_admin;
        $this->isSelf = $user->id === auth()->id();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $this->user->name = $validated['name'];
        $this->user->email = $validated['email'];

        if (! $this->isSelf) {
            $this->user->is_super_admin = $this->is_super_admin;
        }

        if (! empty($validated['password'])) {
            $this->user->password = $validated['password'];
        }

        $this->user->save();

        session()->flash('status', __('User updated successfully.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function deleteUser(): void
    {
        if ($this->isSelf) {
            return;
        }

        $this->user->delete();

        session()->flash('status', __('User deleted.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-lg">
    <flux:heading size="xl">{{ __('Edit User') }}</flux:heading>
    <flux:subheading>{{ $user->name }} ({{ $user->email }})</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:input
            wire:model="name"
            :label="__('Name')"
            required
            autofocus
        />

        <flux:input
            wire:model="email"
            type="email"
            :label="__('Email')"
            required
        />

        <flux:switch
            wire:model="is_super_admin"
            :label="__('Super Admin')"
            :description="__('Grant full administrative access.')"
            :disabled="$isSelf"
        />

        <flux:separator />

        <flux:heading size="lg">{{ __('Change Password') }}</flux:heading>
        <flux:subheading>{{ __('Leave blank to keep the current password.') }}</flux:subheading>

        <flux:input
            wire:model="password"
            type="password"
            :label="__('New Password')"
        />

        <flux:input
            wire:model="password_confirmation"
            type="password"
            :label="__('Confirm New Password')"
        />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">
                {{ __('Save Changes') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>

    @unless ($isSelf)
        <flux:separator class="my-6" />

        <div class="flex items-center justify-between rounded-lg border border-red-200 p-4 dark:border-red-900">
            <div>
                <flux:heading>{{ __('Delete User') }}</flux:heading>
                <flux:subheading>{{ __('Permanently delete this user and all their events.') }}</flux:subheading>
            </div>
            <flux:button
                variant="danger"
                wire:click="deleteUser"
                wire:confirm="{{ __('Delete this user? This will permanently remove the user and all their events. This cannot be undone.') }}"
            >
                {{ __('Delete User') }}
            </flux:button>
        </div>
    @endunless
</div>
```

**Step 4: Run tests**

Run: `php artisan test --compact --filter=AdminUserEdit`
Expected: All pass.

**Step 5: Run Pint and PHPStan**

Run: `vendor/bin/pint --dirty --format agent`
Run: `vendor/bin/phpstan analyse --memory-limit=512M`

**Step 6: Commit**

```
feat: add admin edit user page with self-protection
```

---

### Task 7: Event list page with search, pagination, and delete

**Files:**
- Create: `resources/views/pages/admin/events/⚡index.blade.php`
- Create: `tests/Feature/Admin/AdminEventListTest.php`

**Step 1: Write tests**

Create `tests/Feature/Admin/AdminEventListTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access event list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.events.index'))
        ->assertForbidden();
});

test('admin can see event list', function () {
    $admin = User::factory()->superAdmin()->create();
    $events = Event::factory()->count(3)->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.index')
        ->assertSee($events[0]->name)
        ->assertSee($events[1]->name)
        ->assertSee($events[2]->name);
});

test('event list shows host name', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create(['name' => 'Jane Host']);
    Event::factory()->create(['user_id' => $host->id]);

    Livewire::actingAs($admin)
        ->test('pages::admin.events.index')
        ->assertSee('Jane Host');
});

test('event list is searchable', function () {
    $admin = User::factory()->superAdmin()->create();
    Event::factory()->create(['name' => 'Monday Trivia']);
    Event::factory()->create(['name' => 'Friday Quiz']);

    Livewire::actingAs($admin)
        ->test('pages::admin.events.index')
        ->set('search', 'Monday')
        ->assertSee('Monday Trivia')
        ->assertDontSee('Friday Quiz');
});

test('admin can delete an event', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.index')
        ->call('deleteEvent', $event->id);

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});

test('event list has link to create event', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.index')
        ->assertSeeHtml(route('admin.events.create'));
});

test('event list shows active and ended badges', function () {
    $admin = User::factory()->superAdmin()->create();
    Event::factory()->create(['name' => 'Active Event']);
    Event::factory()->ended()->create(['name' => 'Ended Event']);

    Livewire::actingAs($admin)
        ->test('pages::admin.events.index')
        ->assertSee(__('Active'))
        ->assertSee(__('Ended'));
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=AdminEventList`

**Step 3: Create event list page**

Create `resources/views/pages/admin/events/⚡index.blade.php`:

```blade
<?php

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Events')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function events()
    {
        return Event::query()
            ->with('user')
            ->withCount('teams')
            ->when($this->search, fn ($query, $search) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            )
            ->latest()
            ->paginate(15);
    }

    public function deleteEvent(int $eventId): void
    {
        Event::findOrFail($eventId)->delete();

        Flux::toast(__('Event deleted.'));
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Events') }}</flux:heading>
        <flux:button variant="primary" :href="route('admin.events.create')" wire:navigate>
            {{ __('Create Event') }}
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by event name or host...') }}" icon="magnifying-glass" />

    <flux:table :paginate="$this->events">
        <flux:table.columns>
            <flux:table.column>{{ __('Event') }}</flux:table.column>
            <flux:table.column>{{ __('Host') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Scheduled') }}</flux:table.column>
            <flux:table.column>{{ __('Teams') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->events as $event)
                <flux:table.row :key="$event->id">
                    <flux:table.cell variant="strong">{{ $event->name }}</flux:table.cell>
                    <flux:table.cell>{{ $event->user->name }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($event->isActive())
                            <flux:badge color="green" size="sm" inset="top bottom">{{ __('Active') }}</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm" inset="top bottom">{{ __('Ended') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $event->starts_at->format('M j, Y g:i A') }}</flux:table.cell>
                    <flux:table.cell>{{ $event->teams_count }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil" :href="route('admin.events.edit', $event)" wire:navigate />
                            <flux:button size="sm" variant="ghost" :href="route('events.show', $event)" wire:navigate>
                                {{ __('Manage') }}
                            </flux:button>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                wire:click="deleteEvent({{ $event->id }})"
                                wire:confirm="{{ __('Delete this event? This will permanently remove the event and all its data. This cannot be undone.') }}"
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
```

**Step 4: Run tests**

Run: `php artisan test --compact --filter=AdminEventList`
Expected: All pass.

**Step 5: Run Pint and PHPStan**

Run: `vendor/bin/pint --dirty --format agent`
Run: `vendor/bin/phpstan analyse --memory-limit=512M`

**Step 6: Commit**

```
feat: add admin event list page with search, pagination, and delete
```

---

### Task 8: Create event page (admin version)

**Files:**
- Create: `resources/views/pages/admin/events/⚡create.blade.php`
- Create: `tests/Feature/Admin/AdminEventCreateTest.php`

**Step 1: Write tests**

Create `tests/Feature/Admin/AdminEventCreateTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access admin create event page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.events.create'))
        ->assertForbidden();
});

test('admin can see create event form with user dropdown', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create(['name' => 'Available Host']);

    Livewire::actingAs($admin)
        ->test('pages::admin.events.create')
        ->assertSee('Available Host');
});

test('admin can create an event assigned to any user', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.create')
        ->set('name', 'New Trivia Night')
        ->set('starts_at', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('user_id', $host->id)
        ->call('save')
        ->assertRedirect(route('admin.events.index'));

    $this->assertDatabaseHas('events', [
        'name' => 'New Trivia Night',
        'user_id' => $host->id,
    ]);
});

test('admin create event validates required fields', function () {
    $admin = User::factory()->superAdmin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.create')
        ->call('save')
        ->assertHasErrors(['name', 'starts_at', 'user_id']);
});

test('admin create event generates slug automatically', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.create')
        ->set('name', 'Friday Night Trivia')
        ->set('starts_at', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('user_id', $host->id)
        ->call('save');

    $event = Event::where('name', 'Friday Night Trivia')->first();
    expect($event->slug)->toBe('friday-night-trivia');
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=AdminEventCreate`

**Step 3: Create the page**

Create `resources/views/pages/admin/events/⚡create.blade.php`:

```blade
<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Event')] class extends Component {
    public string $name = '';

    public string $starts_at = '';

    public ?int $user_id = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'user_id' => ['required', 'exists:users,id'],
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, User> */
    #[Computed]
    public function users()
    {
        return User::query()->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function save(): void
    {
        $validated = $this->validate();

        Event::create([
            'name' => $validated['name'],
            'slug' => Event::generateSlug($validated['name']),
            'starts_at' => $validated['starts_at'],
            'user_id' => $validated['user_id'],
        ]);

        session()->flash('status', __('Event created successfully.'));

        $this->redirect(route('admin.events.index'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-lg">
    <flux:heading size="xl">{{ __('Create Event') }}</flux:heading>
    <flux:subheading>{{ __('Create a new trivia event and assign it to a host.') }}</flux:subheading>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:input
            wire:model="name"
            :label="__('Event Name')"
            :placeholder="__('Tuesday Trivia at Joe\'s')"
            required
            autofocus
        />

        <flux:input
            wire:model="starts_at"
            type="datetime-local"
            :label="__('Scheduled Start')"
            required
        />

        <flux:select wire:model="user_id" :label="__('Host')" :placeholder="__('Select a user...')">
            @foreach ($this->users as $user)
                <flux:select.option :value="$user->id">{{ $user->name }} ({{ $user->email }})</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">
                {{ __('Create Event') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('admin.events.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</div>
```

**Step 4: Run tests**

Run: `php artisan test --compact --filter=AdminEventCreate`
Expected: All pass.

**Step 5: Run Pint and PHPStan**

Run: `vendor/bin/pint --dirty --format agent`
Run: `vendor/bin/phpstan analyse --memory-limit=512M`

**Step 6: Commit**

```
feat: add admin create event page with host assignment
```

---

### Task 9: Edit event page

**Files:**
- Create: `resources/views/pages/admin/events/⚡edit.blade.php`
- Create: `tests/Feature/Admin/AdminEventEditTest.php`

**Step 1: Write tests**

Create `tests/Feature/Admin/AdminEventEditTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

test('non-admin cannot access edit event page', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.events.edit', $event))
        ->assertForbidden();
});

test('admin can see edit event form', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->assertSet('name', $event->name)
        ->assertSet('user_id', $event->user_id);
});

test('admin can update event details', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();
    $newHost = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->set('name', 'Updated Event Name')
        ->set('user_id', $newHost->id)
        ->call('save')
        ->assertRedirect(route('admin.events.index'));

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'name' => 'Updated Event Name',
        'user_id' => $newHost->id,
    ]);
});

test('admin can end an active event', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->call('endEvent');

    expect($event->fresh()->ended_at)->not->toBeNull();
});

test('admin can reopen an ended event', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->ended()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->call('reopenEvent');

    expect($event->fresh()->ended_at)->toBeNull();
});

test('admin can delete an event from edit page', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->call('deleteEvent')
        ->assertRedirect(route('admin.events.index'));

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});

test('edit event validates required fields', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('edit event has links to manage scoring and teams', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.events.edit', ['event' => $event])
        ->assertSeeHtml(route('events.show', $event))
        ->assertSeeHtml(route('events.teams', $event));
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=AdminEventEdit`

**Step 3: Create the page**

Create `resources/views/pages/admin/events/⚡edit.blade.php`:

```blade
<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Event')] class extends Component {
    public Event $event;

    public string $name = '';

    public string $starts_at = '';

    public ?int $user_id = null;

    public function mount(Event $event): void
    {
        $this->name = $event->name;
        $this->starts_at = $event->starts_at->format('Y-m-d\TH:i');
        $this->user_id = $event->user_id;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'user_id' => ['required', 'exists:users,id'],
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, User> */
    #[Computed]
    public function users()
    {
        return User::query()->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $this->event->update([
            'name' => $validated['name'],
            'starts_at' => $validated['starts_at'],
            'user_id' => $validated['user_id'],
        ]);

        session()->flash('status', __('Event updated successfully.'));

        $this->redirect(route('admin.events.index'), navigate: true);
    }

    public function endEvent(): void
    {
        $this->event->update(['ended_at' => now()]);
    }

    public function reopenEvent(): void
    {
        $this->event->update(['ended_at' => null]);
    }

    public function deleteEvent(): void
    {
        $this->event->delete();

        session()->flash('status', __('Event deleted.'));

        $this->redirect(route('admin.events.index'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-lg">
    <flux:heading size="xl">{{ __('Edit Event') }}</flux:heading>
    <flux:subheading>{{ $event->name }}</flux:subheading>

    <div class="mt-4 flex items-center gap-2">
        @if ($event->isActive())
            <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
        @else
            <flux:badge color="zinc" size="sm">{{ __('Ended') }}</flux:badge>
        @endif

        @if ($event->isActive())
            <flux:button size="sm" variant="ghost" wire:click="endEvent" wire:confirm="{{ __('End this event?') }}">
                {{ __('End Event') }}
            </flux:button>
        @else
            <flux:button size="sm" variant="ghost" wire:click="reopenEvent">
                {{ __('Reopen Event') }}
            </flux:button>
        @endif
    </div>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:input
            wire:model="name"
            :label="__('Event Name')"
            required
            autofocus
        />

        <flux:input
            wire:model="starts_at"
            type="datetime-local"
            :label="__('Scheduled Start')"
            required
        />

        <flux:select wire:model="user_id" :label="__('Host')" :placeholder="__('Select a user...')">
            @foreach ($this->users as $user)
                <flux:select.option :value="$user->id">{{ $user->name }} ({{ $user->email }})</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">
                {{ __('Save Changes') }}
            </flux:button>
            <flux:button variant="ghost" :href="route('admin.events.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>

    <flux:separator class="my-6" />

    <div class="flex items-center gap-2">
        <flux:button variant="ghost" :href="route('events.show', $event)" wire:navigate icon="cog">
            {{ __('Manage Details') }}
        </flux:button>
        <flux:button variant="ghost" :href="route('events.teams', $event)" wire:navigate icon="users">
            {{ __('Manage Teams') }}
        </flux:button>
    </div>

    <flux:separator class="my-6" />

    <div class="flex items-center justify-between rounded-lg border border-red-200 p-4 dark:border-red-900">
        <div>
            <flux:heading>{{ __('Delete Event') }}</flux:heading>
            <flux:subheading>{{ __('Permanently delete this event and all its data.') }}</flux:subheading>
        </div>
        <flux:button
            variant="danger"
            wire:click="deleteEvent"
            wire:confirm="{{ __('Delete this event? This will permanently remove the event and all its data. This cannot be undone.') }}"
        >
            {{ __('Delete Event') }}
        </flux:button>
    </div>
</div>
```

**Step 4: Run tests**

Run: `php artisan test --compact --filter=AdminEventEdit`
Expected: All pass.

**Step 5: Run Pint and PHPStan**

Run: `vendor/bin/pint --dirty --format agent`
Run: `vendor/bin/phpstan analyse --memory-limit=512M`

**Step 6: Commit**

```
feat: add admin edit event page with status controls
```

---

### Task 10: Final integration test and cleanup

**Files:**
- Modify: `tests/Feature/Admin/AdminMiddlewareTest.php` (add tests for new routes)
- Run all tests

**Step 1: Add middleware tests for new routes**

Add to `tests/Feature/Admin/AdminMiddlewareTest.php`:

```php
test('regular user gets 403 on all admin routes', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.users.edit', $user))->assertForbidden();
    $this->actingAs($user)->get(route('admin.events.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.events.create'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.events.edit', $event))->assertForbidden();
});
```

**Step 2: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests pass.

**Step 3: Run Pint on all modified files**

Run: `vendor/bin/pint --dirty --format agent`

**Step 4: Run PHPStan**

Run: `vendor/bin/phpstan analyse --memory-limit=512M`

**Step 5: Run IDE helper**

Run: `php artisan ide-helper:models --write-mixin --no-interaction`

**Step 6: Commit**

```
test: add middleware tests for all admin routes
```
