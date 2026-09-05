<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface PlanData {
    id: number;
    name: string;
    slug: string;
    price_cents: number;
    invoice_limit: number | null;
    seat_limit: number | null;
    features: string[];
}
interface SubscriptionData {
    plan: PlanData | null;
    stripe_status: string;
}

const props = defineProps<{
    plans: PlanData[];
    subscription: SubscriptionData | null;
}>();

// Cosmetic only — Stripe redirects the browser directly back to this URL after
// checkout, so there's no server-side request in between to flash a message through.
// The webhook (not this) is what actually activates the subscription.
const checkoutResult = computed(() => new URLSearchParams(window.location.search).get('checkout'));

const currentPlanId = computed(() => props.subscription?.plan?.id ?? null);

const priceLabel = (cents: number) => `$${(cents / 100).toFixed(0)}`;

const choosePlan = (planId: number) => router.post(`/billing/checkout/${planId}`);
const openPortal = () => router.post('/billing/portal');
</script>

<template>
    <Head title="Billing" />
    <AppLayout>
        <template #title>Billing</template>

        <div
            v-if="checkoutResult === 'success'"
            class="mb-6 text-[13px] font-medium text-mint-strong bg-mint border border-mint-strong/30 rounded-xl px-4 py-3 max-w-2xl"
        >
            Payment received! It can take a few seconds for your plan to update here —
            Stripe confirms this asynchronously via webhook.
        </div>
        <div
            v-else-if="checkoutResult === 'cancelled'"
            class="mb-6 text-[13px] font-medium text-muted bg-canvas border border-border rounded-xl px-4 py-3 max-w-2xl"
        >
            Checkout cancelled — no charge was made.
        </div>

        <div v-if="subscription?.plan" class="bg-card border border-border rounded-2xl p-6 max-w-2xl mb-6 flex items-center justify-between">
            <div>
                <p class="text-[12px] text-muted">Current plan</p>
                <p class="text-[17px] font-bold text-ink">{{ subscription.plan.name }}</p>
                <p class="text-[12px] text-muted capitalize">{{ subscription.stripe_status }}</p>
            </div>
            <button @click="openPortal" class="border border-border text-ink font-semibold text-[13px] rounded-xl px-4 py-2.5">
                Manage billing
            </button>
        </div>

        <div class="grid sm:grid-cols-3 gap-5 max-w-3xl">
            <div
                v-for="plan in plans"
                :key="plan.id"
                :class="[
                    'bg-card rounded-2xl p-6 border',
                    plan.id === currentPlanId ? 'border-mint-strong border-2' : 'border-border',
                ]"
            >
                <p class="text-[15px] font-bold text-ink">{{ plan.name }}</p>
                <p class="font-mono text-[26px] font-extrabold text-ink mt-3">
                    {{ priceLabel(plan.price_cents) }}<span class="text-[13px] font-medium text-muted">/mo</span>
                </p>
                <ul class="mt-5 space-y-2 text-[12.5px] text-muted">
                    <li v-for="feature in plan.features" :key="feature" class="flex gap-2">
                        <span class="text-mint-strong">✓</span>{{ feature }}
                    </li>
                </ul>
                <button
                    v-if="plan.id !== currentPlanId"
                    @click="choosePlan(plan.id)"
                    class="w-full mt-6 bg-primary text-white font-semibold text-[13px] rounded-xl py-2.5"
                >
                    Choose plan
                </button>
                <p v-else class="w-full mt-6 text-center text-[12.5px] font-semibold text-mint-strong py-2.5">
                    Current plan
                </p>
            </div>
        </div>
    </AppLayout>
</template>
