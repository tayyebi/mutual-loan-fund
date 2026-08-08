<?php

namespace App\Domain\Accounting\Exceptions;

use App\Models\AccountingPeriod;
use RuntimeException;

/**
 * Closed accounting periods cannot receive normal postings. Corrections belong
 * in a new open period, posted as adjustments.
 */
class ClosedPeriodException extends RuntimeException
{
    public function __construct(public readonly AccountingPeriod $period)
    {
        parent::__construct(
            "Accounting period {$period->label()} is closed and cannot receive postings. Post the correction in an open period instead."
        );
    }
}
