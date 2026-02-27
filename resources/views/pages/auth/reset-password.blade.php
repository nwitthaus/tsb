<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Reset password')" :description="__('Please enter your new password below')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <x-auth-input
                name="email"
                :value="request('email')"
                :label="__('Email')"
                type="email"
                required
                autocomplete="email"
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

            <button type="submit" class="flex h-11 w-full items-center justify-center border-2 border-red-600 bg-red-600 font-heading text-[13px] font-bold uppercase tracking-[0.1em] text-white transition-colors hover:border-red-700 hover:bg-red-700" data-test="reset-password-button">
                {{ __('Reset password') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
