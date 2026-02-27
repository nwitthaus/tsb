<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->isOrganizationMember($organization);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->isOrganizationOwner($organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->isOrganizationOwner($organization);
    }

    public function invite(User $user, Organization $organization): bool
    {
        return $user->isOrganizationOwner($organization);
    }

    public function removeMember(User $user, Organization $organization): bool
    {
        return $user->isOrganizationOwner($organization);
    }
}
