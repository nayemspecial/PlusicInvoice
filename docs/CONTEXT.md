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

## Conventions
- Money stored as decimal(10,2), currency as a 3-letter string column (`USD` default).
- Every tenant-scoped model uses `protected $connection = 'tenant';` — copy an existing one
  rather than writing from scratch to avoid forgetting it.
- Factories for tenant models live under `database/factories/Tenants/` matching the model namespace.
