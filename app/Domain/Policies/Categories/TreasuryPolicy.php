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
        return 'Treasury';
    }

    public static function fields(): array
    {
        return [
            'admin_verification_required' => PolicyField::bool(
                'admin_verification_required',
                'Administrator verification required',
                true,
                'When disabled, a transaction whose blockchain evidence the server verified is posted without a second human check.'
            ),
        ];
    }

    public function adminVerificationRequired(): bool
    {
        return $this->bool('admin_verification_required');
    }
}
