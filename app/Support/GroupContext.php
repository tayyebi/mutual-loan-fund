<?php

namespace App\Support;

use App\Models\Group;
use App\Models\GroupMembership;
use RuntimeException;

/**
 * The group and membership resolved for the current request.
 *
 * Populated once by ResolveGroupContext after it has verified the user's
 * membership, then read by controllers, policies and views. Nothing derives the
 * current tenant from user input a second time.
 */
class GroupContext
{
    private ?Group $group = null;

    private ?GroupMembership $membership = null;

    public function set(Group $group, GroupMembership $membership): void
    {
        $this->group = $group;
        $this->membership = $membership;
    }

    public function has(): bool
    {
        return $this->group !== null;
    }

    public function group(): Group
    {
        return $this->group ?? throw new RuntimeException('No group is in context for this request.');
    }

    public function membership(): GroupMembership
    {
        return $this->membership ?? throw new RuntimeException('No membership is in context for this request.');
    }

    public function isAdmin(): bool
    {
        return $this->membership?->isAdmin() ?? false;
    }

    /**
     * Whether the context membership is the given one — "is this mine?".
     */
    public function owns(?GroupMembership $membership): bool
    {
        return $membership !== null
            && $this->membership !== null
            && $membership->getKey() === $this->membership->getKey();
    }
}
