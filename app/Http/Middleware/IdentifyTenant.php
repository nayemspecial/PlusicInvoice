<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
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

        // Shared here (not in HandleInertiaRequests) on purpose: as ROUTE middleware,
        // this always runs AFTER session start + all global 'web' middleware, so the
        // 'tenant' connection is guaranteed correct by the time auth('tenant')->user()
        // is called. Sharing it from a GLOBAL middleware instead would risk reading the
        // tenant guard before this middleware has switched the connection — exactly the
        // bug that briefly broke the session table's DB connection.
        Inertia::share([
            'auth' => ['user' => Auth::guard('tenant')->user()],
            'currentTenant' => $tenant->only(['name', 'subdomain']),
        ]);

        return $next($request);
    }
}
