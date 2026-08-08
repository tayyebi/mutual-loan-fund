<?php

namespace App\Domain\Policies\Exceptions;

use App\Support\DomainRefusal;
use RuntimeException;

/**
 * A requested operation breaks the governing policy. Limits are enforced, not
 * merely displayed.
 */
class PolicyViolationException extends RuntimeException implements DomainRefusal
{
    public function __construct(string $message, public readonly ?string $field = null)
    {
        parent::__construct($message);
    }
}
