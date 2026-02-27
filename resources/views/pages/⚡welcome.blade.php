<?php

use App\Models\Event;
use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts.public')]
#[Title('Trivia Scoreboard')]
class extends Component {
    #[Validate('required|string')]
    public string $code = '';

    public function join(): void
    {
        $this->validate();

        $event = Event::where('slug', $this->code)->first();

        if (! $event) {
            $this->addError('code', 'No event found with that join code.');

            return;
        }

        $this->redirect('/'.$event->slug, navigate: true);
    }

    #[Computed]
    public function eventCount(): int
    {
        return Event::count();
    }

    #[Computed]
    public function teamCount(): int
    {
        return Team::count();
    }
}; ?>

<div class="bg-grid-subtle relative min-h-screen overflow-hidden bg-[#F2F2F0] font-grotesk text-[#141414]">
    {{-- Diagonal accent stripes --}}
    <div class="pointer-events-none absolute -top-12 right-20 h-[300px] w-[3px] rotate-[-20deg] bg-red-600/50"></div>
    <div class="pointer-events-none absolute -top-8 right-[94px] h-[280px] w-px rotate-[-20deg] bg-red-600/30"></div>

    {{-- Top bar --}}
    <div class="relative border-b-2 border-[#141414] py-3.5 text-center">
        <span class="font-heading text-xs font-bold uppercase tracking-[0.2em]">Trivia Scoreboard</span>
        <span class="ml-3 bg-red-600 px-2 py-0.5 text-[10px] font-bold tracking-[0.1em] text-white">LIVE</span>
    </div>

    {{-- Hero --}}
    <div class="relative mx-auto max-w-[640px] px-8 pb-12 pt-[72px] text-center">
        {{-- Section label --}}
        <div class="mb-5 text-[10px] font-medium uppercase tracking-[0.25em] text-[#7A7A7A]">
            &mdash;&mdash; Real-time Scoring &mdash;&mdash;
        </div>

        {{-- Heading --}}
        <h1 class="font-heading text-[56px] font-bold uppercase leading-[0.95] tracking-tight sm:text-[64px]">
            TRIVIA<br>
            <span class="text-red-600">SCORE</span>BOARD
        </h1>

        <p class="mx-auto mt-5 max-w-[360px] text-[15px] leading-relaxed text-[#7A7A7A]">
            Free, fast, no app needed. Enter a code and your audience scores along in real time.
        </p>

        {{-- Join form in bordered frame --}}
        <form wire:submit="join" class="mx-auto mt-11 max-w-[440px] border-2 border-[#141414] bg-white p-6">
            <div class="flex">
                <input
                    wire:model="code"
                    type="text"
                    placeholder="ENTER CODE"
                    maxlength="20"
                    autofocus
                    class="flex-1 border-2 border-r-0 border-[#141414] bg-transparent px-4 py-3.5 font-mono text-[13px] uppercase tracking-[0.1em] text-[#141414] placeholder-[#B0B0B0] outline-none transition-colors focus:border-red-600 focus:border-r-0"
                />
                <button
                    type="submit"
                    class="border-2 border-red-600 bg-red-600 px-6 py-3.5 font-heading text-[13px] font-bold uppercase tracking-[0.1em] text-white transition-colors hover:bg-red-700 hover:border-red-700"
                >
                    JOIN
                </button>
            </div>
            @error('code')
                <p class="mt-2 text-left text-sm text-red-600">{{ $message }}</p>
            @enderror
        </form>

        {{-- Host links --}}
        <p class="mt-5 text-xs tracking-[0.04em] text-[#7A7A7A]">
            Hosting?
            <a href="{{ route('login') }}" class="font-bold text-[#141414] underline hover:text-red-600" wire:navigate>Log in</a>
            or
            <a href="{{ route('register') }}" class="font-bold text-[#141414] underline hover:text-red-600" wire:navigate>Register</a>
        </p>
    </div>

    {{-- Stats with heavy dividers --}}
    <div class="relative mx-auto max-w-[640px] border-y-2 border-[#141414]">
        <div class="flex">
            <div class="flex-1 border-r-2 border-[#141414] py-6 text-center">
                <div class="font-heading text-4xl font-bold text-[#141414]">{{ number_format($this->eventCount) }}</div>
                <div class="mt-1 text-[10px] font-medium uppercase tracking-[0.15em] text-[#7A7A7A]">Events</div>
            </div>
            <div class="flex-1 border-r-2 border-[#141414] py-6 text-center">
                <div class="font-heading text-4xl font-bold text-[#141414]">{{ number_format($this->teamCount) }}</div>
                <div class="mt-1 text-[10px] font-medium uppercase tracking-[0.15em] text-[#7A7A7A]">Teams</div>
            </div>
            <div class="flex-1 py-6 text-center">
                <div class="font-heading text-4xl font-bold text-red-600">5s</div>
                <div class="mt-1 text-[10px] font-medium uppercase tracking-[0.15em] text-[#7A7A7A]">Refresh</div>
            </div>
        </div>
    </div>
</div>
