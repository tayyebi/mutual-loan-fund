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

        $byCode = fn (string $code) => $statement
            ->firstWhere(fn (array $row) => $row['account']->code === $code)['balance'] ?? Decimal::zero();

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

        $outstanding = $this->balances->loanReceivables($group)
            ->flatMap(fn (array $row) => $row['balances']->all())
            ->groupBy(fn (Decimal $balance, string $currency) => $currency)
            ->map(fn (Collection $amounts) => $amounts->reduce(
                fn (Decimal $carry, Decimal $amount) => $carry->plus($amount),
                Decimal::zero()
            ));

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
}
