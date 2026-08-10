<?php

namespace Tests\Feature;

use App\Domain\Access\AccessLevel;
use App\Domain\Access\AccessMap;
use App\Domain\Access\Surface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The URL space is partitioned by access level: /u for a member, /g for a fund
 * administrator, /s for the platform. The levels are cumulative — holding a
 * higher one never takes a lower one away — but the *surfaces* stay separate,
 * so a fund administrator does their own contributing on /u like anyone else.
 *
 * These tests pin the boundary itself. What each surface shows is covered by
 * the feature tests for those pages.
 */
class AccessSurfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_plain_member_reaches_the_member_surface_but_not_the_administrator_one(): void
    {
        $admin = $this->user('Admin');
        $member = $this->user('Member');
        $fund = $this->fund($admin);
        $this->member($fund, $member, $admin);

        $this->actingAs($member)->get(route('u.dashboard', $fund))->assertOk();
        $this->actingAs($member)->get(route('u.money', $fund))->assertOk();
        $this->actingAs($member)->get(route('u.borrowing', $fund))->assertOk();
        $this->actingAs($member)->get(route('u.fund', $fund))->assertOk();
        $this->actingAs($member)->get(route('u.fund.rules', $fund))->assertOk();

        // The whole /g prefix, not merely its write actions.
        $this->actingAs($member)->get(route('g.dashboard', $fund))->assertForbidden();
        $this->actingAs($member)->get(route('g.ledger.index', $fund))->assertForbidden();
        $this->actingAs($member)->get(route('g.accounts.index', $fund))->assertForbidden();
        $this->actingAs($member)->get(route('g.reports.index', $fund))->assertForbidden();
        $this->actingAs($member)->get(route('g.policies.index', $fund))->assertForbidden();
    }

    public function test_a_fund_administrator_holds_both_surfaces(): void
    {
        $admin = $this->user('Admin');
        $fund = $this->fund($admin);

        $this->actingAs($admin)->get(route('g.dashboard', $fund))->assertOk();

        // Being the administrator does not stop them being an investor.
        $this->actingAs($admin)->get(route('u.dashboard', $fund))->assertOk();
        $this->actingAs($admin)->get(route('u.borrowing', $fund))->assertOk();
    }

    public function test_a_system_administrator_gets_no_reach_into_a_fund_they_do_not_belong_to(): void
    {
        $founder = $this->user('Founder');
        $fund = $this->fund($founder);
        $sysAdmin = $this->systemAdmin();

        $this->actingAs($sysAdmin)->get(route('s.dashboard'))->assertOk();

        // Not 403: a non-member should not even learn the fund exists.
        $this->actingAs($sysAdmin)->get(route('u.dashboard', $fund))->assertNotFound();
        $this->actingAs($sysAdmin)->get(route('g.dashboard', $fund))->assertNotFound();
    }

    public function test_a_system_administrator_who_is_also_an_investor_uses_the_member_surface(): void
    {
        $founder = $this->user('Founder');
        $fund = $this->fund($founder);
        $sysAdmin = $this->systemAdmin();
        $this->member($fund, $sysAdmin, $founder);

        $this->actingAs($sysAdmin)->get(route('u.dashboard', $fund))->assertOk();

        // Platform standing grants nothing inside the fund's own books.
        $this->actingAs($sysAdmin)->get(route('g.dashboard', $fund))->assertForbidden();
    }

    public function test_old_admin_urls_redirect_to_the_platform_surface(): void
    {
        $sysAdmin = $this->systemAdmin();

        $this->actingAs($sysAdmin)->get('/admin')->assertRedirect('/s');
        $this->actingAs($sysAdmin)->get('/admin/users')->assertRedirect('/s/users');
    }

    public function test_every_declared_surface_is_internally_consistent(): void
    {
        foreach (AccessMap::SURFACES as $surface) {
            /** @var class-string<Surface> $surface */
            $this->assertContains($surface::level(), AccessLevel::all());
            $this->assertNotSame([], $surface::sections(), $surface.' declares no navigation.');

            foreach ($surface::items() as $item) {
                $this->assertTrue(
                    app('router')->has($item->route),
                    "{$surface}'s nav points at the undefined route '{$item->route}'."
                );
                $this->assertStringStartsWith(
                    $surface::routePrefix(),
                    $item->route,
                    "{$surface}'s nav points outside its own prefix: '{$item->route}'."
                );
            }

            foreach ($surface::routes() as $intent => $route) {
                $this->assertTrue(
                    app('router')->has($route),
                    "{$surface} maps the intent '{$intent}' to the undefined route '{$route}'."
                );
                $this->assertStringStartsWith(
                    $surface::routePrefix(),
                    $route,
                    "{$surface} maps '{$intent}' outside its own prefix: '{$route}'."
                );
            }
        }
    }

    public function test_access_levels_are_cumulative(): void
    {
        $this->assertTrue(AccessLevel::covers(AccessLevel::LEVEL_SYSTEM_ADMIN, AccessLevel::LEVEL_MEMBER));
        $this->assertTrue(AccessLevel::covers(AccessLevel::LEVEL_FUND_ADMIN, AccessLevel::LEVEL_MEMBER));
        $this->assertFalse(AccessLevel::covers(AccessLevel::LEVEL_MEMBER, AccessLevel::LEVEL_FUND_ADMIN));
    }
}
