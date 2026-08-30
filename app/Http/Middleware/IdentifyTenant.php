<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * GLOBAL middleware (registered in bootstrap/app.php via $middleware->web(append: [...]),
 * NOT a route-specific alias) — this is deliberate, see docs/CONTEXT.md Phase 5 notes.
 *
 * Runs on EVERY request. If the subdomain matches a provisioned tenant, switches the
 * 'tenant' DB connection and shares the tenant user with Inertia. If not (e.g. the
 * central marketing domain, or an unknown subdomain), it does nothing and lets the
 * request continue — it never aborts. Route-level enforcement ("this route REQUIRES a
 * tenant, 404 otherwise") is a separate, tiny middleware: RequireTenant.
 *
 * Why global instead of route-scoped: Laravel's built-in middleware
 * (SubstituteBindings, the 'signed' middleware, etc.) are part of the framework's fixed
 * $middlewarePriority list, which repeatedly reordered a route-scoped tenant-resolver
 * to run AFTER them — breaking route-model-binding and signed-URL routes that need the
 * tenant DB connection already switched. As a GLOBAL middleware sitting in the 'web'
 * group's own natural order (after Laravel's built-in StartSession, before anything we
 * append after it), there's no priority-list conflict left to have.
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

        if ($tenant && $tenant->provisioned_at) {
            $this->provisioning->connectAsTenant($tenant);

            app()->instance('currentTenant', $tenant);

            Inertia::share([
                'auth' => ['user' => Auth::guard('tenant')->user()],
                'currentTenant' => $tenant->only(['name', 'subdomain']),
            ]);
        }

        return $next($request);
    }
}
