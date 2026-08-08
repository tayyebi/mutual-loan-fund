<?php

namespace App\Domain\Policies\Categories;

use App\Domain\Policies\PolicyCategory;
use App\Domain\Policies\PolicyField;

class MembershipPolicy extends PolicyCategory
{
    public static function key(): string
    {
        return 'membership';
    }

    public static function label(): string
    {
        return 'Membership';
    }

    public static function fields(): array
    {
        return [
            'member_approval_required' => PolicyField::bool(
                'member_approval_required',
                'Administrator approval required',
                true,
                'When disabled, a membership request becomes active immediately.'
            ),
        ];
    }

    public function approvalRequired(): bool
    {
        return $this->bool('member_approval_required');
    }
}
