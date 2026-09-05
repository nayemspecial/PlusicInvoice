<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Clients\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Invoices\InvoiceController;
use App\Http\Controllers\Invoices\InvoicePublicController;
use App\Http\Controllers\Team\AcceptInvitationController;
use App\Http\Controllers\Team\TeamController;
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

    // Reached only via a signed link emailed by TeamController::invite() — 'signed'
    // is Laravel's own built-in middleware (verifies the URL::temporarySignedRoute
    // signature + expiration), not something we wrote. No 'tenant.auth' here on
    // purpose: the invitee doesn't have an account yet.
    Route::middleware('signed')->group(function (): void {
        Route::get('invitations/{invitation}/accept', [AcceptInvitationController::class, 'create'])->name('invitations.accept');
        Route::post('invitations/{invitation}/accept', [AcceptInvitationController::class, 'store']);
    });

    // Public, no-login invoice view — reached via the invoice's public_token (UUID),
    // not its sequential ID, so it can't be guessed. No 'tenant.auth' here on purpose.
    Route::get('pay/{invoice:public_token}', [InvoicePublicController::class, 'show'])->name('invoices.public');
    Route::get('pay/{invoice:public_token}/pdf', [InvoicePublicController::class, 'pdf'])->name('invoices.public.pdf');

    // Requires a logged-in tenant user. Custom middleware (not Laravel's 'auth' alias)
    // — see EnsureTenantUserIsAuthenticated for why.
    Route::middleware('tenant.auth')->group(function (): void {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('dashboard', DashboardController::class)->name('dashboard');

        // Any logged-in tenant user can browse/view clients — create/update/delete are
        // gated per-action inside ClientController via ClientPolicy (viewers are
        // read-only), not by a blanket route middleware, since 'view' vs 'create' vs
        // 'delete' need different rules for the SAME resource.
        Route::resource('clients', ClientController::class);

        // Same pattern as clients: viewAny/view open to all tenant users, finer
        // create/update/delete rules live in InvoicePolicy, not route middleware.
        Route::resource('invoices', InvoiceController::class);
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::patch('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
        Route::patch('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
        Route::patch('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');

        // Owner-only — 'role:owner' aborts with a 403 for anyone else before the
        // controller even runs. See EnsureUserHasRole.
        Route::middleware('role:owner')->prefix('team')->name('team.')->group(function (): void {
            Route::get('/', [TeamController::class, 'index'])->name('index');
            Route::post('invite', [TeamController::class, 'invite'])->name('invite');
            Route::delete('invitations/{invitation}', [TeamController::class, 'revokeInvitation'])->name('invitations.revoke');
            Route::patch('members/{user}/role', [TeamController::class, 'updateRole'])->name('members.role');
        });
    });
});
