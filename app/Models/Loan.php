<?php

namespace App\Models;

use App\Domain\Money\Decimal;
use App\Models\Concerns\BelongsToGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A member's obligation to the fund.
 *
 * policy_version_id is mandatory and permanent: the loan is governed for its
 * whole life by the rules that were active when it was created, even after the
 * administrator publishes a newer policy.
 */
class Loan extends Model
{
    use BelongsToGroup;
    use HasFactory;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DISBURSED = 'disbursed';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FULLY_REPAID = 'fully_repaid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_DEFAULTED = 'defaulted';
    public const STATUS_CANCELLED = 'cancelled';

    /** Statuses in which principal is still owed to the fund. */
    public const OUTSTANDING_STATUSES = [
        self::STATUS_DISBURSED,
        self::STATUS_ACTIVE,
        self::STATUS_OVERDUE,
        self::STATUS_DEFAULTED,
    ];

    protected $fillable = [
        'group_id',
        'member_id',
        'cost_center_id',
        'policy_version_id',
        'reference',
        'currency',
        'principal',
        'interest_rate',
        'interest_method',
        'term_months',
        'status',
        'purpose',
        'decision_reason',
        'approved_by',
        'approved_at',
        'disbursed_at',
        'first_installment_on',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'principal' => 'decimal:8',
            'interest_rate' => 'decimal:12',
            'term_months' => 'integer',
            'approved_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'first_installment_on' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(GroupMembership::class, 'member_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /**
     * The policy version this loan is governed by. Never swap this for the
     * group's current policy when displaying or recalculating the loan.
     */
    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(GroupPolicy::class, 'policy_version_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class)->orderBy('sequence');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOutstanding(): bool
    {
        return in_array($this->status, self::OUTSTANDING_STATUSES, true);
    }

    public function isDecided(): bool
    {
        return $this->status !== self::STATUS_REQUESTED;
    }

    /**
     * Principal repaid so far, from verified repayment transactions.
     *
     * The authoritative figure for reporting is the ledger balance of Loans
     * Receivable for this member's cost center; this is the loan-level view of
     * the same movement.
     */
    public function principalRepaid(): Decimal
    {
        $paid = $this->installments()->sum('paid_amount');

        return Decimal::of((string) $paid);
    }

    public function outstandingPrincipal(): Decimal
    {
        $scheduled = Decimal::of((string) $this->installments()->sum('principal_amount'));

        if ($scheduled->isZero()) {
            return $this->isOutstanding() ? Decimal::of((string) $this->principal) : Decimal::zero();
        }

        $repaid = $this->principalRepaid();
        $outstanding = Decimal::of((string) $this->principal)->minus($repaid);

        return $outstanding->isNegative() ? Decimal::zero() : $outstanding;
    }

    public function statusLabel(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * The next payment still owed, for "what do I owe, and when?".
     *
     * Reads the already-loaded installments rather than querying, so a list of
     * loans costs one eager load instead of one query per row.
     */
    public function nextInstallment(): ?LoanInstallment
    {
        return $this->installments
            ->reject(fn (LoanInstallment $installment) => $installment->isSettled())
            ->sortBy('due_date')
            ->first();
    }
}
