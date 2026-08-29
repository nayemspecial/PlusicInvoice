<?php

namespace Database\Factories\Tenants;

use App\Models\Tenants\Client;
use App\Models\Tenants\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 200, 5000);
        $tax = round($subtotal * 0.05, 2);

        return [
            'invoice_number' => 'INV-'.fake()->unique()->numerify('####'),
            'client_id' => Client::factory(),
            'status' => fake()->randomElement(['draft', 'sent', 'paid', 'overdue']),
            'issue_date' => fake()->dateTimeBetween('-2 months', 'now'),
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'discount' => 0,
            'total' => $subtotal + $tax,
            'currency' => 'USD',
            'is_recurring' => false,
            'recurring_interval' => null,
            'notes' => null,
            'created_by' => null,
        ];
    }
}
