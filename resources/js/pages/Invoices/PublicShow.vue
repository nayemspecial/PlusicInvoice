<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface InvoiceItem {
    id: number;
    description: string;
    quantity: string;
    unit_price: string;
    line_total: string;
}
interface InvoiceData {
    public_token: string;
    invoice_number: string;
    status: string;
    issue_date: string;
    due_date: string;
    subtotal: string;
    tax_amount: string;
    discount: string;
    total: string;
    currency: string;
    notes: string | null;
    client: { name: string; email: string | null };
    items: InvoiceItem[];
}

defineProps<{
    invoice: InvoiceData;
    tenantName: string;
}>();

const statusStyle: Record<string, string> = {
    paid: 'bg-mint text-mint-strong',
    sent: 'bg-amber/20 text-amber',
    overdue: 'bg-red/15 text-red',
    draft: 'bg-canvas text-muted',
    cancelled: 'bg-canvas text-muted',
};
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />
    <div class="min-h-screen bg-canvas px-6 py-12">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center gap-2.5 mb-8 justify-center">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center font-extrabold text-[13px] text-white bg-primary">P</div>
                <span class="font-bold text-[16px] text-ink">{{ tenantName }}</span>
            </div>

            <div class="bg-card border border-border rounded-2xl p-8">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <p class="text-[20px] font-bold text-ink font-mono">{{ invoice.invoice_number }}</p>
                        <p class="text-[12.5px] text-muted mt-1">Billed to {{ invoice.client.name }}</p>
                    </div>
                    <span :class="['px-3 py-1 rounded-full text-[11px] font-semibold capitalize', statusStyle[invoice.status]]">
                        {{ invoice.status }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6 text-[13px] text-muted">
                    <div>Issued: {{ invoice.issue_date }}</div>
                    <div>Due: {{ invoice.due_date }}</div>
                </div>

                <table class="w-full text-[13.5px] mb-6">
                    <thead>
                        <tr class="text-[10.5px] uppercase tracking-wide text-muted border-b border-border">
                            <td class="py-2">Description</td>
                            <td class="py-2 text-right">Qty</td>
                            <td class="py-2 text-right">Total</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in invoice.items" :key="item.id" class="border-b border-border">
                            <td class="py-3 text-ink">{{ item.description }}</td>
                            <td class="py-3 text-right font-mono text-muted">{{ item.quantity }}</td>
                            <td class="py-3 text-right font-mono text-ink">{{ item.line_total }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="max-w-xs ml-auto space-y-2 text-[13.5px]">
                    <div class="flex justify-between"><span class="text-muted">Subtotal</span><span class="font-mono">{{ invoice.subtotal }}</span></div>
                    <div class="flex justify-between"><span class="text-muted">Tax</span><span class="font-mono">{{ invoice.tax_amount }}</span></div>
                    <div class="flex justify-between"><span class="text-muted">Discount</span><span class="font-mono">-{{ invoice.discount }}</span></div>
                    <div class="flex justify-between text-[18px] font-bold pt-2 border-t border-border">
                        <span>Total due</span><span class="font-mono">{{ invoice.currency }} {{ invoice.total }}</span>
                    </div>
                </div>

                <p v-if="invoice.notes" class="mt-6 text-[13px] text-muted border-t border-border pt-4">{{ invoice.notes }}</p>

                <a
                    :href="`/pay/${invoice.public_token}/pdf`"
                    class="block w-full text-center border border-border text-ink font-semibold text-[13px] rounded-xl py-2.5 mt-6 hover:bg-canvas transition"
                >
                    Download PDF
                </a>
            </div>

            <p class="text-center text-[11.5px] text-muted mt-6">
                Online payment coming soon — please contact {{ tenantName }} directly to settle this invoice.
            </p>
        </div>
    </div>
</template>
