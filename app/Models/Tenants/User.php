<?php

namespace App\Models\Tenants;

use Database\Factories\Tenants\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

/**
 * TENANT-scoped model — every query hits whichever database TenantProvisioningService
 * last connected to. Never used outside of a resolved tenant context (see IdentifyTenant
 * middleware). This is deliberately a separate class from the central App\Models\User.
 */
class User extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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

    public function invoicesCreated(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}
