<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom equivalent of Laravel's built-in RedirectIfAuthenticated ('guest' alias) —
 * see EnsureTenantUserIsAuthenticated for why we don't use the framework's version.
 */
class RedirectIfTenantUserAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('tenant')->check()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
