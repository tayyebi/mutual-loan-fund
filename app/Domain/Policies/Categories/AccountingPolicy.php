<?php

namespace App\Domain\Policies\Categories;

use App\Domain\Policies\PolicyCategory;
use App\Domain\Policies\PolicyField;

class AccountingPolicy extends PolicyCategory
{
    public static function key(): string
    {
        return 'accounting';
    }

    public static function label(): string
    {
        return __('policies.fields.categories.accounting');
    }

    public static function fields(): array
    {
        $currencies = [];

        foreach ((array) config('fund.currencies', []) as $code => $meta) {
            if ($code === config('fund.gold_unit')) {
                continue;
            }

            $currencies[$code] = $code.' — '.($meta['label'] ?? $code);
        }

        return [
            'functional_currency' => PolicyField::enum(
                'functional_currency',
                __('policies.fields.accounting.functional_currency'),
                $currencies,
                (string) config('fund.default_functional_currency'),
                __('policies.fields.accounting.functional_currency_help')
            ),
        ];
    }

    public function functionalCurrency(): string
    {
        return (string) $this->get('functional_currency', config('fund.default_functional_currency'));
    }
}
