<?php

namespace App\Domain\Accounting;

use App\Domain\Accounting\Exceptions\PostingException;
use App\Domain\Accounting\Templates\ContributionTemplate;
use App\Domain\Accounting\Templates\FeeTemplate;
use App\Domain\Accounting\Templates\LoanDisbursementTemplate;
use App\Domain\Accounting\Templates\LoanRepaymentTemplate;
use App\Domain\Accounting\Templates\TransactionTemplate;
use App\Domain\Accounting\Templates\TreasuryExchangeTemplate;
use App\Domain\Accounting\Templates\TreasuryTransferTemplate;
use App\Domain\Audit\AuditAction;
use App\Domain\Audit\AuditRecorder;
use App\Domain\Money\Money;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Group;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * The accounting layer's public face.
 *
 * Business services call this to give a financial event its accounting
 * representation; they never construct journal entries themselves, and neither
 * do controllers.
 */
class AccountingService
{
    /** @var array<string, class-string<TransactionTemplate>> */
    private const TEMPLATES = [
        Transaction::TYPE_CONTRIBUTION => ContributionTemplate::class,
        Transaction::TYPE_LOAN_DISBURSEMENT => LoanDisbursementTemplate::class,
        Transaction::TYPE_LOAN_REPAYMENT => LoanRepaymentTemplate::class,
        Transaction::TYPE_TREASURY_TRANSFER => TreasuryTransferTemplate::class,
        Transaction::TYPE_TREASURY_EXCHANGE => TreasuryExchangeTemplate::class,
        Transaction::TYPE_FEE => FeeTemplate::class,
    ];

    public function __construct(
        private readonly PostingService $posting,
        private readonly FunctionalCurrencyResolver $functionalCurrency,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * Give a verified transaction its journal entry.
     *
     * Called inside the same database transaction that verifies the business
     * event, so the two commit together or not at all.
     */
    public function postTransaction(Transaction $transaction, User $actor): JournalEntry
    {
        if ($transaction->journalEntry()->exists()) {
            throw new PostingException(
                __('exceptions.transaction_already_posted', ['id' => $transaction->getKey()])
            );
        }

        $template = $this->templateFor($transaction);
        $draft = $template->build($transaction);

        return $this->posting->post(
            $transaction->group,
            $draft,
            $actor,
            $this->functionalCurrency->forTransaction($transaction)
        );
    }

    public function templateFor(Transaction $transaction): TransactionTemplate
    {
        $class = self::TEMPLATES[$transaction->type] ?? throw new PostingException(
            __('exceptions.no_accounting_template', ['type' => $transaction->type])
        );

        return app($class);
    }

    /**
     * A manual correction entered by an administrator.
     *
     * Adjustments are how mistakes are fixed: posted entries are never edited,
     * so a correction is itself a new, reasoned, audited entry.
     *
     * @param  list<array{account: Account, side: string, amount: Money, cost_center?: ?CostCenter, description?: ?string}>  $lines
     */
    public function postAdjustment(
        Group $group,
        array $lines,
        string $reason,
        Carbon $date,
        User $actor,
        ?string $description = null,
    ): JournalEntry {
        if (trim($reason) === '') {
            throw new PostingException(__('exceptions.adjustment_requires_reason'));
        }

        $draft = EntryDraft::make('Adjustment', $date, $description ?: 'Accounting adjustment')
            ->because($reason);

        foreach ($lines as $line) {
            $costCenter = $line['cost_center'] ?? null;
            $lineDescription = $line['description'] ?? null;

            $line['side'] === LineDraft::SIDE_DEBIT
                ? $draft->debit($line['account'], $line['amount'], $costCenter, $lineDescription)
                : $draft->credit($line['account'], $line['amount'], $costCenter, $lineDescription);
        }

        $entry = $this->posting->post($group, $draft, $actor, $this->functionalCurrency->forGroup($group));

        $this->audit->record(
            AuditAction::ADJUSTMENT_CREATED,
            group: $group,
            actor: $actor,
            object: $entry,
            new: ['entry_number' => $entry->entry_number, 'reason' => $reason]
        );

        return $entry;
    }

    /**
     * Reverse a posted entry. The original stays visible; the reversal cancels it.
     */
    public function reverse(JournalEntry $entry, string $reason, User $actor, ?Carbon $date = null): JournalEntry
    {
        if (trim($reason) === '') {
            throw new PostingException(__('exceptions.reversal_requires_reason'));
        }

        return $this->posting->reverse($entry, $reason, $actor, $date);
    }
}
