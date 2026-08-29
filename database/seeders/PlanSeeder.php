<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Matches the three plans shown on the pricing section of design_references/index.html.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(['slug' => 'starter'], [
            'name' => 'Starter',
            'stripe_price_id' => null, // fill in once real Stripe price IDs exist (Phase 10)
            'price_cents' => 900,
            'invoice_limit' => 20,
            'seat_limit' => 1,
            'features' => ['20 invoices / month', '1 team seat', 'Branded PDFs', 'Stripe payments'],
        ]);

        Plan::updateOrCreate(['slug' => 'pro'], [
            'name' => 'Pro',
            'stripe_price_id' => null,
            'price_cents' => 2900,
            'invoice_limit' => null,
            'seat_limit' => 5,
            'features' => ['Unlimited invoices', '5 team seats', 'Recurring invoices', 'Collection score & alerts'],
        ]);

        Plan::updateOrCreate(['slug' => 'business'], [
            'name' => 'Business',
            'stripe_price_id' => null,
            'price_cents' => 7900,
            'invoice_limit' => null,
            'seat_limit' => null,
            'features' => ['Everything in Pro', 'Unlimited team seats', 'Priority support'],
        ]);
    }
}
