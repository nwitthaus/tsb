<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ $event->name }}</flux:heading>
            <flux:subheading>{{ __('Join code:') }} {{ $event->slug }}</flux:subheading>
        </div>
    </div>

    @if ($event->isActive())
        <flux:subheading>{{ __('Scoring grid will be built in upcoming tasks.') }}</flux:subheading>
    @else
        <flux:callout variant="warning">
            <flux:callout.heading>{{ __('Final Scores') }}</flux:callout.heading>
            <flux:callout.text>{{ __('This event has ended.') }}</flux:callout.text>
        </flux:callout>
    @endif
</div>
