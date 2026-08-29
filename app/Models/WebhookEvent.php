<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Idempotency guard — see docs/CONTEXT.md. Before processing a Stripe webhook payload,
 * check WebhookEvent::where('stripe_event_id', $event->id)->exists(); if true, skip.
 */
class WebhookEvent extends Model
{
    protected $fillable = [
        'stripe_event_id',
        'type',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
