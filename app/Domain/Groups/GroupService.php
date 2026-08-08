<?php

namespace App\Domain\Groups;

use App\Domain\Accounting\ChartOfAccounts;
use App\Domain\Audit\AuditAction;
use App\Domain\Audit\AuditRecorder;
use App\Domain\Policies\PolicyService;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creating a fund.
 *
 * A group is only usable once it has a chart of accounts, an administrator with
 * a cost center, and a published policy, so all of that happens in one
 * transaction: there is no window in which a group exists but cannot legally
 * record anything.
 */
class GroupService
{
    public function __construct(
        private readonly ChartOfAccounts $chart,
        private readonly CostCenterService $costCenters,
        private readonly PolicyService $policies,
        private readonly AuditRecorder $audit,
    ) {}

    public function create(User $creator, string $name, ?string $description = null): Group
    {
        return DB::transaction(function () use ($creator, $name, $description) {
            $group = Group::create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'description' => $description,
                'status' => Group::STATUS_ACTIVE,
                'created_by' => $creator->getKey(),
            ]);

            $this->chart->install($group);

            $membership = GroupMembership::create([
                'group_id' => $group->getKey(),
                'user_id' => $creator->getKey(),
                'role' => GroupMembership::ROLE_ADMIN,
                'status' => GroupMembership::STATUS_ACTIVE,
                'requested_at' => now(),
                'approved_at' => now(),
                'approved_by' => $creator->getKey(),
            ]);

            $this->costCenters->forMember($membership, $creator);
            $this->policies->createInitialPolicy($group, $creator);

            $this->audit->record(
                AuditAction::GROUP_CREATED,
                group: $group,
                actor: $creator,
                object: $group,
                new: ['name' => $group->name, 'slug' => $group->slug]
            );

            return $group->refresh();
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'fund';
        $slug = $base;
        $suffix = 2;

        while (Group::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
