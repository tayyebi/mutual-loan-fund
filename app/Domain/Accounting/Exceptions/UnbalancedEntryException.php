<?php

namespace App\Domain\Accounting\Exceptions;

use App\Domain\Money\Decimal;
use App\Support\DomainRefusal;
use RuntimeException;

/**
 * SUM(debits) must equal SUM(credits) for every posted entry. An entry that
 * fails this check is never written: the surrounding transaction rolls back.
 */
class UnbalancedEntryException extends RuntimeException implements DomainRefusal
{
    public function __construct(
        public readonly Decimal $debits,
        public readonly Decimal $credits,
        string $currency,
    ) {
        parent::__construct(__('exceptions.journal_unbalanced', [
            'debits' => $debits->format(2),
            'credits' => $credits->format(2),
            'currency' => $currency,
            'difference' => $debits->minus($credits)->format(2),
        ]));
    }
}
