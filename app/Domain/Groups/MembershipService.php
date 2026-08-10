<?php

namespace App\Domain\Groups;

use App\Domain\Audit\AuditAction;
use App\Domain\Audit\AuditRecorder;
use App\Domain\Policies\PolicyService;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Membership lifecycle: requested → approved → active, plus rejection,
 * suspension and removal.
 *
 * Whether an administrator must approve a request is a policy decision, read
 * from the group's active policy rather than hard-coded here.
 */
class MembershipService
{
    public function __construct(
        private readonly CostCenterService $costCenters,
        private readonly PolicyService $policies,
        private readonly AuditRecorder $audit,
    ) {}

    public function request(Group $group, User $user): GroupMembership
    {
        return DB::transaction(function () use ($group, $user) {
            $existing = $user->membershipFor($group);

            if ($existing && $existing->hasAccess()) {
                return $existing;
            }

            if ($existing && $existing->status === GroupMembership::STATUS_REQUESTED) {
                return $existing;
            }

            if ($existing && $existing->status === GroupMembership::STATUS_SUSPENDED) {
                throw new RuntimeException(__('exceptions.membership_suspended'));
            }

            $approvalRequired = $this->policies->activePolicy($group)
                ?->toPolicyConfig()->membership()->approvalRequired() ?? true;

            $membership = $existing ?: new GroupMembership([
                'group_id' => $group->getKey(),
                'user_id' => $user->getKey(),
            ]);

            $membership->fill([
                'group_id' => $group->getKey(),
                'user_id' => $user->getKey(),
                'role' => GroupMembership::ROLE_MEMBER,
                'status' => $approvalRequired
                    ? GroupMembership::STATUS_REQUESTED
                    : GroupMembership::STATUS_ACTIVE,
                'requested_at' => now(),
                'approved_at' => $approvalRequired ? null : now(),
            ])->save();

            if (! $approvalRequired) {
                $this->costCenters->forMember($membership, $user);
            }

            $this->audit->record(
                AuditAction::MEMBER_REQUESTED,
                group: $group,
                actor: $user,
                object: $membership,
                new: ['status' => $membership->status]
            );

            return $membership;
        });
    }

    /**
     * Approving a member also gives them their cost center, so their financial
     * activity has somewhere to be attributed from the first transaction.
     */
    public function approve(GroupMembership $membership, User $actor): GroupMembership
    {
        return DB::transaction(function () use ($membership, $actor) {
            $previous = $membership->status;

            $membership->forceFill([
                'status' => GroupMembership::STATUS_ACTIVE,
                'approved_at' => now(),
                'approved_by' => $actor->getKey(),
            ])->save();

            $this->costCenters->forMember($membership, $actor);

            $this->audit->record(
                AuditAction::MEMBER_APPROVED,
                group: $membership->group,
                actor: $actor,
                object: $membership,
                old: ['status' => $previous],
                new: ['status' => $membership->status]
            );

            return $membership;
        });
    }

    public function reject(GroupMembership $membership, User $actor, ?string $reason = null): GroupMembership
    {
        $previous = $membership->status;

        $membership->forceFill(['status' => GroupMembership::STATUS_REJECTED])->save();

        $this->audit->record(
            AuditAction::MEMBER_REJECTED,
            group: $membership->group,
            actor: $actor,
            object: $membership,
            old: ['status' => $previous],
            new: ['status' => $membership->status, 'reason' => $reason]
        );

        return $membership;
    }

    public function suspend(GroupMembership $membership, User $actor): GroupMembership
    {
        $this->assertNotLastAdmin($membership);

        $previous = $membership->status;

        $membership->forceFill(['status' => GroupMembership::STATUS_SUSPENDED])->save();

        $this->audit->record(
            AuditAction::MEMBER_SUSPENDED,
            group: $membership->group,
            actor: $actor,
            object: $membership,
            old: ['status' => $previous],
            new: ['status' => $membership->status]
        );

        return $membership;
    }

    public function reinstate(GroupMembership $membership, User $actor): GroupMembership
    {
        $previous = $membership->status;

        $membership->forceFill(['status' => GroupMembership::STATUS_ACTIVE])->save();
        $this->costCenters->forMember($membership, $actor);

        $this->audit->record(
            AuditAction::MEMBER_REINSTATED,
            group: $membership->group,
            actor: $actor,
            object: $membership,
            old: ['status' => $previous],
            new: ['status' => $membership->status]
        );

        return $membership;
    }

    public function changeRole(GroupMembership $membership, string $role, User $actor): GroupMembership
    {
        if ($role === GroupMembership::ROLE_MEMBER) {
            $this->assertNotLastAdmin($membership);
        }

        $previous = $membership->role;

        $membership->forceFill(['role' => $role])->save();

        $this->audit->record(
            AuditAction::MEMBER_ROLE_CHANGED,
            group: $membership->group,
            actor: $actor,
            object: $membership,
            old: ['role' => $previous],
            new: ['role' => $role]
        );

        return $membership;
    }

    /**
     * A fund without an administrator can never approve anything again, so the
     * last one cannot be demoted or suspended.
     */
    private function assertNotLastAdmin(GroupMembership $membership): void
    {
        if (! $membership->isAdmin()) {
            return;
        }

        $others = GroupMembership::query()
            ->forGroup($membership->group)
            ->where('role', GroupMembership::ROLE_ADMIN)
            ->whereIn('status', [GroupMembership::STATUS_APPROVED, GroupMembership::STATUS_ACTIVE])
            ->whereKeyNot($membership->getKey())
            ->count();

        if ($others === 0) {
            throw new RuntimeException(__('exceptions.last_group_admin'));
        }
    }
}
