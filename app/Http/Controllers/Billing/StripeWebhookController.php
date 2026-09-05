<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * CENTRAL-level controller — deliberately NOT inside the 'tenant' middleware group
 * (see routes/web.php). Stripe's servers send this request directly; there's no
 * subdomain for a tenant to be resolved from, and this queries CENTRAL models
 * (Subscription, WebhookEvent) on the default connection, never App\Models\Tenants\*.
 */
class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                config('services.stripe.webhook_secret'),
            );
        } catch (SignatureVerificationException $e) {
            // Anyone can POST to this public URL claiming to be Stripe — the signature
            // check is the ONLY thing standing between "trusted payment confirmation"
            // and "attacker-forged subscription activation". Reject anything that
            // doesn't verify, no exceptions.
            Log::warning('Stripe webhook signature verification failed.', ['error' => $e->getMessage()]);

            return response('Invalid signature.', 400);
        }

        // Idempotency: Stripe does not guarantee exactly-once delivery — the same
        // event can arrive more than once. If we've already processed this exact
        // event ID, skip re-processing but still return 200 (a non-200 response makes
        // Stripe retry, which would just cause the duplicate problem again later).
        if (WebhookEvent::where('stripe_event_id', $event->id)->exists()) {
            return response('Already processed.', 200);
        }

        $this->process($event);

        WebhookEvent::create([
            'stripe_event_id' => $event->id,
            'type' => $event->type,
            'processed_at' => now(),
        ]);

        return response('Webhook handled.', 200);
    }

    protected function process(Event $event): void
    {
        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
            default => Log::info("Unhandled Stripe webhook type: {$event->type}"),
        };
    }

    protected function handleCheckoutCompleted(Event $event): void
    {
        $session = $event->data->object;

        $tenantId = $session->metadata->tenant_id ?? null;
        $planId = $session->metadata->plan_id ?? null;

        if (! $tenantId) {
            Log::warning('Stripe checkout.session.completed missing tenant_id metadata.', ['session_id' => $session->id]);

            return;
        }

        Subscription::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'plan_id' => $planId,
                'stripe_customer_id' => $session->customer,
                'stripe_subscription_id' => $session->subscription,
                'stripe_status' => 'active',
                'cancelled_at' => null,
            ]
        );
    }

    protected function handleSubscriptionUpdated(Event $event): void
    {
        $stripeSubscription = $event->data->object;

        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if (! $subscription) {
            Log::warning('Stripe customer.subscription.updated for unknown subscription.', ['id' => $stripeSubscription->id]);

            return;
        }

        $subscription->update([
            'stripe_status' => $stripeSubscription->status,
            'current_period_ends_at' => $stripeSubscription->current_period_end
                ? now()->createFromTimestamp($stripeSubscription->current_period_end)
                : null,
        ]);
    }

    protected function handleSubscriptionDeleted(Event $event): void
    {
        $stripeSubscription = $event->data->object;

        Subscription::where('stripe_subscription_id', $stripeSubscription->id)->update([
            'stripe_status' => 'canceled',
            'cancelled_at' => now(),
        ]);
    }
}
