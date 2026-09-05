<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Tenants\Client;
use App\Models\Tenants\Invoice;
use App\Models\Tenants\InvoiceItem;
use App\Models\Tenants\User;
use App\Services\TenantProvisioningService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds ONE tenant's own database with demo data. Always call
 * connectAsTenant() first (done here) — this class is instantiated and called
 * directly from TenantSeeder, not auto-discovered by DatabaseSeeder::call().
 */
class TenantDatabaseSeeder extends Seeder
{
    public function __construct(protected TenantProvisioningService $provisioning) {}

    /**
     * @param  bool  $detailed  true = hand-crafted data matching design_references/dashboard.html
     *                          (used for the main demo tenant); false = random factory data
     *                          (used for the extra tenants that only exist to prove isolation).
     */
    public function seedFor(Tenant $tenant, bool $detailed = false): void
    {
        $this->provisioning->connectAsTenant($tenant);

        $owner = User::factory()->owner()->create([
            'name' => 'Md. Nayemur Rahman',
            'email' => "owner@{$tenant->subdomain}.test",
            'password' => Hash::make('password'), // dev/demo only
        ]);

        $detailed
            ? $this->seedDetailedDemoData($owner)
            : $this->seedRandomDemoData($owner);
    }

    /**
     * Hand-crafted to match the numbers shown in design_references/dashboard.html
     * so the frontend, once built, matches the approved mockup exactly.
     */
    protected function seedDetailedDemoData(User $owner): void
    {
        $fenwick = Client::create([
            'name' => 'Fenwick & Co.',
            'email' => 'billing@fenwickco.example',
            'phone' => '+44 20 7946 0958',
            'address' => '14 Chancery Lane, London, UK',
            'tax_id' => 'TAX-88213',
        ]);

        $acme = Client::create([
            'name' => 'Acme Studio',
            'email' => 'accounts@acmestudio.example',
            'phone' => '+1 415 555 0148',
            'address' => '221 Market St, San Francisco, CA',
            'tax_id' => 'TAX-55127',
        ]);

        $ridley = Client::create([
            'name' => 'Ridley & Partners',
            'email' => 'finance@ridleypartners.example',
            'phone' => '+44 161 496 0208',
            'address' => '9 King Street, Manchester, UK',
            'tax_id' => 'TAX-91004',
        ]);

        $this->makeInvoice($owner, $fenwick, 'INV-0142', 'sent', 2205.00, [
            ['Product design retainer', 1, 1240.00],
            ['Brand identity — phase 2', 1, 860.00],
        ], taxAmount: 105.00);

        $this->makeInvoice($owner, $acme, 'INV-0141', 'paid', 960.00, [
            ['Landing page redesign', 1, 960.00],
        ]);

        $this->makeInvoice($owner, $ridley, 'INV-0139', 'paid', 4120.00, [
            ['Quarterly retainer — design ops', 1, 4120.00],
        ]);

        $this->makeInvoice($owner, $fenwick, 'INV-0121', 'overdue', 1180.00, [
            ['Icon set + illustration pack', 1, 1180.00],
        ]);
    }

    protected function seedRandomDemoData(User $owner): void
    {
        Client::factory()
            ->count(3)
            ->has(Invoice::factory()->count(2)->state(['created_by' => $owner->id]))
            ->create();
    }

    /**
     * @param  array<int, array{0: string, 1: int, 2: float}>  $items  [description, qty, unit_price]
     */
    protected function makeInvoice(User $owner, Client $client, string $number, string $status, float $total, array $items, float $taxAmount = 0.0): void
    {
        $subtotal = collect($items)->sum(fn ($item) => $item[1] * $item[2]);

        $invoice = Invoice::create([
            'invoice_number' => $number,
            'public_token' => (string) Str::uuid(),
            'client_id' => $client->id,
            'status' => $status,
            'issue_date' => now()->subDays(random_int(3, 20)),
            'due_date' => now()->addDays(random_int(-10, 14)),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount' => 0,
            'total' => $total,
            'currency' => 'USD',
            'created_by' => $owner->id,
        ]);

        foreach ($items as [$description, $quantity, $unitPrice]) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $quantity * $unitPrice,
            ]);
        }
    }
}
