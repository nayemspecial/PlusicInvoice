<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Central marketing/landing page — no tenant middleware, runs on the 'web' guard.
Route::inertia('/', 'Welcome')->name('home');

/*
|--------------------------------------------------------------------------
| Tenant workspace routes
|--------------------------------------------------------------------------
| Every route below only makes sense in the context of ONE tenant, resolved
| from the subdomain by 'tenant' middleware (see IdentifyTenant). Locally,
| this means visiting e.g. http://northwind.plusicinvoice.test:9800/login
| — see docs/CHECKLIST.md Phase 4 for the hosts-file setup.
*/
Route::middleware('tenant')->group(function (): void {

    // Guest-only: redirects an already-logged-in user away from these. Custom
    // middleware (not Laravel's 'guest' alias) — see RedirectIfTenantUserAuthenticated
    // for why.
    Route::middleware('tenant.guest')->group(function (): void {
        Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [RegisteredUserController::class, 'store']);

        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
    });

    // Requires a logged-in tenant user. Custom middleware (not Laravel's 'auth' alias)
    // — see EnsureTenantUserIsAuthenticated for why.
    Route::middleware('tenant.auth')->group(function (): void {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });
});
