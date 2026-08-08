<?php

namespace App\Domain\Policies;

use App\Domain\Audit\AuditRecorder;
use App\Domain\Policies\Exceptions\NoActivePolicyException;
use App\Models\Group;
use App\Models\GroupPolicy;
use App\Models\PolicyAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The only sanctioned entry point for reading and changing group policy.
 *
 * Business services resolve limits through here rather than hard-coding them,
 * and every financial operation stores the version it resolved.
 */
class PolicyService
{
    public function __construct(
        private readonly PolicyValidator $validator,
        private readonly PolicyPublisher $publisher,
        private readonly PolicyDiff $diff,
        private readonly AuditRecorder $audit,
    ) {}

    public function activePolicy(Group $group): ?GroupPolicy
    {
        return $group->policies()
            ->where('status', GroupPolicy::STATUS_PUBLISHED)
            ->whereNull('effective_until')
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Resolve the rules governing an operation being created right now.
     *
     * @throws NoActivePolicyException
     */
    public function requireActivePolicy(Group $group): GroupPolicy
    {
        return $this->activePolicy($group) ?? throw new NoActivePolicyException($group);
    }

    public function draftPolicy(Group $group): ?GroupPolicy
    {
        return $group->policies()->where('status', GroupPolicy::STATUS_DRAFT)->first();
    }

    /**
     * @return Collection<int, GroupPolicy>
     */
    public function history(Group $group): Collection
    {
        return $group->policies()->orderByDesc('version')->get();
    }

    /**
     * Seed a group's first policy version, published immediately so financial
     * operations have rules to resolve against from the start.
     */
    public function createInitialPolicy(Group $group, User $actor, ?PolicyConfig $config = null): GroupPolicy
    {
        return DB::transaction(function () use ($group, $actor, $config) {
            $draft = $this->storeDraft($group, $actor, $config ?? PolicyConfig::defaults(), version: 1);

            return $this->publisher->publish($draft, $actor);
        });
    }

    /**
     * Open a new draft, pre-filled from the active version so an administrator
     * edits a copy of the current rules rather than starting from defaults.
     */
    public function createDraft(Group $group, User $actor): GroupPolicy
    {
        return DB::transaction(function () use ($group, $actor) {
            if ($existing = $this->draftPolicy($group)) {
                throw new RuntimeException(
                    "Group '{$group->name}' already has draft v{$existing->version}. Edit or delete it first."
                );
            }

            $base = $this->activePolicy($group)?->toPolicyConfig() ?? PolicyConfig::defaults();

            return $this->storeDraft($group, $actor, $base);
        });
    }

    /**
     * @param  array<string, array<string, mixed>>  $input
     */
    public function updateDraft(GroupPolicy $draft, array $input, User $actor): GroupPolicy
    {
        if (! $draft->isDraft()) {
            throw new RuntimeException("Policy v{$draft->version} is published and cannot be edited.");
        }

        return DB::transaction(function () use ($draft, $input, $actor) {
            $old = $draft->config ?? [];
            $updated = $draft->toPolicyConfig()->merged($input);

            $draft->config = $updated->toArray();
            $draft->save();

            $this->audit->recordPolicyEvent(
                $draft,
                PolicyAuditEvent::ACTION_UPDATED,
                $actor,
                $old,
                $draft->config
            );

            return $draft;
        });
    }

    public function deleteDraft(GroupPolicy $draft, User $actor): void
    {
        if (! $draft->isDraft()) {
            throw new RuntimeException("Policy v{$draft->version} is published and cannot be deleted.");
        }

        DB::transaction(function () use ($draft, $actor) {
            // Recorded before deletion so the trail keeps the discarded config;
            // the event's policy_version_id is nulled by the foreign key.
            $this->audit->recordPolicyEvent(
                $draft,
                PolicyAuditEvent::ACTION_DELETED,
                $actor,
                $draft->config,
                null
            );

            $draft->delete();
        });
    }

    public function publish(GroupPolicy $draft, User $actor, ?Carbon $effectiveFrom = null): GroupPolicy
    {
        return $this->publisher->publish($draft, $actor, $effectiveFrom);
    }

    /**
     * @return array<string, string>
     */
    public function validate(PolicyConfig $config): array
    {
        return $this->validator->validate($config);
    }

    /**
     * @return list<array{category: string, field: string, from: string, to: string}>
     */
    public function compare(GroupPolicy $from, GroupPolicy $to): array
    {
        return $this->diff->changes($from->toPolicyConfig(), $to->toPolicyConfig());
    }

    /**
     * Changes a draft would introduce, for the publish confirmation screen.
     *
     * @return list<array{category: string, field: string, from: string, to: string}>
     */
    public function pendingChanges(GroupPolicy $draft): array
    {
        $active = $this->activePolicy($draft->group);

        return $this->diff->changes(
            $active?->toPolicyConfig() ?? PolicyConfig::defaults(),
            $draft->toPolicyConfig()
        );
    }

    private function storeDraft(Group $group, User $actor, PolicyConfig $config, ?int $version = null): GroupPolicy
    {
        $version ??= ((int) $group->policies()->max('version')) + 1;

        $draft = GroupPolicy::create([
            'group_id' => $group->getKey(),
            'version' => $version,
            'status' => GroupPolicy::STATUS_DRAFT,
            'config' => $config->toArray(),
            'created_by' => $actor->getKey(),
        ]);

        $this->audit->recordPolicyEvent(
            $draft,
            PolicyAuditEvent::ACTION_CREATED,
            $actor,
            null,
            $draft->config
        );

        return $draft;
    }
}
