@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="font-heading text-[22px] font-semibold uppercase tracking-[0.08em]">{{ $title }}</h1>
    <p class="mt-2 font-grotesk text-[13px] text-[#7A7A7A]">{{ $description }}</p>
</div>
