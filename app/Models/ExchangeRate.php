<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Global valuation data — deliberately NOT group-scoped.
 *
 * Every rate is quoted against the application's reference asset, one gram of
 * 18K gold:  1 g 18K = units_per_gram_18k <unit>.
 */
class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit',
        'units_per_gram_18k',
        'effective_date',
        'source_troy_ounce_24k',
        'source_note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'units_per_gram_18k' => 'decimal:12',
            'source_troy_ounce_24k' => 'decimal:12',
            'effective_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
