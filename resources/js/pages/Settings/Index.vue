<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    settings: {
        invoice_prefix: string;
        default_currency: string;
        default_tax_rate: string;
        invoice_footer_note: string;
    };
}>();

const form = useForm({
    invoice_prefix: props.settings.invoice_prefix,
    default_currency: props.settings.default_currency,
    default_tax_rate: props.settings.default_tax_rate,
    invoice_footer_note: props.settings.invoice_footer_note,
});

const submit = () => form.put('/settings');
</script>

<template>
    <Head title="Settings" />
    <AppLayout>
        <template #title>Settings</template>

        <div class="bg-card border border-border rounded-2xl p-6 max-w-lg">
            <h2 class="text-[15px] font-bold text-ink mb-1">Invoice defaults</h2>
            <p class="text-[12.5px] text-muted mb-5">
                These apply as suggested defaults on new invoices — you can still
                override any of them per invoice.
            </p>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-[13px] font-semibold text-ink mb-1.5">Invoice number prefix</label>
                    <input
                        v-model="form.invoice_prefix"
                        type="text"
                        maxlength="10"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary uppercase"
                    />
                    <p class="text-[11.5px] text-muted mt-1">e.g. "{{ form.invoice_prefix || 'INV' }}" → {{ form.invoice_prefix || 'INV' }}-0001</p>
                    <p v-if="form.errors.invoice_prefix" class="text-[12px] text-red mt-1">{{ form.errors.invoice_prefix }}</p>
                </div>

                <div>
                    <label class="block text-[13px] font-semibold text-ink mb-1.5">Default currency</label>
                    <input
                        v-model="form.default_currency"
                        type="text"
                        maxlength="3"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary uppercase"
                    />
                    <p v-if="form.errors.default_currency" class="text-[12px] text-red mt-1">{{ form.errors.default_currency }}</p>
                </div>

                <div>
                    <label class="block text-[13px] font-semibold text-ink mb-1.5">Default tax rate (%)</label>
                    <input
                        v-model="form.default_tax_rate"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary"
                    />
                    <p class="text-[11.5px] text-muted mt-1">Shown as a one-click "use X%" shortcut when creating an invoice.</p>
                    <p v-if="form.errors.default_tax_rate" class="text-[12px] text-red mt-1">{{ form.errors.default_tax_rate }}</p>
                </div>

                <div>
                    <label class="block text-[13px] font-semibold text-ink mb-1.5">Invoice footer note</label>
                    <textarea
                        v-model="form.invoice_footer_note"
                        rows="3"
                        placeholder="e.g. bank transfer details, thank-you note…"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-[14px] outline-none focus:border-primary"
                    ></textarea>
                    <p class="text-[11.5px] text-muted mt-1">Shown on every PDF and public invoice page.</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-primary text-white font-semibold text-[14px] rounded-xl px-6 py-2.5 disabled:opacity-60"
                >
                    Save settings
                </button>
            </form>
        </div>
    </AppLayout>
</template>
