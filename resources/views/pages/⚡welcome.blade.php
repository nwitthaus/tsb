<?php

use App\Models\Event;
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
}; ?>

<div class="flex flex-col items-center justify-center min-h-[70vh]">
    <div class="text-center mb-10">
        <h1 class="text-4xl font-bold text-neutral-900 dark:text-white">Trivia Scoreboard</h1>
        <p class="mt-3 text-lg text-neutral-500 dark:text-neutral-400">Live scoring for your trivia nights</p>
    </div>

    <div class="w-full max-w-sm">
        <form wire:submit="join" class="space-y-4">
            <flux:input
                wire:model="code"
                :label="__('Join Code')"
                :placeholder="__('Enter your event code')"
                autofocus
            />

            @error('code')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('View Scoreboard') }}
            </flux:button>
        </form>

        <div class="mt-8 text-center text-sm text-neutral-500 dark:text-neutral-400">
            <p>{{ __('Are you a host?') }}</p>
            <div class="mt-2 flex justify-center gap-4">
                <a href="{{ route('login') }}" class="font-medium text-neutral-900 underline dark:text-white" wire:navigate>{{ __('Log in') }}</a>
                <a href="{{ route('register') }}" class="font-medium text-neutral-900 underline dark:text-white" wire:navigate>{{ __('Register') }}</a>
            </div>
        </div>
    </div>
</div>
