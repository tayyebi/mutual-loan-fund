<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A user's relationship with one group. This is the "member" that every
 * member_id column in the schema points at.
 */
class GroupMembership extends Model
{
    use BelongsToGroup;
    use HasFactory;

    public const ROLE_MEMBER = 'member';
    public const ROLE_ADMIN = 'admin';

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'status',
        'requested_at',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function costCenter(): HasOne
    {
        return $this->hasOne(CostCenter::class, 'member_id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'member_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'member_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class, 'member_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN && $this->hasAccess();
    }

    /**
     * Whether this membership may read the group's financial data at all.
     */
    public function hasAccess(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_ACTIVE], true);
    }

    public function membershipDays(): int
    {
        $since = $this->approved_at ?? $this->created_at;

        return $since ? (int) $since->diffInDays(now()) : 0;
    }

    public function displayName(): string
    {
        return $this->user?->name ?? 'Member #'.$this->getKey();
    }
}
