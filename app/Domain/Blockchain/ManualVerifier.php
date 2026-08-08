<?php

namespace App\Domain\Blockchain;

use App\Models\Transaction;

/**
 * The default: the fund records the claimed hash and an administrator confirms
 * it against a block explorer.
 *
 * This is honest about what the application knows. It never marks a transfer as
 * chain-verified without having actually checked one.
 */
class ManualVerifier implements ChainVerifier
{
    public function verify(Transaction $transaction): ChainVerification
    {
        return ChainVerification::unchecked(
            'Automatic blockchain verification is disabled; an administrator must confirm this transfer against the explorer.'
        );
    }
}
