<?php

namespace App\Domain\Accounting\Templates;

use App\Domain\Accounting\EntryDraft;
use App\Models\Transaction;

/**
 * Financial events are posted through predefined templates rather than
 * hand-written entries, so the same kind of event always produces the same
 * accounts, directions and attribution.
 */
interface TransactionTemplate
{
    /**
     * Name recorded on the journal entry.
     */
    public function key(): string;

    public function build(Transaction $transaction): EntryDraft;
}
