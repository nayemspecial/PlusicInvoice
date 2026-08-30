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
     * Checking app()->bound('currentTenant') is reliable here (unlike an earlier
     * version of this file) because IdentifyTenant is now a GLOBAL middleware,
     * registered to run before this one in bootstrap/app.php — see IdentifyTenant's
     * own docblock. On tenant routes it has already shared 'auth'/'currentTenant'
     * itself; this only needs to fill those in for central-only routes (no tenant).
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $shared = [
            ...parent::share($request),
            'name' => config('app.name'),
            'flash' => [
                'status' => session('status'),
            ],
        ];

        if (! app()->bound('currentTenant')) {
            $shared['auth'] = ['user' => $request->user()];
            $shared['currentTenant'] = null;
        }

        return $shared;
    }
}
