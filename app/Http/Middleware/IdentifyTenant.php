<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant from the request subdomain and switches the 'tenant' DB
 * connection to point at it. Apply this to any route group that serves a tenant
 * workspace (e.g. northwind.plusicinvoice.test/*). Not applied to the central
 * marketing site or central admin routes.
 */
class IdentifyTenant
{
    public function __construct(
        protected TenantProvisioningService $provisioning,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = explode('.', $request->getHost())[0];

        $tenant = Tenant::where('subdomain', $subdomain)->first();

        if (! $tenant || ! $tenant->provisioned_at) {
            abort(404, 'Workspace not found.');
        }

        $this->provisioning->connectAsTenant($tenant);

        app()->instance('currentTenant', $tenant);

        return $next($request);
    }
}
