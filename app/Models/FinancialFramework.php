<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named, seeded preset of advisory rules a group administrator may point
 * their group at (Islamic Finance, Microfinance, ...). Purely informational:
 * a group's actual policy is never validated against it, only compared, and
 * drift is a warning, never a block. See FrameworkComplianceChecker.
 */
class FinancialFramework extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'rules',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
        ];
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /**
     * @return list<array{category: string, field: string, operator: string, value: mixed, label: string}>
     */
    public function rules(): array
    {
        return $this->rules ?? [];
    }
}
