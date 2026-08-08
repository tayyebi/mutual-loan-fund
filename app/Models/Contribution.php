<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contribution extends Model
{
    use BelongsToGroup;
    use HasFactory;

    protected $fillable = [
        'group_id',
        'member_id',
        'cost_center_id',
        'transaction_id',
        'policy_version_id',
        'amount',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(GroupPolicy::class, 'policy_version_id');
    }
}
