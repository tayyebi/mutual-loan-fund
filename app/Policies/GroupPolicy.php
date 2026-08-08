<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use App\Support\GroupContext;

/**
 * Group-level abilities. Membership itself is verified by ResolveGroupContext
 * before any of this runs.
 */
class GroupPolicy
{
    public function __construct(private readonly GroupContext $context) {}

    public function view(User $user, Group $group): bool
    {
        return $this->context->has();
    }

    public function administer(User $user, Group $group): bool
    {
        return $this->context->isAdmin();
    }

    public function manageMembers(User $user, Group $group): bool
    {
        return $this->context->isAdmin();
    }

    public function manageTreasuries(User $user, Group $group): bool
    {
        return $this->context->isAdmin();
    }

    public function manageAccounting(User $user, Group $group): bool
    {
        return $this->context->isAdmin();
    }

    public function viewAudit(User $user, Group $group): bool
    {
        return $this->context->isAdmin();
    }

    /**
     * Members see their own position; administrators see everyone's.
     */
    public function viewMemberPosition(User $user, GroupMembership $membership): bool
    {
        return $this->context->isAdmin() || $this->context->owns($membership);
    }
}
