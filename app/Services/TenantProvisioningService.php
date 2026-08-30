<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The one place that knows how to (a) point the 'tenant' connection at a specific
 * tenant's database, and (b) create + migrate a brand new tenant database.
 *
 * Used by:
 *  - IdentifyTenant middleware (connectAsTenant, on every tenant-subdomain request)
 *  - TenantSeeder (createTenant, during `migrate:fresh --seed`)
 *  - The real signup flow, later (createTenant)
 */
class TenantProvisioningService
{
    /**
     * Point the 'tenant' connection at this tenant's database. Safe to call multiple
     * times in one request — DB::purge() forces Laravel to drop any cached PDO
     * connection before reconnecting, so it always ends up on the right database.
     */
    public function connectAsTenant(Tenant $tenant): void
    {
        if (blank($tenant->database_name)) {
            throw new \RuntimeException(
                "Tenant #{$tenant->id} ({$tenant->subdomain}) has no database_name set — cannot connect."
            );
        }

        config(['database.connections.tenant.database' => $tenant->database_name]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Fail LOUD and immediately if the switch didn't actually take effect, instead
        // of letting some unrelated later query surface a confusing "Unknown database
        // 'unset_tenant_connection'" error far from the real cause.
        $actual = DB::connection('tenant')->getDatabaseName();
        if ($actual !== $tenant->database_name) {
            throw new \RuntimeException(
                "Tenant connection switch failed: expected database '{$tenant->database_name}' but connection reports '{$actual}'."
            );
        }
    }

    /**
     * Creates a Tenant row, creates its MySQL database, runs the tenant migrations
     * against it, and marks it provisioned. Does NOT seed demo data — see
     * TenantDatabaseSeeder for that (kept separate so real signups don't get fake data).
     */
    public function createTenant(string $name, string $subdomain): Tenant
    {
        $databaseName = $this->databaseNameFor($subdomain);

        $tenant = Tenant::create([
            'name' => $name,
            'subdomain' => $subdomain,
            'database_name' => $databaseName,
        ]);

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $this->connectAsTenant($tenant);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--realpath' => false,
            '--force' => true,
        ]);

        $tenant->update(['provisioned_at' => now()]);

        return $tenant;
    }

    /**
     * Central DB name + tenant subdomain, e.g. plusic_invoice_tenant_northwind.
     * Prefixing with the central DB name avoids collisions if this app is ever
     * hosted alongside other projects on the same MySQL server.
     */
    protected function databaseNameFor(string $subdomain): string
    {
        $central = config('database.connections.mysql.database');
        $slug = Str::slug($subdomain, '_');

        return "{$central}_tenant_{$slug}";
    }
}
