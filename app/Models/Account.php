<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A ledger account: WHAT something is. Never who it belongs to — that is the
 * cost center's job — and never where it is held — that is the treasury's.
 */
class Account extends Model
{
    use BelongsToGroup;
    use HasFactory;

    public const TYPE_ASSET = 'ASSET';
    public const TYPE_LIABILITY = 'LIABILITY';
    public const TYPE_EQUITY = 'EQUITY';
    public const TYPE_INCOME = 'INCOME';
    public const TYPE_EXPENSE = 'EXPENSE';

    /** Account codes created for every new group. */
    public const LOANS_RECEIVABLE = '1400';
    public const MEMBER_PAYABLES = '2100';
    public const FUND_CAPITAL = '3100';
    public const RETAINED_RESULTS = '3200';
    public const LOAN_INTEREST_INCOME = '4100';
    public const OTHER_INCOME = '4200';
    public const EXCHANGE_GAIN = '4300';
    public const NETWORK_FEES = '5100';
    public const BANK_FEES = '5200';
    public const OTHER_EXPENSES = '5300';
    public const EXCHANGE_LOSS = '5400';

    protected $fillable = [
        'group_id',
        'code',
        'name',
        'type',
        'currency',
        'parent_id',
        'requires_cost_center',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_cost_center' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class, 'id', 'account_id');
    }

    /**
     * Debit-normal account types increase with debits (assets, expenses);
     * the rest increase with credits.
     */
    public function isDebitNormal(): bool
    {
        return in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE], true);
    }

    public function isHeader(): bool
    {
        return $this->parent_id === null;
    }

    public function label(): string
    {
        return $this->code.' '.$this->name;
    }
}
