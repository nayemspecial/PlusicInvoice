<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Placeholder for Phase 4 verification — proves the full auth + tenancy loop works
 * end to end. Gets replaced with the real dashboard (stats, chart, recent invoices)
 * in Phase 11, once Client/Invoice CRUD exist to pull real numbers from.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'tenant' => app('currentTenant')->only(['name', 'subdomain']),
        ]);
    }
}
