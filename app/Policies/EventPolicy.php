<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, Event $event): bool
    {
        return $user->isOrganizationMember($event->organization);
    }

    /**
     * Organization-level ownership is checked in the route/controller via OrganizationPolicy.
     */
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isOrganizationOwner($event->organization);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->isOrganizationOwner($event->organization);
    }
}
