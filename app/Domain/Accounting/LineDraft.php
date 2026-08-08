<?php

namespace App\Domain\Accounting;

use App\Domain\Money\Money;
use App\Models\Account;
use App\Models\CostCenter;

/**
 * One side of an entry, before it is valued and written.
 */
final class LineDraft
{
    public const SIDE_DEBIT = 'debit';
    public const SIDE_CREDIT = 'credit';

    public function __construct(
        public readonly Account $account,
        public readonly string $side,
        public readonly Money $amount,
        public readonly ?CostCenter $costCenter = null,
        public readonly ?string $description = null,
    ) {}

    public function isDebit(): bool
    {
        return $this->side === self::SIDE_DEBIT;
    }
}
