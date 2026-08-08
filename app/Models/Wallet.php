<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An external wallet address belonging to a member.
 *
 * The application is non-custodial: this model has no private key, seed phrase
 * or password field, and never should.
 */
class Wallet extends Model
{
    use BelongsToGroup;
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'group_id',
        'member_id',
        'currency',
        'network',
        'address',
        'label',
        'status',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(GroupMembership::class, 'member_id');
    }

    public function explorerUrl(): ?string
    {
        $template = config("fund.networks.{$this->network}.explorer_address");

        return $template ? str_replace(':address', $this->address, $template) : null;
    }

    public function maskedAddress(): string
    {
        $address = $this->address;

        return strlen($address) <= 14
            ? $address
            : substr($address, 0, 8).'…'.substr($address, -6);
    }
}
