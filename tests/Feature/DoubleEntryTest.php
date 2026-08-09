<?php

namespace Tests\Feature;

use App\Domain\Accounting\AccountingService;
use App\Domain\Accounting\EntryDraft;
use App\Domain\Accounting\Exceptions\ClosedPeriodException;
use App\Domain\Accounting\Exceptions\PostingException;
use App\Domain\Accounting\Exceptions\UnbalancedEntryException;
use App\Domain\Accounting\LedgerBalances;
use App\Domain\Accounting\PeriodService;
use App\Domain\Accounting\PostingService;
use App\Domain\Money\Decimal;
use App\Domain\Money\Money;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

/**
 * The accounting invariants: entries balance, posted entries never change, and
 * closed periods refuse new postings.
 */
class DoubleEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_posted_entry_balances(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $treasury = $this->treasury($fund, $admin);
        $member = $admin->membershipFor($fund);
        $this->rate('USDT', '95');

        $this->contribute($member, $treasury, '500', $admin);

        $entries = JournalEntry::query()->forGroup($fund)->with('lines')->get();

        $this->assertNotEmpty($entries);

        foreach ($entries as $entry) {
            $debits = Decimal::zero();
            $credits = Decimal::zero();

            foreach ($entry->lines as $line) {
                $debits = $debits->plus((string) $line->functional_debit);
                $credits = $credits->plus((string) $line->functional_credit);
            }

            $this->assertTrue(
                $debits->equals($credits),
                "Entry {$entry->entry_number} does not balance: {$debits} vs {$credits}."
            );
        }
    }

    public function test_an_unbalanced_entry_is_refused_and_leaves_no_trace(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $treasury = $this->treasury($fund, $admin, 'IRT', \App\Models\Treasury::TYPE_BANK, 'IRT Bank');

        $capital = Account::query()->forGroup($fund)->where('code', Account::FUND_CAPITAL)->firstOrFail();
        $costCenter = $admin->membershipFor($fund)->costCenter;

        $draft = EntryDraft::make('Test', Carbon::today(), 'Deliberately unbalanced')
            ->debit($treasury->account, Money::of('500', 'IRT'))
            ->credit($capital, Money::of('400', 'IRT'), $costCenter);

        try {
            app(PostingService::class)->post($fund, $draft, $admin, 'IRT');
            $this->fail('An unbalanced entry was accepted.');
        } catch (UnbalancedEntryException) {
            // The whole posting is rolled back, including the entry header.
            $this->assertSame(0, JournalEntry::query()->forGroup($fund)->count());
        }
    }

    public function test_a_posted_entry_cannot_be_edited_or_deleted(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $treasury = $this->treasury($fund, $admin);
        $this->rate('USDT', '95');

        $this->contribute($admin->membershipFor($fund), $treasury, '500', $admin);

        $entry = JournalEntry::query()->forGroup($fund)->firstOrFail();

        try {
            $entry->update(['description' => 'Rewritten history']);
            $this->fail('A posted entry was edited.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('posted', $e->getMessage());
        }

        try {
            $entry->delete();
            $this->fail('A posted entry was deleted.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('posted', $e->getMessage());
        }

        $line = $entry->lines->first();

        try {
            $line->update(['debit' => '999999']);
            $this->fail('A line of a posted entry was edited.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('posted', $e->getMessage());
        }

        // Nothing reached the database.
        $this->assertNotSame('Rewritten history', $entry->fresh()->description);
        $this->assertTrue($line->fresh()->debit == $line->debit);
    }

    public function test_a_correction_is_a_reversal_that_leaves_the_original_visible(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $treasury = $this->treasury($fund, $admin);
        $this->rate('USDT', '95');

        $this->contribute($admin->membershipFor($fund), $treasury, '500', $admin);

        $original = JournalEntry::query()->forGroup($fund)->firstOrFail();
        $balances = app(LedgerBalances::class);

        $this->assertTrue($balances->treasuryBalance($treasury)->amount->equals('500'));

        $reversal = app(AccountingService::class)->reverse($original, 'Recorded against the wrong treasury', $admin);

        $this->assertSame($original->getKey(), $reversal->reverses_entry_id);
        $this->assertSame(JournalEntry::STATUS_REVERSED, $original->fresh()->status);

        // The original is still there; the two entries cancel.
        $this->assertDatabaseHas('journal_entries', ['id' => $original->getKey()]);
        $this->assertTrue($balances->treasuryBalance($treasury)->amount->isZero());

        // Its valuation snapshots are copied, not re-quoted at today's rate.
        $this->assertSame(
            (string) $original->lines->first()->gold_value_snapshot,
            (string) $reversal->lines->first()->gold_value_snapshot
        );
    }

    public function test_an_entry_cannot_be_reversed_twice(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $treasury = $this->treasury($fund, $admin);
        $this->rate('USDT', '95');

        $this->contribute($admin->membershipFor($fund), $treasury, '500', $admin);
        $entry = JournalEntry::query()->forGroup($fund)->firstOrFail();

        app(AccountingService::class)->reverse($entry, 'First correction', $admin);

        $this->expectException(PostingException::class);
        app(AccountingService::class)->reverse($entry->fresh(), 'Second attempt', $admin);
    }

    public function test_a_closed_period_refuses_postings(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $treasury = $this->treasury($fund, $admin);
        $this->rate('USDT', '95');

        $periods = app(PeriodService::class);
        $period = $periods->periodFor($fund, Carbon::today());
        $periods->close($period, $admin);

        $this->expectException(ClosedPeriodException::class);

        $this->contribute($admin->membershipFor($fund), $treasury, '500', $admin);
    }

    public function test_an_account_requiring_a_cost_center_refuses_a_line_without_one(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $treasury = $this->treasury($fund, $admin, 'IRT', \App\Models\Treasury::TYPE_BANK, 'IRT Bank');
        $capital = Account::query()->forGroup($fund)->where('code', Account::FUND_CAPITAL)->firstOrFail();

        $draft = EntryDraft::make('Test', Carbon::today(), 'Missing attribution')
            ->debit($treasury->account, Money::of('500', 'IRT'))
            ->credit($capital, Money::of('500', 'IRT'));

        $this->expectException(PostingException::class);
        $this->expectExceptionMessage('requires a cost center');

        app(PostingService::class)->post($fund, $draft, $admin, 'IRT');
    }

    public function test_an_entry_cannot_touch_another_funds_account(): void
    {
        $alice = $this->user('Alice');
        $bob = $this->user('Bob');
        $fundA = $this->fund($alice, 'Fund A');
        $fundB = $this->fund($bob, 'Fund B');

        $treasuryA = $this->treasury($fundA, $alice, 'IRT', \App\Models\Treasury::TYPE_BANK, 'IRT Bank');
        $capitalB = Account::query()->forGroup($fundB)->where('code', Account::FUND_CAPITAL)->firstOrFail();

        $draft = EntryDraft::make('Test', Carbon::today(), 'Cross-tenant posting')
            ->debit($treasuryA->account, Money::of('500', 'IRT'))
            ->credit($capitalB, Money::of('500', 'IRT'), $bob->membershipFor($fundB)->costCenter);

        $this->expectException(PostingException::class);
        $this->expectExceptionMessage('does not belong to this group');

        app(PostingService::class)->post($fundA, $draft, $alice, 'IRT');
    }
}
