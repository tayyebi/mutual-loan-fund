<?php

namespace App\Domain\Blockchain;

use App\Models\Transaction;

/**
 * Checks that a claimed blockchain transfer really happened, to the right
 * treasury, for the right amount.
 *
 * Submitting a transaction hash never creates a balance on its own — this
 * verification, and then a human decision where policy requires one, is what
 * lets a transaction be posted.
 */
interface ChainVerifier
{
    public function verify(Transaction $transaction): ChainVerification;
}
