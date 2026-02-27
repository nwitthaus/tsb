<x-layouts::auth :title="__('Two-Factor Authentication')">
    <div class="flex flex-col gap-6">
        <div
            class="relative h-auto w-full"
            x-cloak
            x-data="{
                showRecoveryInput: @js($errors->has('recovery_code')),
                code: '',
                recovery_code: '',
                toggleInput() {
                    this.showRecoveryInput = !this.showRecoveryInput;

                    this.code = '';
                    this.recovery_code = '';

                    $dispatch('clear-2fa-auth-code');

                    $nextTick(() => {
                        this.showRecoveryInput
                            ? this.$refs.recovery_code?.focus()
                            : $dispatch('focus-2fa-auth-code');
                    });
                },
            }"
        >
            <div x-show="!showRecoveryInput">
                <x-auth-header
                    :title="__('Authentication Code')"
                    :description="__('Enter the authentication code provided by your authenticator application.')"
                />
            </div>

            <div x-show="showRecoveryInput">
                <x-auth-header
                    :title="__('Recovery Code')"
                    :description="__('Please confirm access to your account by entering one of your emergency recovery codes.')"
                />
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}">
                @csrf

                <div class="mt-6 flex flex-col gap-5 text-center">
                    <div x-show="!showRecoveryInput">
                        <div class="my-2 flex items-center justify-center">
                            <flux:otp
                                x-model="code"
                                length="6"
                                name="code"
                                label="OTP Code"
                                label:sr-only
                                class="mx-auto"
                            />
                        </div>
                        @error('code')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="showRecoveryInput">
                        <div class="my-2">
                            <input
                                type="text"
                                name="recovery_code"
                                x-ref="recovery_code"
                                x-bind:required="showRecoveryInput"
                                autocomplete="one-time-code"
                                x-model="recovery_code"
                                placeholder="{{ __('Recovery code') }}"
                                class="block h-11 w-full border-b border-b-[#D4D4D4] bg-transparent px-0.5 font-grotesk text-sm text-[#141414] placeholder-[#B0B0B0] outline-none transition-colors focus:border-b-red-600"
                            />
                        </div>

                        @error('recovery_code')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="flex h-11 w-full items-center justify-center border-2 border-red-600 bg-red-600 font-heading text-[13px] font-bold uppercase tracking-[0.1em] text-white transition-colors hover:border-red-700 hover:bg-red-700">
                        {{ __('Continue') }}
                    </button>
                </div>

                <div class="mt-5 text-center text-sm leading-5">
                    <span class="text-[#7A7A7A]">{{ __('or you can') }}</span>
                    <span class="cursor-pointer font-semibold text-red-600 hover:text-red-700">
                        <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('login using a recovery code') }}</span>
                        <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('login using an authentication code') }}</span>
                    </span>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
