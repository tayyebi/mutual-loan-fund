<?php

namespace Database\Seeders;

use App\Domain\ExchangeRates\RateService;
use App\Domain\Groups\GroupService;
use App\Domain\Groups\MembershipService;
use App\Domain\Loans\LoanService;
use App\Domain\Money\Decimal;
use App\Domain\Policies\PolicyService;
use App\Domain\SystemAdmin\SystemAdminService;
use App\Domain\Transactions\TransactionService;
use App\Domain\Treasuries\TreasuryService;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * A worked example of the whole application.
 *
 * Everything here goes through the domain services rather than inserting rows,
 * so the seeded data obeys the same invariants as data created through the UI:
 * balanced entries, policy versions recorded, audit trail written.
 */
class DemoFundSeeder extends Seeder
{
    public function run(): void
    {
        $mohammad = $this->account('Mohammad', 'mohammad@example.test');
        $ali = $this->account('Ali', 'ali@example.test');
        $sara = $this->account('Sara', 'sara@example.test');

        // So the platform-administration area has something to sign in and see.
        if (! $mohammad->isSystemAdmin()) {
            app(SystemAdminService::class)->promote($mohammad);
        }

        // Global valuation data, entered a few days apart so the fallback
        // behaviour is visible in the UI.
        $rates = app(RateService::class);
        $rates->record('IRT', Decimal::of('96600000', Decimal::RATE_SCALE), Carbon::today()->subDays(6), $mohammad);
        $rates->record('USDT', Decimal::of('96.6', Decimal::RATE_SCALE), Carbon::today()->subDays(6), $mohammad);
        $rates->record('IRT', Decimal::of('98200000', Decimal::RATE_SCALE), Carbon::today()->subDay(), $mohammad);
        $rates->record('USDT', Decimal::of('98.2', Decimal::RATE_SCALE), Carbon::today()->subDay(), $mohammad);

        $fund = app(GroupService::class)->create(
            $mohammad,
            'Friends Fund',
            'A private mutual loan fund between friends.'
        );

        // A policy the members can actually borrow under.
        $policies = app(PolicyService::class);
        $draft = $policies->createDraft($fund, $mohammad);
        $policies->updateDraft($draft, [
            'loans' => [
                'enabled' => '1',
                'minimum_amount' => '100',
                'maximum_amount' => '2500',
                'interest_rate' => '0',
                'interest_method' => 'none',
                'minimum_term_months' => '1',
                'maximum_term_months' => '12',
                'maximum_active_loans' => '1',
                'minimum_membership_days' => '0',
                'early_repayment_allowed' => '1',
            ],
            'contributions' => ['enabled' => '1', 'minimum_amount' => '50'],
            'accounting' => ['functional_currency' => 'USDT'],
        ], $mohammad);
        $policies->publish($draft->refresh(), $mohammad);

        $memberships = app(MembershipService::class);

        $aliMembership = $memberships->request($fund, $ali);
        $memberships->approve($aliMembership, $mohammad);

        // Sara is left waiting, so the approvals screen has something in it.
        $memberships->request($fund, $sara);

        $treasuries = app(TreasuryService::class);

        $usdt = $treasuries->create($fund, [
            'name' => 'USDT Treasury',
            'type' => Treasury::TYPE_CRYPTO,
            'currency' => 'USDT',
            'network' => 'TRON',
            'external_identifier' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
        ], $mohammad);

        $treasuries->create($fund, [
            'name' => 'IRT Bank',
            'type' => Treasury::TYPE_BANK,
            'currency' => 'IRT',
            'external_identifier' => 'IR62 0170 0000 0011 1111 1111 11',
        ], $mohammad);

        $transactions = app(TransactionService::class);

        // Two verified contributions, and one still awaiting a decision.
        $this->contribute($transactions, $mohammad->membershipFor($fund), $usdt, '5000', $mohammad, 5);
        $this->contribute($transactions, $aliMembership->refresh(), $usdt, '2000', $mohammad, 4);

        $transactions->submitContribution($aliMembership, [
            'treasury' => $usdt,
            'amount' => Decimal::of('750'),
            'currency' => 'USDT',
            'occurred_on' => Carbon::today(),
            'network' => 'TRON',
            'tx_hash' => str_repeat('c4', 32),
            'description' => 'Waiting for verification',
        ], $ali);

        // A loan, disbursed and partly repaid.
        $loans = app(LoanService::class);

        $loan = $loans->request($aliMembership, [
            'amount' => Decimal::of('2000'),
            'currency' => 'USDT',
            'term_months' => 12,
            'purpose' => 'Replacing a laptop',
        ], $ali);

        $loans->approve($loan, $mohammad);

        $transactions->verify(
            $loans->recordDisbursement($loan->fresh(), $usdt, Carbon::today()->subDays(3), $mohammad),
            $mohammad
        );

        $transactions->verify(
            $transactions->submitRepayment($loan->fresh(), [
                'treasury' => $usdt,
                'amount' => Decimal::of('500'),
                'currency' => 'USDT',
                'occurred_on' => Carbon::today()->subDay(),
            ], $ali),
            $mohammad
        );

        $this->command?->info('Demo fund ready. Sign in as mohammad@example.test / password (fund and system administrator).');
    }

    private function account(string $name, string $email): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'password', 'status' => User::STATUS_ACTIVE]
        );
    }

    private function contribute(
        TransactionService $transactions,
        $member,
        Treasury $treasury,
        string $amount,
        User $verifier,
        int $daysAgo,
    ): void {
        $transaction = $transactions->submitContribution($member, [
            'treasury' => $treasury,
            'amount' => Decimal::of($amount),
            'currency' => $treasury->currency,
            'occurred_on' => Carbon::today()->subDays($daysAgo),
            'network' => 'TRON',
            'tx_hash' => str_repeat(dechex($daysAgo + 10), 32),
        ], $member->user);

        $transactions->verify($transaction, $verifier);
    }
}
