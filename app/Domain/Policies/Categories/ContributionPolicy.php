<?php

namespace App\Domain\Policies\Categories;

use App\Domain\Money\Decimal;
use App\Domain\Policies\PolicyCategory;
use App\Domain\Policies\PolicyField;

class ContributionPolicy extends PolicyCategory
{
    public static function key(): string
    {
        return 'contributions';
    }

    public static function label(): string
    {
        return 'Contributions';
    }

    public static function fields(): array
    {
        return [
            'enabled' => PolicyField::bool('enabled', 'Contributions enabled', true),
            'minimum_amount' => PolicyField::money('minimum_amount', 'Minimum amount', '0'),
            'maximum_amount' => PolicyField::money(
                'maximum_amount',
                'Maximum amount',
                null,
                nullable: true,
                help: 'Leave blank for no upper limit.'
            ),
        ];
    }

    public function minimumAmount(): Decimal
    {
        return $this->decimal('minimum_amount') ?? Decimal::zero();
    }

    public function maximumAmount(): ?Decimal
    {
        return $this->decimal('maximum_amount');
    }
}
