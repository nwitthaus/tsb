<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <x-auth-input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div>
                <div class="flex items-baseline justify-between">
                    <label for="password" class="block font-grotesk text-[11px] font-medium uppercase tracking-[0.15em] text-[#7A7A7A]">
                        {{ __('Password') }}
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-[11px] font-semibold text-red-600 hover:text-red-700" wire:navigate>
                            {{ __('Forgot?') }}
                        </a>
                    @endif
                </div>
                <div x-data="{ show: false }" class="relative mt-1.5">
                    <input
                        id="password"
                        name="password"
                        x-bind:type="show ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="{{ __('Password') }}"
                        class="block h-11 w-full border-b border-b-[#D4D4D4] bg-transparent px-0.5 pr-9 font-grotesk text-sm text-[#141414] placeholder-[#B0B0B0] outline-none transition-colors focus:border-b-red-600"
                    />
                    <button type="button" @click="show = !show" class="absolute right-0 top-1/2 -translate-y-1/2 p-1 text-[#7A7A7A] transition-colors hover:text-[#141414]">
                        <svg x-show="!show" class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg x-show="show" x-cloak class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <label class="flex cursor-pointer items-center gap-2.5">
                <div class="relative flex items-center">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} class="peer size-4 cursor-pointer appearance-none border border-[#141414] bg-transparent transition-colors checked:bg-[#141414]" />
                    <svg class="pointer-events-none absolute inset-0 m-auto size-2.5 text-white opacity-0 peer-checked:opacity-100" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
                <span class="font-grotesk text-xs text-[#7A7A7A]">{{ __('Remember me') }}</span>
            </label>

            <button type="submit" class="flex h-11 w-full items-center justify-center border-2 border-red-600 bg-red-600 font-heading text-[13px] font-bold uppercase tracking-[0.1em] text-white transition-colors hover:border-red-700 hover:bg-red-700" data-test="login-button">
                {{ __('Log in') }}
            </button>
        </form>

        @if (Route::has('register'))
            <div class="text-center text-sm text-[#7A7A7A]">
                <span>{{ __('Don\'t have an account?') }}</span>
                <a href="{{ route('register') }}" class="font-semibold text-red-600 hover:text-red-700" wire:navigate>{{ __('Sign up') }}</a>
            </div>
        @endif
    </div>
</x-layouts::auth>
