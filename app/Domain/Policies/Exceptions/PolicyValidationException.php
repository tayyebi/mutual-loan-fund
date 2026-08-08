<?php

namespace App\Domain\Policies\Exceptions;

use RuntimeException;

/**
 * An invalid policy can never be published.
 */
class PolicyValidationException extends RuntimeException
{
    /**
     * @param  array<string, string>  $errors  keyed by "category.field"
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('The policy is not valid: '.implode(' ', $errors));
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
