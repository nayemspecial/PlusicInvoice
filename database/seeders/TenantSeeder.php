<?php

namespace Database\Seeders;

use App\Services\TenantProvisioningService;
use Illuminate\Database\Seeder;

/**
 * Creates the demo tenants and provisions a real, separate MySQL database for each
 * one (via TenantProvisioningService — same code path a real signup will use later).
 *
 * Northwind Traders is the "main" demo tenant, matching design_references/dashboard.html
 * exactly. Fenwick & Co. and Acme Studio exist mainly to prove tenant isolation —
 * you should be able to log into either and never see Northwind's data.
 */
class TenantSeeder extends Seeder
{
    public function __construct(
        protected TenantProvisioningService $provisioning,
        protected TenantDatabaseSeeder $tenantDatabaseSeeder,
    ) {}

    public function run(): void
    {
        $northwind = $this->provisioning->createTenant('Northwind Traders', 'northwind');
        $this->tenantDatabaseSeeder->seedFor($northwind, detailed: true);

        $fenwick = $this->provisioning->createTenant('Fenwick & Co.', 'fenwick');
        $this->tenantDatabaseSeeder->seedFor($fenwick, detailed: false);

        $acme = $this->provisioning->createTenant('Acme Studio', 'acme');
        $this->tenantDatabaseSeeder->seedFor($acme, detailed: false);

        $this->command?->info('3 tenants provisioned: northwind, fenwick, acme (each with a real, separate database).');
        $this->command?->info('Login for the main demo tenant: owner@northwind.test / password');
    }
}
