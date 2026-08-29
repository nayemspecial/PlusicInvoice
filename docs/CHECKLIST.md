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
- [ ] **You run:** `php artisan migrate:fresh --seed` and confirm 3 tenant databases exist with data

## Phase 4 — Custom authentication (tenant-scoped)
- [ ] Registration: `Hash::make()`, creates first user as `owner` role
- [ ] Login: manual credential check, `Auth::login()`, `session()->regenerate()`
- [ ] Logout: session invalidate + CSRF token regenerate
- [ ] Rate limiting on login (`RateLimiter` facade, 5/min per email+IP)
- [ ] Remember-me (hashed token)
- [ ] Password reset (signed, expiring, single-use token)

## Phase 5 — Role-based access
- [ ] Middleware/policy enforcing Owner / Admin / Accountant / Viewer per route

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
