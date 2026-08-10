<?php

namespace App\Domain\Policies;

use App\Domain\Audit\AuditRecorder;
use App\Models\Group;
use App\Models\GroupPolicy;
use App\Models\PolicyAuditEvent;
use App\Models\User;
use App\Support\ImmutabilityGuard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Publishing a policy version: validate, close the outgoing version, open the
 * new one, and audit — all inside one database transaction, so the group is
 * never observed with two active versions or none.
 */
class PolicyPublisher
{
    public function __construct(
        private readonly PolicyValidator $validator,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @throws \App\Domain\Policies\Exceptions\PolicyValidationException
     */
    public function publish(GroupPolicy $draft, User $actor, ?Carbon $effectiveFrom = null): GroupPolicy
    {
        if (! $draft->isDraft()) {
            throw new RuntimeException(__('exceptions.policy_not_draft', ['version' => $draft->version]));
        }

        // Validate before opening the transaction: an invalid policy is refused
        // outright rather than rolled back.
        $config = $draft->toPolicyConfig();
        $this->validator->validateOrFail($config);

        $effectiveFrom ??= Carbon::today();

        return DB::transaction(function () use ($draft, $actor, $effectiveFrom, $config) {
            // Serialise concurrent publications for this group: without the lock,
            // two administrators could each close the current version and open
            // their own, leaving two open-ended versions.
            $group = Group::whereKey($draft->group_id)->lockForUpdate()->firstOrFail();

            $current = GroupPolicy::query()
                ->forGroup($group)
                ->where('status', GroupPolicy::STATUS_PUBLISHED)
                ->whereNull('effective_until')
                ->lockForUpdate()
                ->first();

            if ($current) {
                $this->supersede($current, $effectiveFrom, $actor);
            }

            ImmutabilityGuard::without(ImmutabilityGuard::POLICY, function () use ($draft, $actor, $effectiveFrom) {
                $draft->forceFill([
                    'status' => GroupPolicy::STATUS_PUBLISHED,
                    'effective_from' => $effectiveFrom,
                    'effective_until' => null,
                    'published_by' => $actor->getKey(),
                    'published_at' => now(),
                ])->save();
            });

            $this->audit->recordPolicyEvent(
                $draft,
                PolicyAuditEvent::ACTION_PUBLISHED,
                $actor,
                null,
                $config->toArray()
            );

            return $draft->refresh();
        });
    }

    /**
     * Close the outgoing version.
     *
     * The effective range is the administrative record of when a version was in
     * force. Resolution for new operations uses the open-ended published version,
     * and historical operations use their own stored policy_version_id, so the
     * dates are never used to re-derive which rules governed an existing record.
     */
    private function supersede(GroupPolicy $current, Carbon $effectiveFrom, User $actor): void
    {
        $from = $current->effective_from ? Carbon::parse($current->effective_from) : $effectiveFrom;

        // Normally the previous version ends the day before the new one starts.
        // When both start on the same day the previous version simply ends on
        // its own start date; the database forbids effective_until < effective_from.
        $until = $effectiveFrom->copy()->subDay();

        if ($until->lt($from)) {
            $until = $from->copy();
        }

        ImmutabilityGuard::without(ImmutabilityGuard::POLICY, function () use ($current, $until) {
            $current->forceFill([
                'status' => GroupPolicy::STATUS_SUPERSEDED,
                'effective_until' => $until,
            ])->save();
        });

        $this->audit->recordPolicyEvent(
            $current,
            PolicyAuditEvent::ACTION_SUPERSEDED,
            $actor,
            $current->config(),
            null
        );
    }
}
