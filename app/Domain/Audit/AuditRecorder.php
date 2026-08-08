<?php

namespace App\Domain\Audit;

use App\Models\AuditLog;
use App\Models\Group;
use App\Models\GroupPolicy;
use App\Models\PolicyAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes the append-only audit trail.
 *
 * Audit rows are created inside the same database transaction as the action
 * they describe: if the action rolls back, so does its trace, and a committed
 * action can never be missing one.
 */
class AuditRecorder
{
    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function record(
        string $action,
        ?Group $group = null,
        ?User $actor = null,
        ?Model $object = null,
        array $old = [],
        array $new = [],
    ): AuditLog {
        return AuditLog::create([
            'group_id' => $group?->getKey(),
            'actor_id' => $actor?->getKey() ?? auth()->id(),
            'action' => $action,
            'object_type' => $object ? $object::class : null,
            'object_id' => $object?->getKey(),
            'old_values' => $old === [] ? null : $old,
            'new_values' => $new === [] ? null : $new,
            'ip_address' => $this->ipAddress(),
        ]);
    }

    /**
     * Policy changes keep a dedicated trail carrying the full before/after
     * configuration, draft edits included.
     *
     * @param  array<string, mixed>|null  $oldConfig
     * @param  array<string, mixed>|null  $newConfig
     */
    public function recordPolicyEvent(
        GroupPolicy $policy,
        string $action,
        ?User $actor = null,
        ?array $oldConfig = null,
        ?array $newConfig = null,
    ): PolicyAuditEvent {
        return PolicyAuditEvent::create([
            'group_id' => $policy->group_id,
            'actor_id' => $actor?->getKey() ?? auth()->id(),
            'policy_version_id' => $policy->getKey(),
            'version' => $policy->version,
            'action' => $action,
            'old_config' => $oldConfig,
            'new_config' => $newConfig,
            'ip_address' => $this->ipAddress(),
        ]);
    }

    private function ipAddress(): ?string
    {
        return app()->runningInConsole() ? null : request()->ip();
    }
}
