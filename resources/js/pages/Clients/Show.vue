<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface Invoice {
    id: number;
    invoice_number: string;
    status: string;
    total: string;
    currency: string;
    issue_date: string;
    due_date: string;
}
interface ClientData {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    tax_id: string | null;
    invoices: Invoice[];
}

const props = defineProps<{
    client: ClientData;
    can: { update: boolean; delete: boolean };
}>();

const statusStyle: Record<string, string> = {
    paid: 'bg-mint text-mint-strong',
    sent: 'bg-amber/20 text-amber',
    overdue: 'bg-red/15 text-red',
    draft: 'bg-canvas text-muted',
    cancelled: 'bg-canvas text-muted',
};

const destroy = () => {
    if (confirm(`Delete ${props.client.name}? This cannot be undone.`)) {
        router.delete(`/clients/${props.client.id}`);
    }
};
</script>

<template>
    <Head :title="client.name" />
    <AppLayout>
        <template #title>{{ client.name }}</template>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Client details -->
            <div class="bg-card border border-border rounded-2xl p-6 h-fit">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-[15px] font-bold text-ink">Details</h2>
                    <Link v-if="can.update" :href="`/clients/${client.id}/edit`" class="text-[12px] font-semibold text-muted hover:text-ink">
                        Edit
                    </Link>
                </div>
                <dl class="space-y-3 text-[13.5px]">
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Email</dt>
                        <dd class="text-ink">{{ client.email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Phone</dt>
                        <dd class="text-ink">{{ client.phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Address</dt>
                        <dd class="text-ink whitespace-pre-line">{{ client.address ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Tax ID</dt>
                        <dd class="text-ink">{{ client.tax_id ?? '—' }}</dd>
                    </div>
                </dl>
                <button
                    v-if="can.delete"
                    @click="destroy"
                    class="mt-6 w-full text-[12.5px] font-semibold text-red border border-red/30 rounded-xl py-2 hover:bg-red/5 transition"
                >
                    Delete client
                </button>
            </div>

            <!-- Invoice history -->
            <div class="lg:col-span-2 bg-card border border-border rounded-2xl overflow-hidden h-fit">
                <div class="px-6 py-5 border-b border-border">
                    <h2 class="text-[15px] font-bold text-ink">Invoice history</h2>
                    <p class="text-[12px] text-muted mt-0.5">Click a row to view the full invoice.</p>
                </div>
                <table v-if="client.invoices.length" class="w-full text-[13.5px]">
                    <thead>
                        <tr class="text-[10.5px] uppercase tracking-wide text-muted bg-canvas">
                            <td class="px-6 py-3">Invoice</td>
                            <td class="px-6 py-3">Issued</td>
                            <td class="px-6 py-3">Due</td>
                            <td class="px-6 py-3">Status</td>
                            <td class="px-6 py-3 text-right">Total</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="inv in client.invoices"
                            :key="inv.id"
                            class="border-t border-border hover:bg-canvas cursor-pointer transition"
                            @click="router.visit(`/invoices/${inv.id}`)"
                        >
                            <td class="px-6 py-3.5 font-mono text-ink">{{ inv.invoice_number }}</td>
                            <td class="px-6 py-3.5 text-muted">{{ inv.issue_date }}</td>
                            <td class="px-6 py-3.5 text-muted">{{ inv.due_date }}</td>
                            <td class="px-6 py-3.5">
                                <span :class="['px-2 py-0.5 rounded-full text-[10.5px] font-semibold capitalize', statusStyle[inv.status]]">
                                    {{ inv.status }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right font-mono text-ink">{{ inv.currency }} {{ inv.total }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="px-6 py-10 text-center text-muted text-[13.5px]">No invoices yet.</p>
            </div>
        </div>
    </AppLayout>
</template>
