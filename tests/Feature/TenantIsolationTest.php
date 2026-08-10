<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * The security invariant the whole application rests on: a member of one fund
 * can reach nothing belonging to another, however they ask.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_of_one_fund_cannot_open_another_funds_dashboard(): void
    {
        $alice = $this->user('Alice');
        $bob = $this->user('Bob');

        $fundA = $this->fund($alice, 'Fund A');
        $fundB = $this->fund($bob, 'Fund B');

        $this->actingAs($alice)
            ->get(route('u.dashboard', $fundB))
            // Not 403: a non-member should not even learn the fund exists.
            ->assertNotFound();

        $this->actingAs($alice)->get(route('u.dashboard', $fundA))->assertOk();
    }

    public function test_changing_an_id_in_the_url_cannot_reach_another_funds_records(): void
    {
        $alice = $this->user('Alice');
        $bob = $this->user('Bob');

        $fundA = $this->fund($alice, 'Fund A');
        $fundB = $this->fund($bob, 'Fund B');

        $treasuryB = $this->treasury($fundB, $bob);
        $memberB = $bob->membershipFor($fundB);
        $this->rate('USDT', '95');
        $contributionB = $this->contribute($memberB, $treasuryB, '500', $bob);

        // Alice is a legitimate member of fund A, and asks fund A for fund B's
        // transaction. Route model binding finds the row; the middleware rejects it.
        $this->actingAs($alice)
            ->get(route('u.activity.show', [$fundA, $contributionB]))
            ->assertNotFound();

        $accountB = Account::query()->forGroup($fundB)->where('code', Account::FUND_CAPITAL)->firstOrFail();

        $this->actingAs($alice)
            ->get(route('g.accounts.show', [$fundA, $accountB]))
            ->assertNotFound();
    }

    public function test_a_pending_member_has_no_access_until_approved(): void
    {
        $admin = $this->user('Admin');
        $newcomer = $this->user('Newcomer');
        $fund = $this->fund($admin);

        $this->actingAs($newcomer)->post(route('groups.join', $fund))->assertRedirect();

        $this->actingAs($newcomer)->get(route('u.dashboard', $fund))->assertNotFound();

        $membership = $newcomer->membershipFor($fund);
        app(\App\Domain\Groups\MembershipService::class)->approve($membership, $admin);

        $this->actingAs($newcomer)->get(route('u.dashboard', $fund))->assertOk();
    }

    public function test_an_ordinary_member_cannot_reach_administrative_screens(): void
    {
        $admin = $this->user('Admin');
        $member = $this->user('Member');
        $fund = $this->fund($admin);
        $this->member($fund, $member, $admin);

        $this->actingAs($member)->get(route('g.audit.index', $fund))->assertForbidden();
        $this->actingAs($member)->get(route('g.periods.index', $fund))->assertForbidden();
        $this->actingAs($member)->post(route('g.policies.store', $fund))->assertForbidden();
    }

    public function test_a_group_owned_record_cannot_be_saved_without_a_group(): void
    {
        $this->expectException(RuntimeException::class);

        // The last line of defence: even a direct write is refused.
        JournalLine::create([
            'account_id' => 1,
            'currency' => 'USDT',
            'debit' => '1',
            'credit' => '0',
            'functional_currency' => 'USDT',
        ]);
    }

    public function test_a_suspended_member_loses_access(): void
    {
        $admin = $this->user('Admin');
        $member = $this->user('Member');
        $fund = $this->fund($admin);
        $membership = $this->member($fund, $member, $admin);

        $this->actingAs($member)->get(route('u.dashboard', $fund))->assertOk();

        app(\App\Domain\Groups\MembershipService::class)->suspend($membership, $admin);

        $this->actingAs($member)->get(route('u.dashboard', $fund))->assertNotFound();
    }
}
