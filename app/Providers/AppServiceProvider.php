<?php

namespace App\Providers;

use App\Models\Tenants\User as TenantUser;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureTenantGates();
    }

    /**
     * Role-based abilities for the tenant guard. Used for fine-grained checks inside
     * controllers/Vue-shared-props (e.g. $user->can('manageTeam')) — separate from the
     * coarser route-level 'role:owner' middleware, which is faster to read for simple
     * "this whole route needs role X" cases. Both exist deliberately; use whichever
     * fits: middleware for a whole route, Gate::allows()/can() for a single decision
     * mixed into other logic (e.g. "show this button only if...").
     */
    protected function configureTenantGates(): void
    {
        Gate::define('manageTeam', fn (TenantUser $user): bool => $user->role === 'owner');

        Gate::define('manageBilling', fn (TenantUser $user): bool => $user->role === 'owner');

        Gate::define('manageInvoices', fn (TenantUser $user): bool => in_array($user->role, ['owner', 'admin', 'accountant'], true));

        Gate::define('viewInvoices', fn (TenantUser $user): bool => true);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
