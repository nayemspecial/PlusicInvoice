<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Populated/updated exclusively from Stripe webhook handlers (Phase 10). Do not write
 * to stripe_status directly from application code outside the webhook handler — that
 * field should always reflect what Stripe told us, not what we hope happened.
 */
class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_status',
        'stripe_price_id',
        'current_period_ends_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'current_period_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return $this->stripe_status === 'active';
    }
}
