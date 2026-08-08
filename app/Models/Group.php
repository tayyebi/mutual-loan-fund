<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The tenant boundary: one independent mutual loan fund.
 */
class Group extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'created_by',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class);
    }

    public function activeMemberships(): HasMany
    {
        return $this->memberships()->whereIn('status', [
            GroupMembership::STATUS_APPROVED,
            GroupMembership::STATUS_ACTIVE,
        ]);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(GroupPolicy::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }

    public function treasuries(): HasMany
    {
        return $this->hasMany(Treasury::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function accountingPeriods(): HasMany
    {
        return $this->hasMany(AccountingPeriod::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * The policy version governing operations created right now.
     *
     * Never call this to render or recalculate a historical operation: those
     * carry their own policy_version_id.
     */
    public function activePolicy(): ?GroupPolicy
    {
        return $this->policies()
            ->where('status', GroupPolicy::STATUS_PUBLISHED)
            ->whereNull('effective_until')
            ->orderByDesc('version')
            ->first();
    }

    public function draftPolicy(): ?GroupPolicy
    {
        return $this->policies()
            ->where('status', GroupPolicy::STATUS_DRAFT)
            ->first();
    }

    public function functionalCurrency(): string
    {
        return $this->activePolicy()?->config('accounting.functional_currency')
            ?? config('fund.default_functional_currency');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
