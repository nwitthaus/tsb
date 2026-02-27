<?php

use App\Models\OrganizationInvitation;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Accept Invitation')] class extends Component {
    public OrganizationInvitation $invitation;

    public bool $emailMismatch = false;

    public function mount(string $token): void
    {
        $this->invitation = OrganizationInvitation::query()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->firstOrFail();

        if (auth()->user()->email !== $this->invitation->email) {
            $this->emailMismatch = true;
        }
    }

    public function accept(): void
    {
        if ($this->emailMismatch) {
            return;
        }

        $this->invitation->organization->users()->attach(auth()->id(), [
            'role' => $this->invitation->role,
        ]);

        $this->invitation->update(['accepted_at' => now()]);

        $this->redirect(route('organizations.show', $this->invitation->organization), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-lg space-y-6">
    <flux:heading size="xl">{{ __('Organization Invitation') }}</flux:heading>

    @if ($emailMismatch)
        <flux:card>
            <flux:heading>{{ __('Email Mismatch') }}</flux:heading>
            <flux:subheading>
                {{ __('This invitation was sent to :email. You are currently logged in as :current.', [
                    'email' => $invitation->email,
                    'current' => auth()->user()->email,
                ]) }}
            </flux:subheading>
            <flux:subheading class="mt-2">
                {{ __('Please log in with the correct email address to accept this invitation.') }}
            </flux:subheading>
        </flux:card>
    @else
        <flux:card>
            <flux:heading>{{ __('You\'ve been invited!') }}</flux:heading>
            <flux:subheading>
                {{ __(':inviter has invited you to join :org as a :role.', [
                    'inviter' => $invitation->inviter->name,
                    'org' => $invitation->organization->name,
                    'role' => ucfirst($invitation->role),
                ]) }}
            </flux:subheading>

            <div class="mt-4 flex gap-2">
                <flux:button variant="primary" wire:click="accept">
                    {{ __('Accept Invitation') }}
                </flux:button>
                <flux:button :href="route('dashboard')" wire:navigate>
                    {{ __('Decline') }}
                </flux:button>
            </div>
        </flux:card>
    @endif
</div>
