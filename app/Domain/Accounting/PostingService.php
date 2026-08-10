<?php

namespace App\Domain\Accounting;

use App\Domain\Accounting\Exceptions\PostingException;
use App\Domain\Accounting\Exceptions\UnbalancedEntryException;
use App\Domain\Audit\AuditAction;
use App\Domain\Audit\AuditRecorder;
use App\Domain\ExchangeRates\RateService;
use App\Domain\Money\Decimal;
use App\Models\Group;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use App\Support\ImmutabilityGuard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Writes and posts journal entries. This is the only class in the application
 * permitted to create ledger rows.
 *
 * Everything it writes happens inside one database transaction: an entry that
 * fails validation leaves no trace, and a posted entry is complete, balanced and
 * valued at the moment it becomes visible.
 */
class PostingService
{
    public function __construct(
        private readonly RateService $rates,
        private readonly PeriodService $periods,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * Create and post an entry atomically. The normal path for a verified
     * financial event.
     */
    public function post(Group $group, EntryDraft $draft, User $actor, ?string $functionalCurrency = null): JournalEntry
    {
        return DB::transaction(function () use ($group, $draft, $actor, $functionalCurrency) {
            $entry = $this->createDraft($group, $draft, $actor, $functionalCurrency);

            return $this->postDraft($entry, $actor);
        });
    }

    /**
     * Write an unposted entry. Draft entries affect no balance.
     */
    public function createDraft(Group $group, EntryDraft $draft, User $actor, ?string $functionalCurrency = null): JournalEntry
    {
        $functionalCurrency ??= $group->functionalCurrency();
        $lines = $draft->lines();

        $this->validateLines($group, $lines);

        return DB::transaction(function () use ($group, $draft, $actor, $functionalCurrency, $lines) {
            // The period is decided by the entry date: a posting dated inside a
            // closed month is refused even if today's month is open.
            $period = $this->periods->requireOpenPeriod($group, $draft->entryDate);

            $entry = JournalEntry::create([
                'group_id' => $group->getKey(),
                'entry_number' => $this->nextEntryNumber($group, $draft->entryDate),
                'transaction_id' => $draft->transaction()?->getKey(),
                'accounting_period_id' => $period->getKey(),
                'template' => $draft->template,
                'entry_date' => $draft->entryDate,
                'functional_currency' => $functionalCurrency,
                'description' => $draft->description,
                'reason' => $draft->reason(),
                'status' => JournalEntry::STATUS_DRAFT,
                'created_by' => $actor->getKey(),
            ]);

            foreach ($this->valueLines($lines, $functionalCurrency, $draft->entryDate) as $index => $valued) {
                JournalLine::create([
                    'journal_entry_id' => $entry->getKey(),
                    'group_id' => $group->getKey(),
                    'account_id' => $valued['line']->account->getKey(),
                    'cost_center_id' => $valued['line']->costCenter?->getKey(),
                    'currency' => $valued['line']->amount->currency,
                    'debit' => $valued['line']->isDebit() ? $valued['native']->toString() : '0',
                    'credit' => $valued['line']->isDebit() ? '0' : $valued['native']->toString(),
                    'functional_currency' => $functionalCurrency,
                    'functional_debit' => $valued['line']->isDebit() ? $valued['functional']->toString() : '0',
                    'functional_credit' => $valued['line']->isDebit() ? '0' : $valued['functional']->toString(),
                    'exchange_rate_snapshot' => $valued['rate']->toString(),
                    'gold_rate_snapshot' => $valued['gold_rate']?->toString(),
                    'gold_value_snapshot' => $valued['gold_value']?->toString(),
                    'description' => $valued['line']->description,
                    'sequence' => $index + 1,
                ]);
            }

            return $entry->load('lines');
        });
    }

    /**
     * Validate and post a draft. After this the entry is immutable.
     */
    public function postDraft(JournalEntry $entry, User $actor): JournalEntry
    {
        if ($entry->isPosted()) {
            return $entry;
        }

        return DB::transaction(function () use ($entry, $actor) {
            $this->validateBalanced($entry);

            // The period is re-checked here: a draft may have been sitting
            // around since before the month was closed.
            $this->periods->requireOpenPeriod($entry->group, Carbon::parse($entry->entry_date));

            ImmutabilityGuard::without(ImmutabilityGuard::LEDGER, function () use ($entry, $actor) {
                $entry->forceFill([
                    'status' => JournalEntry::STATUS_POSTED,
                    'posting_date' => Carbon::today(),
                    'posted_by' => $actor->getKey(),
                    'posted_at' => now(),
                ])->save();
            });

            $this->audit->record(
                AuditAction::JOURNAL_POSTED,
                group: $entry->group,
                actor: $actor,
                object: $entry,
                new: [
                    'entry_number' => $entry->entry_number,
                    'template' => $entry->template,
                    'description' => $entry->description,
                ]
            );

            return $entry->refresh();
        });
    }

    /**
     * Reverse a posted entry.
     *
     * The reversal copies each original line's valuation snapshots verbatim
     * rather than re-quoting today's rates: reversing an old entry must undo
     * exactly what was posted, not a revalued version of it.
     */
    public function reverse(JournalEntry $entry, string $reason, User $actor, ?Carbon $date = null): JournalEntry
    {
        if (! $entry->isPosted()) {
            throw new PostingException(__('exceptions.journal_not_posted_cannot_reverse', ['entry' => $entry->entry_number]));
        }

        if ($entry->isReversed()) {
            throw new PostingException(__('exceptions.journal_already_reversed', ['entry' => $entry->entry_number]));
        }

        $date = ($date ?? Carbon::today())->copy()->startOfDay();

        return DB::transaction(function () use ($entry, $reason, $actor, $date) {
            $group = $entry->group;

            // Corrections for a closed month are posted in an open period.
            $period = $this->periods->requireOpenPeriod($group, $date);

            $reversal = JournalEntry::create([
                'group_id' => $group->getKey(),
                'entry_number' => $this->nextEntryNumber($group, $date),
                'transaction_id' => $entry->transaction_id,
                'accounting_period_id' => $period->getKey(),
                'reverses_entry_id' => $entry->getKey(),
                'template' => 'Reversal',
                'entry_date' => $date,
                'functional_currency' => $entry->functional_currency,
                'description' => "Reversal of {$entry->entry_number}: {$entry->description}",
                'reason' => $reason,
                'status' => JournalEntry::STATUS_DRAFT,
                'created_by' => $actor->getKey(),
            ]);

            foreach ($entry->lines as $index => $line) {
                JournalLine::create([
                    'journal_entry_id' => $reversal->getKey(),
                    'group_id' => $group->getKey(),
                    'account_id' => $line->account_id,
                    'cost_center_id' => $line->cost_center_id,
                    'currency' => $line->currency,
                    // Sides swap; amounts and snapshots are preserved exactly.
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'functional_currency' => $line->functional_currency,
                    'functional_debit' => $line->functional_credit,
                    'functional_credit' => $line->functional_debit,
                    'exchange_rate_snapshot' => $line->exchange_rate_snapshot,
                    'gold_rate_snapshot' => $line->gold_rate_snapshot,
                    'gold_value_snapshot' => $line->gold_value_snapshot,
                    'description' => $line->description,
                    'sequence' => $index + 1,
                ]);
            }

            $reversal = $this->postDraft($reversal->load('lines'), $actor);

            // The original stays posted and visible; 'reversed' records that a
            // reversing entry exists, which is why isPosted() includes it.
            ImmutabilityGuard::without(ImmutabilityGuard::LEDGER, function () use ($entry) {
                $entry->forceFill(['status' => JournalEntry::STATUS_REVERSED])->save();
            });

            $this->audit->record(
                AuditAction::JOURNAL_REVERSED,
                group: $group,
                actor: $actor,
                object: $entry,
                old: ['status' => JournalEntry::STATUS_POSTED],
                new: ['status' => JournalEntry::STATUS_REVERSED, 'reversal' => $reversal->entry_number, 'reason' => $reason]
            );

            return $reversal;
        });
    }

    /**
     * SUM(debits) = SUM(credits), checked on the functional column so that
     * multi-currency entries balance on a single dimension.
     *
     * @throws UnbalancedEntryException
     */
    public function validateBalanced(JournalEntry $entry): void
    {
        $debits = Decimal::zero();
        $credits = Decimal::zero();

        foreach ($entry->lines as $line) {
            $debits = $debits->plus((string) $line->functional_debit);
            $credits = $credits->plus((string) $line->functional_credit);
        }

        if (! $debits->equals($credits)) {
            throw new UnbalancedEntryException($debits, $credits, $entry->functional_currency);
        }

        if ($debits->isZero()) {
            throw new PostingException(__('exceptions.journal_no_value', ['entry' => $entry->entry_number]));
        }
    }

    /**
     * @param  list<LineDraft>  $lines
     */
    private function validateLines(Group $group, array $lines): void
    {
        if (count($lines) < 2) {
            throw new PostingException(__('exceptions.entry_needs_two_lines'));
        }

        foreach ($lines as $line) {
            $account = $line->account;

            // Tenant isolation reaches into the ledger: an entry may never touch
            // another group's account or cost center.
            if (! $account->belongsToGroup($group)) {
                throw new PostingException(__('exceptions.account_foreign_to_group', ['code' => $account->code]));
            }

            if (! $account->is_active) {
                throw new PostingException(__('exceptions.account_inactive', ['account' => $account->label()]));
            }

            if ($line->costCenter && ! $line->costCenter->belongsToGroup($group)) {
                throw new PostingException(__('exceptions.cost_center_foreign_to_group'));
            }

            if ($account->requires_cost_center && ! $line->costCenter) {
                throw new PostingException(__('exceptions.account_requires_cost_center', ['account' => $account->label()]));
            }

            if (! $line->amount->isPositive()) {
                throw new PostingException(__('exceptions.line_amount_must_be_positive', ['account' => $account->label()]));
            }
        }
    }

    /**
     * Value every line in the functional currency and in grams of 18K gold.
     *
     * When all lines share one currency the functional amounts are allocated
     * from a single conversion of the entry total, with the rounding residual
     * given to the largest line on each side. Converting each line separately
     * could leave the two sides differing by a hundred-millionth and make an
     * otherwise valid entry unpostable.
     *
     * @param  list<LineDraft>  $lines
     * @return list<array{line: LineDraft, native: Decimal, functional: Decimal, rate: Decimal, gold_rate: ?Decimal, gold_value: ?Decimal}>
     */
    private function valueLines(array $lines, string $functionalCurrency, Carbon $date): array
    {
        $currencies = array_unique(array_map(fn (LineDraft $line) => $line->amount->currency, $lines));
        $singleCurrency = count($currencies) === 1 ? reset($currencies) : null;

        $valued = [];

        foreach ($lines as $line) {
            $currency = $line->amount->currency;
            $native = $line->amount->amount;

            // The amount is converted directly rather than multiplied by the
            // rounded rate snapshot: rounding a rate to twelve places and then
            // scaling it by a large amount reintroduces the error the snapshot
            // was meant to record.
            $rate = $currency === $functionalCurrency
                ? Decimal::of('1', Decimal::RATE_SCALE)
                : $this->rates->crossRate($currency, $functionalCurrency, $date);

            $functional = $currency === $functionalCurrency
                ? $native
                : $this->rates->convert($native, $currency, $functionalCurrency, $date);

            // Gold is a reporting layer, so a missing gold rate leaves the
            // snapshot null instead of blocking the posting or inventing a value.
            $goldQuote = $this->rates->tryQuote($currency, $date);

            $valued[] = [
                'line' => $line,
                'native' => $native,
                'functional' => $functional->withScale(Decimal::MONEY_SCALE),
                'rate' => $rate,
                'gold_rate' => $goldQuote?->unitsPerGram18k,
                'gold_value' => $goldQuote?->toGold($native),
            ];
        }

        if ($singleCurrency !== null && $singleCurrency !== $functionalCurrency) {
            $valued = $this->reallocateResidual($valued, $functionalCurrency, $date, $singleCurrency);
        }

        return $valued;
    }

    /**
     * @param  list<array{line: LineDraft, native: Decimal, functional: Decimal, rate: Decimal, gold_rate: ?Decimal, gold_value: ?Decimal}>  $valued
     * @return list<array{line: LineDraft, native: Decimal, functional: Decimal, rate: Decimal, gold_rate: ?Decimal, gold_value: ?Decimal}>
     */
    private function reallocateResidual(array $valued, string $functionalCurrency, Carbon $date, string $currency): array
    {
        foreach ([true, false] as $debitSide) {
            $indexes = array_keys(array_filter(
                $valued,
                fn (array $item) => $item['line']->isDebit() === $debitSide
            ));

            if ($indexes === []) {
                continue;
            }

            $nativeTotal = Decimal::zero();
            foreach ($indexes as $i) {
                $nativeTotal = $nativeTotal->plus($valued[$i]['native']);
            }

            // One conversion of the side's total is the authoritative figure.
            $target = $this->rates->convert($nativeTotal, $currency, $functionalCurrency, $date);

            $allocated = Decimal::zero();
            $largest = $indexes[0];

            foreach ($indexes as $i) {
                if ($valued[$i]['native']->greaterThan($valued[$largest]['native'])) {
                    $largest = $i;
                }

                $allocated = $allocated->plus($valued[$i]['functional']);
            }

            $residual = $target->minus($allocated);

            if (! $residual->isZero()) {
                $valued[$largest]['functional'] = $valued[$largest]['functional']->plus($residual);
            }
        }

        return $valued;
    }

    /**
     * Sequential per group and year: JE-2026-000001.
     */
    private function nextEntryNumber(Group $group, Carbon $date): string
    {
        $year = $date->year;

        // Lock the group row so two concurrent postings cannot claim the same
        // number and collide on the unique index.
        Group::whereKey($group->getKey())->lockForUpdate()->first();

        $used = JournalEntry::query()
            ->forGroup($group)
            ->where('entry_number', 'like', "JE-{$year}-%")
            ->count();

        return sprintf('JE-%d-%06d', $year, $used + 1);
    }
}
