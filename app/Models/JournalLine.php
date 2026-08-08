<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGroup;
use App\Support\ImmutabilityGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * One debit or credit. Exactly one side is non-zero (enforced in the database).
 *
 * Each line carries both its native amount and its functional-currency
 * equivalent. Entries balance on the functional amounts, which is what lets a
 * treasury exchange (USDT out, IRT in) be a single balanced entry.
 */
class JournalLine extends Model
{
    use BelongsToGroup;
    use HasFactory;

    protected $fillable = [
        'journal_entry_id',
        'group_id',
        'account_id',
        'cost_center_id',
        'currency',
        'debit',
        'credit',
        'functional_currency',
        'functional_debit',
        'functional_credit',
        'exchange_rate_snapshot',
        'gold_rate_snapshot',
        'gold_value_snapshot',
        'description',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:8',
            'credit' => 'decimal:8',
            'functional_debit' => 'decimal:8',
            'functional_credit' => 'decimal:8',
            'exchange_rate_snapshot' => 'decimal:12',
            'gold_rate_snapshot' => 'decimal:12',
            'gold_value_snapshot' => 'decimal:8',
            'sequence' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $guard = function (self $line): void {
            if (ImmutabilityGuard::isLifted(ImmutabilityGuard::LEDGER)) {
                return;
            }

            $entry = $line->journalEntry;

            if ($entry && $entry->isPosted()) {
                throw new RuntimeException(
                    "Journal entry {$entry->entry_number} is posted; its lines cannot be modified or removed."
                );
            }
        };

        static::updating($guard);
        static::deleting($guard);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /**
     * Restrict a query to lines that actually affect official balances.
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query->whereHas('journalEntry', function (Builder $entry) {
            $entry->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED]);
        });
    }

    public function isDebit(): bool
    {
        return bccomp((string) $this->debit, '0', 8) === 1;
    }
}
