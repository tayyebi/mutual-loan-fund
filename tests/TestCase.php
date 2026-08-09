<?php

namespace Tests;

use App\Domain\ExchangeRates\RateService;
use App\Domain\Groups\GroupService;
use App\Domain\Groups\MembershipService;
use App\Domain\Money\Decimal;
use App\Domain\Policies\PolicyConfig;
use App\Domain\Policies\PolicyService;
use App\Domain\Transactions\TransactionService;
use App\Domain\Treasuries\TreasuryService;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Transaction;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    protected function user(string $name = 'Mohammad', ?string $email = null): User
    {
        return User::create([
            'name' => $name,
            'email' => $email ?? strtolower($name).'-'.uniqid().'@example.test',
            'password' => 'password-for-tests',
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * A fund with its chart of accounts, an administrator and a published policy.
     *
     * @param  array<string, array<string, mixed>>  $policyOverrides
     */
    protected function fund(?User $admin = null, string $name = 'Friends Fund', array $policyOverrides = []): Group
    {
        $admin ??= $this->user();

        $group = app(GroupService::class)->create($admin, $name);

        if ($policyOverrides !== []) {
            $this->publishPolicy($group, $admin, $policyOverrides);
        }

        return $group->refresh();
    }

    /**
     * Publish a new policy version with the given values merged over the active one.
     *
     * @param  array<string, array<string, mixed>>  $overrides
     */
    protected function publishPolicy(Group $group, User $admin, array $overrides): void
    {
        $policies = app(PolicyService::class);

        $draft = $policies->createDraft($group, $admin);
        $policies->updateDraft($draft, $overrides, $admin);
        $policies->publish($draft->refresh(), $admin);
    }

    protected function member(Group $group, User $user, User $admin): GroupMembership
    {
        $memberships = app(MembershipService::class);

        $membership = $memberships->request($group, $user);

        if (! $membership->isAdmin() && $membership->status === GroupMembership::STATUS_REQUESTED) {
            $memberships->approve($membership, $admin);
        }

        return $membership->refresh();
    }

    protected function treasury(
        Group $group,
        User $admin,
        string $currency = 'USDT',
        string $type = Treasury::TYPE_CRYPTO,
        string $name = 'USDT Treasury',
    ): Treasury {
        return app(TreasuryService::class)->create($group, [
            'name' => $name,
            'type' => $type,
            'currency' => $currency,
            'network' => $type === Treasury::TYPE_CRYPTO ? 'TRON' : null,
            'external_identifier' => $type === Treasury::TYPE_CRYPTO
                ? 'TW8ZQ4pL8otSzgjLj6tTR7NHqjeKQxGTCi'
                : 'IR-000-1234',
        ], $admin);
    }

    /**
     * A gold rate, so postings that need a functional conversion can be valued.
     */
    protected function rate(string $unit, string $perGram18k, ?Carbon $date = null, ?User $actor = null): void
    {
        app(RateService::class)->record(
            $unit,
            Decimal::of($perGram18k, Decimal::RATE_SCALE),
            $date ?? Carbon::today(),
            $actor ?? $this->user('Rate Keeper'),
        );
    }

    /**
     * A verified contribution: the whole path from claim to posted journal entry.
     */
    protected function contribute(
        GroupMembership $member,
        Treasury $treasury,
        string $amount,
        User $verifier,
        ?Carbon $date = null,
    ): Transaction {
        $transactions = app(TransactionService::class);

        $transaction = $transactions->submitContribution($member, [
            'treasury' => $treasury,
            'amount' => Decimal::of($amount),
            'currency' => $treasury->currency,
            'occurred_on' => $date ?? Carbon::today(),
        ], $member->user);

        return $transactions->verify($transaction, $verifier);
    }

    /**
     * The default policy document, for tests that need to know the starting rules.
     */
    protected function defaultPolicyConfig(): PolicyConfig
    {
        return PolicyConfig::defaults();
    }
}
