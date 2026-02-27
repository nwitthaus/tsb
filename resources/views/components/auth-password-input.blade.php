@props([
    'name' => 'password',
    'label' => 'Password',
    'placeholder' => 'Password',
    'autocomplete' => 'current-password',
    'required' => true,
])

<div x-data="{ show: false }">
    <label for="{{ $name }}" class="block font-grotesk text-[11px] font-medium uppercase tracking-[0.15em] text-[#7A7A7A]">
        {{ $label }}
    </label>
    <div class="relative mt-1.5">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            x-bind:type="show ? 'text' : 'password'"
            @if($required) required @endif
            autocomplete="{{ $autocomplete }}"
            placeholder="{{ $placeholder }}"
            class="block h-11 w-full border-b border-b-[#D4D4D4] bg-transparent px-0.5 pr-9 font-grotesk text-sm text-[#141414] placeholder-[#B0B0B0] outline-none transition-colors focus:border-b-red-600"
        />
        <button type="button" @click="show = !show" class="absolute right-0 top-1/2 -translate-y-1/2 p-1 text-[#7A7A7A] transition-colors hover:text-[#141414]">
            {{-- Eye icon (show password) --}}
            <svg x-show="!show" class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
            {{-- Eye-slash icon (hide password) --}}
            <svg x-show="show" x-cloak class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>
        </button>
    </div>
    @error($name)
        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
