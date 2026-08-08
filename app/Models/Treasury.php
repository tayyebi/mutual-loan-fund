<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WHERE the group's assets are held.
 *
 * There is no balance column on purpose: a treasury's balance is always derived
 * from posted journal lines against its ledger account.
 */
class Treasury extends Model
{
    use BelongsToGroup;
    use HasFactory;

    public const TYPE_CRYPTO = 'crypto';
    public const TYPE_BANK = 'bank';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'group_id',
        'account_id',
        'name',
        'type',
        'currency',
        'network',
        'external_identifier',
        'status',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(TreasuryReconciliation::class);
    }

    public function isCrypto(): bool
    {
        return $this->type === self::TYPE_CRYPTO;
    }

    public function explorerUrl(): ?string
    {
        if (! $this->isCrypto() || blank($this->external_identifier)) {
            return null;
        }

        $template = config("fund.networks.{$this->network}.explorer_address");

        return $template ? str_replace(':address', $this->external_identifier, $template) : null;
    }
}
