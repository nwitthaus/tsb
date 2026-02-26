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

<div class="min-h-screen bg-[#0a0a0a] font-body text-white">
    {{-- Ticker bar --}}
    <div class="h-1 animate-ticker bg-gradient-to-r from-red-600 via-amber-500 to-red-600"></div>

    {{-- Hero --}}
    <div class="relative overflow-hidden px-6 pb-12 pt-16 sm:px-12 md:px-20 lg:px-28">
        {{-- Red radial glow --}}
        <div class="pointer-events-none absolute -right-20 -top-32 size-[400px] rounded-full bg-red-600/10 blur-3xl"></div>

        {{-- Live badge --}}
        <div class="mb-8 inline-flex items-center gap-2 rounded bg-red-600 px-3.5 py-1.5 text-xs font-bold uppercase tracking-[0.15em]">
            <span class="inline-block size-2 animate-pulse-dot rounded-full bg-white"></span>
            Live Scoring
        </div>

        {{-- Heading --}}
        <h1 class="font-display text-5xl uppercase leading-none tracking-tight sm:text-6xl lg:text-7xl">
            TRIVIA<br>
            <span class="text-red-600">SCORE</span>BOARD
        </h1>

        <p class="mt-4 max-w-md text-lg font-light text-zinc-400">
            Real-time scoring for your trivia nights. Free, fast, no app needed.
        </p>

        {{-- Join form --}}
        <form wire:submit="join" class="mt-10 max-w-md">
            <div class="flex">
                <input
                    wire:model="code"
                    type="text"
                    placeholder="ENTER CODE"
                    maxlength="20"
                    autofocus
                    class="flex-1 rounded-l-lg border-2 border-white/10 border-r-transparent bg-white/5 px-5 py-3.5 font-mono text-base uppercase tracking-widest text-white placeholder-zinc-600 outline-none transition-colors focus:border-red-600 focus:border-r-transparent"
                />
                <button
                    type="submit"
                    class="rounded-r-lg border-2 border-red-600 bg-gradient-to-br from-red-600 to-red-700 px-7 py-3.5 text-sm font-bold uppercase tracking-wider text-white transition-all hover:from-red-500 hover:to-red-600"
                >
                    Join&nbsp;Game
                </button>
            </div>
            @error('code')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </form>

        {{-- Host links --}}
        <p class="mt-8 text-sm text-zinc-500">
            Hosting tonight?
            <a href="{{ route('login') }}" class="font-semibold text-red-600 hover:text-red-500" wire:navigate>Log in</a>
            or
            <a href="{{ route('register') }}" class="font-semibold text-red-600 hover:text-red-500" wire:navigate>Create an account</a>
        </p>
    </div>

    {{-- Stats strip --}}
    <div class="flex border-t border-white/[0.06]">
        <div class="flex-1 border-r border-white/[0.06] px-6 py-5 text-center sm:px-8">
            <div class="font-display text-3xl text-red-600">{{ number_format($this->eventCount) }}</div>
            <div class="mt-1 text-[11px] uppercase tracking-[0.1em] text-zinc-500">Events Hosted</div>
        </div>
        <div class="flex-1 border-r border-white/[0.06] px-6 py-5 text-center sm:px-8">
            <div class="font-display text-3xl text-red-600">{{ number_format($this->teamCount) }}</div>
            <div class="mt-1 text-[11px] uppercase tracking-[0.1em] text-zinc-500">Teams Scored</div>
        </div>
        <div class="flex-1 px-6 py-5 text-center sm:px-8">
            <div class="font-display text-3xl text-red-600">5s</div>
            <div class="mt-1 text-[11px] uppercase tracking-[0.1em] text-zinc-500">Live Refresh</div>
        </div>
    </div>
</div>
