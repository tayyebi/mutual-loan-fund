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
        return __('policies.fields.categories.contributions');
    }

    public static function fields(): array
    {
        return [
            'enabled' => PolicyField::bool('enabled', __('policies.fields.contributions.enabled'), true),
            'minimum_amount' => PolicyField::money('minimum_amount', __('policies.fields.contributions.minimum_amount'), '0'),
            'maximum_amount' => PolicyField::money(
                'maximum_amount',
                __('policies.fields.contributions.maximum_amount'),
                null,
                nullable: true,
                help: __('policies.fields.contributions.maximum_amount_help')
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
