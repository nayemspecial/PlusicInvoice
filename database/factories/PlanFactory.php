<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => 'Starter',
            'slug' => 'starter',
            'stripe_price_id' => null,
            'price_cents' => 900,
            'invoice_limit' => 20,
            'seat_limit' => 1,
            'features' => ['Branded PDF invoices', 'Stripe payments'],
        ];
    }
}
