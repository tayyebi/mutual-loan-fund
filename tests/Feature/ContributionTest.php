<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerBalances;
use App\Domain\Money\Decimal;
use App\Domain\Transactions\TransactionService;
use App\Models\Account;
use App\Models\Contribution;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

/**
 * Contributions: the path from a member's claim to a balanced, attributed entry.
 */
class ContributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_submitted_contribution_moves_nothing_until_it_is_verified(): void
    {
        $admin = $this->user('Admin');
        $ali = $this->user('Ali');
        $fund = $this->fund($admin);
        $member = $this->member($fund, $ali, $admin);
        $treasury = $this->treasury($fund, $admin);
        $this->rate('USDT', '95');

        $transaction = app(TransactionService::class)->submitContribution($member, [
            'treasury' => $treasury,
            'amount' => Decimal::of('500'),
            'currency' => 'USDT',
            'occurred_on' => Carbon::today(),
        ], $ali);

        $this->assertSame(Transaction::STATUS_PENDING, $transaction->status);
        $this->assertSame(0, JournalEntry::query()->forGroup($fund)->count());
        $this->assertTrue(app(LedgerBalances::class)->treasuryBalance($treasury)->amount->isZero());
    }

    public function test_verifying_posts_a_balanced_entry_attributed_to_the_member(): void
    {
        $admin = $this->user('Admin');
        $ali = $this->user('Ali');
        $fund = $this->fund($admin);
        $member = $this->member($fund, $ali, $admin);
        $treasury = $this->treasury($fund, $admin);
        $this->rate('USDT', '95');

        $transaction = $this->contribute($member, $treasury, '500', $admin);

        $this->assertSame(Transaction::STATUS_VERIFIED, $transaction->status);

        $balances = app(LedgerBalances::class);

        // The treasury holds the money.
        $this->assertTrue($balances->treasuryBalance($treasury)->amount->equals('500'));

        // Fund capital is credited, attributed to Ali's cost center and no one else's.
        $capital = Account::query()->forGroup($fund)->where('code', Account::FUND_CAPITAL)->firstOrFail();

        $this->assertTrue(
            $balances->accountBalance($capital, null, $member->costCenter)->equals(
                $balances->accountBalance($capital)
            )
        );

        $entry = $transaction->journalEntry()->with('lines')->firstOrFail();
        $this->assertCount(2, $entry->lines);
        $this->assertSame('Contribution', $entry->template);

        // The treasury line carries no cost center: a treasury is a place, not a person.
        $treasuryLine = $entry->lines->firstWhere('account_id', $treasury->account_id);
        $this->assertNull($treasuryLine->cost_center_id);

        // And the contribution record exists alongside the accounting.
        $this->assertDatabaseHas('contributions', [
            'transaction_id' => $transaction->getKey(),
            'member_id' => $member->getKey(),
            'cost_center_id' => $member->costCenter->getKey(),
        ]);
    }

    public function test_the_same_blockchain_transfer_cannot_be_credited_twice(): void
    {
        $admin = $this->user('Admin');
        $ali = $this->user('Ali');
        $fund = $this->fund($admin);
        $member = $this->member($fund, $ali, $admin);
        $treasury = $this->treasury($fund, $admin);
        $this->rate('USDT', '95');

        $transactions = app(TransactionService::class);

        $payload = [
            'treasury' => $treasury,
            'amount' => Decimal::of('500'),
            'currency' => 'USDT',
            'occurred_on' => Carbon::today(),
            'network' => 'TRON',
            'tx_hash' => '9f8e7d6c5b4a39281706f5e4d3c2b1a09f8e7d6c5b4a39281706f5e4d3c2b1a0',
        ];

        $transactions->submitContribution($member, $payload, $ali);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already been submitted');

        $transactions->submitContribution($member, $payload, $ali);
    }

    public function test_a_contribution_below_the_policy_minimum_is_refused(): void
    {
        $admin = $this->user('Admin');
        $ali = $this->user('Ali');
        $fund = $this->fund($admin, 'Friends Fund', [
            'contributions' => ['enabled' => '1', 'minimum_amount' => '100'],
        ]);
        $member = $this->member($fund, $ali, $admin);
        $treasury = $this->treasury($fund, $admin);

        $this->expectException(\App\Domain\Policies\Exceptions\PolicyViolationException::class);

        app(TransactionService::class)->submitContribution($member, [
            'treasury' => $treasury,
            'amount' => Decimal::of('50'),
            'currency' => 'USDT',
            'occurred_on' => Carbon::today(),
        ], $ali);
    }

    public function test_a_member_cannot_verify_their_own_contribution(): void
    {
        $admin = $this->user('Admin');
        $ali = $this->user('Ali');
        $fund = $this->fund($admin);
        $member = $this->member($fund, $ali, $admin);
        app(\App\Domain\Groups\MembershipService::class)->changeRole($member, 'admin', $admin);
        $treasury = $this->treasury($fund, $admin);
        $this->rate('USDT', '95');

        $transaction = app(TransactionService::class)->submitContribution($member, [
            'treasury' => $treasury,
            'amount' => Decimal::of('500'),
            'currency' => 'USDT',
            'occurred_on' => Carbon::today(),
        ], $ali);

        // Ali is an administrator now, but still submitted this himself.
        $this->actingAs($ali)
            ->post(route('g.transactions.verify', [$fund, $transaction]))
            ->assertForbidden();

        $this->assertSame(Transaction::STATUS_PENDING, $transaction->fresh()->status);
    }

    public function test_a_rejected_transaction_never_reaches_the_ledger(): void
    {
        $admin = $this->user('Admin');
        $ali = $this->user('Ali');
        $fund = $this->fund($admin);
        $member = $this->member($fund, $ali, $admin);
        $treasury = $this->treasury($fund, $admin);

        $transaction = app(TransactionService::class)->submitContribution($member, [
            'treasury' => $treasury,
            'amount' => Decimal::of('500'),
            'currency' => 'USDT',
            'occurred_on' => Carbon::today(),
        ], $ali);

        app(TransactionService::class)->reject($transaction, $admin, 'No evidence of the transfer');

        $this->assertSame(Transaction::STATUS_REJECTED, $transaction->fresh()->status);
        $this->assertSame(0, JournalEntry::query()->forGroup($fund)->count());
        $this->assertSame(0, Contribution::query()->forGroup($fund)->count());
    }
}
