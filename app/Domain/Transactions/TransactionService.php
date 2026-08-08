<?php

namespace App\Domain\Transactions;

use App\Domain\Accounting\AccountingService;
use App\Domain\Audit\AuditAction;
use App\Domain\Audit\AuditRecorder;
use App\Domain\Blockchain\ChainVerification;
use App\Domain\Blockchain\ChainVerifier;
use App\Domain\Money\Decimal;
use App\Domain\Policies\Exceptions\PolicyViolationException;
use App\Domain\Policies\PolicyService;
use App\Domain\Transactions\Events\TransactionVerified;
use App\Models\Contribution;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Business financial events and their journey to the ledger.
 *
 * A submitted transaction is a claim, not money: it changes nothing until it is
 * verified, and verification and posting happen in one database transaction so
 * the business record and its accounting can never disagree.
 */
class TransactionService
{
    public function __construct(
        private readonly AccountingService $accounting,
        private readonly PolicyService $policies,
        private readonly ChainVerifier $chain,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * A member pays money in.
     *
     * @param  array{treasury: Treasury, amount: Decimal, currency: string, occurred_on: Carbon, reference?: ?string, description?: ?string, network?: ?string, tx_hash?: ?string, from_address?: ?string}  $data
     */
    public function submitContribution(GroupMembership $member, array $data, User $actor): Transaction
    {
        $group = $member->group;
        $policy = $this->policies->requireActivePolicy($group);
        $contributions = $policy->toPolicyConfig()->contributions();

        if (! $contributions->enabled()) {
            throw new PolicyViolationException('This fund is not accepting contributions.');
        }

        $amount = $data['amount'];

        if ($amount->lessThan($contributions->minimumAmount())) {
            throw new PolicyViolationException(
                "The minimum contribution is {$contributions->minimumAmount()->format(2)} {$data['currency']}.",
                'amount'
            );
        }

        $maximum = $contributions->maximumAmount();

        if ($maximum !== null && $amount->greaterThan($maximum)) {
            throw new PolicyViolationException(
                "The maximum contribution is {$maximum->format(2)} {$data['currency']}.",
                'amount'
            );
        }

        return $this->create($group, [
            'treasury_id' => $data['treasury']->getKey(),
            'member_id' => $member->getKey(),
            'policy_version_id' => $policy->getKey(),
            'type' => Transaction::TYPE_CONTRIBUTION,
            'direction' => Transaction::DIRECTION_IN,
            'amount' => $amount->toString(),
            'currency' => $data['currency'],
            'occurred_on' => $data['occurred_on'],
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'] ?? null,
            'network' => $data['network'] ?? null,
            'tx_hash' => $data['tx_hash'] ?? null,
            'from_address' => $data['from_address'] ?? null,
            'to_address' => $data['treasury']->external_identifier,
        ], $actor);
    }

    /**
     * A member pays a loan back.
     *
     * @param  array{treasury: Treasury, amount: Decimal, currency: string, occurred_on: Carbon, reference?: ?string, description?: ?string, network?: ?string, tx_hash?: ?string, from_address?: ?string}  $data
     */
    public function submitRepayment(Loan $loan, array $data, User $actor): Transaction
    {
        $group = $loan->group;

        // The loan's own policy version governs it, not whatever is active now.
        $repayments = $loan->policyVersion->toPolicyConfig()->repayments();

        if (! $repayments->enabled()) {
            throw new PolicyViolationException('Repayments are not enabled for this fund.');
        }

        if (! $loan->isOutstanding()) {
            throw new PolicyViolationException('This loan is not outstanding.');
        }

        $amount = $data['amount'];

        if ($amount->lessThan($repayments->minimumAmount())) {
            throw new PolicyViolationException(
                "The minimum repayment is {$repayments->minimumAmount()->format(2)} {$loan->currency}.",
                'amount'
            );
        }

        if ($data['currency'] !== $loan->currency) {
            throw new PolicyViolationException("This loan is repaid in {$loan->currency}.", 'currency');
        }

        $outstanding = $loan->outstandingPrincipal();

        if (! $loan->policyVersion->toPolicyConfig()->loans()->allowsEarlyRepayment()
            && $amount->greaterThan($outstanding)) {
            throw new PolicyViolationException('Early repayment is not permitted under this loan\'s policy.', 'amount');
        }

        return $this->create($group, [
            'treasury_id' => $data['treasury']->getKey(),
            'member_id' => $loan->member_id,
            'loan_id' => $loan->getKey(),
            'policy_version_id' => $loan->policy_version_id,
            'type' => Transaction::TYPE_LOAN_REPAYMENT,
            'direction' => Transaction::DIRECTION_IN,
            'amount' => $amount->toString(),
            'currency' => $data['currency'],
            'occurred_on' => $data['occurred_on'],
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'] ?? null,
            'network' => $data['network'] ?? null,
            'tx_hash' => $data['tx_hash'] ?? null,
            'from_address' => $data['from_address'] ?? null,
            'to_address' => $data['treasury']->external_identifier,
        ], $actor);
    }

    /**
     * Administrative movements: transfers, exchanges and standalone fees.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function record(Group $group, array $attributes, User $actor): Transaction
    {
        $policy = $this->policies->requireActivePolicy($group);

        return $this->create($group, $attributes + ['policy_version_id' => $policy->getKey()], $actor);
    }

    /**
     * Ask the chain whether a claimed transfer is real. Recorded either way:
     * a failed check is evidence too.
     */
    public function runChainVerification(Transaction $transaction): ChainVerification
    {
        if (! $transaction->hasChainEvidence()) {
            return ChainVerification::unchecked('No transaction hash was submitted.');
        }

        $result = $this->chain->verify($transaction);

        $transaction->forceFill([
            'chain_evidence' => [
                'confirmed' => $result->confirmed,
                'checked' => $result->checked,
                'reason' => $result->reason,
                'payload' => $result->evidence,
                'checked_at' => now()->toIso8601String(),
            ],
            'chain_verified_at' => $result->confirmed ? now() : null,
            'confirmations' => $result->confirmations,
            'from_address' => $result->from ?: $transaction->from_address,
        ])->save();

        if ($result->confirmed) {
            $this->audit->record(
                AuditAction::TRANSACTION_CHAIN_VERIFIED,
                group: $transaction->group,
                object: $transaction,
                new: ['tx_hash' => $transaction->tx_hash, 'network' => $transaction->network]
            );
        }

        return $result;
    }

    /**
     * Verify a transaction and give it its accounting representation.
     *
     * Everything here is one atomic unit: the ledger entry, the transaction's
     * new status, the contribution record and any loan movement commit together.
     */
    public function verify(Transaction $transaction, User $actor): Transaction
    {
        return DB::transaction(function () use ($transaction, $actor) {
            // Lock the row: two administrators pressing verify at the same moment
            // must not both post the same event.
            $locked = Transaction::query()
                ->whereKey($transaction->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isPending()) {
                throw new RuntimeException("This transaction is already {$locked->status}.");
            }

            $locked->setRelations($transaction->getRelations());

            $entry = $this->accounting->postTransaction($locked, $actor);

            $locked->forceFill([
                'status' => Transaction::STATUS_VERIFIED,
                'verified_by' => $actor->getKey(),
                'verified_at' => now(),
            ])->save();

            if ($locked->type === Transaction::TYPE_CONTRIBUTION) {
                $this->recordContribution($locked);
            }

            event(new TransactionVerified($locked, $actor));

            $this->audit->record(
                AuditAction::TRANSACTION_VERIFIED,
                group: $locked->group,
                actor: $actor,
                object: $locked,
                old: ['status' => Transaction::STATUS_PENDING],
                new: [
                    'status' => Transaction::STATUS_VERIFIED,
                    'journal_entry' => $entry->entry_number,
                    'amount' => (string) $locked->amount,
                    'currency' => $locked->currency,
                ]
            );

            return $locked->refresh();
        });
    }

    public function reject(Transaction $transaction, User $actor, string $reason): Transaction
    {
        if (! $transaction->isPending()) {
            throw new RuntimeException("This transaction is already {$transaction->status}.");
        }

        $transaction->forceFill([
            'status' => Transaction::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'verified_by' => $actor->getKey(),
            'verified_at' => now(),
        ])->save();

        $this->audit->record(
            AuditAction::TRANSACTION_REJECTED,
            group: $transaction->group,
            actor: $actor,
            object: $transaction,
            old: ['status' => Transaction::STATUS_PENDING],
            new: ['status' => Transaction::STATUS_REJECTED, 'reason' => $reason]
        );

        return $transaction;
    }

    /**
     * Whether policy still requires a human to confirm this transfer.
     */
    public function needsAdminVerification(Transaction $transaction): bool
    {
        $required = $transaction->policyVersion
            ?->toPolicyConfig()->treasury()->adminVerificationRequired() ?? true;

        return $required || $transaction->chain_verified_at === null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function create(Group $group, array $attributes, User $actor): Transaction
    {
        try {
            $transaction = Transaction::create($attributes + [
                'group_id' => $group->getKey(),
                'status' => Transaction::STATUS_PENDING,
                'created_by' => $actor->getKey(),
            ]);
        } catch (QueryException $e) {
            // The (network, tx_hash) unique index is what actually prevents a
            // blockchain transfer being credited twice.
            if ($this->isDuplicateChainTransaction($e)) {
                throw new RuntimeException(
                    'That blockchain transaction has already been submitted to this application.'
                );
            }

            throw $e;
        }

        $this->audit->record(
            AuditAction::TRANSACTION_SUBMITTED,
            group: $group,
            actor: $actor,
            object: $transaction,
            new: [
                'type' => $transaction->type,
                'amount' => (string) $transaction->amount,
                'currency' => $transaction->currency,
                'tx_hash' => $transaction->tx_hash,
            ]
        );

        return $transaction;
    }

    private function recordContribution(Transaction $transaction): void
    {
        $costCenter = $transaction->member?->costCenter;

        Contribution::create([
            'group_id' => $transaction->group_id,
            'member_id' => $transaction->member_id,
            'cost_center_id' => $costCenter?->getKey(),
            'transaction_id' => $transaction->getKey(),
            'policy_version_id' => $transaction->policy_version_id,
            'amount' => (string) $transaction->amount,
            'currency' => $transaction->currency,
        ]);
    }

    private function isDuplicateChainTransaction(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'transactions_chain_identity_idx');
    }
}
