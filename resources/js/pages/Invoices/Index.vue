<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface InvoiceRow {
    id: number;
    invoice_number: string;
    status: string;
    total: string;
    currency: string;
    due_date: string;
    client: { name: string };
}
interface PaginatedInvoices {
    data: InvoiceRow[];
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    invoices: PaginatedInvoices;
    filters: { search: string; status: string };
    can: { create: boolean };
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const statuses = ['', 'draft', 'sent', 'paid', 'overdue', 'cancelled'];

const statusStyle: Record<string, string> = {
    paid: 'bg-mint text-mint-strong',
    sent: 'bg-amber/20 text-amber',
    overdue: 'bg-red/15 text-red',
    draft: 'bg-canvas text-muted',
    cancelled: 'bg-canvas text-muted',
};

let debounceTimer: ReturnType<typeof setTimeout>;
const applyFilters = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get('/invoices', { search: search.value, status: status.value }, { preserveState: true, replace: true });
    }, 300);
};
</script>

<template>
    <Head title="Invoices" />
    <AppLayout>
        <template #title>Invoices</template>

        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div class="flex gap-3">
                <input
                    v-model="search"
                    @input="applyFilters"
                    type="text"
                    placeholder="Search invoices…"
                    class="rounded-xl border border-border px-3.5 py-2.5 text-[13.5px] outline-none focus:border-primary bg-card"
                />
                <select v-model="status" @change="applyFilters" class="rounded-xl border border-border px-3.5 py-2.5 text-[13.5px] outline-none bg-card capitalize">
                    <option v-for="s in statuses" :key="s" :value="s">{{ s || 'All statuses' }}</option>
                </select>
            </div>
            <Link v-if="can.create" href="/invoices/create" class="bg-primary text-white font-semibold text-[13.5px] rounded-xl px-4 py-2.5 whitespace-nowrap">
                + New Invoice
            </Link>
        </div>

        <div class="bg-card border border-border rounded-2xl overflow-hidden">
            <table class="w-full text-[13.5px]">
                <thead>
                    <tr class="text-[10.5px] uppercase tracking-wide text-muted bg-canvas">
                        <td class="px-6 py-3">Invoice</td>
                        <td class="px-6 py-3">Client</td>
                        <td class="px-6 py-3">Due</td>
                        <td class="px-6 py-3">Status</td>
                        <td class="px-6 py-3 text-right">Total</td>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="inv in invoices.data"
                        :key="inv.id"
                        class="border-t border-border hover:bg-canvas cursor-pointer transition"
                        @click="router.visit(`/invoices/${inv.id}`)"
                    >
                        <td class="px-6 py-3.5 font-mono font-semibold text-ink">{{ inv.invoice_number }}</td>
                        <td class="px-6 py-3.5 text-ink">{{ inv.client.name }}</td>
                        <td class="px-6 py-3.5 text-muted">{{ inv.due_date }}</td>
                        <td class="px-6 py-3.5">
                            <span :class="['px-2 py-0.5 rounded-full text-[10.5px] font-semibold capitalize', statusStyle[inv.status]]">
                                {{ inv.status }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-right font-mono text-ink">{{ inv.currency }} {{ inv.total }}</td>
                    </tr>
                    <tr v-if="invoices.data.length === 0">
                        <td colspan="5" class="px-6 py-10 text-center text-muted text-[13.5px]">No invoices found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="invoices.links.length > 3" class="flex items-center gap-1.5 mt-5">
            <template v-for="(link, i) in invoices.links" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    v-html="link.label"
                    :class="['px-3 py-1.5 rounded-lg text-[12.5px] font-medium', link.active ? 'bg-primary text-white' : 'bg-card border border-border text-muted hover:text-ink']"
                />
                <span v-else v-html="link.label" class="px-3 py-1.5 text-[12.5px] text-muted/40" />
            </template>
        </div>
    </AppLayout>
</template>
