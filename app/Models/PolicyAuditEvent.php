<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Policy changes keep their own audit trail so the complete before/after
 * configuration survives, draft edits included.
 */
class PolicyAuditEvent extends Model
{
    use BelongsToGroup;

    public const UPDATED_AT = null;

    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_PUBLISHED = 'published';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_SUPERSEDED = 'superseded';

    protected $fillable = [
        'group_id',
        'actor_id',
        'policy_version_id',
        'version',
        'action',
        'old_config',
        'new_config',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'old_config' => 'array',
            'new_config' => 'array',
            'version' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(GroupPolicy::class, 'policy_version_id');
    }
}
