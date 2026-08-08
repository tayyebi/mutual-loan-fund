<?php

namespace App\Domain\Loans;

use App\Domain\Accounting\LedgerBalances;
use App\Domain\Audit\AuditAction;
use App\Domain\Audit\AuditRecorder;
use App\Domain\Money\Decimal;
use App\Domain\Policies\Exceptions\PolicyViolationException;
use App\Domain\Policies\PolicyService;
use App\Domain\Transactions\TransactionService;
use App\Models\Account;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\GroupPolicy;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Transaction;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The loan lifecycle.
 *
 * A loan records the policy version it was created under and keeps it for life:
 * eligibility, interest and term are always read from that version, so
 * publishing new rules never rewrites an existing loan.
 */
class LoanService
{
    public function __construct(
        private readonly PolicyService $policies,
        private readonly LedgerBalances $balances,
        private readonly InstallmentPlanner $planner,
        private readonly RepaymentAllocator $allocator,
        private readonly TransactionService $transactions,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * Check a request against the rules. Used both to show the member where they
     * stand and to enforce the decision on the server.
     */
    public function eligibility(
        GroupMembership $member,
        Decimal $amount,
        string $currency,
        int $termMonths,
        ?GroupPolicy $policy = null,
        ?Loan $excluding = null,
    ): LoanEligibility {
        $policy ??= $this->policies->requireActivePolicy($member->group);
        $loans = $policy->toPolicyConfig()->loans();

        $failures = [];

        if (! $loans->enabled()) {
            $failures[] = 'Loans are not enabled for this fund.';
        }

        $outstanding = $this->outstandingPrincipalFor($member, $currency, $excluding);
        $activeLoans = $this->activeLoanCount($member, $excluding);
        $membershipDays = $member->membershipDays();

        if ($amount->lessThan($loans->minimumAmount())) {
            $failures[] = "The minimum loan is {$loans->minimumAmount()->format(2)} {$currency}.";
        }

        $maximum = $loans->maximumAmount();

        if ($maximum !== null) {
            if ($amount->greaterThan($maximum)) {
                $failures[] = "The maximum loan is {$maximum->format(2)} {$currency}.";
            }

            // Exposure is judged on the total the member would owe, not on this
            // request alone.
            if ($amount->plus($outstanding)->greaterThan($maximum)) {
                $failures[] = sprintf(
                    'This would take total borrowing to %s %s, above the %s %s maximum.',
                    $amount->plus($outstanding)->format(2),
                    $currency,
                    $maximum->format(2),
                    $currency
                );
            }
        }

        if ($termMonths < $loans->minimumTermMonths() || $termMonths > $loans->maximumTermMonths()) {
            $failures[] = sprintf(
                'The term must be between %d and %d months.',
                $loans->minimumTermMonths(),
                $loans->maximumTermMonths()
            );
        }

        if ($activeLoans >= $loans->maximumActiveLoans()) {
            $failures[] = $loans->maximumActiveLoans() === 1
                ? 'This fund allows one active loan at a time.'
                : "This fund allows {$loans->maximumActiveLoans()} active loans at a time.";
        }

        if ($membershipDays < $loans->minimumMembershipDays()) {
            $failures[] = sprintf(
                'Members must belong to the fund for %d days before borrowing (currently %d).',
                $loans->minimumMembershipDays(),
                $membershipDays
            );
        }

        return new LoanEligibility(
            eligible: $failures === [],
            failures: $failures,
            requested: $amount,
            maximum: $maximum,
            outstanding: $outstanding,
            activeLoans: $activeLoans,
            maximumActiveLoans: $loans->maximumActiveLoans(),
            membershipDays: $membershipDays,
            requiredMembershipDays: $loans->minimumMembershipDays(),
            currency: $currency,
        );
    }

    /**
     * @param  array{amount: Decimal, currency: string, term_months: int, purpose?: ?string}  $data
     */
    public function request(GroupMembership $member, array $data, User $actor): Loan
    {
        $group = $member->group;
        $policy = $this->policies->requireActivePolicy($group);

        $eligibility = $this->eligibility(
            $member,
            $data['amount'],
            $data['currency'],
            $data['term_months'],
            $policy
        );

        if (! $eligibility->eligible) {
            throw new PolicyViolationException($eligibility->firstFailure() ?? 'This loan is not permitted.');
        }

        return DB::transaction(function () use ($member, $group, $policy, $data, $actor) {
            $loans = $policy->toPolicyConfig()->loans();

            $loan = Loan::create([
                'group_id' => $group->getKey(),
                'member_id' => $member->getKey(),
                'cost_center_id' => $member->costCenter?->getKey(),
                // The governing version, stored now and never swapped later.
                'policy_version_id' => $policy->getKey(),
                'reference' => $this->nextReference($group),
                'currency' => $data['currency'],
                'principal' => $data['amount']->toString(),
                // Rate and method are snapshotted from the policy: the contract
                // is fixed at creation even though the policy may move on.
                'interest_rate' => $loans->interestRate()->toString(),
                'interest_method' => $loans->interestMethod(),
                'term_months' => $data['term_months'],
                'status' => Loan::STATUS_REQUESTED,
                'purpose' => $data['purpose'] ?? null,
                'created_by' => $actor->getKey(),
            ]);

            $this->audit->record(
                AuditAction::LOAN_REQUESTED,
                group: $group,
                actor: $actor,
                object: $loan,
                new: [
                    'reference' => $loan->reference,
                    'principal' => (string) $loan->principal,
                    'currency' => $loan->currency,
                    'policy_version' => $policy->version,
                ]
            );

            return $loan;
        });
    }

    /**
     * Approval re-checks the rules — against the loan's own policy version, not
     * whatever is active today.
     */
    public function approve(Loan $loan, User $actor, ?string $note = null): Loan
    {
        if ($loan->status !== Loan::STATUS_REQUESTED) {
            throw new RuntimeException("Loan {$loan->reference} is {$loan->statusLabel()} and cannot be approved.");
        }

        $eligibility = $this->eligibility(
            $loan->member,
            Decimal::of((string) $loan->principal),
            $loan->currency,
            (int) $loan->term_months,
            $loan->policyVersion,
            excluding: $loan
        );

        if (! $eligibility->eligible) {
            throw new PolicyViolationException($eligibility->firstFailure() ?? 'This loan is no longer permitted.');
        }

        $loan->forceFill([
            'status' => Loan::STATUS_APPROVED,
            'approved_by' => $actor->getKey(),
            'approved_at' => now(),
            'decision_reason' => $note,
        ])->save();

        $this->audit->record(
            AuditAction::LOAN_APPROVED,
            group: $loan->group,
            actor: $actor,
            object: $loan,
            old: ['status' => Loan::STATUS_REQUESTED],
            new: ['status' => Loan::STATUS_APPROVED, 'policy_version' => $loan->policyVersion->version]
        );

        return $loan;
    }

    public function reject(Loan $loan, User $actor, string $reason): Loan
    {
        if ($loan->status !== Loan::STATUS_REQUESTED) {
            throw new RuntimeException("Loan {$loan->reference} is {$loan->statusLabel()} and cannot be rejected.");
        }

        $loan->forceFill([
            'status' => Loan::STATUS_REJECTED,
            'approved_by' => $actor->getKey(),
            'approved_at' => now(),
            'decision_reason' => $reason,
        ])->save();

        $this->audit->record(
            AuditAction::LOAN_REJECTED,
            group: $loan->group,
            actor: $actor,
            object: $loan,
            old: ['status' => Loan::STATUS_REQUESTED],
            new: ['status' => Loan::STATUS_REJECTED, 'reason' => $reason]
        );

        return $loan;
    }

    public function cancel(Loan $loan, User $actor): Loan
    {
        if (! in_array($loan->status, [Loan::STATUS_REQUESTED, Loan::STATUS_APPROVED], true)) {
            throw new RuntimeException("Loan {$loan->reference} can no longer be cancelled.");
        }

        $previous = $loan->status;
        $loan->forceFill(['status' => Loan::STATUS_CANCELLED])->save();

        $this->audit->record(
            AuditAction::LOAN_CANCELLED,
            group: $loan->group,
            actor: $actor,
            object: $loan,
            old: ['status' => $previous],
            new: ['status' => Loan::STATUS_CANCELLED]
        );

        return $loan;
    }

    /**
     * Record the payment out to the member.
     *
     * This creates a pending transaction rather than touching the loan directly:
     * the money only becomes real, and the loan only becomes active, when that
     * transaction is verified and posted.
     */
    public function recordDisbursement(
        Loan $loan,
        Treasury $treasury,
        Carbon $occurredOn,
        User $actor,
        ?string $reference = null,
        ?string $txHash = null,
    ): Transaction {
        if ($loan->status !== Loan::STATUS_APPROVED) {
            throw new RuntimeException("Loan {$loan->reference} must be approved before it can be disbursed.");
        }

        if ($treasury->currency !== $loan->currency) {
            throw new RuntimeException("Loan {$loan->reference} is denominated in {$loan->currency}.");
        }

        return $this->transactions->record($loan->group, [
            'treasury_id' => $treasury->getKey(),
            'member_id' => $loan->member_id,
            'loan_id' => $loan->getKey(),
            'policy_version_id' => $loan->policy_version_id,
            'type' => Transaction::TYPE_LOAN_DISBURSEMENT,
            'direction' => Transaction::DIRECTION_OUT,
            'amount' => (string) $loan->principal,
            'currency' => $loan->currency,
            'occurred_on' => $occurredOn,
            'reference' => $reference,
            'description' => "Disbursement of loan {$loan->reference}",
            'network' => $treasury->network,
            'tx_hash' => $txHash,
            'from_address' => $treasury->external_identifier,
        ], $actor);
    }

    /**
     * Apply a verified transaction to the loan.
     *
     * Runs inside the verification's database transaction, so a loan can never
     * be marked repaid by a posting that later rolls back.
     */
    public function applyVerifiedTransaction(Transaction $transaction, User $actor): void
    {
        $loan = $transaction->loan;

        if (! $loan) {
            return;
        }

        match ($transaction->type) {
            Transaction::TYPE_LOAN_DISBURSEMENT => $this->activate($loan, $transaction, $actor),
            Transaction::TYPE_LOAN_REPAYMENT => $this->applyRepayment($loan, $transaction),
            default => null,
        };
    }

    /**
     * Installments past their due date, and the loans that own them.
     */
    public function refreshOverdueState(Group $group): int
    {
        $today = Carbon::today();

        $overdue = LoanInstallment::query()
            ->forGroup($group)
            ->whereIn('status', [LoanInstallment::STATUS_PENDING, LoanInstallment::STATUS_PARTIALLY_PAID])
            ->whereDate('due_date', '<', $today)
            ->get();

        foreach ($overdue as $installment) {
            $installment->forceFill(['status' => LoanInstallment::STATUS_OVERDUE])->save();
        }

        $loanIds = $overdue->pluck('loan_id')->unique();

        Loan::query()
            ->forGroup($group)
            ->whereIn('id', $loanIds)
            ->whereIn('status', [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE])
            ->update(['status' => Loan::STATUS_OVERDUE]);

        return $overdue->count();
    }

    /**
     * Principal the member still owes, taken from the ledger.
     *
     * Approved-but-undisbursed loans are added on top: the fund has committed
     * that money even though it has not moved yet.
     */
    public function outstandingPrincipalFor(GroupMembership $member, string $currency, ?Loan $excluding = null): Decimal
    {
        $costCenter = $member->costCenter;
        $outstanding = Decimal::zero();

        if ($costCenter) {
            $receivable = Account::query()
                ->forGroup($member->group)
                ->where('code', Account::LOANS_RECEIVABLE)
                ->first();

            if ($receivable) {
                $outstanding = $this->balances
                    ->costCenterAccountNativeBalances($costCenter, $receivable)
                    ->get($currency, Decimal::zero());
            }
        }

        $committed = Loan::query()
            ->forGroup($member->group)
            ->where('member_id', $member->getKey())
            ->where('currency', $currency)
            ->where('status', Loan::STATUS_APPROVED)
            ->when($excluding, fn ($q) => $q->whereKeyNot($excluding->getKey()))
            ->sum('principal');

        return $outstanding->plus((string) $committed);
    }

    private function activeLoanCount(GroupMembership $member, ?Loan $excluding = null): int
    {
        return Loan::query()
            ->forGroup($member->group)
            ->where('member_id', $member->getKey())
            ->whereIn('status', array_merge(Loan::OUTSTANDING_STATUSES, [
                Loan::STATUS_REQUESTED,
                Loan::STATUS_APPROVED,
            ]))
            ->when($excluding, fn ($q) => $q->whereKeyNot($excluding->getKey()))
            ->count();
    }

    private function activate(Loan $loan, Transaction $transaction, User $actor): void
    {
        $disbursedOn = Carbon::parse($transaction->occurred_on);

        $loan->forceFill([
            'status' => Loan::STATUS_ACTIVE,
            'disbursed_at' => $disbursedOn,
        ])->save();

        if ($loan->installments()->doesntExist()) {
            $first = $disbursedOn->copy()->addMonthNoOverflow();
            $loan->forceFill(['first_installment_on' => $first])->save();

            foreach ($this->planner->plan($loan, $first) as $installment) {
                $installment->save();
            }
        }

        $this->audit->record(
            AuditAction::LOAN_DISBURSED,
            group: $loan->group,
            actor: $actor,
            object: $loan,
            new: [
                'status' => Loan::STATUS_ACTIVE,
                'principal' => (string) $loan->principal,
                'installments' => $loan->installments()->count(),
            ]
        );
    }

    private function applyRepayment(Loan $loan, Transaction $transaction): void
    {
        $allocation = $this->allocator->allocate($loan, Decimal::of((string) $transaction->amount));

        foreach ($allocation->installments as $installmentId => $applied) {
            $installment = $loan->installments()->whereKey($installmentId)->first();

            if (! $installment) {
                continue;
            }

            $paid = Decimal::of((string) $installment->paid_amount)->plus($applied);
            $total = Decimal::of((string) $installment->amount);

            $installment->forceFill([
                'paid_amount' => $paid->toString(),
                'status' => $paid->greaterThanOrEqual($total)
                    ? LoanInstallment::STATUS_PAID
                    : LoanInstallment::STATUS_PARTIALLY_PAID,
            ])->save();
        }

        $loan->refresh();

        // Settlement is decided by the schedule being satisfied, not by the
        // payment count: partial and early repayments both land here.
        $unsettled = $loan->installments()
            ->where('status', '!=', LoanInstallment::STATUS_PAID)
            ->exists();

        $outstanding = $loan->outstandingPrincipal();

        if (! $unsettled || $outstanding->isZero()) {
            $loan->forceFill(['status' => Loan::STATUS_FULLY_REPAID])->save();
        } elseif ($loan->status === Loan::STATUS_DISBURSED) {
            $loan->forceFill(['status' => Loan::STATUS_ACTIVE])->save();
        }
    }

    private function nextReference(Group $group): string
    {
        $count = Loan::query()->forGroup($group)->count();

        return sprintf('L-%05d', $count + 1);
    }
}
