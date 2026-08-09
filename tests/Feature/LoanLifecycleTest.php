<?php

namespace Tests\Feature;

use App\Domain\Accounting\LedgerBalances;
use App\Domain\Loans\LoanService;
use App\Domain\Money\Decimal;
use App\Domain\Policies\Exceptions\PolicyViolationException;
use App\Domain\Transactions\TransactionService;
use App\Models\Account;
use App\Models\Loan;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The loan lifecycle, and the accounting it produces at each step.
 */
class LoanLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A fund with money in it and a member who has been around long enough to borrow.
     *
     * @return array{0: \App\Models\Group, 1: \App\Models\User, 2: \App\Models\GroupMembership, 3: \App\Models\Treasury}
     */
    private function fundedGroup(array $policyOverrides = []): array
    {
        $admin = $this->user('Admin');
        $ali = $this->user('Ali');

        $fund = $this->fund($admin, 'Friends Fund', $policyOverrides + [
            'loans' => ['enabled' => '1', 'maximum_amount' => '2500', 'minimum_membership_days' => '0'],
        ]);

        $member = $this->member($fund, $ali, $admin);
        $treasury = $this->treasury($fund, $admin);
        $this->rate('USDT', '95');

        $this->contribute($admin->membershipFor($fund), $treasury, '5000', $ali);

        return [$fund, $admin, $member, $treasury];
    }

    public function test_disbursement_creates_a_receivable_and_reduces_the_treasury(): void
    {
        [$fund, $admin, $member, $treasury] = $this->fundedGroup();

        $loans = app(LoanService::class);
        $balances = app(LedgerBalances::class);

        $loan = $loans->request($member, [
            'amount' => Decimal::of('2000'),
            'currency' => 'USDT',
            'term_months' => 12,
        ], $member->user);

        $loans->approve($loan, $admin);

        $disbursement = $loans->recordDisbursement($loan->fresh(), $treasury, Carbon::today(), $admin);
        app(TransactionService::class)->verify($disbursement, $admin);

        $loan->refresh();

        $this->assertSame(Loan::STATUS_ACTIVE, $loan->status);
        $this->assertCount(12, $loan->installments);

        // The treasury paid out; the fund now owns a claim on Ali.
        $this->assertTrue($balances->treasuryBalance($treasury)->amount->equals('3000'));

        $receivable = Account::query()->forGroup($fund)->where('code', Account::LOANS_RECEIVABLE)->firstOrFail();

        $this->assertTrue(
            $balances->costCenterAccountNativeBalances($member->costCenter, $receivable)
                ->get('USDT')
                ->equals('2000')
        );
    }

    public function test_repayment_reduces_the_receivable_and_restores_the_treasury(): void
    {
        [$fund, $admin, $member, $treasury] = $this->fundedGroup();

        $loans = app(LoanService::class);
        $transactions = app(TransactionService::class);
        $balances = app(LedgerBalances::class);

        $loan = $loans->request($member, [
            'amount' => Decimal::of('1200'),
            'currency' => 'USDT',
            'term_months' => 12,
        ], $member->user);

        $loans->approve($loan, $admin);
        $transactions->verify($loans->recordDisbursement($loan->fresh(), $treasury, Carbon::today(), $admin), $admin);

        $repayment = $transactions->submitRepayment($loan->fresh(), [
            'treasury' => $treasury,
            'amount' => Decimal::of('300'),
            'currency' => 'USDT',
            'occurred_on' => Carbon::today(),
        ], $member->user);

        $transactions->verify($repayment, $admin);

        $receivable = Account::query()->forGroup($fund)->where('code', Account::LOANS_RECEIVABLE)->firstOrFail();

        $this->assertTrue(
            $balances->costCenterAccountNativeBalances($member->costCenter, $receivable)->get('USDT')->equals('900')
        );

        // 5000 contributed − 1200 lent + 300 repaid.
        $this->assertTrue($balances->treasuryBalance($treasury)->amount->equals('4100'));
    }

    public function test_interest_is_recognised_as_income_attributed_to_the_borrower(): void
    {
        [$fund, $admin, $member, $treasury] = $this->fundedGroup([
            'loans' => [
                'enabled' => '1',
                'maximum_amount' => '2500',
                'minimum_membership_days' => '0',
                'interest_rate' => '12',
                'interest_method' => 'flat',
            ],
        ]);

        $loans = app(LoanService::class);
        $transactions = app(TransactionService::class);

        $loan = $loans->request($member, [
            'amount' => Decimal::of('1200'),
            'currency' => 'USDT',
            'term_months' => 12,
        ], $member->user);

        $loans->approve($loan, $admin);
        $transactions->verify($loans->recordDisbursement($loan->fresh(), $treasury, Carbon::today(), $admin), $admin);

        $loan->refresh();

        // 12% flat on 1,200 over 12 months is 144 of interest across the schedule.
        $scheduledInterest = $loan->installments->reduce(
            fn (Decimal $carry, $installment) => $carry->plus((string) $installment->interest_amount),
            Decimal::zero()
        );
        $this->assertTrue($scheduledInterest->equals('144'));

        // Pay the first installment in full: interest first, then principal.
        $first = $loan->installments->first();
        $transactions->verify(
            $transactions->submitRepayment($loan, [
                'treasury' => $treasury,
                'amount' => Decimal::of((string) $first->amount),
                'currency' => 'USDT',
                'occurred_on' => Carbon::today(),
            ], $member->user),
            $admin
        );

        $balances = app(LedgerBalances::class);
        $income = Account::query()->forGroup($fund)->where('code', Account::LOAN_INTEREST_INCOME)->firstOrFail();

        $this->assertTrue(
            $balances->costCenterAccountNativeBalances($member->costCenter, $income)
                ->get('USDT')
                ->equals((string) $first->interest_amount)
        );
    }

    public function test_the_policy_limit_is_enforced_not_merely_displayed(): void
    {
        [$fund, $admin, $member, $treasury] = $this->fundedGroup();

        $this->expectException(PolicyViolationException::class);

        app(LoanService::class)->request($member, [
            'amount' => Decimal::of('3000'),
            'currency' => 'USDT',
            'term_months' => 12,
        ], $member->user);
    }

    public function test_a_second_active_loan_is_refused_when_the_policy_allows_one(): void
    {
        [$fund, $admin, $member, $treasury] = $this->fundedGroup();

        $loans = app(LoanService::class);

        $loans->request($member, [
            'amount' => Decimal::of('500'),
            'currency' => 'USDT',
            'term_months' => 6,
        ], $member->user);

        $this->expectException(PolicyViolationException::class);
        $this->expectExceptionMessage('one active loan');

        $loans->request($member, [
            'amount' => Decimal::of('500'),
            'currency' => 'USDT',
            'term_months' => 6,
        ], $member->user);
    }

    public function test_a_loan_is_settled_when_the_schedule_is_satisfied(): void
    {
        [$fund, $admin, $member, $treasury] = $this->fundedGroup();

        $loans = app(LoanService::class);
        $transactions = app(TransactionService::class);

        $loan = $loans->request($member, [
            'amount' => Decimal::of('600'),
            'currency' => 'USDT',
            'term_months' => 6,
        ], $member->user);

        $loans->approve($loan, $admin);
        $transactions->verify($loans->recordDisbursement($loan->fresh(), $treasury, Carbon::today(), $admin), $admin);

        $transactions->verify(
            $transactions->submitRepayment($loan->fresh(), [
                'treasury' => $treasury,
                'amount' => Decimal::of('600'),
                'currency' => 'USDT',
                'occurred_on' => Carbon::today(),
            ], $member->user),
            $admin
        );

        $this->assertSame(Loan::STATUS_FULLY_REPAID, $loan->fresh()->status);
        $this->assertTrue($loan->fresh()->outstandingPrincipal()->isZero());
    }

    public function test_nobody_approves_their_own_loan(): void
    {
        [$fund, $admin, $member, $treasury] = $this->fundedGroup();

        $adminMembership = $admin->membershipFor($fund);

        $loan = app(LoanService::class)->request($adminMembership, [
            'amount' => Decimal::of('500'),
            'currency' => 'USDT',
            'term_months' => 6,
        ], $admin);

        $this->actingAs($admin)
            ->post(route('g.loans.approve', [$fund, $loan]))
            ->assertForbidden();

        $this->assertSame(Loan::STATUS_REQUESTED, $loan->fresh()->status);
    }
}
