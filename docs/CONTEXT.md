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
- **Ordering fix (FINAL, Phase 5 — architecture changed again):** the previous fix
  (custom `tenant.auth`/`tenant.guest` instead of Laravel's built-ins) solved the
  Authenticate-ordering bug, but Phase 5's `{invitation}`/`{user}` route-model-binding
  and the `signed` middleware (both Laravel built-ins, both priority-listed) hit the
  SAME class of bug again. **Root cause, properly understood this time:** ANY
  priority-listed Laravel middleware mixed into a route can cause the framework to
  re-sort the whole chain, and our route-scoped `IdentifyTenant` had no fixed position
  to defend itself with. **The real fix:** `IdentifyTenant` is now a GLOBAL middleware
  (`$middleware->web(append: [...])`  in `bootstrap/app.php`, listed FIRST of our custom
  additions) — it runs for every request, connects the DB if the subdomain matches a
  tenant, and never aborts. A separate, tiny route-level `RequireTenant` middleware
  (aliased to `tenant`) just checks `app()->bound('currentTenant')` and 404s if not —
  it does no DB work itself, so it has nothing for the priority-list to break. Global
  middleware order among our OWN custom classes is simple sequential registration order
  (no priority-list involved when neither class is framework-priority-listed), which is
  why this is finally robust. Lesson: don't fight Laravel's `$middlewarePriority` list —
  sidestep it by keeping order-critical logic global and order-insensitive logic
  (simple boolean checks) route-scoped.
- `SESSION_CONNECTION=mysql` is set explicitly in `.env` (not left as `null`/default) so
  session storage never depends on ambiguous default-connection resolution.
- Local dev: `php artisan serve` doesn't do Host-header routing, so multi-subdomain testing
  only needs hosts-file entries (or `*.localhost`, which modern browsers resolve to 127.0.0.1
  automatically — no hosts file needed at all), not a real web server / virtual host config.

## Phase 5 additions (role-based access + team invites)
- Two authorization mechanisms, used for different situations — both are Laravel
  primitives, no package: **`role:owner` route middleware** (`EnsureUserHasRole`) for
  "this whole route requires role X", and **Gate abilities** (`$user->can('manageTeam')`,
  defined in `AppServiceProvider::configureTenantGates()`) for finer-grained checks mixed
  into other logic (e.g. "show this button only if..."). `App\Models\Tenants\User` needed
  the `Authorizable` trait/contract added for `->can()` to work — same pattern as
  `Authenticatable`/`CanResetPassword` in Phase 4: framework trait, not a package.
- Team invites use `URL::temporarySignedRoute()` (Laravel's signed URLs) instead of a
  separate hashed-token column — the signature itself is tamper-proof and self-expiring.
  Only password reset uses the hashed-token-in-DB pattern (Laravel's own default for that
  specific broker); invites don't need it since there's no separate "broker" involved.
  See `app/Http/Controllers/Team/`.
- Mail is sent via `MAIL_MAILER=log` in dev — invite emails land in
  `storage/logs/laravel.log` instead of actually sending. Fine for local testing; swap
  the mailer config for a real provider before going live.
- `invitations` table lives in the TENANT database (`database/migrations/tenant/`), same
  as everything else team-related — invitations are scoped to one workspace, not central.

## Phase 6 additions (Client CRUD) — a real bug caught and fixed
- **Bug found while building this phase:** `$this->authorize()` (the `AuthorizesRequests`
  trait) and `$request->user()` both resolve against Laravel's DEFAULT auth guard
  (`config('auth.defaults.guard')` = `'web'`) unless told otherwise — NOT our `tenant`
  guard. Every tenant-scoped controller written this way would have silently checked
  authorization against a guard nobody is ever logged into, always failing (or, for
  `$request->user()`, returning null and crashing). This had already been silently
  wrong in `TeamController::invite()` since Phase 5.
- **The fix:** `IdentifyTenant` (global middleware) now also runs
  `config(['auth.defaults.guard' => 'tenant'])` whenever a tenant is resolved. Central
  routes (no tenant) keep the normal `'web'` default. This means `$request->user()`,
  `Auth::user()`, `Gate::authorize()`, `$this->authorize()`, and Policy classes all now
  work correctly on tenant routes WITHOUT writing `guard('tenant')` everywhere — the one
  correct fix in the middleware makes every future controller "just work" by default.
- `App\Policies\Tenants\ClientPolicy` — namespaced to mirror `App\Models\Tenants\Client`
  so Laravel's policy auto-discovery finds it; also registered explicitly in
  `AppServiceProvider::configureTenantPolicies()` as a defensive backup.
- Authorization split by ACTION, not one blanket role check: `viewAny`/`view` — any
  tenant user; `create`/`update` — owner/admin/accountant; `delete` — owner/admin only
  (an accountant can fix a client's details but not delete the client and cascade-delete
  its invoice history).

## Phase 7 additions (Invoice CRUD)
- `public_token` (UUID) powers the no-login public invoice link — deliberately a
  SEPARATE column from the sequential `id`, so a client can't guess other invoices by
  incrementing a number in the URL. Auto-generated in `Invoice::booted()`'s `creating`
  hook, so it's never in an invalid state.
- Status transitions (`send`, `markAsPaid`, `cancel`) are separate PATCH endpoints, not
  one generic "update status" action — each has its own `InvoicePolicy` rule AND its
  own "which prior status is this valid from" check in the controller. Two authorization
  layers on purpose: the Policy answers "can this ROLE ever do this", the controller's
  status check answers "can this invoice, RIGHT NOW, have this done to it".
- Editing/deleting is restricted to `status === 'draft'` — enforced in the controller
  (`InvoiceController`), not the Policy. A `Policy::update()` returning true means "this
  role is allowed to edit invoices in general"; whether THIS SPECIFIC invoice is
  currently editable is a business-state question, not a role question — keeping them
  separate avoids the Policy needing to know about invoice lifecycle rules.
- `effectiveStatus()` on the model computes "Overdue" for display (a `sent` invoice
  past due) without changing the stored `status` column — no scheduled job exists yet
  to actually flip it. A real scheduled command (`php artisan invoices:mark-overdue`,
  run hourly) would be a natural next addition, and is a good "what would you add with
  more time" interview answer.
- Invoice numbering (`Invoice::nextInvoiceNumber()`) is a simple `count()+1`, not
  race-condition-safe under heavy concurrent creation — a known, documented trade-off
  acceptable at this project's scale. A production system at higher volume would use a
  DB-level sequence or a locking transaction instead.

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
  pages. `resources/js/layouts/AppLayout.vue` — the full sidebar shell, matching
  `dashboard.html` exactly (built in Phase 5.5, once real authenticated pages existed to
  test it against). Every authenticated tenant page wraps its content in `<AppLayout>`;
  nav items for pages that don't exist yet (Invoices, Clients, Billing, Settings) render
  disabled with a "soon" badge rather than being omitted or linking to a 404.

