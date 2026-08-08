<?php

namespace App\Domain\Policies\Categories;

use App\Domain\Money\Decimal;
use App\Domain\Policies\PolicyCategory;
use App\Domain\Policies\PolicyField;

class RepaymentPolicy extends PolicyCategory
{
    public static function key(): string
    {
        return 'repayments';
    }

    public static function label(): string
    {
        return 'Repayments';
    }

    public static function fields(): array
    {
        return [
            'enabled' => PolicyField::bool('enabled', 'Repayments enabled', true),
            'minimum_amount' => PolicyField::money('minimum_amount', 'Minimum amount', '0'),
        ];
    }

    public function minimumAmount(): Decimal
    {
        return $this->decimal('minimum_amount') ?? Decimal::zero();
    }
}
