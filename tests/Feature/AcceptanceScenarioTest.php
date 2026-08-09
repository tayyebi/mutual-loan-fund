<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerBalances;
use App\Domain\Groups\GroupService;
use App\Domain\Groups\MembershipService;
use App\Domain\Loans\LoanService;
use App\Domain\Money\Decimal;
use App\Domain\Reports\ReportService;
use App\Domain\Transactions\TransactionService;
use App\Domain\Treasuries\TreasuryService;
use App\Models\Account;
use App\Models\Loan;
use App\Models\Treasury;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The complete scenario from the specification, start to finish: registration
 * through to a balanced trial balance and an auditable history.
 */
class AcceptanceScenarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_whole_fund_lifecycle_holds_together(): void
    {
        $mohammad = $this->user('Mohammad');
        $ali = $this->user('Ali');

        // 2. Mohammad creates a group.
        $fund = app(GroupService::class)->create($mohammad, 'Friends Fund');

        // 3–5. Ali requests membership, is approved, and receives a cost center.
        $memberships = app(MembershipService::class);
        $aliMembership = $memberships->request($fund, $ali);
        $memberships->approve($aliMembership, $mohammad);
        $aliMembership->refresh();

        $this->assertNotNull($aliMembership->costCenter);

        // 6. Ali registers an external USDT wallet.
        $wallet = app(\App\Domain\Wallets\WalletService::class)->register($aliMembership, [
            'currency' => 'USDT',
            'network' => 'TRON',
            'address' => 'TW8ZQ4pL8otSzgjLj6tTR7NHqjeKQxGTCi',
            'label' => 'Ali main',
        ], $ali);

        // The application is non-custodial: there is nowhere to put a key.
        $this->assertArrayNotHasKey('private_key', $wallet->getAttributes());
        $this->assertArrayNotHasKey('seed_phrase', $wallet->getAttributes());

        // 7. The administrator creates a USDT treasury.
        $treasury = app(TreasuryService::class)->create($fund, [
            'name' => 'USDT Treasury',
            'type' => Treasury::TYPE_CRYPTO,
            'currency' => 'USDT',
            'network' => 'TRON',
            'external_identifier' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
        ], $mohammad);

        // A rate, so the fund can be valued in gold.
        $this->rate('USDT', '95', Carbon::today(), $mohammad);

        // 8–11. Ali sends USDT and submits the transfer; the administrator verifies it.
        $transactions = app(TransactionService::class);

        $contribution = $transactions->submitContribution($aliMembership, [
            'treasury' => $treasury,
            'amount' => Decimal::of('5000'),
            'currency' => 'USDT',
            'occurred_on' => Carbon::today(),
            'network' => 'TRON',
            'tx_hash' => 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90',
            'from_address' => $wallet->address,
        ], $ali);

        $transactions->verify($contribution, $mohammad);

        $balances = app(LedgerBalances::class);
        $reports = app(ReportService::class);

        // 12–15. A balanced entry, attributed to Ali, increasing the treasury.
        $this->assertTrue($balances->treasuryBalance($treasury)->amount->equals('5000'));

        $capital = Account::query()->forGroup($fund)->where('code', Account::FUND_CAPITAL)->firstOrFail();
        $this->assertTrue(
            $balances->costCenterAccountNativeBalances($aliMembership->costCenter, $capital)->get('USDT')->equals('5000')
        );

        // 16. The fund's value is expressed in grams of 18K gold.
        $dashboard = $reports->dashboard($fund);
        $this->assertTrue($dashboard['gold']->equals('52.63157895'));

        // 17–19. Ali requests a loan and it is approved.
        $loans = app(LoanService::class);

        $eligibility = $loans->eligibility($aliMembership, Decimal::of('2000'), 'USDT', 12);
        $this->assertTrue($eligibility->eligible);

        $loan = $loans->request($aliMembership, [
            'amount' => Decimal::of('2000'),
            'currency' => 'USDT',
            'term_months' => 12,
        ], $ali);

        $loans->approve($loan, $mohammad);

        // 20–22. The money goes out and the receivable is posted.
        $disbursement = $loans->recordDisbursement($loan->fresh(), $treasury, Carbon::today(), $mohammad);
        $transactions->verify($disbursement, $mohammad);

        $receivable = Account::query()->forGroup($fund)->where('code', Account::LOANS_RECEIVABLE)->firstOrFail();

        $this->assertTrue($balances->treasuryBalance($treasury)->amount->equals('3000'));
        $this->assertTrue(
            $balances->costCenterAccountNativeBalances($aliMembership->costCenter, $receivable)->get('USDT')->equals('2000')
        );

        // 23–26. Ali repays; the receivable falls and the treasury recovers.
        $repayment = $transactions->submitRepayment($loan->fresh(), [
            'treasury' => $treasury,
            'amount' => Decimal::of('500'),
            'currency' => 'USDT',
            'occurred_on' => Carbon::today(),
        ], $ali);

        $transactions->verify($repayment, $mohammad);

        $this->assertTrue($balances->treasuryBalance($treasury)->amount->equals('3500'));
        $this->assertTrue(
            $balances->costCenterAccountNativeBalances($aliMembership->costCenter, $receivable)->get('USDT')->equals('1500')
        );

        // 27. Ali's position reflects all of it.
        $position = $reports->memberPosition($aliMembership->refresh());
        $this->assertTrue($position['contributed']->isPositive());
        $this->assertTrue($position['outstanding']->isPositive());

        // 28. The trial balance still balances.
        $totals = $reports->trialBalanceTotals($fund);
        $this->assertTrue($totals['balanced'], 'The trial balance does not balance.');

        // 29. Everything appears in the group's timeline.
        $this->assertCount(3, $reports->recentActivity($fund));

        // 30. And the history is auditable.
        $this->assertDatabaseHas('audit_logs', [
            'group_id' => $fund->getKey(),
            'action' => \App\Domain\Audit\AuditAction::LOAN_APPROVED,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'group_id' => $fund->getKey(),
            'action' => \App\Domain\Audit\AuditAction::JOURNAL_POSTED,
        ]);

        $this->assertSame(Loan::STATUS_ACTIVE, $loan->fresh()->status);
    }
}
