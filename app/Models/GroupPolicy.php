<?php

namespace App\Models;

use App\Domain\Policies\PolicyConfig;
use App\Models\Concerns\BelongsToGroup;
use App\Support\ImmutabilityGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

/**
 * One version of a group's financial rules.
 *
 * Published rows are immutable: the model refuses to update or delete them.
 * PolicyPublisher is the single place allowed to lift that guard, and only to
 * close a version's effective_until when its successor is published.
 */
class GroupPolicy extends Model
{
    use BelongsToGroup;
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SUPERSEDED = 'superseded';

    protected $table = 'group_policies';

    protected $fillable = [
        'group_id',
        'version',
        'status',
        'config',
        'effective_from',
        'effective_until',
        'created_by',
        'published_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'version' => 'integer',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $policy): void {
            if (ImmutabilityGuard::isLifted(ImmutabilityGuard::POLICY)) {
                return;
            }

            // getOriginal, not the current value: what matters is whether the
            // row was already published before this update.
            if ($policy->getOriginal('status') !== self::STATUS_DRAFT) {
                throw new RuntimeException(
                    "Policy v{$policy->version} is published and cannot be modified. Create a new draft version instead."
                );
            }
        });

        static::deleting(function (self $policy): void {
            if (ImmutabilityGuard::isLifted(ImmutabilityGuard::POLICY)) {
                return;
            }

            if ($policy->getOriginal('status') !== self::STATUS_DRAFT) {
                throw new RuntimeException(
                    "Policy v{$policy->version} is published and cannot be deleted."
                );
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'policy_version_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'policy_version_id');
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(PolicyAuditEvent::class, 'policy_version_id');
    }

    /**
     * Read a value out of the configuration document, e.g. config('loans.maximum_amount').
     */
    public function config(?string $key = null, mixed $default = null): mixed
    {
        $config = $this->config ?? [];

        return $key === null ? $config : data_get($config, $key, $default);
    }

    public function toPolicyConfig(): PolicyConfig
    {
        return PolicyConfig::fromArray($this->config ?? []);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isActive(): bool
    {
        return $this->isPublished() && $this->effective_until === null;
    }

    public function label(): string
    {
        return 'v'.$this->version;
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->isDraft() => 'Draft',
            $this->isActive() => 'Active',
            $this->isPublished() => 'Published',
            default => 'Superseded',
        };
    }
}
