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
        $this->validate();

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'is_super_admin' => $this->is_super_admin,
        ]);

        session()->flash('status', __('User created successfully.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-lg space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Create User') }}</flux:heading>
        <flux:subheading>{{ __('Add a new user to the system.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="name" label="{{ __('Name') }}" placeholder="{{ __('Full name') }}" />
        <flux:input wire:model="email" label="{{ __('Email') }}" type="email" placeholder="{{ __('Email address') }}" />
        <flux:input wire:model="password" label="{{ __('Password') }}" type="password" />
        <flux:input wire:model="password_confirmation" label="{{ __('Confirm Password') }}" type="password" />

        <flux:switch wire:model="is_super_admin" label="{{ __('Super Admin') }}" description="{{ __('Grant this user full administrative access.') }}" />

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Create User') }}</flux:button>
            <flux:button :href="route('admin.users.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
