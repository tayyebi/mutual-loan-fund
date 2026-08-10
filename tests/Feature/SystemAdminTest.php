<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Platform operations under /admin: user and fund lifecycle across the whole
 * application. Nothing here reaches into any fund's own financial data — that
 * invariant is asserted here alongside the lifecycle actions themselves.
 */
class SystemAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_away_from_the_admin_area(): void
    {
        foreach (['s.dashboard', 's.users.index', 's.funds.index', 's.audit.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_an_ordinary_user_cannot_reach_the_admin_area(): void
    {
        $user = $this->user('Ali');

        foreach (['s.dashboard', 's.users.index', 's.funds.index', 's.audit.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }

    public function test_a_fund_administrator_is_not_a_system_administrator(): void
    {
        $admin = $this->user('Fund Admin');
        $this->fund($admin);

        $this->actingAs($admin)->get(route('s.dashboard'))->assertForbidden();
    }

    public function test_a_system_admin_can_view_the_platform_operations_screens(): void
    {
        $admin = $this->systemAdmin();
        $this->user('Someone');
        $this->fund($this->user('Founder'));

        $this->actingAs($admin)->get(route('s.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('s.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('s.funds.index'))->assertOk();
        $this->actingAs($admin)->get(route('s.audit.index'))->assertOk();
    }

    public function test_a_system_admin_can_suspend_and_reinstate_a_user(): void
    {
        $admin = $this->systemAdmin();
        $target = $this->user('Target', 'target@example.test');

        $this->actingAs($admin)
            ->post(route('s.users.suspend', $target))
            ->assertRedirect();

        $this->assertSame(User::STATUS_SUSPENDED, $target->fresh()->status);

        $this->post(route('login'), [
            'email' => 'target@example.test',
            'password' => 'password-for-tests',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($admin)
            ->post(route('s.users.reinstate', $target))
            ->assertRedirect();

        $this->assertSame(User::STATUS_ACTIVE, $target->fresh()->status);
    }

    public function test_a_system_admin_can_suspend_and_reinstate_a_fund(): void
    {
        $admin = $this->systemAdmin();
        $founder = $this->user('Founder');
        $member = $this->user('Member');
        $fund = $this->fund($founder);
        $this->member($fund, $member, $founder);

        $this->actingAs($admin)
            ->post(route('s.funds.suspend', $fund))
            ->assertRedirect();

        $this->assertSame(Group::STATUS_SUSPENDED, $fund->fresh()->status);
        $this->actingAs($member)->get(route('u.dashboard', $fund))->assertForbidden();

        $this->actingAs($admin)
            ->post(route('s.funds.reinstate', $fund))
            ->assertRedirect();

        $this->assertSame(Group::STATUS_ACTIVE, $fund->fresh()->status);
        $this->actingAs($member)->get(route('u.dashboard', $fund))->assertOk();
    }

    public function test_a_system_admin_can_promote_and_demote_another_user(): void
    {
        $admin = $this->systemAdmin();
        $target = $this->user('Target');

        $this->actingAs($admin)
            ->post(route('s.users.promote', $target))
            ->assertRedirect();

        $this->assertTrue($target->fresh()->isSystemAdmin());

        $this->actingAs($admin)
            ->post(route('s.users.demote', $target))
            ->assertRedirect();

        $this->assertFalse($target->fresh()->isSystemAdmin());
    }

    public function test_the_only_system_administrator_cannot_be_demoted_or_suspended(): void
    {
        $admin = $this->systemAdmin();

        $this->actingAs($admin)
            ->post(route('s.users.demote', $admin))
            ->assertSessionHasErrors('user');
        $this->assertTrue($admin->fresh()->isSystemAdmin());

        $this->actingAs($admin)
            ->post(route('s.users.suspend', $admin))
            ->assertSessionHasErrors('user');
        $this->assertSame(User::STATUS_ACTIVE, $admin->fresh()->status);
    }

    public function test_a_system_admin_can_be_demoted_once_another_exists(): void
    {
        $first = $this->systemAdmin('First');
        $second = $this->systemAdmin('Second');

        $this->actingAs($first)
            ->post(route('s.users.demote', $first))
            ->assertRedirect();

        $this->assertFalse($first->fresh()->isSystemAdmin());
        $this->assertTrue($second->fresh()->isSystemAdmin());
    }

    public function test_only_a_system_admin_may_manage_exchange_rates(): void
    {
        $fundAdmin = $this->user('Fund Admin');
        $this->fund($fundAdmin);
        $systemAdmin = $this->systemAdmin();

        $this->actingAs($fundAdmin)
            ->post(route('exchange-rates.store'), [
                'unit' => 'USDT',
                'units_per_gram_18k' => '95',
                'effective_date' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($systemAdmin)
            ->post(route('exchange-rates.store'), [
                'unit' => 'USDT',
                'units_per_gram_18k' => '95',
                'effective_date' => now()->toDateString(),
            ])
            ->assertRedirect();
    }

    public function test_the_admin_area_never_exposes_a_funds_members_or_activity(): void
    {
        $admin = $this->systemAdmin();
        $founder = $this->user('Founder');
        $secretMember = $this->user('Secret Member');
        $fund = $this->fund($founder, 'Confidential Fund');
        $this->member($fund, $secretMember, $founder);

        $response = $this->actingAs($admin)->get(route('s.funds.show', $fund));

        $response->assertOk();
        $response->assertDontSee($secretMember->email);
    }
}
