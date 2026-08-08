<?php

namespace App\Domain\Accounting;

use App\Domain\Money\Money;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

/**
 * A journal entry under construction.
 *
 * Accounting templates return one of these; PostingService is what values,
 * validates and writes it. Controllers never build entries directly.
 */
final class EntryDraft
{
    /** @var list<LineDraft> */
    private array $lines = [];

    private ?Transaction $transaction = null;

    private ?string $reason = null;

    private function __construct(
        public readonly string $template,
        public readonly Carbon $entryDate,
        public readonly string $description,
    ) {}

    public static function make(string $template, Carbon $entryDate, string $description): self
    {
        return new self($template, $entryDate->copy()->startOfDay(), $description);
    }

    public function forTransaction(?Transaction $transaction): self
    {
        $this->transaction = $transaction;

        return $this;
    }

    public function because(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function debit(Account $account, Money $amount, ?CostCenter $costCenter = null, ?string $description = null): self
    {
        return $this->line(LineDraft::SIDE_DEBIT, $account, $amount, $costCenter, $description);
    }

    public function credit(Account $account, Money $amount, ?CostCenter $costCenter = null, ?string $description = null): self
    {
        return $this->line(LineDraft::SIDE_CREDIT, $account, $amount, $costCenter, $description);
    }

    /**
     * Add a line only when the amount is non-zero, so templates can offer
     * optional legs (interest, fees) without emitting empty lines that the
     * database would reject.
     */
    public function debitIfNonZero(Account $account, Money $amount, ?CostCenter $costCenter = null, ?string $description = null): self
    {
        return $amount->isZero() ? $this : $this->debit($account, $amount, $costCenter, $description);
    }

    public function creditIfNonZero(Account $account, Money $amount, ?CostCenter $costCenter = null, ?string $description = null): self
    {
        return $amount->isZero() ? $this : $this->credit($account, $amount, $costCenter, $description);
    }

    /**
     * @return list<LineDraft>
     */
    public function lines(): array
    {
        return $this->lines;
    }

    public function transaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    private function line(string $side, Account $account, Money $amount, ?CostCenter $costCenter, ?string $description): self
    {
        $this->lines[] = new LineDraft($account, $side, $amount, $costCenter, $description);

        return $this;
    }
}
