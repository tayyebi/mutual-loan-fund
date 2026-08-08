<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WHO or WHAT financial activity belongs to. A cost center never holds money.
 *
 * It stays historically stable even when the member's name changes, which is
 * why reports attribute by cost center rather than by user.
 */
class CostCenter extends Model
{
    use BelongsToGroup;
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'group_id',
        'code',
        'name',
        'description',
        'parent_id',
        'member_id',
        'status',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(GroupMembership::class, 'member_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function label(): string
    {
        return $this->code.' '.$this->name;
    }
}
