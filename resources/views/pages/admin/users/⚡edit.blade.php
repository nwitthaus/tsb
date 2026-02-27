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

<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.users.index')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $user->name }}</flux:heading>
            <flux:subheading>{{ $user->email }}</flux:subheading>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Account Details --}}
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Account Details') }}</flux:heading>
                <flux:subheading>{{ __('Update the user\'s name and email address.') }}</flux:subheading>
            </div>
            <div class="space-y-6 bg-white p-5 dark:bg-zinc-900">
                <flux:input wire:model="name" :label="__('Name')" />
                <flux:input wire:model="email" :label="__('Email')" type="email" />
            </div>
        </div>

        {{-- Permissions --}}
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Permissions') }}</flux:heading>
                <flux:subheading>{{ __('Configure administrative access.') }}</flux:subheading>
            </div>
            <div class="bg-white p-5 dark:bg-zinc-900">
                <flux:switch wire:model="is_super_admin" :label="__('Super Admin')" :description="__('Grant this user full administrative access.')" :disabled="$isSelf" />
            </div>
        </div>

        {{-- Change Password --}}
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Change Password') }}</flux:heading>
                <flux:subheading>{{ __('Leave blank to keep the current password.') }}</flux:subheading>
            </div>
            <div class="space-y-6 bg-white p-5 dark:bg-zinc-900">
                <flux:input wire:model="password" :label="__('New Password')" type="password" />
                <flux:input wire:model="password_confirmation" :label="__('Confirm New Password')" type="password" />
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.users.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" type="submit">{{ __('Save Changes') }}</flux:button>
        </div>
    </form>

    {{-- Danger Zone --}}
    @unless ($isSelf)
        <div class="overflow-hidden rounded-lg border border-red-200 dark:border-red-900">
            <div class="border-b border-red-200 bg-red-50 px-5 py-4 dark:border-red-900 dark:bg-red-950/30">
                <flux:heading size="lg" class="!text-red-700 dark:!text-red-400">{{ __('Danger Zone') }}</flux:heading>
                <flux:subheading class="!text-red-500/80">{{ __('Irreversible actions that affect this user.') }}</flux:subheading>
            </div>
            <div class="flex flex-col items-start justify-between gap-3 bg-white p-5 sm:flex-row sm:items-center dark:bg-zinc-900">
                <div>
                    <flux:heading>{{ __('Delete User') }}</flux:heading>
                    <flux:subheading>{{ __('Permanently delete this user account.') }}</flux:subheading>
                </div>
                <flux:button
                    variant="danger"
                    size="sm"
                    class="shrink-0"
                    wire:click="deleteUser"
                    wire:confirm="{{ __('Are you sure you want to delete this user? This action cannot be undone.') }}"
                >
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    @endunless
</div>
