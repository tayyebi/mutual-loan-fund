<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A business financial event.
 *
 * Creating one changes nothing financially. Only verification posts a journal
 * entry, and only posted journal entries move balances.
 */
class Transaction extends Model
{
    use BelongsToGroup;
    use HasFactory;

    public const TYPE_CONTRIBUTION = 'contribution';
    public const TYPE_LOAN_DISBURSEMENT = 'loan_disbursement';
    public const TYPE_LOAN_REPAYMENT = 'loan_repayment';
    public const TYPE_TREASURY_TRANSFER = 'treasury_transfer';
    public const TYPE_TREASURY_EXCHANGE = 'treasury_exchange';
    public const TYPE_FEE = 'fee';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';
    public const DIRECTION_INTERNAL = 'internal';

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'group_id',
        'treasury_id',
        'counter_treasury_id',
        'member_id',
        'loan_id',
        'policy_version_id',
        'type',
        'direction',
        'amount',
        'currency',
        'counter_amount',
        'counter_currency',
        'fee_amount',
        'fee_currency',
        'status',
        'reference',
        'description',
        'network',
        'tx_hash',
        'from_address',
        'to_address',
        'confirmations',
        'chain_verified_at',
        'chain_evidence',
        'occurred_on',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            // decimal casts keep money as strings; bcmath does the arithmetic.
            'amount' => 'decimal:8',
            'counter_amount' => 'decimal:8',
            'fee_amount' => 'decimal:8',
            'chain_evidence' => 'array',
            'occurred_on' => 'date',
            'chain_verified_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function counterTreasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class, 'counter_treasury_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(GroupMembership::class, 'member_id');
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(GroupPolicy::class, 'policy_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(TransactionReceipt::class);
    }

    public function contribution(): HasOne
    {
        return $this->hasOne(Contribution::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * The original (non-reversal) entry created when this transaction was verified.
     */
    public function journalEntry(): HasOne
    {
        return $this->hasOne(JournalEntry::class)->whereNull('reverses_entry_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function hasChainEvidence(): bool
    {
        return filled($this->tx_hash);
    }

    public function explorerUrl(): ?string
    {
        if (! $this->hasChainEvidence()) {
            return null;
        }

        $template = config("fund.networks.{$this->network}.explorer_tx");

        return $template ? str_replace(':hash', $this->tx_hash, $template) : null;
    }

    public function typeLabel(): string
    {
        return ucfirst(str_replace('_', ' ', $this->type));
    }

    /**
     * Sign as seen from the fund's treasuries, used by the activity timeline.
     */
    public function signedAmount(): string
    {
        return match ($this->direction) {
            self::DIRECTION_IN => '+'.$this->amount,
            self::DIRECTION_OUT => '-'.$this->amount,
            default => $this->amount,
        };
    }
}
