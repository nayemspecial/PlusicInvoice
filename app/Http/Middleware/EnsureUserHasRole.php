<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:owner') or ->middleware('role:owner,admin')
 *
 * Coarse, whole-route gating — "only these roles may even reach this controller
 * action at all". For decisions mixed into other logic (e.g. "show this button only
 * if...") use the Gate abilities defined in AppServiceProvider instead
 * ($user->can('manageTeam')) — both exist on purpose, for different situations.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::guard('tenant')->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
