<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Name -->
            <x-auth-input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <x-auth-input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <x-auth-password-input
                name="password"
                :label="__('Password')"
                :placeholder="__('Password')"
                autocomplete="new-password"
            />

            <!-- Confirm Password -->
            <x-auth-password-input
                name="password_confirmation"
                :label="__('Confirm password')"
                :placeholder="__('Confirm password')"
                autocomplete="new-password"
            />

            <button type="submit" class="flex h-11 w-full items-center justify-center border-2 border-red-600 bg-red-600 font-heading text-[13px] font-bold uppercase tracking-[0.1em] text-white transition-colors hover:border-red-700 hover:bg-red-700" data-test="register-user-button">
                {{ __('Create account') }}
            </button>
        </form>

        <div class="text-center text-sm text-[#7A7A7A]">
            <span>{{ __('Already have an account?') }}</span>
            <a href="{{ route('login') }}" class="font-semibold text-red-600 hover:text-red-700" wire:navigate>{{ __('Log in') }}</a>
        </div>
    </div>
</x-layouts::auth>
