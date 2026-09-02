<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{ tenant: { name: string; subdomain: string } }>();
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <template #title>Dashboard</template>

        <div class="bg-card border border-border rounded-2xl p-8 max-w-lg">
            <h2 class="text-[17px] font-bold text-ink mb-2">You're in 🎉</h2>
            <p class="text-[13.5px] text-muted leading-relaxed">
                This is a placeholder — the real dashboard (stats, chart, recent invoices,
                matching <code>design_references/dashboard.html</code>) gets built in Phase 11,
                once Client/Invoice CRUD exist. The sidebar you're looking at now (Phase 5.5)
                is the real, permanent app shell — every future page renders inside it.
            </p>
        </div>
    </AppLayout>
</template>
