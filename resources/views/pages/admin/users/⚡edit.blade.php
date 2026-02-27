<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
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
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_super_admin = $user->is_super_admin;
        $this->isSelf = $user->id === auth()->id();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $this->user->name = $this->name;
        $this->user->email = $this->email;

        if (! $this->isSelf) {
            $this->user->is_super_admin = $this->is_super_admin;
        }

        if (! empty($this->password)) {
            $this->user->password = $this->password;
        }

        $this->user->save();

        Flux::toast(__('User updated successfully.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function deleteUser(): void
    {
        if ($this->isSelf) {
            return;
        }

        $this->user->delete();

        Flux::toast(__('User deleted.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-lg space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Edit User') }}</flux:heading>
        <flux:subheading>{{ $user->name }} &mdash; {{ $user->email }}</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="name" label="{{ __('Name') }}" />
        <flux:input wire:model="email" label="{{ __('Email') }}" type="email" />

        <flux:switch wire:model="is_super_admin" label="{{ __('Super Admin') }}" description="{{ __('Grant this user full administrative access.') }}" :disabled="$isSelf" />

        <flux:separator />

        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Change Password') }}</flux:heading>
            <flux:subheading>{{ __('Leave blank to keep the current password.') }}</flux:subheading>
            <flux:input wire:model="password" label="{{ __('New Password') }}" type="password" />
            <flux:input wire:model="password_confirmation" label="{{ __('Confirm New Password') }}" type="password" />
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
            <flux:button :href="route('admin.users.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>

    @unless ($isSelf)
        <flux:separator />

        <flux:card class="border-red-200 dark:border-red-800">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading>{{ __('Danger Zone') }}</flux:heading>
                    <flux:subheading>{{ __('Permanently delete this user account.') }}</flux:subheading>
                </div>
                <flux:button
                    variant="danger"
                    wire:click="deleteUser"
                    wire:confirm="{{ __('Are you sure you want to delete this user? This action cannot be undone.') }}"
                >
                    {{ __('Delete User') }}
                </flux:button>
            </div>
        </flux:card>
    @endunless
</div>
