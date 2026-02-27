<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <x-auth-input
                name="email"
                :label="__('Email address')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <button type="submit" class="flex h-11 w-full items-center justify-center border-2 border-red-600 bg-red-600 font-heading text-[13px] font-bold uppercase tracking-[0.1em] text-white transition-colors hover:border-red-700 hover:bg-red-700" data-test="email-password-reset-link-button">
                {{ __('Email password reset link') }}
            </button>
        </form>

        <div class="text-center text-sm text-[#7A7A7A]">
            <span>{{ __('Or, return to') }}</span>
            <a href="{{ route('login') }}" class="font-semibold text-red-600 hover:text-red-700" wire:navigate>{{ __('log in') }}</a>
        </div>
    </div>
</x-layouts::auth>
