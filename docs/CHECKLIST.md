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

## Phase 5.5 — App shell (Master Layout)
- [x] `AppLayout.vue` built — matches `design_references/dashboard.html` exactly
      (collapsible sidebar, mobile drawer, topbar with tenant name + user + logout)
- [x] `Dashboard.vue` and `Team/Index.vue` migrated to use it (removed their own
      ad-hoc headers/logout buttons — the layout owns those now)
- [x] Nav items for Invoices/Clients/Billing/Settings are present but shown disabled
      ("soon" badge) until their respective phases build the actual pages/routes
- [ ] **You run:** reload `/dashboard` and `/team`, confirm sidebar collapse (desktop)
      and the mobile hamburger drawer both work, and disabled nav items don't navigate

## Phase 6 — Client CRUD
- [x] Inertia pages: list (search + pagination), create, edit, view (with invoice history)
- [x] `ClientPolicy` — view/viewAny for anyone, create/update for owner+admin+accountant,
      delete restricted to owner+admin
- [x] Fixed a real bug: default auth guard mismatch broke `$this->authorize()` and
      `$request->user()` on tenant routes — see `docs/CONTEXT.md` Phase 6 notes
- [x] Client Show page displays real seeded invoice data (Northwind's 4 demo invoices)
      via the existing `invoices` relationship — full Invoice CRUD UI is Phase 7
- [ ] **You run:** visit `/clients`, search, create a new client, open Northwind's
      seeded clients (Fenwick & Co., Acme Studio, Ridley & Partners) and confirm their
      invoice history shows correctly, edit a client, and — logged in as a `viewer`
      role account — confirm the "+ New Client" button and Edit/Delete are hidden

## Phase 7 — Invoice CRUD
- [x] Line items (dynamic add/remove rows), tax, discount, server-authoritative total
      calculation (`Invoice::recalculateTotals()` — client-side total shown is a
      preview only, never trusted for the saved amount)
- [x] Status transitions as separate endpoints (send/mark-paid/cancel), each with its
      own `InvoicePolicy` rule and its own "which prior status is this valid from" check
- [x] `effectiveStatus()` shows "Overdue" for a `sent` invoice past its due date without
      needing a scheduled job yet (a good later addition — see the method's docblock)
- [x] Public shareable invoice view — `public_token` (UUID, not the sequential ID) as
      the route key, no login required, no tenant.auth
- [x] Only `draft` invoices are editable/deletable — enforced in the controller
      (not just the policy), since "can this role edit invoices" and "can THIS invoice
      currently be edited" are different questions
- [ ] **You run:** create a new invoice with 2-3 line items, confirm the total is
      correct, mark it sent, open its public link in an incognito window (should work
      without login), mark it paid, then confirm you can no longer edit or delete it
      (try visiting `/invoices/{id}/edit` directly — should redirect with an error)

## Phase 8 — PDF
- [ ] `barryvdh/laravel-dompdf` invoice PDF + download route

## Phase 8 — PDF
- [x] `barryvdh/laravel-dompdf` — invoice PDF via a table-based Blade template
      (dompdf has weak CSS support — no flexbox/grid — so tables are the standard,
      reliable approach for this library, not a stylistic choice)
- [x] Download route for authenticated users AND for the public (no-login) invoice page
- [ ] **You run:** `composer require barryvdh/laravel-dompdf` locally, replace files,
      then download a PDF both from `/invoices/{id}` (logged in) and from the public
      `/pay/{token}` page (logged out) — confirm both produce a correctly formatted PDF

## Phase 9 — Email
- [ ] Queued invoice-sent email

## Phase 9 — Email
- [x] `InvoiceSentMail` — `implements ShouldQueue`, sent via `->queue()` (not `->send()`)
      when an invoice is marked sent, with the PDF attached and a link to the public
      invoice page
- [x] PDF is rendered INSIDE the queued job (`build()`), not passed in from the
      controller — keeps binary data out of the serialized queue payload
- [x] Handles clients with no email on file (skips sending, tells you in the flash
      message instead of silently failing or crashing)
- [ ] **You run:** `QUEUE_CONNECTION=database` means queued jobs sit in the `jobs`
      table until a worker processes them — they will NOT send automatically just by
      clicking "Mark as sent". Either run `php artisan queue:work` in a separate
      terminal before testing, or temporarily set `QUEUE_CONNECTION=sync` in `.env`
      for immediate (non-queued) sending while testing locally. Mark an invoice sent,
      then check `storage/logs/laravel.log` for the email (MAIL_MAILER=log) — confirm
      the PDF attachment is mentioned and the "View invoice" link works

## Phase 10 — Stripe (raw SDK)
- [ ] Checkout session creation
- [ ] Webhook endpoint + signature verification + idempotency via `webhook_events`
- [ ] Plan-based feature gating

## Phase 10 — Stripe (raw SDK)
- [x] Checkout session creation (`BillingController::checkout()`) — subscription
      NOT created here, only after the webhook confirms payment (spoofable redirect
      vs. signature-verified webhook — see the method's docblock)
- [x] Webhook endpoint (CENTRAL, not tenant-scoped) with signature verification +
      idempotency via `webhook_events`, handling `checkout.session.completed`,
      `customer.subscription.updated`, `customer.subscription.deleted`
- [x] Stripe Billing Portal integration (manage card/cancel — no custom UI needed)
- [x] Plan-based feature gating: `Tenant::currentPlan()` (falls back to Starter for
      un-provisioned subscriptions), invoice-limit check in `InvoiceController::store()`
- [x] Webhook route excluded from CSRF verification (`bootstrap/app.php`) — Stripe
      can't send a Laravel CSRF token, signature verification replaces it
- [ ] **You run:** `composer require stripe/stripe-php`, then:
      1. Create a free Stripe account (test mode), get your test Secret/Publishable
         keys from the Stripe Dashboard, put them in `.env` (`STRIPE_KEY`, `STRIPE_SECRET`)
      2. In the Stripe Dashboard, create 3 test Products/Prices matching Starter/Pro/
         Business, then update each `Plan` row's `stripe_price_id` (via `php artisan tinker`
         or a quick seeder tweak) with the real test price IDs
      3. Install the Stripe CLI, run `stripe listen --forward-to
         localhost:9800/webhooks/stripe` — it prints a webhook signing secret, put
         that in `STRIPE_WEBHOOK_SECRET`
      4. Visit `/billing` as an owner, click "Choose plan" on Pro, complete Stripe's
         test checkout (card `4242 4242 4242 4242`, any future date/CVC) — confirm the
         `stripe listen` terminal shows the webhook firing and `/billing` (after a
         refresh) shows Pro as your current plan
      5. Create invoices past Starter's 20/month limit on a DIFFERENT un-upgraded
         tenant (Fenwick or Acme) and confirm the plan-limit error appears

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
