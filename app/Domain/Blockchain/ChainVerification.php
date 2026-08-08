<?php

namespace App\Domain\Blockchain;

use App\Domain\Money\Decimal;

/**
 * The result of checking a claimed transfer against the chain.
 */
final class ChainVerification
{
    /**
     * @param  array<string, mixed>  $evidence  raw payload kept for the audit trail
     */
    private function __construct(
        public readonly bool $confirmed,
        public readonly ?string $reason = null,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly ?Decimal $amount = null,
        public readonly ?int $confirmations = null,
        public readonly array $evidence = [],
        public readonly bool $checked = true,
    ) {}

    /**
     * @param  array<string, mixed>  $evidence
     */
    public static function confirmed(
        string $from,
        string $to,
        Decimal $amount,
        ?int $confirmations = null,
        array $evidence = [],
    ): self {
        return new self(true, null, $from, $to, $amount, $confirmations, $evidence);
    }

    /**
     * @param  array<string, mixed>  $evidence
     */
    public static function rejected(string $reason, array $evidence = []): self
    {
        return new self(false, $reason, evidence: $evidence);
    }

    /**
     * No automated check was performed; an administrator must confirm by hand.
     */
    public static function unchecked(string $reason): self
    {
        return new self(false, $reason, checked: false);
    }
}
