<?php

namespace App\Domain\Accounting\Exceptions;

use App\Domain\Money\Decimal;
use RuntimeException;

/**
 * SUM(debits) must equal SUM(credits) for every posted entry. An entry that
 * fails this check is never written: the surrounding transaction rolls back.
 */
class UnbalancedEntryException extends RuntimeException
{
    public function __construct(
        public readonly Decimal $debits,
        public readonly Decimal $credits,
        string $currency,
    ) {
        parent::__construct(sprintf(
            'Journal entry does not balance: debits %s %s, credits %s %s (difference %s).',
            $debits->format(2),
            $currency,
            $credits->format(2),
            $currency,
            $debits->minus($credits)->format(2)
        ));
    }
}
