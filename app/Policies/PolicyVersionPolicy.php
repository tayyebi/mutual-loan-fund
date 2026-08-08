<?php

namespace App\Policies;

use App\Models\GroupPolicy;
use App\Models\User;
use App\Support\GroupContext;

/**
 * Who may work with versioned group policies.
 *
 * Only administrators create, edit, publish or delete them. Ordinary members can
 * see the rules that govern them — the active version — but not the drafts and
 * history behind it.
 */
class PolicyVersionPolicy
{
    public function __construct(private readonly GroupContext $context) {}

    public function viewHistory(User $user): bool
    {
        return $this->context->isAdmin();
    }

    public function view(User $user, GroupPolicy $policy): bool
    {
        // A draft is not authoritative and must not shape members' expectations.
        return $this->context->isAdmin() || $policy->isPublished();
    }

    public function create(User $user): bool
    {
        return $this->context->isAdmin();
    }

    public function update(User $user, GroupPolicy $policy): bool
    {
        return $this->context->isAdmin() && $policy->isDraft();
    }

    public function publish(User $user, GroupPolicy $policy): bool
    {
        return $this->context->isAdmin() && $policy->isDraft();
    }

    public function delete(User $user, GroupPolicy $policy): bool
    {
        return $this->context->isAdmin() && $policy->isDraft();
    }
}
