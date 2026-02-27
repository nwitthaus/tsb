<x-mail::message>
# {{ __('You\'ve Been Invited') }}

{{ __(':inviter has invited you to join **:org** as a **:role**.', [
    'inviter' => $invitation->inviter->name,
    'org' => $invitation->organization->name,
    'role' => ucfirst($invitation->role),
]) }}

<x-mail::button :url="route('invitations.accept', $invitation->token)">
{{ __('Accept Invitation') }}
</x-mail::button>

{{ __('If you did not expect this invitation, you can ignore this email.') }}

{{ config('app.name') }}
</x-mail::message>
