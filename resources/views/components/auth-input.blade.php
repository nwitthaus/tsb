@props([
    'name',
    'label',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'autofocus' => false,
    'autocomplete' => null,
])

<div>
    <label for="{{ $name }}" class="block font-grotesk text-[11px] font-medium uppercase tracking-[0.15em] text-[#7A7A7A]">
        {{ $label }}
    </label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if($autofocus) autofocus @endif
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        class="mt-1.5 block h-11 w-full border-b border-b-[#D4D4D4] bg-transparent px-0.5 font-grotesk text-sm text-[#141414] placeholder-[#B0B0B0] outline-none transition-colors focus:border-b-red-600"
    />
    @error($name)
        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
