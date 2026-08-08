<?php

namespace App\Models\Concerns;

use App\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Applied to every group-owned entity.
 *
 * Tenant isolation is enforced in three independent places, so that no single
 * mistake can leak data across funds:
 *
 *   1. ResolveGroupContext rejects any bound model whose group_id differs from
 *      the group in the URL.
 *   2. Queries are written through forGroup() / the group relation.
 *   3. This trait refuses to persist a row without a group_id at all.
 */
trait BelongsToGroup
{
    public static function bootBelongsToGroup(): void
    {
        static::saving(function (self $model): void {
            if (blank($model->group_id)) {
                throw new RuntimeException(
                    static::class.' cannot be saved without a group_id.'
                );
            }
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * The only sanctioned way to scope a query to a tenant.
     */
    public function scopeForGroup(Builder $query, Group|int $group): Builder
    {
        return $query->where(
            $this->qualifyColumn('group_id'),
            $group instanceof Group ? $group->getKey() : $group
        );
    }

    public function belongsToGroup(Group|int $group): bool
    {
        return (int) $this->group_id === (int) ($group instanceof Group ? $group->getKey() : $group);
    }
}
