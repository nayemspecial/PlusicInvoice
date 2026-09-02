<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface InvoiceItem {
    id: number;
    description: string;
    quantity: string;
    unit_price: string;
    line_total: string;
}
interface InvoiceData {
    id: number;
    invoice_number: string;
    public_token: string;
    status: string;
    issue_date: string;
    due_date: string;
    subtotal: string;
    tax_amount: string;
    discount: string;
    total: string;
    currency: string;
    notes: string | null;
    client: { id: number; name: string; email: string | null };
    items: InvoiceItem[];
    created_by: { name: string } | null;
}

const props = defineProps<{
    invoice: InvoiceData;
    can: { update: boolean; delete: boolean; send: boolean; markAsPaid: boolean; cancel: boolean };
}>();

const statusStyle: Record<string, string> = {
    paid: 'bg-mint text-mint-strong',
    sent: 'bg-amber/20 text-amber',
    overdue: 'bg-red/15 text-red',
    draft: 'bg-canvas text-muted',
    cancelled: 'bg-canvas text-muted',
};

const publicUrl = computed(() => `${window.location.origin}/pay/${props.invoice.public_token}`);
const copied = ref(false);
const copyLink = () => {
    navigator.clipboard.writeText(publicUrl.value);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};

const send = () => router.patch(`/invoices/${props.invoice.id}/send`);
const markAsPaid = () => router.patch(`/invoices/${props.invoice.id}/mark-paid`);
const cancelInvoice = () => {
    if (confirm('Cancel this invoice?')) router.patch(`/invoices/${props.invoice.id}/cancel`);
};
const destroy = () => {
    if (confirm(`Delete ${props.invoice.invoice_number}? This cannot be undone.`)) {
        router.delete(`/invoices/${props.invoice.id}`);
    }
};
</script>

<template>
    <Head :title="invoice.invoice_number" />
    <AppLayout>
        <template #title>{{ invoice.invoice_number }}</template>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Invoice + items -->
            <div class="lg:col-span-2 bg-card border border-border rounded-2xl p-7">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <p class="text-[12px] text-muted">Billed to</p>
                        <p class="text-[16px] font-bold text-ink">{{ invoice.client.name }}</p>
                        <p v-if="invoice.client.email" class="text-[12.5px] text-muted">{{ invoice.client.email }}</p>
                    </div>
                    <span :class="['px-3 py-1 rounded-full text-[11px] font-semibold capitalize', statusStyle[invoice.status]]">
                        {{ invoice.status }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6 text-[13px]">
                    <div><span class="text-muted">Issued:</span> {{ invoice.issue_date }}</div>
                    <div><span class="text-muted">Due:</span> {{ invoice.due_date }}</div>
                </div>

                <table class="w-full text-[13.5px] mb-6">
                    <thead>
                        <tr class="text-[10.5px] uppercase tracking-wide text-muted border-b border-border">
                            <td class="py-2">Description</td>
                            <td class="py-2 text-right">Qty</td>
                            <td class="py-2 text-right">Unit price</td>
                            <td class="py-2 text-right">Total</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in invoice.items" :key="item.id" class="border-b border-border">
                            <td class="py-3">{{ item.description }}</td>
                            <td class="py-3 text-right font-mono">{{ item.quantity }}</td>
                            <td class="py-3 text-right font-mono">{{ item.unit_price }}</td>
                            <td class="py-3 text-right font-mono">{{ item.line_total }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="max-w-xs ml-auto space-y-2 text-[13.5px]">
                    <div class="flex justify-between"><span class="text-muted">Subtotal</span><span class="font-mono">{{ invoice.subtotal }}</span></div>
                    <div class="flex justify-between"><span class="text-muted">Tax</span><span class="font-mono">{{ invoice.tax_amount }}</span></div>
                    <div class="flex justify-between"><span class="text-muted">Discount</span><span class="font-mono">-{{ invoice.discount }}</span></div>
                    <div class="flex justify-between text-[16px] font-bold pt-2 border-t border-border">
                        <span>Total</span><span class="font-mono">{{ invoice.currency }} {{ invoice.total }}</span>
                    </div>
                </div>

                <p v-if="invoice.notes" class="mt-6 text-[13px] text-muted border-t border-border pt-4">{{ invoice.notes }}</p>
            </div>

            <!-- Actions -->
            <div class="space-y-4 h-fit">
                <div class="bg-card border border-border rounded-2xl p-5 space-y-2.5">
                    <h3 class="text-[13.5px] font-bold text-ink mb-1">Actions</h3>
                    <button v-if="can.send" @click="send" class="w-full bg-primary text-white font-semibold text-[13px] rounded-xl py-2.5">
                        Mark as sent
                    </button>
                    <button v-if="can.markAsPaid" @click="markAsPaid" class="w-full bg-mint-strong text-white font-semibold text-[13px] rounded-xl py-2.5">
                        Mark as paid
                    </button>
                    <Link v-if="can.update" :href="`/invoices/${invoice.id}/edit`" class="block w-full text-center border border-border text-ink font-semibold text-[13px] rounded-xl py-2.5">
                        Edit
                    </Link>
                    <button v-if="can.cancel" @click="cancelInvoice" class="w-full text-[12.5px] font-semibold text-muted hover:text-ink py-1.5">
                        Cancel invoice
                    </button>
                    <button v-if="can.delete" @click="destroy" class="w-full text-[12.5px] font-semibold text-red py-1.5">
                        Delete draft
                    </button>
                </div>

                <div class="bg-card border border-border rounded-2xl p-5">
                    <h3 class="text-[13.5px] font-bold text-ink mb-2">Public link</h3>
                    <p class="text-[11.5px] text-muted mb-3">Share this with your client — no login required.</p>
                    <button @click="copyLink" class="w-full border border-border rounded-xl py-2 text-[12px] font-mono text-muted hover:bg-canvas truncate px-3">
                        {{ copied ? 'Copied!' : publicUrl }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
