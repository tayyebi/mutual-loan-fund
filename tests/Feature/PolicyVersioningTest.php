<?php

namespace Tests\Feature;

use App\Domain\Loans\LoanService;
use App\Domain\Money\Decimal;
use App\Domain\Policies\Exceptions\PolicyValidationException;
use App\Domain\Policies\PolicyService;
use App\Models\GroupPolicy;
use App\Models\PolicyAuditEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Versioned policies: published versions are immutable, only one is active, and
 * a change never reaches back into operations already recorded.
 */
class PolicyVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_fund_starts_with_a_published_v1(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);

        $active = app(PolicyService::class)->activePolicy($fund);

        $this->assertNotNull($active);
        $this->assertSame(1, $active->version);
        $this->assertTrue($active->isActive());
        $this->assertNull($active->effective_until);
    }

    public function test_a_published_policy_cannot_be_edited_or_deleted(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $active = app(PolicyService::class)->activePolicy($fund);

        try {
            $active->update(['config' => []]);
            $this->fail('A published policy was edited.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('published', $e->getMessage());
        }

        try {
            $active->delete();
            $this->fail('A published policy was deleted.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('published', $e->getMessage());
        }

        $this->assertNotEmpty($active->fresh()->config);
    }

    public function test_publishing_supersedes_the_previous_version_and_leaves_exactly_one_active(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $policies = app(PolicyService::class);

        $v1 = $policies->activePolicy($fund);

        $this->publishPolicy($fund, $admin, ['loans' => ['maximum_amount' => '3000']]);

        $v1->refresh();
        $v2 = $policies->activePolicy($fund);

        $this->assertSame(GroupPolicy::STATUS_SUPERSEDED, $v1->status);
        $this->assertNotNull($v1->effective_until);
        $this->assertSame(2, $v2->version);

        $this->assertSame(
            1,
            $fund->policies()->where('status', GroupPolicy::STATUS_PUBLISHED)->whereNull('effective_until')->count()
        );
    }

    public function test_a_draft_affects_nothing_until_it_is_published(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $policies = app(PolicyService::class);

        $draft = $policies->createDraft($fund, $admin);
        $policies->updateDraft($draft, ['loans' => ['maximum_amount' => '99999']], $admin);

        $active = $policies->activePolicy($fund);

        $this->assertSame(1, $active->version);
        $this->assertSame('2500.00000000', $active->toPolicyConfig()->loans()->maximumAmount()->toString());

        // And a draft can still be discarded.
        $policies->deleteDraft($draft->refresh(), $admin);
        $this->assertNull($policies->draftPolicy($fund));
    }

    public function test_only_one_draft_exists_at_a_time(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $policies = app(PolicyService::class);

        $policies->createDraft($fund, $admin);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already has draft');

        $policies->createDraft($fund, $admin);
    }

    public function test_an_invalid_policy_cannot_be_published(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $policies = app(PolicyService::class);

        $draft = $policies->createDraft($fund, $admin);
        $policies->updateDraft($draft, [
            'loans' => ['minimum_amount' => '5000', 'maximum_amount' => '1000'],
        ], $admin);

        try {
            $policies->publish($draft->refresh(), $admin);
            $this->fail('An incoherent policy was published.');
        } catch (PolicyValidationException $e) {
            $this->assertArrayHasKey('loans.minimum_amount', $e->errors());
        }

        $this->assertSame(1, $policies->activePolicy($fund)->version);
    }

    public function test_an_existing_loan_keeps_its_policy_version_when_the_rules_change(): void
    {
        $admin = $this->user('Admin');
        $ali = $this->user('Ali');

        $fund = $this->fund($admin, 'Friends Fund', [
            'loans' => [
                'enabled' => '1',
                'maximum_amount' => '2000',
                'minimum_membership_days' => '0',
                'interest_rate' => '0',
                'interest_method' => 'none',
            ],
        ]);

        $member = $this->member($fund, $ali, $admin);

        $loan = app(LoanService::class)->request($member, [
            'amount' => Decimal::of('1500'),
            'currency' => 'USDT',
            'term_months' => 12,
        ], $ali);

        $governingVersion = $loan->policyVersion->version;
        $this->assertSame('0.000000000000', $loan->interest_rate);

        // The administrator publishes new, stricter terms.
        $this->publishPolicy($fund, $admin, [
            'loans' => [
                'enabled' => '1',
                'maximum_amount' => '3000',
                'minimum_membership_days' => '0',
                'interest_rate' => '5',
                'interest_method' => 'flat',
            ],
        ]);

        $loan->refresh();

        // The loan is untouched: same version, same rate, same contract.
        $this->assertSame($governingVersion, $loan->policyVersion->version);
        $this->assertSame('0.000000000000', $loan->interest_rate);
        $this->assertSame(
            '2000.00000000',
            $loan->policyVersion->toPolicyConfig()->loans()->maximumAmount()->toString()
        );

        // A new loan is governed by the new version.
        $next = app(LoanService::class)->request(
            $admin->membershipFor($fund),
            ['amount' => Decimal::of('2500'), 'currency' => 'USDT', 'term_months' => 12],
            $admin
        );

        $this->assertGreaterThan($governingVersion, $next->policyVersion->version);
        $this->assertSame('5.000000000000', $next->interest_rate);
    }

    public function test_every_policy_change_is_audited_including_draft_edits(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $policies = app(PolicyService::class);

        $draft = $policies->createDraft($fund, $admin);
        $policies->updateDraft($draft, ['loans' => ['maximum_amount' => '3000']], $admin);
        $policies->publish($draft->refresh(), $admin);

        $actions = PolicyAuditEvent::query()->forGroup($fund)->pluck('action');

        foreach ([
            PolicyAuditEvent::ACTION_CREATED,
            PolicyAuditEvent::ACTION_UPDATED,
            PolicyAuditEvent::ACTION_PUBLISHED,
            PolicyAuditEvent::ACTION_SUPERSEDED,
        ] as $action) {
            $this->assertTrue($actions->contains($action), "Missing audit event: {$action}");
        }

        $update = PolicyAuditEvent::query()
            ->forGroup($fund)
            ->where('action', PolicyAuditEvent::ACTION_UPDATED)
            ->firstOrFail();

        // The before and after configurations are both kept.
        $this->assertNotNull($update->old_config);
        $this->assertNotNull($update->new_config);
    }

    public function test_operations_are_refused_when_no_policy_is_active(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);

        // Force the fund into a state with no active version.
        \App\Support\ImmutabilityGuard::without(\App\Support\ImmutabilityGuard::POLICY, function () use ($fund) {
            $fund->policies()->update(['status' => GroupPolicy::STATUS_SUPERSEDED, 'effective_until' => now()]);
        });

        $this->expectException(\App\Domain\Policies\Exceptions\NoActivePolicyException::class);

        app(PolicyService::class)->requireActivePolicy($fund->refresh());
    }
}
