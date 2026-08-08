<?php

namespace App\Domain\Groups;

use App\Domain\Audit\AuditAction;
use App\Domain\Audit\AuditRecorder;
use App\Models\CostCenter;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;

/**
 * Cost centers: the accounting dimension that says who or what activity belongs
 * to. Every active member gets one, and it outlives changes to their name.
 */
class CostCenterService
{
    public function __construct(private readonly AuditRecorder $audit) {}

    /**
     * The member's cost center, created on first need.
     *
     * Idempotent, so approving a member twice cannot produce two attribution
     * dimensions for the same person.
     */
    public function forMember(GroupMembership $membership, ?User $actor = null): CostCenter
    {
        if ($existing = $membership->costCenter()->first()) {
            return $existing;
        }

        $costCenter = CostCenter::create([
            'group_id' => $membership->group_id,
            'code' => $this->nextCode($membership->group),
            'name' => $membership->displayName(),
            'member_id' => $membership->getKey(),
            'status' => CostCenter::STATUS_ACTIVE,
        ]);

        $this->audit->record(
            AuditAction::COST_CENTER_CREATED,
            group: $membership->group,
            actor: $actor,
            object: $costCenter,
            new: ['code' => $costCenter->code, 'name' => $costCenter->name]
        );

        return $costCenter;
    }

    /**
     * A cost center that is not a member — a project, an activity, a shared cost.
     */
    public function create(Group $group, string $name, ?string $description, User $actor, ?CostCenter $parent = null): CostCenter
    {
        $costCenter = CostCenter::create([
            'group_id' => $group->getKey(),
            'code' => $this->nextCode($group),
            'name' => $name,
            'description' => $description,
            'parent_id' => $parent?->getKey(),
            'status' => CostCenter::STATUS_ACTIVE,
        ]);

        $this->audit->record(
            AuditAction::COST_CENTER_CREATED,
            group: $group,
            actor: $actor,
            object: $costCenter,
            new: ['code' => $costCenter->code, 'name' => $costCenter->name]
        );

        return $costCenter;
    }

    /**
     * CC-001, CC-002, … per group.
     */
    private function nextCode(Group $group): string
    {
        $last = CostCenter::query()
            ->forGroup($group)
            ->where('code', 'like', 'CC-%')
            ->orderByDesc('code')
            ->value('code');

        $next = $last ? ((int) substr($last, 3)) + 1 : 1;

        return sprintf('CC-%03d', $next);
    }
}
