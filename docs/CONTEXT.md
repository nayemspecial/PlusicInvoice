# PlusicInvoice — Project Context

Reference doc for anyone (human or AI) picking up this codebase. Read this before writing code.

## What this is
A multi-tenant SaaS invoicing platform, built as a Toptal-portfolio project. Each tenant
(agency/studio) gets a fully isolated MySQL database. See `docs/CHECKLIST.md` for phase-by-phase
progress, and `design_references/` for the approved homepage + dashboard HTML mockups (colors,
fonts, and demo data used there should match what we seed and build).

## Stack
- Laravel 13, Vue 3 + Inertia.js, Tailwind CSS 4
- MySQL (Laragon locally)
- Multi-tenancy: **custom-built**, no package. DB-per-tenant via `config()` + `DB::purge()` +
  `DB::reconnect()` on a `tenant` connection defined in `config/database.php`.
- Auth: **custom-built**, no Breeze/Fortify. Laravel's `Hash`, `Session`, `RateLimiter` facades
  are used directly — see Phase 4 in the checklist before writing any auth code.
- Billing: raw `stripe/stripe-php` SDK (no Cashier) — Phase 10.
- PDF: `barryvdh/laravel-dompdf` — Phase 8.
- Testing: Pest.

## Two databases, two "User" concepts — don't confuse them
1. **Central DB** (`plusic_invoice`) — platform-level. Holds `tenants`, `plans`, `subscriptions`,
   `webhook_events`, and a central `users` table for **platform admins only** (not tenant users).
   Model: `App\Models\User` (central), `App\Models\Tenant`, `App\Models\Plan`,
   `App\Models\Subscription`, `App\Models\WebhookEvent`.
2. **Tenant DB** (one per tenant, e.g. `plusic_invoice_tenant_northwind`) — holds that tenant's
   own `users` (agency staff, roles: owner/admin/accountant/viewer), `clients`, `invoices`,
   `invoice_items`, `activity_logs`, `settings`. Models live under `App\Models\Tenants\*`
   (namespace `App\Models\Tenants`, folder `app/Models/Tenants/`) and all declare
   `protected $connection = 'tenant';`.

Tenant migrations live in `database/migrations/tenant/` — a **separate path** from
`database/migrations/`, so `php artisan migrate` never touches them by accident. They only run
against the `tenant` connection, triggered by `TenantProvisioningService`.

## How tenant DB switching works
`TenantProvisioningService::connectAsTenant(Tenant $tenant)`:
```php
config(['database.connections.tenant.database' => $tenant->database_name]);
DB::purge('tenant');
DB::reconnect('tenant');
```
Call this before touching any `App\Models\Tenants\*` model. In HTTP requests this happens in
`IdentifyTenant` middleware (resolves tenant from subdomain). In console/seeders, call it manually.

## Demo/seed data
Seeded tenants and numbers are taken directly from `design_references/dashboard.html` and
`index.html` so the seeded database matches the approved mockups exactly:
- Tenants: Northwind Traders (`northwind`), Fenwick & Co. (`fenwick`), Acme Studio (`acme`)
- Sample invoices under Northwind: INV-0142 ($2,205.00, Sent), INV-0141 ($960.00, Paid),
  INV-0139 ($4,120.00, Paid), INV-0121 ($1,180.00, Overdue)
- Plans: Starter $9, Pro $29, Business $79 (matches the pricing section in `index.html`)

## Auth conventions (added in Phase 4)
- Two separate Laravel Auth guards: `web` (central admin, `App\Models\User`) and `tenant`
  (agency staff, `App\Models\Tenants\User`) — see `config/auth.php`. Tenant routes always use
  `auth:tenant` / `guest:tenant` middleware, never the bare `auth`/`guest` aliases.
- `App\Models\Tenants\User` implements `Authenticatable` + `CanResetPassword` contracts
  manually (via Laravel's own traits) instead of extending the framework's base `User` class —
  keeps it a plain Eloquent model while still working with Auth guards and the Password broker.
- Password reset uses `Password::broker('tenant_users')`, configured with `'connection' =>
  'tenant'` in `config/auth.php` — reads/writes whichever tenant DB is currently connected.
- **Ordering fix (final):** Laravel's built-in `Authenticate`/`RedirectIfAuthenticated`
  middleware (used by the `auth`/`guest` aliases) are part of the framework's fixed
  `$middlewarePriority` list. Our custom `tenant` (IdentifyTenant) middleware is NOT in that
  list — and whenever a priority-listed middleware is present, Laravel re-sorts the ENTIRE
  middleware chain, which repeatedly caused `Authenticate` to run before `IdentifyTenant`
  despite being registered after it in `routes/web.php`. Symptom: `SQLSTATE[HY000] [1049]
  Unknown database 'unset_tenant_connection'` on random queries (sessions, then users).
  Two earlier fix attempts (`prependToPriorityList` targeting different classes) were
  unreliable. **The actual fix:** stopped using Laravel's built-in `auth`/`guest` aliases for
  tenant routes entirely — replaced with custom `tenant.auth` / `tenant.guest` middleware
  (`EnsureTenantUserIsAuthenticated`, `RedirectIfTenantUserAuthenticated`). Neither is in
  Laravel's priority list, so they always run in plain registration order — after `tenant`,
  exactly as written. Lesson: don't mix custom route-ordering-dependent middleware with any
  of Laravel's priority-listed built-ins; write the small amount of custom logic instead.
- `SESSION_CONNECTION=mysql` is set explicitly in `.env` (not left as `null`/default) so
  session storage never depends on ambiguous default-connection resolution.
- Local dev: `php artisan serve` doesn't do Host-header routing, so multi-subdomain testing
  only needs hosts-file entries (or `*.localhost`, which modern browsers resolve to 127.0.0.1
  automatically — no hosts file needed at all), not a real web server / virtual host config.

## Backend conventions
- Money stored as decimal(10,2), currency as a 3-letter string column (`USD` default).
- Every tenant-scoped model uses `protected $connection = 'tenant';` — copy an existing one
  rather than writing from scratch to avoid forgetting it.
- Factories for tenant models live under `database/factories/Tenants/` matching the model namespace.

## Frontend conventions (added when wiring the layout foundation)
- Laravel's `vue` starter kit uses **lowercase** `resources/js/pages/` and TypeScript (`.ts`/`.vue`
  with `<script setup lang="ts">`), not the older `Pages/` capitalized convention — match this
  case exactly or Inertia's `resolve()` in `app.ts` won't find the component.
- `app.blade.php` and `app.ts` were bare placeholders under the "no auth" starter choice — both
  were filled in manually (`@inertia`/`@inertiaHead` directives, `resolve`/`setup` in `app.ts`).
  If a page renders a blank screen, check these two files first.
- Design tokens (colors, fonts) live in `resources/css/app.css` under `@theme inline`, copied
  directly from `design_references/dashboard.html` and `index.html` — e.g. `bg-canvas`,
  `text-ink`, `bg-primary`, `bg-mint`, `font-mono` are all available as Tailwind utilities
  app-wide. Don't hardcode hex colors in components; use these tokens so the whole app stays
  visually consistent with the approved mockups.
- `resources/js/layouts/GuestLayout.vue` — minimal centered-card layout for login/register/reset
  pages. The full sidebar `AppLayout.vue` (matching `dashboard.html`) is built later, in Phase 6,
  alongside the pages that actually need it.

