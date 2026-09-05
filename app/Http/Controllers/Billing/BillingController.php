<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\StripeClient;

/**
 * Tenant-scoped (owner-only, see routes/web.php's 'role:owner' group) — but the
 * actual Plan/Subscription records live in the CENTRAL database (App\Models\Plan,
 * App\Models\Subscription), not the tenant database. This controller runs inside a
 * tenant request (needs to know WHICH tenant is upgrading via app('currentTenant')),
 * but its Eloquent queries deliberately use the CENTRAL models, not App\Models\Tenants\*.
 */
class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = app('currentTenant');
        $subscription = Subscription::where('tenant_id', $tenant->id)->with('plan')->first();

        return Inertia::render('Billing/Index', [
            'plans' => Plan::orderBy('price_cents')->get(),
            'subscription' => $subscription,
        ]);
    }

    /**
     * Creates a Stripe Checkout Session directly via the raw SDK (no Cashier) and
     * redirects the browser to Stripe's hosted checkout page. The actual Subscription
     * row is NOT created here — it's created/updated later by StripeWebhookController
     * when Stripe confirms payment succeeded. This is deliberate: trusting a redirect
     * back from Stripe as "payment succeeded" is spoofable; the webhook (signature-
     * verified, see StripeWebhookController) is the only source of truth for that.
     */
    public function checkout(Request $request, Plan $plan): RedirectResponse
    {
        $tenant = app('currentTenant');
        $stripe = new StripeClient(config('services.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'line_items' => [[
                'price' => $plan->stripe_price_id,
                'quantity' => 1,
            ]],
            'customer_email' => $request->user()->email,
            'success_url' => route('billing.index').'?checkout=success',
            'cancel_url' => route('billing.index').'?checkout=cancelled',
            // Metadata is how the webhook (which runs OUTSIDE any tenant context —
            // Stripe has no idea what a "subdomain" is) knows which tenant and plan
            // this checkout belongs to.
            'metadata' => [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
            ],
        ]);

        return redirect($session->url);
    }

    /**
     * Redirects to Stripe's Billing Portal so the tenant can update their card,
     * change plans, or cancel — without us building any of that UI ourselves.
     */
    public function portal(Request $request): RedirectResponse
    {
        $tenant = app('currentTenant');
        $subscription = Subscription::where('tenant_id', $tenant->id)->first();

        if (! $subscription?->stripe_customer_id) {
            return back()->withErrors(['billing' => 'No billing account found yet — subscribe to a plan first.']);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        $portalSession = $stripe->billingPortal->sessions->create([
            'customer' => $subscription->stripe_customer_id,
            'return_url' => route('billing.index'),
        ]);

        return redirect($portalSession->url);
    }
}
