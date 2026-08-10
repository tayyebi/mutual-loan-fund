<?php

namespace App\Domain\Reports;

use App\Domain\Accounting\LedgerBalances;
use App\Domain\ExchangeRates\RateService;
use App\Domain\Money\Decimal;
use App\Domain\Money\Money;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Loan;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-only views over the ledger.
 *
 * Every figure here is derived from posted journal lines. Nothing is stored as a
 * summary, so a report can never drift away from the accounts it describes.
 */
class ReportService
{
    public function __construct(
        private readonly LedgerBalances $balances,
        private readonly RateService $rates,
    ) {}

    /**
     * @return Collection<int, array{account: Account, debit: Decimal, credit: Decimal, balance: Decimal}>
     */
    public function trialBalance(Group $group, ?Carbon $asOf = null): Collection
    {
        return $this->balances->trialBalance($group, $asOf);
    }

    /**
     * Totals for the trial balance footer: the invariant made visible.
     *
     * @return array{debit: Decimal, credit: Decimal, balanced: bool}
     */
    public function trialBalanceTotals(Group $group, ?Carbon $asOf = null): array
    {
        $debit = Decimal::zero();
        $credit = Decimal::zero();

        foreach ($this->trialBalance($group, $asOf) as $row) {
            $debit = $debit->plus($row['debit']);
            $credit = $credit->plus($row['credit']);
        }

        return [
            'debit' => $debit,
            'credit' => $credit,
            'balanced' => $debit->equals($credit),
        ];
    }

    /**
     * @return array{assets: Decimal, liabilities: Decimal, equity: Decimal, result: Decimal, balanced: bool, currency: string}
     */
    public function balanceSheet(Group $group, ?Carbon $asOf = null): array
    {
        $totals = $this->balances->typeTotals($group, $asOf);

        // Income and expenses of the period roll into equity as the result for
        // the year; without it the sheet would not balance.
        $result = $totals[Account::TYPE_INCOME]->minus($totals[Account::TYPE_EXPENSE]);
        $equity = $totals[Account::TYPE_EQUITY]->plus($result);

        return [
            'assets' => $totals[Account::TYPE_ASSET],
            'liabilities' => $totals[Account::TYPE_LIABILITY],
            'equity' => $equity,
            'result' => $result,
            'balanced' => $totals[Account::TYPE_ASSET]->equals($totals[Account::TYPE_LIABILITY]->plus($equity)),
            'currency' => $group->functionalCurrency(),
        ];
    }

    /**
     * @return array{income: Decimal, expenses: Decimal, net: Decimal, currency: string, rows: Collection<int, array{account: Account, balance: Decimal}>}
     */
    public function incomeStatement(Group $group, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $totals = $this->balances->typeTotals($group, $to, $from);

        $rows = $this->balances->trialBalance($group, $to)
            ->filter(fn (array $row) => in_array(
                $row['account']->type,
                [Account::TYPE_INCOME, Account::TYPE_EXPENSE],
                true
            ))
            ->map(fn (array $row) => ['account' => $row['account'], 'balance' => $row['balance']])
            ->values();

        return [
            'income' => $totals[Account::TYPE_INCOME],
            'expenses' => $totals[Account::TYPE_EXPENSE],
            'net' => $totals[Account::TYPE_INCOME]->minus($totals[Account::TYPE_EXPENSE]),
            'currency' => $group->functionalCurrency(),
            'rows' => $rows,
        ];
    }

    /**
     * Native balance per treasury, with its gold equivalent at today's rate.
     *
     * @return Collection<int, array{treasury: \App\Models\Treasury, balance: Money, gold: ?Decimal}>
     */
    public function treasuryReport(Group $group, ?Carbon $asOf = null): Collection
    {
        return $this->balances->treasuryBalances($group, $asOf)->map(function (array $row) use ($asOf) {
            $quote = $this->rates->tryQuote($row['balance']->currency, $asOf);

            return [
                'treasury' => $row['treasury'],
                'balance' => $row['balance'],
                'gold' => $quote?->toGold($row['balance']->amount),
            ];
        });
    }

    /**
     * @return Collection<int, array{cost_center: CostCenter, balances: Collection<string, Decimal>}>
     */
    public function loanReceivables(Group $group, ?Carbon $asOf = null): Collection
    {
        return $this->balances->loanReceivables($group, $asOf);
    }

    /**
     * @return Collection<int, array{account: Account, debit: Decimal, credit: Decimal, balance: Decimal}>
     */
    public function costCenterStatement(CostCenter $costCenter, ?Carbon $asOf = null): Collection
    {
        return $this->balances->costCenterStatement($costCenter, $asOf);
    }

    /**
     * A member's own position, in the terms they care about.
     *
     * @return array{contributed: Decimal, outstanding: Decimal, interest_paid: Decimal, currency: string, gold: ?Decimal, statement: Collection<int, array{account: Account, debit: Decimal, credit: Decimal, balance: Decimal}>}
     */
    public function memberPosition(GroupMembership $member, ?Carbon $asOf = null): array
    {
        $costCenter = $member->costCenter;
        $currency = $member->group->functionalCurrency();

        if (! $costCenter) {
            return [
                'contributed' => Decimal::zero(),
                'outstanding' => Decimal::zero(),
                'interest_paid' => Decimal::zero(),
                'currency' => $currency,
                'gold' => null,
                'statement' => collect(),
            ];
        }

        $statement = $this->costCenterStatement($costCenter, $asOf);

        $byCode = function (string $code) use ($statement): Decimal {
            $row = $statement->first(fn (array $row) => $row['account']->code === $code);

            return $row['balance'] ?? Decimal::zero();
        };

        $contributed = $byCode(Account::FUND_CAPITAL);
        $outstanding = $byCode(Account::LOANS_RECEIVABLE);
        $interestPaid = $byCode(Account::LOAN_INTEREST_INCOME);

        $quote = $this->rates->tryQuote($currency, $asOf);

        return [
            'contributed' => $contributed,
            'outstanding' => $outstanding,
            'interest_paid' => $interestPaid,
            'currency' => $currency,
            'gold' => $quote?->toGold($contributed->minus($outstanding)),
            'statement' => $statement,
        ];
    }

    /**
     * The whole fund expressed in grams of 18K gold, from the snapshots frozen
     * on each posted line.
     *
     * @return array{grams: Decimal, unvalued_lines: int, treasuries: Collection<int, array{treasury: \App\Models\Treasury, balance: Money, gold: ?Decimal}>}
     */
    public function goldValuation(Group $group, ?Carbon $asOf = null): array
    {
        return $this->balances->goldValuation($group, $asOf) + [
            'treasuries' => $this->treasuryReport($group, $asOf),
        ];
    }

    /**
     * Everything the group dashboard shows.
     *
     * @return array{gold: Decimal, unvalued_lines: int, treasuries: Collection<int, mixed>, outstanding_loans: Collection<string, Decimal>, activity: Collection<int, Transaction>, pending: int}
     */
    public function dashboard(Group $group): array
    {
        $valuation = $this->balances->goldValuation($group);

        // Summed explicitly per currency: flattening the per-cost-centre maps
        // would collapse two members' balances in the same currency into one.
        $outstanding = collect();

        foreach ($this->balances->loanReceivables($group) as $row) {
            foreach ($row['balances'] as $currency => $amount) {
                $outstanding[$currency] = ($outstanding[$currency] ?? Decimal::zero())->plus($amount);
            }
        }

        return [
            'gold' => $valuation['grams'],
            'unvalued_lines' => $valuation['unvalued_lines'],
            'treasuries' => $this->treasuryReport($group),
            'outstanding_loans' => $outstanding,
            'activity' => $this->recentActivity($group),
            'pending' => Transaction::query()
                ->forGroup($group)
                ->where('status', Transaction::STATUS_PENDING)
                ->count(),
        ];
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function recentActivity(Group $group, int $limit = 10): Collection
    {
        return Transaction::query()
            ->forGroup($group)
            ->with(['member.user', 'treasury'])
            ->where('status', Transaction::STATUS_VERIFIED)
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Loans a member can still repay, for the repayment form.
     *
     * @return Collection<int, Loan>
     */
    public function repayableLoans(GroupMembership $member): Collection
    {
        return Loan::query()
            ->forGroup($member->group)
            ->where('member_id', $member->getKey())
            ->whereIn('status', Loan::OUTSTANDING_STATUSES)
            ->orderBy('reference')
            ->get();
    }

    /*
    |---------------------------------------------------------------------------
    | The member's own view
    |---------------------------------------------------------------------------
    | What the /u surface reads. Everything below is scoped to one membership and
    | phrased in the terms that membership cares about, so a member page never
    | has to filter a fund-wide result set down to "mine" itself.
    */

    /**
     * Everything the member overview needs in one call.
     *
     * `tracked` is the honest answer to "are these figures real?". A membership
     * with no cost centre has no ledger identity yet, so memberPosition() can
     * only return zeroes — which is indistinguishable from a member who has
     * genuinely contributed nothing. The flag lets the page say which it is
     * instead of showing a confident 0.00.
     *
     * @return array{position: array<string, mixed>, tracked: bool, pending: int, outstanding: Collection<int, Loan>, undecided: Collection<int, Loan>}
     */
    public function memberOverview(GroupMembership $member, ?Carbon $asOf = null): array
    {
        $loans = $this->memberLoans($member);

        return [
            'position' => $this->memberPosition($member, $asOf),
            'tracked' => $member->costCenter !== null,
            'pending' => $this->memberSubmissions($member)
                ->where('status', Transaction::STATUS_PENDING)
                ->count(),
            'outstanding' => $loans->filter(fn (Loan $loan) => $loan->isOutstanding())->values(),
            'undecided' => $loans->filter(fn (Loan $loan) => in_array($loan->status, [
                Loan::STATUS_REQUESTED,
                Loan::STATUS_APPROVED,
            ], true))->values(),
        ];
    }

    /**
     * Every loan this member has ever had, newest first.
     *
     * @return Collection<int, Loan>
     */
    public function memberLoans(GroupMembership $member): Collection
    {
        return Loan::query()
            ->forGroup($member->group)
            ->where('member_id', $member->getKey())
            // Eager-loaded so Loan::nextInstallment() costs nothing per row.
            ->with(['policyVersion', 'installments'])
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Transactions this member submitted or that were recorded against them —
     * their side of the fund's activity, in every status.
     *
     * @return Collection<int, Transaction>
     */
    public function memberSubmissions(GroupMembership $member, ?int $limit = null): Collection
    {
        return Transaction::query()
            ->forGroup($member->group)
            ->where('member_id', $member->getKey())
            ->with(['treasury', 'loan'])
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->when($limit, fn ($q, $take) => $q->limit($take))
            ->get();
    }

    /**
     * Money in: what this member has paid into the fund, and what is still
     * waiting on an administrator.
     *
     * @return Collection<int, Transaction>
     */
    public function memberContributions(GroupMembership $member): Collection
    {
        return $this->memberSubmissions($member)
            ->where('type', Transaction::TYPE_CONTRIBUTION)
            ->values();
    }
}
