<?php

namespace App\Domain\Accounting;

use App\Domain\Money\Decimal;
use App\Domain\Money\Money;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Group;
use App\Models\JournalEntry;
use App\Models\Treasury;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every balance in the application is derived here, from posted journal lines.
 *
 * There is no mutable balance column anywhere in the schema: the ledger is the
 * source of truth, so a treasury balance, a member's position and the trial
 * balance are all the same query shape over the same rows.
 */
class LedgerBalances
{
    /**
     * Native balances of one account, per currency.
     *
     * @return Collection<string, Decimal> currency => signed balance
     */
    public function accountNativeBalances(Account $account, ?Carbon $asOf = null): Collection
    {
        $rows = $this->lines($account->group, $asOf)
            ->where('jl.account_id', $account->getKey())
            ->groupBy('jl.currency')
            ->select('jl.currency', DB::raw('SUM(jl.debit) as debit'), DB::raw('SUM(jl.credit) as credit'))
            ->get();

        return collect($rows)->mapWithKeys(fn (object $row) => [
            $row->currency => $this->signed($account, (string) $row->debit, (string) $row->credit),
        ]);
    }

    /**
     * Functional-currency balance of one account, optionally narrowed to a
     * single cost center.
     */
    public function accountBalance(Account $account, ?Carbon $asOf = null, ?CostCenter $costCenter = null): Decimal
    {
        $row = $this->lines($account->group, $asOf)
            ->where('jl.account_id', $account->getKey())
            ->when($costCenter, fn (Builder $q) => $q->where('jl.cost_center_id', $costCenter->getKey()))
            ->select(
                DB::raw('COALESCE(SUM(jl.functional_debit), 0) as debit'),
                DB::raw('COALESCE(SUM(jl.functional_credit), 0) as credit')
            )
            ->first();

        return $this->signed($account, (string) ($row->debit ?? '0'), (string) ($row->credit ?? '0'));
    }

    /**
     * A treasury's balance in its own currency — what the fund believes it holds
     * there, and the figure reconciliation compares against the outside world.
     */
    public function treasuryBalance(Treasury $treasury, ?Carbon $asOf = null): Money
    {
        if (! $treasury->account) {
            return Money::zero($treasury->currency);
        }

        $row = $this->lines($treasury->group, $asOf)
            ->where('jl.account_id', $treasury->account_id)
            ->where('jl.currency', $treasury->currency)
            ->select(
                DB::raw('COALESCE(SUM(jl.debit), 0) as debit'),
                DB::raw('COALESCE(SUM(jl.credit), 0) as credit')
            )
            ->first();

        $balance = Decimal::of((string) ($row->debit ?? '0'))->minus((string) ($row->credit ?? '0'));

        return Money::of($balance, $treasury->currency);
    }

    /**
     * @return Collection<int, array{treasury: Treasury, balance: Money}>
     */
    public function treasuryBalances(Group $group, ?Carbon $asOf = null): Collection
    {
        return $group->treasuries()
            ->with('account')
            ->orderBy('name')
            ->get()
            ->map(fn (Treasury $treasury) => [
                'treasury' => $treasury,
                'balance' => $this->treasuryBalance($treasury, $asOf),
            ]);
    }

    /**
     * Trial balance: one row per account with movement, in functional currency.
     *
     * @return Collection<int, array{account: Account, debit: Decimal, credit: Decimal, balance: Decimal}>
     */
    public function trialBalance(Group $group, ?Carbon $asOf = null): Collection
    {
        $totals = $this->lines($group, $asOf)
            ->groupBy('jl.account_id')
            ->select(
                'jl.account_id',
                DB::raw('SUM(jl.functional_debit) as debit'),
                DB::raw('SUM(jl.functional_credit) as credit')
            )
            ->get()
            ->keyBy('account_id');

        return $group->accounts()
            ->orderBy('code')
            ->get()
            ->filter(fn (Account $account) => $totals->has($account->getKey()))
            ->map(function (Account $account) use ($totals) {
                $row = $totals->get($account->getKey());
                $debit = Decimal::of((string) $row->debit);
                $credit = Decimal::of((string) $row->credit);

                return [
                    'account' => $account,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $this->signed($account, $debit->toString(), $credit->toString()),
                ];
            })
            ->values();
    }

    /**
     * Totals per account type, for the balance sheet and income statement.
     *
     * @return array<string, Decimal>
     */
    public function typeTotals(Group $group, ?Carbon $asOf = null, ?Carbon $from = null): array
    {
        $rows = $this->lines($group, $asOf, $from)
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->groupBy('a.type')
            ->select('a.type', DB::raw('SUM(jl.functional_debit) as debit'), DB::raw('SUM(jl.functional_credit) as credit'))
            ->get();

        $totals = [
            Account::TYPE_ASSET => Decimal::zero(),
            Account::TYPE_LIABILITY => Decimal::zero(),
            Account::TYPE_EQUITY => Decimal::zero(),
            Account::TYPE_INCOME => Decimal::zero(),
            Account::TYPE_EXPENSE => Decimal::zero(),
        ];

        foreach ($rows as $row) {
            $debit = Decimal::of((string) $row->debit);
            $credit = Decimal::of((string) $row->credit);

            $totals[$row->type] = in_array($row->type, [Account::TYPE_ASSET, Account::TYPE_EXPENSE], true)
                ? $debit->minus($credit)
                : $credit->minus($debit);
        }

        return $totals;
    }

    /**
     * Balances by account for one cost center: the member's position, derived
     * rather than stored.
     *
     * @return Collection<int, array{account: Account, debit: Decimal, credit: Decimal, balance: Decimal}>
     */
    public function costCenterStatement(CostCenter $costCenter, ?Carbon $asOf = null): Collection
    {
        $rows = $this->lines($costCenter->group, $asOf)
            ->where('jl.cost_center_id', $costCenter->getKey())
            ->groupBy('jl.account_id')
            ->select(
                'jl.account_id',
                DB::raw('SUM(jl.functional_debit) as debit'),
                DB::raw('SUM(jl.functional_credit) as credit')
            )
            ->get();

        $accounts = Account::query()
            ->whereIn('id', $rows->pluck('account_id'))
            ->get()
            ->keyBy('id');

        return collect($rows)
            ->map(function (object $row) use ($accounts) {
                $account = $accounts->get($row->account_id);
                $debit = Decimal::of((string) $row->debit);
                $credit = Decimal::of((string) $row->credit);

                return [
                    'account' => $account,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $this->signed($account, $debit->toString(), $credit->toString()),
                ];
            })
            ->sortBy(fn (array $row) => $row['account']->code)
            ->values();
    }

    /**
     * Native-currency balance of one account for one cost center — used for
     * outstanding loan principal per member.
     *
     * @return Collection<string, Decimal>
     */
    public function costCenterAccountNativeBalances(CostCenter $costCenter, Account $account, ?Carbon $asOf = null): Collection
    {
        $rows = $this->lines($costCenter->group, $asOf)
            ->where('jl.cost_center_id', $costCenter->getKey())
            ->where('jl.account_id', $account->getKey())
            ->groupBy('jl.currency')
            ->select('jl.currency', DB::raw('SUM(jl.debit) as debit'), DB::raw('SUM(jl.credit) as credit'))
            ->get();

        return collect($rows)->mapWithKeys(fn (object $row) => [
            $row->currency => $this->signed($account, (string) $row->debit, (string) $row->credit),
        ]);
    }

    /**
     * Outstanding principal per cost center, in native currencies.
     *
     * @return Collection<int, array{cost_center: CostCenter, balances: Collection<string, Decimal>}>
     */
    public function loanReceivables(Group $group, ?Carbon $asOf = null): Collection
    {
        $account = Account::query()->forGroup($group)->where('code', Account::LOANS_RECEIVABLE)->first();

        if (! $account) {
            return collect();
        }

        $rows = $this->lines($group, $asOf)
            ->where('jl.account_id', $account->getKey())
            ->whereNotNull('jl.cost_center_id')
            ->groupBy('jl.cost_center_id', 'jl.currency')
            ->select(
                'jl.cost_center_id',
                'jl.currency',
                DB::raw('SUM(jl.debit) as debit'),
                DB::raw('SUM(jl.credit) as credit')
            )
            ->get();

        $costCenters = CostCenter::query()
            ->whereIn('id', $rows->pluck('cost_center_id')->unique())
            ->get()
            ->keyBy('id');

        return collect($rows)
            ->groupBy('cost_center_id')
            ->map(fn (Collection $group, $costCenterId) => [
                'cost_center' => $costCenters->get($costCenterId),
                'balances' => $group->mapWithKeys(fn (object $row) => [
                    $row->currency => Decimal::of((string) $row->debit)->minus((string) $row->credit),
                ])->filter(fn (Decimal $balance) => ! $balance->isZero()),
            ])
            ->filter(fn (array $row) => $row['cost_center'] !== null && $row['balances']->isNotEmpty())
            ->sortBy(fn (array $row) => $row['cost_center']->code)
            ->values();
    }

    /**
     * Group value expressed in grams of 18K gold, from the valuation snapshots
     * frozen on each line at posting time.
     *
     * Lines posted before a gold rate existed for their currency carry no
     * snapshot and are reported separately rather than valued at today's rate.
     *
     * @return array{grams: Decimal, unvalued_lines: int}
     */
    public function goldValuation(Group $group, ?Carbon $asOf = null): array
    {
        $row = $this->lines($group, $asOf)
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('a.type', Account::TYPE_ASSET)
            ->select(
                DB::raw('COALESCE(SUM(CASE WHEN jl.debit > 0 THEN jl.gold_value_snapshot ELSE -jl.gold_value_snapshot END), 0) as grams'),
                DB::raw('COUNT(*) FILTER (WHERE jl.gold_value_snapshot IS NULL) as unvalued')
            )
            ->first();

        return [
            'grams' => Decimal::of((string) ($row->grams ?? '0')),
            'unvalued_lines' => (int) ($row->unvalued ?? 0),
        ];
    }

    /**
     * Posted lines for one account, most recent first — the general ledger view.
     */
    public function generalLedger(Account $account, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        return $account->lines()
            ->with(['journalEntry', 'costCenter'])
            ->posted()
            ->when($from, fn ($q) => $q->whereHas('journalEntry', fn ($e) => $e->whereDate('entry_date', '>=', $from)))
            ->when($to, fn ($q) => $q->whereHas('journalEntry', fn ($e) => $e->whereDate('entry_date', '<=', $to)))
            ->get()
            ->sortByDesc(fn ($line) => [$line->journalEntry->entry_date, $line->id])
            ->values();
    }

    /**
     * Posted lines only.
     *
     * An entry marked 'reversed' is still posted and its lines still count: the
     * reversing entry contributes the opposite lines, so the two cancel and the
     * whole history stays visible.
     */
    private function lines(Group $group, ?Carbon $asOf = null, ?Carbon $from = null): Builder
    {
        return DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.group_id', $group->getKey())
            ->whereIn('je.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->when($asOf, fn (Builder $q) => $q->whereDate('je.entry_date', '<=', $asOf))
            ->when($from, fn (Builder $q) => $q->whereDate('je.entry_date', '>=', $from));
    }

    /**
     * Debit-normal accounts increase with debits; the rest with credits.
     */
    private function signed(Account $account, string $debit, string $credit): Decimal
    {
        return $account->isDebitNormal()
            ? Decimal::of($debit)->minus($credit)
            : Decimal::of($credit)->minus($debit);
    }
}
