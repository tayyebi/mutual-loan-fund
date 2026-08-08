<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGroup;
use App\Support\ImmutabilityGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

/**
 * The accounting representation of a financial event.
 *
 * Once posted the entry is immutable: it cannot be edited or deleted, and the
 * only permitted state change is being marked 'reversed' by PostingService when
 * a reversing entry is posted against it. Corrections are new entries, never
 * edits — the whole history stays visible.
 */
class JournalEntry extends Model
{
    use BelongsToGroup;
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'group_id',
        'entry_number',
        'transaction_id',
        'accounting_period_id',
        'reverses_entry_id',
        'template',
        'entry_date',
        'posting_date',
        'functional_currency',
        'description',
        'reason',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posting_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $entry): void {
            if (ImmutabilityGuard::isLifted(ImmutabilityGuard::LEDGER)) {
                return;
            }

            // getOriginal, not the current value: what matters is the status the
            // row already had in the database before this update.
            if ($entry->getOriginal('status') !== self::STATUS_DRAFT) {
                throw new RuntimeException(
                    "Journal entry {$entry->entry_number} is posted and cannot be modified. Post a reversing entry instead."
                );
            }
        });

        static::deleting(function (self $entry): void {
            if (ImmutabilityGuard::isLifted(ImmutabilityGuard::LEDGER)) {
                return;
            }

            if ($entry->getOriginal('status') !== self::STATUS_DRAFT) {
                throw new RuntimeException(
                    "Journal entry {$entry->entry_number} is posted and cannot be deleted."
                );
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('sequence');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_entry_id');
    }

    public function reversal(): HasMany
    {
        return $this->hasMany(self::class, 'reverses_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return in_array($this->status, [self::STATUS_POSTED, self::STATUS_REVERSED], true);
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }
}
