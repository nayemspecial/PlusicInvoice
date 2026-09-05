<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A row here = one isolated tenant workspace. Lives in the CENTRAL database.
 * Use TenantProvisioningService::connectAsTenant($tenant) before querying that
 * tenant's own data (App\Models\Tenants\*).
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'subdomain',
        'database_name',
        'provisioned_at',
    ];

    protected function casts(): array
    {
        return [
            'provisioned_at' => 'datetime',
        ];
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Every tenant is treated as being on (at least) Starter, even before they ever
     * complete a Stripe checkout — a freshly provisioned tenant has no Subscription
     * row yet, but still needs SOME plan limits enforced (see InvoiceController's
     * feature-gating check) rather than being treated as unlimited by omission.
     */
    public function currentPlan(): ?Plan
    {
        if ($this->subscription && $this->subscription->isActive()) {
            return $this->subscription->plan;
        }

        return Plan::where('slug', 'starter')->first();
    }
}
