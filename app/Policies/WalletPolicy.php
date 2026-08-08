<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;
use App\Support\GroupContext;

class WalletPolicy
{
    public function __construct(private readonly GroupContext $context) {}

    public function viewAny(User $user): bool
    {
        return $this->context->has();
    }

    public function create(User $user): bool
    {
        return $this->context->has();
    }

    /**
     * A member manages their own wallets; administrators can see all of them but
     * a wallet address belongs to the person who owns the keys.
     */
    public function update(User $user, Wallet $wallet): bool
    {
        return $this->context->owns($wallet->member);
    }
}
