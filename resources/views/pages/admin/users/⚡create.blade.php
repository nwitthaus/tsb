<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create User')] class extends Component {
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_super_admin = false;

    public string $organization_id = '';

    public string $organization_role = OrganizationRole::Scorekeeper->value;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'organization_id' => 'nullable|exists:organizations,id',
            'organization_role' => ['required_with:organization_id', Rule::enum(OrganizationRole::class)],
        ];
    }

    /** @return Collection<int, Organization> */
    #[Computed]
    public function organizations(): Collection
    {
        return Organization::query()->orderBy('name')->get(['id', 'name']);
    }

    public function save(): void
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'is_super_admin' => $this->is_super_admin,
        ]);

        if ($this->organization_id) {
            $user->organizations()->attach($this->organization_id, [
                'role' => $this->organization_role,
            ]);
        }

        Flux::toast(__('User created successfully.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<div class="max-w-lg space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Create User') }}</flux:heading>
        <flux:subheading>{{ __('Add a new user to the system.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="name" :label="__('Name')" :placeholder="__('Full name')" />
        <flux:input wire:model="email" :label="__('Email')" type="email" :placeholder="__('Email address')" />
        <flux:input wire:model="password" :label="__('Password')" type="password" />
        <flux:input wire:model="password_confirmation" :label="__('Confirm Password')" type="password" />

        <flux:switch wire:model="is_super_admin" :label="__('Super Admin')" :description="__('Grant this user full administrative access.')" />

        <flux:separator />

        <flux:select wire:model.live="organization_id" :label="__('Organization')" :placeholder="__('None')" :description="__('Optionally assign this user to an organization.')">
            @foreach ($this->organizations as $org)
                <flux:select.option :value="$org->id">{{ $org->name }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($organization_id)
            <flux:select wire:model="organization_role" :label="__('Organization Role')">
                @foreach (App\Enums\OrganizationRole::cases() as $role)
                    <flux:select.option :value="$role->value">{{ __(ucfirst($role->value)) }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.users.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" type="submit">{{ __('Create User') }}</flux:button>
        </div>
    </form>
</div>
