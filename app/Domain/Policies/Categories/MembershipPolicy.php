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
        return __('policies.fields.categories.membership');
    }

    public static function fields(): array
    {
        return [
            'member_approval_required' => PolicyField::bool(
                'member_approval_required',
                __('policies.fields.membership.member_approval_required'),
                true,
                __('policies.fields.membership.member_approval_required_help')
            ),
        ];
    }

    public function approvalRequired(): bool
    {
        return $this->bool('member_approval_required');
    }
}
