<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface ClientOption {
    id: number;
    name: string;
}

const props = defineProps<{
    clients: ClientOption[];
    nextInvoiceNumber: string;
}>();

const form = useForm({
    client_id: props.clients[0]?.id ?? '',
    issue_date: new Date().toISOString().slice(0, 10),
    due_date: new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 10),
    currency: 'USD',
    tax_amount: 0,
    discount: 0,
    notes: '',
    items: [{ description: '', quantity: 1, unit_price: 0 }],
});

const addItem = () => form.items.push({ description: '', quantity: 1, unit_price: 0 });
const removeItem = (index: number) => {
    if (form.items.length > 1) form.items.splice(index, 1);
};

// Client-side preview only — the server recalculates authoritatively from the items
// it actually saves (Invoice::recalculateTotals()). Never trust this number for the
// real charge; it's here purely so the person filling the form sees a live total.
const subtotal = computed(() =>
    form.items.reduce((sum, item) => sum + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0), 0)
);
const total = computed(() => subtotal.value + Number(form.tax_amount || 0) - Number(form.discount || 0));

const submit = () => form.post('/invoices');
</script>

<template>
    <Head title="New Invoice" />
    <AppLayout>
        <template #title>New Invoice</template>

        <form @submit.prevent="submit" class="max-w-3xl space-y-6">
            <div class="bg-card border border-border rounded-2xl p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-[15px] font-bold text-ink">Details</h2>
                    <span class="font-mono text-[12px] text-muted">{{ nextInvoiceNumber }}</span>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-semibold text-ink mb-1.5">Client</label>
                        <select v-model="form.client_id" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary">
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <p v-if="form.errors.client_id" class="text-[12px] text-red mt-1">{{ form.errors.client_id }}</p>
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-ink mb-1.5">Currency</label>
                        <input v-model="form.currency" type="text" maxlength="3" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary uppercase" />
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-ink mb-1.5">Issue date</label>
                        <input v-model="form.issue_date" type="date" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-ink mb-1.5">Due date</label>
                        <input v-model="form.due_date" type="date" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary" />
                        <p v-if="form.errors.due_date" class="text-[12px] text-red mt-1">{{ form.errors.due_date }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-card border border-border rounded-2xl p-6">
                <h2 class="text-[15px] font-bold text-ink mb-5">Line items</h2>
                <div class="space-y-3">
                    <div v-for="(item, i) in form.items" :key="i" class="flex gap-2 items-start">
                        <input
                            v-model="item.description"
                            type="text"
                            placeholder="Description"
                            class="flex-1 rounded-xl border border-border px-3 py-2.5 text-[13.5px] outline-none focus:border-primary"
                        />
                        <input
                            v-model.number="item.quantity"
                            type="number"
                            step="0.01"
                            min="0.01"
                            placeholder="Qty"
                            class="w-20 rounded-xl border border-border px-3 py-2.5 text-[13.5px] outline-none focus:border-primary"
                        />
                        <input
                            v-model.number="item.unit_price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Unit price"
                            class="w-28 rounded-xl border border-border px-3 py-2.5 text-[13.5px] outline-none focus:border-primary"
                        />
                        <button type="button" @click="removeItem(i)" class="text-red text-[18px] px-2 py-1.5">&times;</button>
                    </div>
                </div>
                <p v-if="form.errors.items" class="text-[12px] text-red mt-2">{{ form.errors.items }}</p>
                <button type="button" @click="addItem" class="mt-3 text-[12.5px] font-semibold text-primary">+ Add line</button>

                <div class="border-t border-border mt-5 pt-5 space-y-3 max-w-xs ml-auto">
                    <div class="flex justify-between text-[13.5px]">
                        <span class="text-muted">Subtotal</span>
                        <span class="font-mono">{{ subtotal.toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[13.5px]">
                        <span class="text-muted">Tax</span>
                        <input v-model.number="form.tax_amount" type="number" step="0.01" min="0" class="w-24 rounded-lg border border-border px-2 py-1 text-right font-mono text-[13px] outline-none" />
                    </div>
                    <div class="flex justify-between items-center text-[13.5px]">
                        <span class="text-muted">Discount</span>
                        <input v-model.number="form.discount" type="number" step="0.01" min="0" class="w-24 rounded-lg border border-border px-2 py-1 text-right font-mono text-[13px] outline-none" />
                    </div>
                    <div class="flex justify-between text-[16px] font-bold pt-2 border-t border-border">
                        <span>Total</span>
                        <span class="font-mono">{{ form.currency }} {{ total.toFixed(2) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-card border border-border rounded-2xl p-6">
                <label class="block text-[13px] font-semibold text-ink mb-1.5">Notes (optional)</label>
                <textarea v-model="form.notes" rows="3" class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary"></textarea>
            </div>

            <button type="submit" :disabled="form.processing" class="bg-primary text-white font-semibold text-[14px] rounded-xl px-6 py-2.5 disabled:opacity-60">
                Save as draft
            </button>
        </form>
    </AppLayout>
</template>
