<?php

namespace App\Domain\Policies\Exceptions;

use App\Models\Group;
use RuntimeException;

/**
 * Thrown when a financial operation is attempted with no published policy to
 * govern it. Operations are refused rather than falling back to hard-coded
 * defaults, so nothing is ever created under unknown rules.
 */
class NoActivePolicyException extends RuntimeException
{
    public function __construct(Group $group)
    {
        parent::__construct(
            "Group '{$group->name}' has no active policy version. Publish a policy before recording financial operations."
        );
    }
}
