<?php

namespace App\Domain\Policies\Categories;

use App\Domain\Policies\PolicyCategory;
use App\Domain\Policies\PolicyField;

class TreasuryPolicy extends PolicyCategory
{
    public static function key(): string
    {
        return 'treasury';
    }

    public static function label(): string
    {
        return __('policies.fields.categories.treasury');
    }

    public static function fields(): array
    {
        return [
            'admin_verification_required' => PolicyField::bool(
                'admin_verification_required',
                __('policies.fields.treasury.admin_verification_required'),
                true,
                __('policies.fields.treasury.admin_verification_required_help')
            ),
        ];
    }

    public function adminVerificationRequired(): bool
    {
        return $this->bool('admin_verification_required');
    }
}
