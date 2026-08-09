<?php

namespace App\Domain\SystemAdmin;

use App\Domain\Audit\AuditAction;
use App\Domain\Audit\AuditRecorder;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Platform operations: user and fund lifecycle across the whole application.
 *
 * Nothing here touches a fund's members, transactions, loans or ledger — that
 * stays exactly as private as it is today. This is the account-and-fund
 * lifecycle layer only, parallel to but independent from MembershipService.
 */
class SystemAdminService
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function suspendUser(User $target, User $actor): User
    {
        return DB::transaction(function () use ($target, $actor) {
            $this->assertNotLastSystemAdmin($target);

            $previous = $target->status;

            $target->forceFill(['status' => User::STATUS_SUSPENDED])->save();

            $this->audit->record(
                AuditAction::USER_SUSPENDED,
                actor: $actor,
                object: $target,
                old: ['status' => $previous],
                new: ['status' => $target->status],
            );

            return $target;
        });
    }

    public function reinstateUser(User $target, User $actor): User
    {
        return DB::transaction(function () use ($target, $actor) {
            $previous = $target->status;

            $target->forceFill(['status' => User::STATUS_ACTIVE])->save();

            $this->audit->record(
                AuditAction::USER_REINSTATED,
                actor: $actor,
                object: $target,
                old: ['status' => $previous],
                new: ['status' => $target->status],
            );

            return $target;
        });
    }

    /**
     * $actor is nullable so the bootstrap console command (which runs before
     * any authenticated session exists) can grant the first system admin;
     * such entries are attributed to "system" in the audit trail.
     */
    public function promote(User $target, ?User $actor = null): User
    {
        return DB::transaction(function () use ($target, $actor) {
            $previous = $target->system_role;

            $target->forceFill(['system_role' => User::SYSTEM_ROLE_SYSTEM_ADMIN])->save();

            $this->audit->record(
                AuditAction::SYSTEM_ADMIN_GRANTED,
                actor: $actor,
                object: $target,
                old: ['system_role' => $previous],
                new: ['system_role' => $target->system_role],
            );

            return $target;
        });
    }

    public function demote(User $target, ?User $actor = null): User
    {
        return DB::transaction(function () use ($target, $actor) {
            $this->assertNotLastSystemAdmin($target);

            $previous = $target->system_role;

            $target->forceFill(['system_role' => User::SYSTEM_ROLE_USER])->save();

            $this->audit->record(
                AuditAction::SYSTEM_ADMIN_REVOKED,
                actor: $actor,
                object: $target,
                old: ['system_role' => $previous],
                new: ['system_role' => $target->system_role],
            );

            return $target;
        });
    }

    public function suspendFund(Group $target, User $actor): Group
    {
        return DB::transaction(function () use ($target, $actor) {
            $previous = $target->status;

            $target->forceFill(['status' => Group::STATUS_SUSPENDED])->save();

            $this->audit->record(
                AuditAction::GROUP_SUSPENDED,
                group: $target,
                actor: $actor,
                object: $target,
                old: ['status' => $previous],
                new: ['status' => $target->status],
            );

            return $target;
        });
    }

    public function reinstateFund(Group $target, User $actor): Group
    {
        return DB::transaction(function () use ($target, $actor) {
            $previous = $target->status;

            $target->forceFill(['status' => Group::STATUS_ACTIVE])->save();

            $this->audit->record(
                AuditAction::GROUP_REINSTATED,
                group: $target,
                actor: $actor,
                object: $target,
                old: ['status' => $previous],
                new: ['status' => $target->status],
            );

            return $target;
        });
    }

    /**
     * The platform without a system administrator can never grant that role
     * again, so the last one cannot be demoted or suspended.
     */
    private function assertNotLastSystemAdmin(User $target): void
    {
        if (! $target->isSystemAdmin()) {
            return;
        }

        $others = User::query()
            ->where('system_role', User::SYSTEM_ROLE_SYSTEM_ADMIN)
            ->where('status', User::STATUS_ACTIVE)
            ->whereKeyNot($target->getKey())
            ->count();

        if ($others === 0) {
            throw new RuntimeException('This is the platform\'s only administrator. Promote another user first.');
        }
    }
}
