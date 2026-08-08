<?php

namespace App\Domain\ExchangeRates\Exceptions;

use App\Support\DomainRefusal;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * No rate exists on or before the requested date.
 *
 * The operation fails rather than proceeding: the application never invents an
 * exchange rate, because an invented rate would be snapshotted into the ledger
 * and become permanent.
 */
class MissingRateException extends RuntimeException implements DomainRefusal
{
    public function __construct(public readonly string $unit, public readonly Carbon $date)
    {
        parent::__construct(sprintf(
            'No 18K gold rate has been entered for %s on or before %s. Enter a rate before recording this operation.',
            $unit,
            $date->toDateString()
        ));
    }
}
