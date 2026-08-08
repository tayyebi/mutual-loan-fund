<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuryReconciliation extends Model
{
    use BelongsToGroup;

    protected $fillable = [
        'group_id',
        'treasury_id',
        'as_of',
        'external_balance',
        'ledger_balance',
        'difference',
        'currency',
        'note',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'as_of' => 'date',
            'external_balance' => 'decimal:8',
            'ledger_balance' => 'decimal:8',
            'difference' => 'decimal:8',
        ];
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function isReconciled(): bool
    {
        return bccomp((string) $this->difference, '0', 8) === 0;
    }
}
