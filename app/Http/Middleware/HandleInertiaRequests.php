<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * For tenant-workspace routes, 'auth' and 'currentTenant' are overwritten by
     * IdentifyTenant middleware AFTER this runs (both call Inertia::share(), and later
     * calls win) — see app/Http/Middleware/IdentifyTenant.php for why that's the correct
     * place for tenant-guard data, not here. This only covers the central 'web' guard,
     * used by the marketing site / central admin routes that have no tenant at all.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'currentTenant' => null,
        ];
    }
}
