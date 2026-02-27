<x-layouts::auth :title="__('Email verification')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Verify your email')"
            :description="__('Please verify your email address by clicking on the link we just emailed to you.')"
        />

        @if (session('status') == 'verification-link-sent')
            <p class="text-center text-sm font-medium text-green-600">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </p>
        @endif

        <div class="flex flex-col items-center gap-3">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                @csrf
                <button type="submit" class="flex h-11 w-full items-center justify-center border-2 border-red-600 bg-red-600 font-heading text-[13px] font-bold uppercase tracking-[0.1em] text-white transition-colors hover:border-red-700 hover:bg-red-700">
                    {{ __('Resend verification email') }}
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="font-grotesk text-sm font-semibold text-red-600 transition-colors hover:text-red-700" data-test="logout-button">
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</x-layouts::auth>
