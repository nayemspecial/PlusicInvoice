# PlusicInvoice — Phase Checklist

Small phases, in order. Don't start a phase until the previous one is checked off and verified
working (not just "code written" — actually run and confirmed).

## Phase 0 — Project bootstrap
- [x] `laravel new PlusicInvoice` (Vue starter kit, no auth scaffold, Pest)
- [x] Tailwind CSS 4 installed
- [x] MySQL connected, initial `migrate` run (default users/cache/jobs tables)

## Phase 1 — Central foundation
- [x] `tenant` connection added to `config/database.php`
- [x] `tenants` table migration (central)
- [x] `plans` table migration (central)
- [x] `subscriptions` table migration (central)
- [x] `webhook_events` table migration (central)
- [x] `TenantProvisioningService` (create DB, run tenant migrations, connect-as-tenant)
- [x] `IdentifyTenant` middleware (subdomain lookup → connect-as-tenant), registered as `tenant` alias

## Phase 2 — Tenant schema & models
- [x] Tenant migrations in `database/migrations/tenant/`: users, password_reset_tokens, clients,
      invoices, invoice_items, activity_logs, settings
- [x] Central models: `Tenant`, `Plan`, `Subscription`, `WebhookEvent`
- [x] Tenant models under `App\Models\Tenants\*`: `User`, `Client`, `Invoice`, `InvoiceItem`,
      `ActivityLog`, `Setting` — all with `protected $connection = 'tenant';`

## Phase 3 — Factories & seeders
- [x] Central factories: `TenantFactory`, `PlanFactory`
- [x] Tenant factories under `database/factories/Tenants/`: `UserFactory`, `ClientFactory`,
      `InvoiceFactory`, `InvoiceItemFactory`
- [x] `PlanSeeder` — Starter/Pro/Business matching the pricing page
- [x] `TenantSeeder` — creates Northwind/Fenwick/Acme tenant rows, provisions each database
- [x] `TenantDatabaseSeeder` — seeds one tenant's DB (owner user, clients, invoices matching
      `design_references/dashboard.html` demo numbers)
- [x] Fixed: `migrate:fresh` only resets the central DB, not tenant databases (separate
      physical MySQL DBs). `TenantSeeder` now drops the 3 demo tenant databases first
      (`TenantProvisioningService::dropTenantDatabaseForSubdomain()` — dev/seeding only,
      never called from the real signup flow) so re-running `migrate:fresh --seed`
      during development is safe and repeatable.
- [ ] **You run:** `php artisan migrate:fresh --seed` and confirm 3 tenant databases exist with data

## Phase 3.5 — Frontend/layout foundation
- [x] Fixed `app.blade.php` + `app.ts` Inertia wiring (was placeholder from the "no auth" starter)
- [x] Design tokens (colors, fonts) added to `resources/css/app.css`, matching `design_references/`
- [x] `GuestLayout.vue` — minimal layout for auth pages
- [ ] `AppLayout.vue` (full sidebar shell, matching `dashboard.html`) — build in Phase 6

## Phase 4 — Custom authentication (tenant-scoped)
- [x] Registration: `Hash::make()`, creates first user as `owner` role
- [x] Login: manual credential check, `Auth::login()`, `session()->regenerate()`
- [x] Logout: session invalidate + CSRF token regenerate
- [x] Rate limiting on login (`RateLimiter` facade, 5/min per email+IP)
- [x] Remember-me (`Auth::guard('tenant')->login($user, remember: true)`)
- [x] Password reset (Laravel's `Password` broker, tenant-scoped, hashed tokens by default)
- [x] Fixed the real bug (took 3 attempts — see `docs/CONTEXT.md`): Laravel's built-in
      `auth`/`guest` middleware aliases are priority-listed by the framework, which kept
      reordering `IdentifyTenant` to run too late. Replaced with custom `tenant.auth` /
      `tenant.guest` middleware that Laravel has no ordering opinion about.
- [x] **Verified by hand:** login, logout, register (first user owner, second user viewer),
      cross-tenant isolation (Northwind's credentials correctly rejected on Fenwick), rate limiting

### Local subdomain testing (do this once)
`php artisan serve` doesn't do Host-header routing — it serves one app regardless of
hostname — so no Laragon virtual host config is needed. Just map the subdomains to
`127.0.0.1` in your **hosts file** (Windows: `C:\Windows\System32\drivers\etc\hosts`,
edit as Administrator):
```
127.0.0.1 plusicinvoice.test
127.0.0.1 northwind.plusicinvoice.test
127.0.0.1 fenwick.plusicinvoice.test
127.0.0.1 acme.plusicinvoice.test
```
Then visit `http://northwind.plusicinvoice.test:9800/register` (or `/login` — the seeded
owner is `owner@northwind.test` / `password`).

## Phase 5 — Role-based access
- [x] `role:owner` route middleware (`EnsureUserHasRole`) + Gate abilities
      (`manageTeam`, `manageBilling`, `manageInvoices`, `viewInvoices`) for finer checks
- [x] Team invite flow: invite by email+role (signed URL, 7-day expiry, emailed via
      `MAIL_MAILER=log`), revoke pending invite, accept invite → creates account
- [x] Owner can change a member's role from the Team page; can't demote the last owner
- [x] **Architecture change:** `IdentifyTenant` is now GLOBAL middleware (not
      route-scoped) — see `docs/CONTEXT.md` for why (Phase 5's signed routes + route
      model binding exposed the same class of bug Phase 4 had with `Authenticate`)
- [ ] **You run:** register as owner (already done in Phase 4) → visit `/team` → invite
      a second email → check `storage/logs/laravel.log` for the invite email → copy the
      signed link → open it in an incognito window → accept → confirm the new user has
      the invited role → as owner, try changing your own role away from owner while
      being the only owner (should be blocked) → invite a second owner-role... (can't,
      role dropdown only offers admin/accountant/viewer for invites, by design)

## Phase 6 — Client CRUD
- [ ] Inertia pages: list, create, edit, view (with invoice history)

## Phase 7 — Invoice CRUD
- [ ] Line items, tax, discount, total calculation
- [ ] Status transitions: Draft → Sent → Paid → Overdue → Cancelled
- [ ] Public shareable invoice view (no login required)

## Phase 8 — PDF
- [ ] `barryvdh/laravel-dompdf` invoice PDF + download route

## Phase 9 — Email
- [ ] Queued invoice-sent email

## Phase 10 — Stripe (raw SDK)
- [ ] Checkout session creation
- [ ] Webhook endpoint + signature verification + idempotency via `webhook_events`
- [ ] Plan-based feature gating

## Phase 11 — Dashboard
- [ ] Stats cards, revenue chart, recent invoices — matching `design_references/dashboard.html`

## Phase 12 — Settings
- [ ] Branding, currency, tax rate, invoice number format

## Phase 13 — Tests (Pest)
- [ ] Tenant isolation test (Tenant A can never see Tenant B's data)
- [ ] Auth flow test
- [ ] Webhook idempotency test

## Phase 14 — Polish
- [ ] README, demo video, GitHub cleanup
