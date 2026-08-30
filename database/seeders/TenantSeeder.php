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
    protected array $demoSubdomains = ['northwind', 'fenwick', 'acme'];

    public function __construct(
        protected TenantProvisioningService $provisioning,
        protected TenantDatabaseSeeder $tenantDatabaseSeeder,
    ) {}

    public function run(): void
    {
        // migrate:fresh only resets the CENTRAL database — each tenant's database is
        // a separate physical MySQL database that survives a fresh migrate untouched.
        // Drop the demo ones first so re-running `migrate:fresh --seed` during
        // development doesn't hit duplicate-email errors against leftover data.
        // (dropTenantDatabaseForSubdomain is dev/seeding-only — never called from the
        // real signup flow.)
        foreach ($this->demoSubdomains as $subdomain) {
            $this->provisioning->dropTenantDatabaseForSubdomain($subdomain);
        }

        $northwind = $this->provisioning->createTenant('Northwind Traders', 'northwind');
        $this->tenantDatabaseSeeder->seedFor($northwind, detailed: true);

        $fenwick = $this->provisioning->createTenant('Fenwick & Co.', 'fenwick');
        $this->tenantDatabaseSeeder->seedFor($fenwick, detailed: false);

        $acme = $this->provisioning->createTenant('Acme Studio', 'acme');
        $this->tenantDatabaseSeeder->seedFor($acme, detailed: false);

        $this->command?->info('3 tenants provisioned fresh: northwind, fenwick, acme (each with a real, separate database).');
        $this->command?->info('Login for the main demo tenant: owner@northwind.test / password');
    }
}
