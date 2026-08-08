<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A global identity. Users hold no financial data: everything financial belongs
 * to a group and is reached through a membership.
 */
class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class);
    }

    /**
     * Memberships that grant access to a group's financial data.
     */
    public function activeMemberships(): HasMany
    {
        return $this->memberships()->whereIn('status', [
            GroupMembership::STATUS_APPROVED,
            GroupMembership::STATUS_ACTIVE,
        ]);
    }

    public function membershipFor(Group $group): ?GroupMembership
    {
        return $this->memberships()->where('group_id', $group->getKey())->first();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
