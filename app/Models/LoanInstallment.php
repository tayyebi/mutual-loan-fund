<?php

namespace App\Models;

use App\Domain\Money\Decimal;
use App\Models\Concerns\BelongsToGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanInstallment extends Model
{
    use BelongsToGroup;
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'group_id',
        'loan_id',
        'sequence',
        'due_date',
        'principal_amount',
        'interest_amount',
        'amount',
        'paid_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'due_date' => 'date',
            'principal_amount' => 'decimal:8',
            'interest_amount' => 'decimal:8',
            'amount' => 'decimal:8',
            'paid_amount' => 'decimal:8',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function remaining(): Decimal
    {
        $remaining = Decimal::of((string) $this->amount)->minus((string) $this->paid_amount);

        return $remaining->isNegative() ? Decimal::zero() : $remaining;
    }

    public function isSettled(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue(): bool
    {
        return ! $this->isSettled() && $this->due_date->isPast();
    }
}
